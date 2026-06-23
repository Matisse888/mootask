<?php

namespace App\Module;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache as CacheFacade;

/**
 * Cache module
 */
class Cache
{
    /**
     * Cache prefix
     *
     * @var string
     */
    protected static string $prefix = 'mootask:';

    /**
     * Get cache key with prefix
     *
     * @param string $key
     * @return string
     */
    protected static function getKey(string $key): string
    {
        return self::$prefix . $key;
    }

    /**
     * Get value from cache
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return CacheFacade::get(self::getKey($key), $default);
    }

    /**
     * Set value to cache
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl TTL in seconds
     * @return bool
     */
    public static function set(string $key, $value, int $ttl = 3600): bool
    {
        return CacheFacade::put(self::getKey($key), $value, $ttl);
    }

    /**
     * Check if key exists
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return CacheFacade::has(self::getKey($key));
    }

    /**
     * Delete key from cache
     *
     * @param string $key
     * @return bool
     */
    public static function delete(string $key): bool
    {
        return CacheFacade::forget(self::getKey($key));
    }

    /**
     * Remember value
     *
     * @param string $key
     * @param callable $callback
     * @param int $ttl
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = 3600)
    {
        return CacheFacade::remember(self::getKey($key), $ttl, $callback);
    }

    /**
     * Increment value
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public static function increment(string $key, int $value = 1): int
    {
        return CacheFacade::increment(self::getKey($key), $value);
    }

    /**
     * Decrement value
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public static function decrement(string $key, int $value = 1): int
    {
        return CacheFacade::decrement(self::getKey($key), $value);
    }

    /**
     * Clear all cache with prefix
     *
     * @return bool
     */
    public static function flush(): bool
    {
        return CacheFacade::flush();
    }

    /**
     * Get tags from cache
     *
     * @param array $tags
     * @return mixed
     */
    public static function tags(array $tags)
    {
        return CacheFacade::tags($tags);
    }

    /**
     * Store in Redis directly
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return mixed
     */
    public static function storeInRedis(string $key, $value, int $ttl = 0)
    {
        $redisKey = self::getKey($key);

        if ($ttl > 0) {
            return Redis::setex($redisKey, $ttl, is_array($value) ? json_encode($value) : $value);
        }

        return Redis::set($redisKey, is_array($value) ? json_encode($value) : $value);
    }

    /**
     * Get from Redis directly
     *
     * @param string $key
     * @param bool $decode
     * @return mixed
     */
    public static function getFromRedis(string $key, bool $decode = true)
    {
        $value = Redis::get(self::getKey($key));

        if ($decode && $value) {
            return json_decode($value, true);
        }

        return $value;
    }

    /**
     * Delete from Redis directly
     *
     * @param string $key
     * @return int
     */
    public static function deleteFromRedis(string $key): int
    {
        return Redis::del(self::getKey($key));
    }

    /**
     * Set hash in Redis
     *
     * @param string $key
     * @param array $data
     * @param int $ttl
     * @return mixed
     */
    public static function hMSet(string $key, array $data, int $ttl = 0)
    {
        $redisKey = self::getKey($key);
        Redis::hMSet($redisKey, $data);

        if ($ttl > 0) {
            Redis::expire($redisKey, $ttl);
        }

        return true;
    }

    /**
     * Get hash from Redis
     *
     * @param string $key
     * @param string|null $field
     * @return mixed
     */
    public static function hGet(string $key, ?string $field = null)
    {
        $redisKey = self::getKey($key);

        if ($field === null) {
            return Redis::hGetAll($redisKey);
        }

        return Redis::hGet($redisKey, $field);
    }

    /**
     * Push to list in Redis
     *
     * @param string $key
     * @param mixed $value
     * @return int
     */
    public static function lPush(string $key, $value): int
    {
        return Redis::lpush(self::getKey($key), is_array($value) ? json_encode($value) : $value);
    }

    /**
     * Pop from list in Redis
     *
     * @param string $key
     * @param bool $decode
     * @return mixed
     */
    public static function rPop(string $key, bool $decode = true)
    {
        $value = Redis::rpop(self::getKey($key));

        if ($decode && $value) {
            return json_decode($value, true);
        }

        return $value;
    }

    /**
     * Get list length
     *
     * @param string $key
     * @return int
     */
    public static function lLen(string $key): int
    {
        return Redis::llen(self::getKey($key));
    }

    /**
     * Add to set
     *
     * @param string $key
     * @param mixed ...$members
     * @return int
     */
    public static function sAdd(string $key, ...$members): int
    {
        $processed = [];

        foreach ($members as $member) {
            $processed[] = is_array($member) ? json_encode($member) : $member;
        }

        return Redis::sadd(self::getKey($key), $processed);
    }

    /**
     * Get members of set
     *
     * @param string $key
     * @param bool $decode
     * @return array
     */
    public static function sMembers(string $key, bool $decode = true): array
    {
        $members = Redis::smembers(self::getKey($key));

        if ($decode) {
            return array_map(function ($member) {
                return json_decode($member, true) ?? $member;
            }, $members);
        }

        return $members;
    }

    /**
     * Check if member exists in set
     *
     * @param string $key
     * @param mixed $member
     * @return bool
     */
    public static function sIsMember(string $key, $member): bool
    {
        $member = is_array($member) ? json_encode($member) : $member;

        return Redis::sismember(self::getKey($key), $member) > 0;
    }
}
