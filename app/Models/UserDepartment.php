<?php

namespace App\Models;

/**
 * User Department model
 *
 * @property int $id
 * @property string $name
 * @property int $pid
 * @property int $sort
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class UserDepartment extends AbstractModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_departments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'pid',
        'sort',
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
     * Get parent department
     */
    public function parent()
    {
        return $this->belongsTo(UserDepartment::class, 'pid');
    }

    /**
     * Get child departments
     */
    public function children()
    {
        return $this->hasMany(UserDepartment::class, 'pid')->orderBy('sort');
    }

    /**
     * Get users in this department
     */
    public function users()
    {
        return $this->hasMany(User::class, 'department_id');
    }
}
