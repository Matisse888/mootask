<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Exceptions\ApiException;

/**
 * Base model class
 *
 * @property int $id
 * @property int $user_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class AbstractModel extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the attributes that should be date mutated.
     *
     * @return array
     */
    protected function getDates(): array
    {
        return ['created_at', 'updated_at', 'deleted_at'];
    }

    /**
     * Create a new model instance with auto-fill common fields
     *
     * @param array $attributes
     * @return static
     */
    public static function createWithUser(array $attributes = [])
    {
        $userId = request()->attributes->get('user_id');

        if ($userId) {
            $attributes['user_id'] = $userId;
        }

        return static::create($attributes);
    }

    /**
     * Update the model with auto-fill user_id
     *
     * @param array $attributes
     * @param array $options
     * @return bool
     */
    public function updateWithUser(array $attributes = [], array $options = []): bool
    {
        $userId = request()->attributes->get('user_id');

        if ($userId && !isset($attributes['user_id'])) {
            $attributes['user_id'] = $userId;
        }

        return $this->update($attributes, $options);
    }

    /**
     * Find by ID or throw exception
     *
     * @param int $id
     * @param array $columns
     * @return static
     * @throws ApiException
     */
    public static function findOrFailWithException(int $id, array $columns = ['*']): static
    {
        $model = static::find($id, $columns);

        if (!$model) {
            throw ApiException::notFound('资源不存在');
        }

        return $model;
    }

    /**
     * Get paginated results
     *
     * @param array $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function paginateWithParams(array $params = [])
    {
        $query = static::query();

        // Apply filters
        if (isset($params['keyword']) && $params['keyword']) {
            $query->where('name', 'like', "%{$params['keyword']}%");
        }

        if (isset($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }

        // Apply sorting
        $sortField = $params['sort_field'] ?? 'created_at';
        $sortOrder = $params['sort_order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        // Apply pagination
        $page = max(1, (int)($params['page'] ?? 1));
        $pageSize = min(100, max(1, (int)($params['page_size'] ?? 20)));

        return $query->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * Convert model to array
     *
     * @return array
     */
    public function toInfo(): array
    {
        return $this->toArray();
    }
}
