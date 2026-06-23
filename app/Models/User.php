<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * User model
 *
 * @property int $id
 * @property string $email
 * @property string $password
 * @property string $name
 * @property string $avatar
 * @property string $phone
 * @property int $department_id
 * @property string $status
 * @property string $last_login_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class User extends Authenticatable
{
    use Notifiable;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'password',
        'name',
        'avatar',
        'phone',
        'department_id',
        'status',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * User status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_BANNED = 'banned';

    /**
     * Get the department
     */
    public function department()
    {
        return $this->belongsTo(UserDepartment::class, 'department_id');
    }

    /**
     * Get projects created by this user
     */
    public function createdProjects()
    {
        return $this->hasMany(Project::class, 'user_id');
    }

    /**
     * Get projects user belongs to
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_users', 'user_id', 'project_id')
            ->withPivot(['role', 'sort', 'created_at']);
    }

    /**
     * Get tasks assigned to this user
     */
    public function assignedTasks()
    {
        return $this->hasMany(ProjectTask::class, 'assignee_user_id');
    }

    /**
     * Get dialogs this user is part of
     */
    public function dialogs()
    {
        return $this->belongsToMany(WebSocketDialog::class, 'web_socket_dialog_users', 'user_id', 'dialog_id')
            ->withPivot(['role', 'last_read_at', 'created_at']);
    }

    /**
     * Get files uploaded by this user
     */
    public function files()
    {
        return $this->hasMany(File::class, 'user_id');
    }

    /**
     * Get current authenticated user
     *
     * @return self|null
     */
    public static function auth(): ?self
    {
        $userId = request()->attributes->get('user_id');

        if (!$userId) {
            return null;
        }

        return self::find($userId);
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
            'email' => $this->email,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'phone' => $this->phone,
            'department_id' => $this->department_id,
            'department' => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ] : null,
            'status' => $this->status,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
        ];
    }

    /**
     * Convert to simple info array
     *
     * @return array
     */
    public function toSimple(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->avatar,
        ];
    }
}
