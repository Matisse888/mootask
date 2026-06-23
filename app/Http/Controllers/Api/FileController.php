<?php

namespace App\Http\Controllers\Api;

use App\Models\File;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Module\Base;
use App\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * File Controller
 *
 * @package App\Http\Controllers\Api
 */
class FileController extends AbstractController
{
    /**
     * Upload file
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function upload(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|file|max:102400', // 100MB
            'project_id' => 'nullable|integer|exists:projects,id',
            'task_id' => 'nullable|integer',
            'dialog_id' => 'nullable|integer',
            'message_id' => 'nullable|integer',
        ], [
            'file.required' => '请选择要上传的文件',
            'file.file' => '上传的不是有效的文件',
            'file.max' => '文件大小不能超过100MB',
        ]);

        $userId = $this->getUserId();

        // Check project permission
        if ($request->has('project_id')) {
            $project = Project::find($request->input('project_id'));

            if (!$project) {
                return $this->error('项目不存在');
            }

            if (!$project->isOwner($userId) && !$project->isMember($userId)) {
                return $this->error('无权限上传到该项目', null, 403);
            }
        }

        // Check task permission
        if ($request->has('task_id')) {
            $task = ProjectTask::find($request->input('task_id'));

            if (!$task) {
                return $this->error('任务不存在');
            }

            $project = Project::find($task->project_id);

            if (!$project->isOwner($userId) && !$project->isMember($userId)) {
                return $this->error('无权限上传到该任务', null, 403);
            }
        }

        try {
            $file = File::upload($request->file('file'), [
                'project_id' => $request->input('project_id'),
                'task_id' => $request->input('task_id'),
                'dialog_id' => $request->input('dialog_id'),
                'message_id' => $request->input('message_id'),
            ]);

            return $this->success($file->toInfo(), '上传成功');
        } catch (\Exception $e) {
            return $this->error('上传失败：' . $e->getMessage());
        }
    }

    /**
     * Upload multiple files
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadMultiple(Request $request)
    {
        $this->validate($request, [
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|max:102400',
            'project_id' => 'nullable|integer|exists:projects,id',
            'task_id' => 'nullable|integer',
            'dialog_id' => 'nullable|integer',
            'message_id' => 'nullable|integer',
        ], [
            'files.required' => '请选择要上传的文件',
            'files.max' => '最多只能同时上传10个文件',
            'files.*.max' => '单个文件大小不能超过100MB',
        ]);

        $userId = $this->getUserId();

        // Check project permission
        if ($request->has('project_id')) {
            $project = Project::find($request->input('project_id'));

            if (!$project) {
                return $this->error('项目不存在');
            }

            if (!$project->isOwner($userId) && !$project->isMember($userId)) {
                return $this->error('无权限上传到该项目', null, 403);
            }
        }

        $files = [];
        $errors = [];

        foreach ($request->file('files') as $index => $uploadFile) {
            try {
                $file = File::upload($uploadFile, [
                    'project_id' => $request->input('project_id'),
                    'task_id' => $request->input('task_id'),
                    'dialog_id' => $request->input('dialog_id'),
                    'message_id' => $request->input('message_id'),
                ]);

                $files[] = $file->toInfo();
            } catch (\Exception $e) {
                $errors[] = [
                    'index' => $index,
                    'name' => $uploadFile->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        if (empty($files)) {
            return $this->error('所有文件上传失败');
        }

        return $this->success([
            'success' => $files,
            'errors' => $errors,
        ], count($files) . ' 个文件上传成功');
    }

    /**
     * Get file list
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list(Request $request)
    {
        $userId = $this->getUserId();

        $params = $request->only(['project_id', 'task_id', 'dialog_id', 'type', 'page', 'page_size']);

        $query = File::where('user_id', $userId);

        if (!empty($params['project_id'])) {
            $query->where('project_id', $params['project_id']);
        }

        if (!empty($params['task_id'])) {
            $query->where('task_id', $params['task_id']);
        }

        if (!empty($params['dialog_id'])) {
            $query->where('dialog_id', $params['dialog_id']);
        }

        // Filter by type
        if (!empty($params['type'])) {
            $mimeTypes = [
                'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                'video' => ['video/mp4', 'video/webm', 'video/quicktime'],
                'audio' => ['audio/mpeg', 'audio/wav', 'audio/ogg'],
                'document' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ];

            if (isset($mimeTypes[$params['type']])) {
                $query->whereIn('mime_type', $mimeTypes[$params['type']]);
            }
        }

        $query->orderBy('created_at', 'desc');

        $page = max(1, (int)($params['page'] ?? 1));
        $pageSize = min(100, max(1, (int)($params['page_size'] ?? 20)));

        $result = $query->paginate($pageSize, ['*'], 'page', $page);

        // Calculate total size
        $totalSize = File::where('user_id', $userId)->sum('size');

        return $this->success([
            'list' => $result->map(function ($file) {
                return $file->toInfo();
            }),
            'total' => $result->total(),
            'page' => $result->currentPage(),
            'page_size' => $result->perPage(),
            'total_pages' => $result->lastPage(),
            'total_size' => $totalSize,
            'total_size_formatted' => Base::formatBytes($totalSize),
        ]);
    }

    /**
     * Get file detail
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $file = File::with('uploader')->find($id);

        if (!$file) {
            return $this->error('文件不存在');
        }

        return $this->success($file->toInfo());
    }

    /**
     * Download file
     *
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
     */
    public function download(int $id)
    {
        $file = File::find($id);

        if (!$file) {
            return $this->error('文件不存在');
        }

        $path = storage_path('app/public/' . $file->path);

        if (!file_exists($path)) {
            return $this->error('文件不存在或已被删除');
        }

        return response()->download($path, $file->original_name);
    }

    /**
     * Delete file
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(int $id)
    {
        $userId = $this->getUserId();

        $file = File::find($id);

        if (!$file) {
            return $this->error('文件不存在');
        }

        // Only uploader can delete
        if ($file->user_id !== $userId) {
            return $this->error('无权限删除该文件', null, 403);
        }

        // Delete physical file
        $path = storage_path('app/public/' . $file->path);

        if (file_exists($path)) {
            unlink($path);
        }

        $file->delete();

        return $this->success(null, '删除成功');
    }

    /**
     * Get image preview URL
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function preview(int $id)
    {
        $file = File::find($id);

        if (!$file) {
            return $this->error('文件不存在');
        }

        // Check if it's an image
        if (!str_starts_with($file->mime_type, 'image/')) {
            return $this->error('该文件不是图片');
        }

        return $this->success([
            'url' => $file->url,
            'name' => $file->original_name,
            'size' => $file->size,
            'mime_type' => $file->mime_type,
        ]);
    }

    /**
     * Batch delete files
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function batchDelete(Request $request)
    {
        $userId = $this->getUserId();

        $this->validate($request, [
            'ids' => 'required|array|min:1|max:50',
            'ids.*' => 'integer',
        ]);

        $ids = $request->input('ids');

        $files = File::whereIn('id', $ids)
            ->where('user_id', $userId)
            ->get();

        if ($files->isEmpty()) {
            return $this->error('没有找到可删除的文件');
        }

        foreach ($files as $file) {
            $path = storage_path('app/public/' . $file->path);

            if (file_exists($path)) {
                unlink($path);
            }

            $file->delete();
        }

        return $this->success(['count' => $files->count()], $files->count() . ' 个文件已删除');
    }
}
