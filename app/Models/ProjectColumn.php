<?php

namespace App\Models;

/**
 * Project Column model (Kanban columns)
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property int $sort
 * @property string $color
 * @property int $task_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProjectColumn extends AbstractModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'project_columns';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'project_id',
        'name',
        'sort',
        'color',
        'task_count',
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
     * Get the project
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the tasks
     */
    public function tasks()
    {
        return $this->hasMany(ProjectTask::class, 'column_id')->orderBy('sort');
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
            'name' => $this->name,
            'sort' => $this->sort,
            'color' => $this->color,
            'task_count' => $this->task_count ?? 0,
            'tasks' => $this->tasks ? $this->tasks->map(function ($task) {
                return $task->toInfo();
            }) : [],
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
        ];
    }
}
