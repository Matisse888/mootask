<?php

namespace App\Module;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Base utility module
 */
class Base
{
    /**
     * Success response
     *
     * @param string $msg
     * @param mixed $data
     * @return array
     */
    public static function retSuccess(string $msg = '操作成功', $data = null): array
    {
        return [
            'ret' => 1,
            'msg' => $msg,
            'data' => $data
        ];
    }

    /**
     * Error response
     *
     * @param string $msg
     * @param mixed $data
     * @return array
     */
    public static function retError(string $msg = '操作失败', $data = null): array
    {
        return [
            'ret' => 0,
            'msg' => $msg,
            'data' => $data
        ];
    }

    /**
     * Get timestamp
     *
     * @return int
     */
    public static function getTimestamp(): int
    {
        return time();
    }

    /**
     * Get datetime
     *
     * @return string
     */
    public static function getDateTime(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Generate unique token
     *
     * @param string $prefix
     * @return string
     */
    public static function generateToken(string $prefix = ''): string
    {
        return $prefix . bin2hex(random_bytes(32));
    }

    /**
     * Generate unique ID
     *
     * @return string
     */
    public static function generateUniqueId(): string
    {
        return uniqid() . mt_rand(1000, 9999);
    }

    /**
     * Mask sensitive data
     *
     * @param string $data
     * @param int $start
     * @param int $end
     * @return string
     */
    public static function maskString(string $data, int $start = 3, int $end = 4): string
    {
        $length = strlen($data);

        if ($length <= $start + $end) {
            return str_repeat('*', $length);
        }

        return substr($data, 0, $start) . str_repeat('*', $length - $start - $end) . substr($data, -$end);
    }

    /**
     * Format bytes to human readable
     *
     * @param int $bytes
     * @return string
     */
    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Sanitize HTML
     *
     * @param string $html
     * @return string
     */
    public static function sanitizeHtml(string $html): string
    {
        return strip_tags($html, '<p><br><strong><em><u><a><img><ul><ol><li>');
    }

    /**
     * Get client IP
     *
     * @return string
     */
    public static function getClientIp(): string
    {
        $request = request();

        if ($request->server('HTTP_X_FORWARDED_FOR')) {
            $ips = explode(',', $request->server('HTTP_X_FORWARDED_FOR'));
            return trim($ips[0]);
        }

        if ($request->server('HTTP_X_REAL_IP')) {
            return $request->server('HTTP_X_REAL_IP');
        }

        return $request->ip();
    }

    /**
     * Check if request is AJAX
     *
     * @return bool
     */
    public static function isAjax(): bool
    {
        return request()->ajax() || request()->wantsJson();
    }

    /**
     * Array to tree structure
     *
     * @param array $list
     * @param string $pk
     * @param string $pid
     * @param string $child
     * @param int $root
     * @return array
     */
    public static function arrayToTree(array $list, string $pk = 'id', string $pid = 'pid', string $child = 'children', int $root = 0): array
    {
        $tree = [];
        $refer = [];

        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }

        foreach ($list as $key => $data) {
            $parentId = $data[$pid] ?? 0;

            if ($parentId == $root) {
                $tree[] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    $parent[$child][] = &$list[$key];
                }
            }
        }

        return $tree;
    }

    /**
     * Log info
     *
     * @param string $message
     * @param array $context
     */
    public static function logInfo(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    /**
     * Log error
     *
     * @param string $message
     * @param array $context
     */
    public static function logError(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }
}
