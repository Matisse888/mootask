<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\ProjectColumn;
use App\Models\ProjectTask;
use App\Models\ProjectTag;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;

/**
 * Task Controller
 *
 * @package App\Http\Controllers\Api
 */
class TaskController extends AbstractController
{
    /**
     * Create task
     *
     * @param Request $request
     * @param int $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request, int $projectId)
    {
        $userId = $this->getUserId();

        $project = Project::find($projectId);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId) && !$project->isMember($userId)) {
            return $this->error('无权限操作该项目', null, 403);
        }

        $this->validate($request, [
            'column_id' => 'required|integer|exists:project_columns,id',
            'name' => 'required|string|max:200',
            'desc' => 'nullable|string|max:5000',
            'assignee_user_id' => 'nullable|integer|exists:users,id',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'type' => 'nullable|in:task,bug,improvement,epic',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date|after_or_equal:start_date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'labels' => 'nullable|array',
            'labels.*' => 'integer|exists:project_tags,id',
            'parent_id' => 'nullable|integer|exists:project_tasks,id',
        ], [
            'column_id.required' => '列ID不能为空',
            'column_id.exists' => '列不存在',
            'name.required' => '任务名称不能为空',
            'name.max' => '任务名称最多200个字符',
        ]);

        $column = ProjectColumn::where('project_id', $projectId)->find($request->input('column_id'));

        if (!$column) {
            return $this->error('列不存在');
        }

        $maxSort = ProjectTask::where('column_id', $request->input('column_id'))->max('sort') ?? -1;

        $task = ProjectTask::create([
            'project_id' => $projectId,
            'column_id' => $request->input('column_id'),
            'parent_id' => $request->input('parent_id', 0),
            'name' => $request->input('name'),
            'desc' => $request->input('desc', ''),
            'user_id' => $userId,
            'assignee_user_id' => $request->input('assignee_user_id'),
            'priority' => $request->input('priority', 'medium'),
            'status' => ProjectTask::STATUS_TODO,
            'type' => $request->input('type', 'task'),
            'start_date' => $request->input('start_date'),
            'due_date' => $request->input('due_date'),
            'estimated_hours' => $request->input('estimated_hours', 0),
            'actual_hours' => 0,
            'progress' => 0,
            'sort' => $maxSort + 1,
            'labels' => $request->input('labels', []),
        ]);

        // Update column task count
        $column->increment('task_count');

        // Update project task count
        $project->increment('task_count');

        // Attach tags
        if ($request->has('labels')) {
            $task->tags()->attach($request->input('labels'));
        }

        $task = ProjectTask::with(['creator', 'assignee', 'tags'])->find($task->id);

        return $this->success($task->toInfo(), '创建成功');
    }

    /**
     * Get task detail
     *
     * @param int $projectId
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $projectId, int $id)
    {
        $userId = $this->getUserId();

        $project = Project::find($projectId);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId) && !$project->isMember($userId)) {
            return $this->error('无权限访问该项目', null, 403);
        }

        $task = ProjectTask::with(['creator', 'assignee', 'tags', 'subTasks.assignee', 'files'])
            ->where('project_id', $projectId)
            ->find($id);

        if (!$task) {
            return $this->error('任务不存在');
        }

        return $this->success($task->toInfo());
    }

    /**
     * Update task
     *
     * @param Request $request
     * @param int $projectId
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, int $projectId, int $id)
    {
        $userId = $this->getUserId();

        $project = Project::find($projectId);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId) && !$project->isMember($userId)) {
            return $this->error('无权限操作该项目', null, 403);
        }

        $task = ProjectTask::where('project_id', $projectId)->find($id);

        if (!$task) {
            return $this->error('任务不存在');
        }

        $this->validate($request, [
            'column_id' => 'sometimes|integer|exists:project_columns,id',
            'name' => 'sometimes|string|max:200',
            'desc' => 'nullable|string|max:5000',
            'assignee_user_id' => 'nullable|integer|exists:users,id',
            'priority' => 'sometimes|in:low,medium,high,urgent',
            'status' => 'sometimes|in:todo,in_progress,done,cancelled',
            'type' => 'sometimes|in:task,bug,improvement,epic',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'estimated_hours' => 'nullable|numeric|min:0',
            'actual_hours' => 'nullable|numeric|min:0',
            'progress' => 'nullable|integer|min:0|max:100',
            'sort' => 'nullable|integer',
            'labels' => 'nullable|array',
            'labels.*' => 'integer|exists:project_tags,id',
        ]);

        $oldColumnId = $task->column_id;

        $task->update($request->only([
            'column_id', 'name', 'desc', 'assignee_user_id',
            'priority', 'status', 'type', 'start_date',
            'due_date', 'estimated_hours', 'actual_hours',
            'progress', 'sort', 'labels'
        ]));

        // Handle column change
        if ($request->has('column_id') && $request->input('column_id') != $oldColumnId) {
            ProjectColumn::where('id', $oldColumnId)->decrement('task_count');
            ProjectColumn::where('id', $request->input('column_id'))->increment('task_count');
        }

        // Handle status change to done
        if ($request->has('status') && $request->input('status') === ProjectTask::STATUS_DONE && $task->status !== ProjectTask::STATUS_DONE) {
            $task->update(['completed_at' => now()]);
        }

        // Update tags
        if ($request->has('labels')) {
            $task->tags()->sync($request->input('labels'));
        }

        $task = ProjectTask::with(['creator', 'assignee', 'tags'])->find($id);

        return $this->success($task->toInfo(), '更新成功');
    }

    /**
     * Delete task
     *
     * @param int $projectId
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(int $projectId, int $id)
    {
        $userId = $this->getUserId();

        $project = Project::find($projectId);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId) && !$project->isMember($userId)) {
            return $this->error('无权限操作该项目', null, 403);
        }

        $task = ProjectTask::where('project_id', $projectId)->find($id);

        if (!$task) {
            return $this->error('任务不存在');
        }

        // Delete sub tasks
        ProjectTask::where('parent_id', $id)->delete();

        // Update column and project task count
        ProjectColumn::where('id', $task->column_id)->decrement('task_count', 1 + $task->sub_task_count);
        $project->decrement('task_count', 1 + $task->sub_task_count);

        $task->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * Move task to column
     *
     * @param Request $request
     * @param int $projectId
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function move(Request $request, int $projectId, int $id)
    {
        $userId = $this->getUserId();

        $project = Project::find($projectId);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId) && !$project->isMember($userId)) {
            return $this->error('无权限操作该项目', null, 403);
        }

        $task = ProjectTask::where('project_id', $projectId)->find($id);

        if (!$task) {
            return $this->error('任务不存在');
        }

        $this->validate($request, [
            'column_id' => 'required|integer|exists:project_columns,id',
            'sort' => 'nullable|integer',
        ]);

        $oldColumnId = $task->column_id;
        $newColumnId = $request->input('column_id');
        $newSort = $request->input('sort', 0);

        $task->update([
            'column_id' => $newColumnId,
            'sort' => $newSort,
        ]);

        // Update task counts
        if ($oldColumnId != $newColumnId) {
            ProjectColumn::where('id', $oldColumnId)->decrement('task_count');
            ProjectColumn::where('id', $newColumnId)->increment('task_count');
        }

        return $this->success($task->toInfo(), '移动成功');
    }

    /**
     * Assign task
     *
     * @param Request $request
     * @param int $projectId
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function assign(Request $request, int $projectId, int $id)
    {
        $userId = $this->getUserId();

        $project = Project::find($projectId);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId) && !$project->isMember($userId)) {
            return $this->error('无权限操作该项目', null, 403);
        }

        $task = ProjectTask::where('project_id', $projectId)->find($id);

        if (!$task) {
            return $this->error('任务不存在');
        }

        $this->validate($request, [
            'assignee_user_id' => 'nullable|integer|exists:users,id',
        ]);

        $task->update([
            'assignee_user_id' => $request->input('assignee_user_id'),
        ]);

        return $this->success($task->toInfo(), '指派成功');
    }

    /**
     * Get tasks by column
     *
     * @param int $projectId
     * @param int $columnId
     * @return \Illuminate\Http\JsonResponse
     */
    public function columnTasks(int $projectId, int $columnId)
    {
        $userId = $this->getUserId();

        $project = Project::find($projectId);

        if (!$project) {
            return $this->error('项目不存在');
        }

        if (!$project->isOwner($userId) && !$project->isMember($userId)) {
            return $this->error('无权限访问该项目', null, 403);
        }

        $column = ProjectColumn::where('project_id', $projectId)->find($columnId);

        if (!$column) {
            return $this->error('列不存在');
        }

        $tasks = ProjectTask::with(['creator', 'assignee', 'tags'])
            ->where('project_id', $projectId)
            ->where('column_id', $columnId)
            ->whereNull('parent_id')
            ->orderBy('sort')
            ->get();

        return $this->success($tasks->map(function ($task) {
            return $task->toInfo();
        }));
    }

    /**
     * Get my tasks
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function myTasks(Request $request)
    {
        $userId = $this->getUserId();

        $params = $request->only(['status', 'priority', 'project_id', 'page', 'page_size']);

        $query = ProjectTask::with(['project', 'column', 'creator'])
            ->where('assignee_user_id', $userId);

        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (!empty($params['priority'])) {
            $query->where('priority', $params['priority']);
        }

        if (!empty($params['project_id'])) {
            $query->where('project_id', $params['project_id']);
        }

        $query->orderBy('created_at', 'desc');

        $page = max(1, (int)($params['page'] ?? 1));
        $pageSize = min(100, max(1, (int)($params['page_size'] ?? 20)));

        $result = $query->paginate($pageSize, ['*'], 'page', $page);

        $tasks = $result->map(function ($task) {
            $info = $task->toInfo();
            $info['project'] = $task->project ? [
                'id' => $task->project->id,
                'name' => $task->project->name,
                'color' => $task->project->color,
            ] : null;
            $info['column'] = $task->column ? [
                'id' => $task->column->id,
                'name' => $task->column->name,
                'color' => $task->column->color,
            ] : null;
            return $info;
        });

        return $this->success([
            'list' => $tasks,
            'total' => $result->total(),
            'page' => $result->currentPage(),
            'page_size' => $result->perPage(),
            'total_pages' => $result->lastPage(),
        ]);
    }
}
