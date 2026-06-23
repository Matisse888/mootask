<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\UserDepartment;
use App\Module\Base;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;

/**
 * User Controller
 *
 * @package App\Http\Controllers\Api
 */
class UserController extends AbstractController
{
    /**
     * Get user info
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function info()
    {
        $user = User::with('department')->find($this->getUserId());

        if (!$user) {
            return $this->error('用户不存在');
        }

        return $this->success($user->toInfo(), '获取成功');
    }

    /**
     * Update user info
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'sometimes|string|max:50',
            'phone' => 'sometimes|nullable|string|max:20',
            'avatar' => 'sometimes|nullable|string|max:500',
            'department_id' => 'sometimes|nullable|integer|exists:user_departments,id',
        ]);

        $user = User::find($this->getUserId());

        if (!$user) {
            return $this->error('用户不存在');
        }

        $user->update($request->only(['name', 'phone', 'avatar', 'department_id']));

        $user = User::with('department')->find($this->getUserId());

        return $this->success($user->toInfo(), '更新成功');
    }

    /**
     * Update password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(Request $request)
    {
        $this->validate($request, [
            'old_password' => 'required|string|min:6',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'old_password.required' => '旧密码不能为空',
            'old_password.min' => '旧密码至少6位',
            'password.required' => '新密码不能为空',
            'password.min' => '新密码至少6位',
            'password.confirmed' => '两次密码输入不一致',
        ]);

        $user = User::find($this->getUserId());

        if (!$user) {
            return $this->error('用户不存在');
        }

        if (!\Hash::check($request->input('old_password'), $user->password)) {
            return $this->error('旧密码错误');
        }

        $user->update([
            'password' => \Hash::make($request->input('password'))
        ]);

        return $this->success(null, '密码修改成功');
    }

    /**
     * Get user list
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $this->validate($request, [
            'keyword' => 'nullable|string|max:100',
            'department_id' => 'nullable|integer|exists:user_departments,id',
            'page' => 'nullable|integer|min:1',
            'page_size' => 'nullable|integer|min:1|max:100',
        ]);

        $params = $request->only(['keyword', 'department_id', 'page', 'page_size']);

        $query = User::where('status', 'active');

        if (!empty($params['keyword'])) {
            $query->where(function ($q) use ($params) {
                $q->where('name', 'like', "%{$params['keyword']}%")
                    ->orWhere('email', 'like', "%{$params['keyword']}%");
            });
        }

        if (!empty($params['department_id'])) {
            $query->where('department_id', $params['department_id']);
        }

        $query->orderBy('created_at', 'desc');

        $page = max(1, (int)($params['page'] ?? 1));
        $pageSize = min(100, max(1, (int)($params['page_size'] ?? 20)));

        $result = $query->paginate($pageSize, ['*'], 'page', $page);

        $users = $result->map(function ($user) {
            return $user->toInfo();
        });

        return $this->success([
            'list' => $users,
            'total' => $result->total(),
            'page' => $result->currentPage(),
            'page_size' => $result->perPage(),
            'total_pages' => $result->lastPage(),
        ]);
    }

    /**
     * Get user by ID
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $id)
    {
        $user = User::with('department')->find($id);

        if (!$user) {
            return $this->error('用户不存在');
        }

        return $this->success($user->toInfo());
    }

    /**
     * Get departments
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function departments()
    {
        $departments = UserDepartment::where('pid', 0)
            ->with('children')
            ->orderBy('sort')
            ->get();

        return $this->success($departments->map(function ($dept) {
            return [
                'id' => $dept->id,
                'name' => $dept->name,
                'pid' => $dept->pid,
                'sort' => $dept->sort,
                'children' => $dept->children ? $dept->children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'pid' => $child->pid,
                        'sort' => $child->sort,
                    ];
                }) : [],
            ];
        }));
    }

    /**
     * Search users
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $this->validate($request, [
            'keyword' => 'required|string|min:1|max:100',
        ]);

        $keyword = $request->input('keyword');

        $users = User::where('status', 'active')
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            })
            ->limit(20)
            ->get();

        return $this->success($users->map(function ($user) {
            return $user->toSimple();
        }));
    }
}
