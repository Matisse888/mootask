<?php

namespace App\Models;

/**
 * Project model
 *
 * @property int $id
 * @property string $name
 * @property string $desc
 * @property int $user_id
 * @property int $owner_user_id
 * @property int $archived_at
 * @property int $task_count
 * @property int $member_count
 * @property string $color
 * @property string $icon
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Project extends AbstractModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'projects';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'desc',
        'user_id',
        'owner_user_id',
        'archived_at',
        'task_count',
        'member_count',
        'color',
        'icon',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'archived_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the columns
     */
    public function columns()
    {
        return $this->hasMany(ProjectColumn::class, 'project_id')->orderBy('sort');
    }

    /**
     * Get the tasks
     */
    public function tasks()
    {
        return $this->hasMany(ProjectTask::class, 'project_id');
    }

    /**
     * Get the tags
     */
    public function tags()
    {
        return $this->hasMany(ProjectTag::class, 'project_id');
    }

    /**
     * Get the members
     */
    public function members()
    {
        return $this->belongsToMany(User::class, 'project_users', 'project_id', 'user_id')
            ->withPivot(['role', 'sort', 'created_at']);
    }

    /**
     * Get the owner
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /**
     * Get the creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if user is member
     *
     * @param int $userId
     * @return bool
     */
    public function isMember(int $userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    /**
     * Check if user is owner
     *
     * @param int $userId
     * @return bool
     */
    public function isOwner(int $userId): bool
    {
        return $this->owner_user_id === $userId;
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
            'desc' => $this->desc,
            'user_id' => $this->user_id,
            'owner_user_id' => $this->owner_user_id,
            'owner' => $this->owner ? $this->owner->toSimple() : null,
            'archived_at' => $this->archived_at ? $this->archived_at->toDateTimeString() : null,
            'task_count' => $this->task_count ?? 0,
            'member_count' => $this->member_count ?? 0,
            'color' => $this->color,
            'icon' => $this->icon,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }
}
