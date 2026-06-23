<?php

namespace App\Models;

/**
 * Project Task model
 *
 * @property int $id
 * @property int $project_id
 * @property int $column_id
 * @property int $parent_id
 * @property string $name
 * @property string $desc
 * @property int $user_id
 * @property int $assignee_user_id
 * @property string $priority
 * @property string $status
 * @property string $type
 * @property string $start_date
 * @property string $due_date
 * @property int $estimated_hours
 * @property int $actual_hours
 * @property int $progress
 * @property int $sort
 * @property int $sub_task_count
 * @property int $completed_sub_task_count
 * @property int $file_count
 * @property int $comment_count
 * @property string $labels
 * @property \Carbon\Carbon $completed_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProjectTask extends AbstractModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_tasks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'column_id',
        'parent_id',
        'name',
        'desc',
        'user_id',
        'assignee_user_id',
        'priority',
        'status',
        'type',
        'start_date',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'progress',
        'sort',
        'sub_task_count',
        'completed_sub_task_count',
        'file_count',
        'comment_count',
        'labels',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'labels' => 'array',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Task priority constants
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Task status constants
     */
    const STATUS_TODO = 'todo';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_DONE = 'done';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Task type constants
     */
    const TYPE_TASK = 'task';
    const TYPE_BUG = 'bug';
    const TYPE_IMPROVEMENT = 'improvement';
    const TYPE_EPIC = 'epic';

    /**
     * Get the project
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the column
     */
    public function column()
    {
        return $this->belongsTo(ProjectColumn::class, 'column_id');
    }

    /**
     * Get the assignee
     */
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get parent task
     */
    public function parent()
    {
        return $this->belongsTo(ProjectTask::class, 'parent_id');
    }

    /**
     * Get sub tasks
     */
    public function subTasks()
    {
        return $this->hasMany(ProjectTask::class, 'parent_id');
    }

    /**
     * Get tags
     */
    public function tags()
    {
        return $this->belongsToMany(ProjectTag::class, 'project_task_tags', 'task_id', 'tag_id');
    }

    /**
     * Get files
     */
    public function files()
    {
        return $this->hasMany(File::class, 'task_id');
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
            'project_id' => $this->project_id,
            'column_id' => $this->column_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'desc' => $this->desc,
            'user_id' => $this->user_id,
            'creator' => $this->creator ? $this->creator->toSimple() : null,
            'assignee_user_id' => $this->assignee_user_id,
            'assignee' => $this->assignee ? $this->assignee->toSimple() : null,
            'priority' => $this->priority,
            'status' => $this->status,
            'type' => $this->type,
            'start_date' => $this->start_date,
            'due_date' => $this->due_date,
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'progress' => $this->progress,
            'sort' => $this->sort,
            'sub_task_count' => $this->sub_task_count ?? 0,
            'completed_sub_task_count' => $this->completed_sub_task_count ?? 0,
            'file_count' => $this->file_count ?? 0,
            'comment_count' => $this->comment_count ?? 0,
            'labels' => $this->labels ?? [],
            'tags' => $this->tags ? $this->tags->map(function ($tag) {
                return $tag->toInfo();
            }) : [],
            'completed_at' => $this->completed_at ? $this->completed_at->toDateTimeString() : null,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }
}
