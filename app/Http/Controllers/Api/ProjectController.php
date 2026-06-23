<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\ProjectTag;
use App\Models\ProjectTask;
use App\Module\Base;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;

/**
 * Project Controller
 *
 * @package App\Http\Controllers\Api
 */
class ProjectController extends AbstractController
{
    /**
     * Get project list
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lists(Request $request)
    {
        $params = $request->only(['archived', 'keyword', 'page', 'page_size']);

        $userId = $this->getUserId();

        $query = Project::with(['owner', 'members'])
            ->whereHas('members', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->orWhere('owner_user_id', $userId);

        if (isset($params['archived']) && $params['archived']) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        if (!empty($params['keyword'])) {
            $query->where('name', 'like', "%{$params['keyword']}%");
        }

        $query->orderBy('updated_at', 'desc');

        $page = max(1, (int)($params['page'] ?? 1));
        $pageSize = min(100, max(1, (int)($params['page_size'] ?? 20)));

        $result = $query->paginate($pageSize, ['*'], 'page', $page);

        $projects = $result->map(function ($project) {
            return $project->toInfo();
        });

        return $this->success([
            'list' => $projects,
            'total' => $result->total(),
            'page' => $result->currentPage(),
            'page_size' => $result->perPage(),
            'total_pages' => $result->lastPage(),
        ]);
    }

    /**
     * Create project
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:100',
            'desc' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
        ], [
            'name.required' => '项目名称不能为空',
            'name.max' => '项目名称最多100个字符',
            'desc.max' => '项目描述最多1000个字符',
        ]);

        $userId = $this->getUserId();

        // Create project
        $project = Project::create([
            'name' => $request->input('name'),
            'desc' => $request->input('desc', ''),
            'user_id' => $userId,
            'owner_user_id' => $userId,
            'color' => $request->input('color', '#409EFF'),
            'icon' => $request->input('icon', 'folder'),
            'task_count' => 0,
            'member_count' => 1,
        ]);

        // Add creator as member
        $project->members()->attach($userId, ['role' => 'owner', 'sort' => 0]);

        // Create default columns
        $defaultColumns = [
            ['name' => '待办', 'sort' => 0, 'color' => '#909399'],
            ['name' => '进行中', 'sort' => 1, 'color' => '#409EFF'],
            ['name' => '已完成', 'sort' => 2, 'color' => '#67C23A'],
        ];

        foreach ($defaultColumns as $column) {
            ProjectColumn::create([
                'project_id' => $project->id,
                'name' => $column['name'],
                'sort' => $column['sort'],
                'color' => $column['color'],
                'task_count' => 0,
            ]);
        }

        $project = Project::with(['owner', 'columns'])->find($project->id);

        return $this->success($project->toInfo(), '创建成功');
    }

    /**
     * Get project detail
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $userId = $this->getUserId();

        $project = Project::with(['owner', 'members', 'columns.tasks.tags', 'columns.tasks.assignee'])
            ->find($id);

        if (!$project) {
            return $this->error('项目不存在');
        }

        // Check permission
        if (!$project->isOwner($userId) && !$project->isMember($userId)) {
            return $this->error('无权限访问该项目', null, 403);
        }

        return $this->success($project->toInfo());
    }

    /**
     * Update project
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $id)
    {
        $userId = $this->getUserId();

        $project = Project::find($id);

        if (!$project) {
            return $this->error('项目不存在');
        }

        // Check permission (only owner can update)
        if (!$project->isOwner($userId)) {
            return $this->error('无权限修改该项目', null, 403);
        }

        $this->validate($request, [
            'name' => 'sometimes|string|max:100',
            'desc' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:20',
            'icon' => 'nullable|string|max:50',
        ]);

        $project->update($request->only(['name', 'desc', 'color', 'icon']));

        $project = Project::with(['owner', 'members'])->find($id);

        return $this->success($project->toInfo(), '更新成功');
    }

    /**
     * Delete project
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(int $id)
    {
        $userId = $this->getUserId();

        $project = Project::find($id);

        if (!$project) {
            return $this->error('项目不存在');
        }

        // Check permission (only owner can delete)
        if (!$project->isOwner($userId)) {
            return $this->error('无权限删除该项目', null, 403);
        }

        $project->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * Archive project
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function archive(int $id)
    {
        $userId = $this->getUserId();

        $project = Project::find($id);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId)) {
            return $this->error('无权限操作该项目', null, 403);
        }

        $project->update(['archived_at' => now()]);

        return $this->success(null, '归档成功');
    }

    /**
     * Unarchive project
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function unarchive(int $id)
    {
        $userId = $this->getUserId();

        $project = Project::find($id);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId)) {
            return $this->error('无权限操作该项目', null, 403);
        }

        $project->update(['archived_at' => null]);

        return $this->success(null, '取消归档成功');
    }

    /**
     * Add project member
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function addMember(Request $request, int $id)
    {
        $userId = $this->getUserId();

        $project = Project::find($id);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId)) {
            return $this->error('无权限操作该项目', null, 403);
        }

        $this->validate($request, [
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'nullable|in:owner,admin,member,guest',
        ], [
            'user_id.required' => '用户ID不能为空',
            'user_id.exists' => '用户不存在',
        ]);

        $memberId = $request->input('user_id');
        $role = $request->input('role', 'member');

        if ($project->isMember($memberId)) {
            return $this->error('该用户已是项目成员');
        }

        $project->members()->attach($memberId, ['role' => $role, 'sort' => 0]);

        $project->increment('member_count');

        return $this->success(null, '添加成员成功');
    }

    /**
     * Remove project member
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeMember(Request $request, int $id)
    {
        $userId = $this->getUserId();

        $project = Project::find($id);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId)) {
            return $this->error('无权限操作该项目', null, 403);
        }

        $this->validate($request, [
            'user_id' => 'required|integer',
        ]);

        $memberId = $request->input('user_id');

        if ($project->isOwner($memberId)) {
            return $this->error('无法移除项目所有者');
        }

        if (!$project->isMember($memberId)) {
            return $this->error('该用户不是项目成员');
        }

        $project->members()->detach($memberId);

        $project->decrement('member_count');

        return $this->success(null, '移除成员成功');
    }

    /**
     * Create project column
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function createColumn(Request $request, int $id)
    {
        $userId = $this->getUserId();

        $project = Project::find($id);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId) && !$project->isMember($userId)) {
            return $this->error('无权限操作该项目', null, 403);
        }

        $this->validate($request, [
            'name' => 'required|string|max:50',
            'color' => 'nullable|string|max:20',
        ]);

        $maxSort = ProjectColumn::where('project_id', $id)->max('sort') ?? -1;

        $column = ProjectColumn::create([
            'project_id' => $id,
            'name' => $request->input('name'),
            'sort' => $maxSort + 1,
            'color' => $request->input('color', '#909399'),
            'task_count' => 0,
        ]);

        return $this->success($column->toInfo(), '创建成功');
    }

    /**
     * Update project column
     *
     * @param Request $request
     * @param int $id
     * @param int $columnId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateColumn(Request $request, int $id, int $columnId)
    {
        $userId = $this->getUserId();

        $project = Project::find($id);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId) && !$project->isMember($userId)) {
            return $this->error('无权限操作该项目', null, 403);
        }

        $column = ProjectColumn::where('project_id', $id)->find($columnId);

        if (!$column) {
            return $this->error('列不存在');
        }

        $this->validate($request, [
            'name' => 'sometimes|string|max:50',
            'color' => 'nullable|string|max:20',
            'sort' => 'nullable|integer',
        ]);

        $column->update($request->only(['name', 'color', 'sort']));

        return $this->success($column->toInfo(), '更新成功');
    }

    /**
     * Delete project column
     *
     * @param int $id
     * @param int $columnId
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteColumn(int $id, int $columnId)
    {
        $userId = $this->getUserId();

        $project = Project::find($id);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId)) {
            return $this->error('无权限操作该项目', null, 403);
        }

        $column = ProjectColumn::where('project_id', $id)->find($columnId);

        if (!$column) {
            return $this->error('列不存在');
        }

        // Check if column has tasks
        if ($column->task_count > 0) {
            return $this->error('请先移除列中的所有任务');
        }

        $column->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * Create project tag
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTag(Request $request, int $id)
    {
        $userId = $this->getUserId();

        $project = Project::find($id);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId) && !$project->isMember($userId)) {
            return $this->error('无权限操作该项目', null, 403);
        }

        $this->validate($request, [
            'name' => 'required|string|max:20',
            'color' => 'nullable|string|max:20',
        ]);

        $tag = ProjectTag::create([
            'project_id' => $id,
            'name' => $request->input('name'),
            'color' => $request->input('color', '#909399'),
        ]);

        return $this->success($tag->toInfo(), '创建成功');
    }

    /**
     * Get project tags
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function tags(int $id)
    {
        $userId = $this->getUserId();

        $project = Project::find($id);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId) && !$project->isMember($userId)) {
            return $this->error('无权限访问该项目', null, 403);
        }

        $tags = ProjectTag::where('project_id', $id)->get();

        return $this->success($tags->map(function ($tag) {
            return $tag->toInfo();
        }));
    }
}
