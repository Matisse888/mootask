<?php

namespace App\Http\Controllers\Api;

use App\Models\WebSocketDialog;
use App\Models\WebSocketDialogMsg;
use App\Module\Cache;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;

/**
 * Dialog Controller (Instant Messaging)
 *
 * @package App\Http\Controllers\Api
 */
class DialogController extends AbstractController
{
    /**
     * Get dialog list
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists()
    {
        $userId = $this->getUserId();

        $dialogs = WebSocketDialog::with(['users', 'lastMessage.sender'])
            ->whereHas('users', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->orderBy('last_msg_at', 'desc')
            ->get();

        // Add unread count from cache
        $dialogs = $dialogs->map(function ($dialog) use ($userId) {
            $info = $dialog->toInfo();
            $info['unread_count'] = Cache::getFromRedis("dialog:{$dialog->id}:unread:{$userId}", false) ?? 0;
            return $info;
        });

        return $this->success($dialogs);
    }

    /**
     * Create dialog
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $userId = $this->getUserId();

        $this->validate($request, [
            'type' => 'required|in:private,group',
            'name' => 'required_if:type,group|string|max:50',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ], [
            'type.required' => '类型不能为空',
            'name.required_if' => '群聊名称不能为空',
            'user_ids.required' => '用户ID不能为空',
        ]);

        $type = $request->input('type');
        $userIds = $request->input('user_ids');

        // Add current user
        $userIds = array_unique(array_merge($userIds, [$userId]));

        // For private dialog, check if already exists
        if ($type === 'private' && count($userIds) === 2) {
            $existing = WebSocketDialog::where('type', 'private')
                ->whereHas('users', function ($q) use ($userIds) {
                    $q->whereIn('user_id', $userIds);
                })
                ->with(['users', 'lastMessage.sender'])
                ->first();

            if ($existing) {
                return $this->success($existing->toInfo());
            }
        }

        $dialog = WebSocketDialog::create([
            'type' => $type,
            'name' => $type === 'group' ? $request->input('name') : '',
            'user_id' => $userId,
            'unread_count' => 0,
        ]);

        // Add members
        foreach ($userIds as $uid) {
            $role = $uid === $userId ? 'owner' : 'member';
            $dialog->users()->attach($uid, ['role' => $role, 'last_read_at' => now()]);
        }

        $dialog = WebSocketDialog::with(['users', 'lastMessage.sender'])->find($dialog->id);

        return $this->success($dialog->toInfo(), '创建成功');
    }

    /**
     * Get dialog detail
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $userId = $this->getUserId();

        $dialog = WebSocketDialog::with(['users', 'creator'])
            ->find($id);

        if (!$dialog) {
            return $this->error('对话不存在');
        }

        // Check if user is member
        if (!$dialog->users->contains('id', $userId)) {
            return $this->error('无权限访问该对话', null, 403);
        }

        $info = $dialog->toInfo();

        // Get unread count
        $info['unread_count'] = Cache::getFromRedis("dialog:{$id}:unread:{$userId}", false) ?? 0;

        return $this->success($info);
    }

    /**
     * Get messages
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function messages(Request $request, int $id)
    {
        $userId = $this->getUserId();

        $dialog = WebSocketDialog::find($id);

        if (!$dialog) {
            return $this->error('对话不存在');
        }

        // Check if user is member
        if (!$dialog->users()->where('user_id', $userId)->exists()) {
            return $this->error('无权限访问该对话', null, 403);
        }

        $this->validate($request, [
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:100',
            'before_id' => 'nullable|integer',
        ]);

        $page = max(1, (int)($request->input('page', 1)));
        $pageSize = min(100, max(1, (int)($request->input('page_size', 20))));
        $beforeId = $request->input('before_id');

        $query = WebSocketDialogMsg::with(['sender', 'reply'])
            ->where('dialog_id', $id);

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);

        // Mark as read
        $dialog->users()->updateExistingPivot($userId, ['last_read_at' => now()]);
        Cache::deleteFromRedis("dialog:{$id}:unread:{$userId}");

        return $this->success([
            'list' => $messages->map(function ($msg) {
                return $msg->toInfo();
            })->reverse()->values(),
            'total' => $messages->total(),
            'page' => $messages->currentPage(),
            'page_size' => $messages->perPage(),
            'total_pages' => $messages->lastPage(),
        ]);
    }

    /**
     * Send message
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request, int $id)
    {
        $userId = $this->getUserId();

        $dialog = WebSocketDialog::find($id);

        if (!$dialog) {
            return $this->error('对话不存在');
        }

        // Check if user is member
        if (!$dialog->users()->where('user_id', $userId)->exists()) {
            return $this->error('无权限访问该对话', null, 403);
        }

        $this->validate($request, [
            'type' => 'required|in:text,image,file,audio,video',
            'content' => 'required_if:type,text|string|max:5000',
            'file_url' => 'nullable|string|max:500',
            'file_name' => 'nullable|string|max:255',
            'file_size' => 'nullable|integer',
            'reply_id' => 'nullable|integer|exists:web_socket_dialog_messages,id',
        ], [
            'type.required' => '消息类型不能为空',
            'content.required_if' => '消息内容不能为空',
        ]);

        $message = WebSocketDialogMsg::create([
            'dialog_id' => $id,
            'user_id' => $userId,
            'type' => $request->input('type'),
            'content' => $request->input('content', ''),
            'file_url' => $request->input('file_url'),
            'file_name' => $request->input('file_name'),
            'file_size' => $request->input('file_size', 0),
            'reply_id' => $request->input('reply_id', 0),
            'is_recalled' => 0,
        ]);

        // Update dialog
        $dialog->update([
            'last_msg_id' => $message->id,
            'last_msg_at' => $message->created_at,
        ]);

        // Update unread count for other members
        $members = $dialog->users()->where('user_id', '!=', $userId)->get();

        foreach ($members as $member) {
            $unreadKey = "dialog:{$id}:unread:{$member->id}";
            $current = Cache::getFromRedis($unreadKey, false) ?? 0;
            Cache::storeInRedis($unreadKey, $current + 1);
        }

        $message = WebSocketDialogMsg::with(['sender', 'reply'])->find($message->id);

        // TODO: Send to WebSocket

        return $this->success($message->toInfo(), '发送成功');
    }

    /**
     * Recall message
     *
     * @param Request $request
     * @param int $id
     * @param int $msgId
     * @return \Illuminate\Http\JsonResponse
     */
    public function recallMessage(Request $request, int $id, int $msgId)
    {
        $userId = $this->getUserId();

        $message = WebSocketDialogMsg::where('dialog_id', $id)->find($msgId);

        if (!$message) {
            return $this->error('消息不存在');
        }

        // Only sender can recall
        if ($message->user_id !== $userId) {
            return $this->error('只能撤回自己的消息', null, 403);
        }

        // Can only recall within 5 minutes
        if ($message->created_at->diffInMinutes(now()) > 5) {
            return $this->error('超过撤回时间限制');
        }

        $message->update(['is_recalled' => 1]);

        return $this->success(null, '撤回成功');
    }

    /**
     * Delete message
     *
     * @param int $id
     * @param int $msgId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteMessage(int $id, int $msgId)
    {
        $userId = $this->getUserId();

        $message = WebSocketDialogMsg::where('dialog_id', $id)->find($msgId);

        if (!$message) {
            return $this->error('消息不存在');
        }

        // Only sender can delete
        if ($message->user_id !== $userId) {
            return $this->error('只能删除自己的消息', null, 403);
        }

        $message->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * Add dialog member
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addMember(Request $request, int $id)
    {
        $userId = $this->getUserId();

        $dialog = WebSocketDialog::find($id);

        if (!$dialog) {
            return $this->error('对话不存在');
        }

        if ($dialog->type !== 'group') {
            return $this->error('私聊不能添加成员');
        }

        // Check permission
        $member = $dialog->users()->where('user_id', $userId)->first();

        if (!$member || !in_array($member->pivot->role, ['owner', 'admin'])) {
            return $this->error('无权限操作', null, 403);
        }

        $this->validate($request, [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $userIds = $request->input('user_ids');

        foreach ($userIds as $uid) {
            if (!$dialog->users()->where('user_id', $uid)->exists()) {
                $dialog->users()->attach($uid, ['role' => 'member', 'last_read_at' => now()]);

                // System message
                WebSocketDialogMsg::create([
                    'dialog_id' => $id,
                    'user_id' => 0,
                    'type' => 'system',
                    'content' => '添加成员',
                    'is_recalled' => 0,
                ]);
            }
        }

        return $this->success(null, '添加成功');
    }

    /**
     * Remove dialog member
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMember(Request $request, int $id)
    {
        $userId = $this->getUserId();

        $dialog = WebSocketDialog::find($id);

        if (!$dialog) {
            return $this->error('对话不存在');
        }

        if ($dialog->type !== 'group') {
            return $this->error('私聊不能移除成员');
        }

        // Check permission
        $member = $dialog->users()->where('user_id', $userId)->first();

        if (!$member || !in_array($member->pivot->role, ['owner', 'admin'])) {
            return $this->error('无权限操作', null, 403);
        }

        $this->validate($request, [
            'user_id' => 'required|integer',
        ]);

        $targetUserId = $request->input('user_id');

        // Cannot remove owner
        if ($dialog->user_id === $targetUserId) {
            return $this->error('不能移除群主');
        }

        $dialog->users()->detach($targetUserId);

        // System message
        WebSocketDialogMsg::create([
            'dialog_id' => $id,
            'user_id' => 0,
            'type' => 'system',
            'content' => '移除成员',
            'is_recalled' => 0,
        ]);

        return $this->success(null, '移除成功');
    }

    /**
     * Leave dialog
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function leave(int $id)
    {
        $userId = $this->getUserId();

        $dialog = WebSocketDialog::find($id);

        if (!$dialog) {
            return $this->error('对话不存在');
        }

        if ($dialog->type === 'private') {
            return $this->error('私聊无法退出');
        }

        // Cannot leave if owner
        if ($dialog->user_id === $userId) {
            return $this->error('群主无法退出，请先转让群');
        }

        $dialog->users()->detach($userId);

        // System message
        WebSocketDialogMsg::create([
            'dialog_id' => $id,
            'user_id' => 0,
            'type' => 'system',
            'content' => '退出群聊',
            'is_recalled' => 0,
        ]);

        return $this->success(null, '退出成功');
    }
}
