<?php

namespace App\Models;

use Illuminate\Http\UploadedFile;
use App\Module\Base;

/**
 * File model
 *
 * @property int $id
 * @property string $name
 * @property string $original_name
 * @property string $path
 * @property string $url
 * @property string $mime_type
 * @property int $size
 * @property int $user_id
 * @property int $project_id
 * @property int $task_id
 * @property int $dialog_id
 * @property int $message_id
 * @property string $disk
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class File extends AbstractModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'files';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'original_name',
        'path',
        'url',
        'mime_type',
        'size',
        'user_id',
        'project_id',
        'task_id',
        'dialog_id',
        'message_id',
        'disk',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the uploader
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the project
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the task
     */
    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    /**
     * Upload file
     *
     * @param UploadedFile $file
     * @param array $data
     * @return self
     */
    public static function upload(UploadedFile $file, array $data = []): self
    {
        $userId = request()->attributes->get('user_id');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $name = Base::generateUniqueId() . '.' . $extension;
        $path = $file->storeAs('uploads/' . date('Y/m/d'), $name, 'public');

        return self::create([
            'name' => $name,
            'original_name' => $originalName,
            'path' => $path,
            'url' => asset('storage/' . $path),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'user_id' => $userId,
            'disk' => 'public',
            'project_id' => $data['project_id'] ?? 0,
            'task_id' => $data['task_id'] ?? 0,
            'dialog_id' => $data['dialog_id'] ?? 0,
            'message_id' => $data['message_id'] ?? 0,
        ]);
    }

    /**
     * Convert to info array
     *
     * @return array
     */
    public function toInfo(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'original_name' => $this->original_name,
            'url' => $this->url,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'size_formatted' => Base::formatBytes($this->size),
            'user_id' => $this->user_id,
            'uploader' => $this->uploader ? $this->uploader->toSimple() : null,
            'project_id' => $this->project_id,
            'task_id' => $this->task_id,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
        ];
    }
}
