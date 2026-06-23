<?php

namespace App\Module;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use App\Models\User;
use App\Exceptions\ApiException;

/**
 * Authentication module
 */
class Auth
{
    /**
     * JWT secret key
     *
     * @var string
     */
    protected static string $secret;

    /**
     * Token TTL in minutes
     *
     * @var int
     */
    protected static int $ttl;

    /**
     * Initialize auth module
     */
    public static function init(): void
    {
        self::$secret = config('jwt.secret', env('JWT_SECRET'));
        self::$ttl = (int)config('jwt.ttl', 1440);
    }

    /**
     * Login user
     *
     * @param string $email
     * @param string $password
     * @return array
     */
    public static function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ApiException::businessError('账号或密码错误', 1001);
        }

        if (!Hash::check($password, $user->password)) {
            throw ApiException::businessError('账号或密码错误', 1001);
        }

        if ($user->status !== 'active') {
            throw ApiException::businessError('账号已被停用', 1003);
        }

        return self::generateAuthData($user);
    }

    /**
     * Register new user
     *
     * @param string $email
     * @param string $password
     * @param string $name
     * @return array
     */
    public static function register(string $email, string $password, string $name): array
    {
        $exists = User::where('email', $email)->exists();

        if ($exists) {
            throw ApiException::businessError('邮箱已被注册', 1004);
        }

        $user = User::create([
            'email' => $email,
            'password' => Hash::make($password),
            'name' => $name,
            'avatar' => self::generateAvatar($name),
            'status' => 'active',
        ]);

        return self::generateAuthData($user);
    }

    /**
     * Generate auth data
     *
     * @param User $user
     * @return array
     */
    public static function generateAuthData(User $user): array
    {
        self::init();

        $token = self::generateToken($user);
        $refreshToken = self::generateRefreshToken($user);

        // Store token in Redis
        self::storeToken($user->id, $token);

        return [
            'token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => self::$ttl * 60,
            'user' => $user->toInfo()
        ];
    }

    /**
     * Generate JWT token
     *
     * @param User $user
     * @return string
     */
    protected static function generateToken(User $user): string
    {
        self::init();

        $payload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + (self::$ttl * 60),
            'user_id' => $user->id,
            'type' => 'access'
        ];

        return JWT::encode($payload, self::$secret, 'HS256');
    }

    /**
     * Generate refresh token
     *
     * @param User $user
     * @return string
     */
    protected static function generateRefreshToken(User $user): string
    {
        self::init();

        $payload = [
            'iss' => config('app.url'),
            'aud' => config('app.url'),
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + (self::$ttl * 60 * 7), // 7 days
            'user_id' => $user->id,
            'type' => 'refresh'
        ];

        return JWT::encode($payload, self::$secret, 'HS256');
    }

    /**
     * Validate token
     *
     * @param string $token
     * @return array|null
     */
    public static function validateToken(string $token): ?array
    {
        self::init();

        try {
            $decoded = JWT::decode($token, new Key(self::$secret, 'HS256'));

            // Check token type
            if ($decoded->type !== 'access') {
                return null;
            }

            // Check if token is blacklisted
            if (self::isTokenBlacklisted($token)) {
                return null;
            }

            // Get user
            $user = User::find($decoded->user_id);

            if (!$user || $user->status !== 'active') {
                return null;
            }

            return [
                'user_id' => $decoded->user_id,
                'user' => $user
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Refresh token
     *
     * @param string $refreshToken
     * @return array
     */
    public static function refreshToken(string $refreshToken): array
    {
        self::init();

        try {
            $decoded = JWT::decode($refreshToken, new Key(self::$secret, 'HS256'));

            if ($decoded->type !== 'refresh') {
                throw ApiException::businessError('刷新令牌无效', 1005);
            }

            $user = User::find($decoded->user_id);

            if (!$user || $user->status !== 'active') {
                throw ApiException::businessError('用户不存在或已被停用', 1006);
            }

            return self::generateAuthData($user);
        } catch (\Exception $e) {
            throw ApiException::businessError('刷新令牌已过期', 1007);
        }
    }

    /**
     * Logout user
     *
     * @param int $userId
     * @param string|null $token
     */
    public static function logout(int $userId, ?string $token = null): void
    {
        if ($token) {
            self::blacklistToken($token);
        }

        // Clear all user tokens
        $pattern = "auth:token:{$userId}:*";
        $keys = Redis::keys($pattern);

        foreach ($keys as $key) {
            Redis::del($key);
        }
    }

    /**
     * Store token in Redis
     *
     * @param int $userId
     * @param string $token
     */
    protected static function storeToken(int $userId, string $token): void
    {
        $key = "auth:token:{$userId}:" . substr(md5($token), 0, 16);
        $ttl = self::$ttl * 60;

        Redis::setex($key, $ttl, $token);
    }

    /**
     * Blacklist token
     *
     * @param string $token
     */
    protected static function blacklistToken(string $token): void
    {
        $key = "auth:blacklist:" . substr(md5($token), 0, 16);

        try {
            $decoded = JWT::decode($token, new Key(self::$secret, 'HS256'));
            $ttl = max(1, $decoded->exp - time());
        } catch (\Exception $e) {
            $ttl = 3600;
        }

        Redis::setex($key, $ttl, 1);
    }

    /**
     * Check if token is blacklisted
     *
     * @param string $token
     * @return bool
     */
    protected static function isTokenBlacklisted(string $token): bool
    {
        $key = "auth:blacklist:" . substr(md5($token), 0, 16);

        return Redis::exists($key) > 0;
    }

    /**
     * Generate avatar from name
     *
     * @param string $name
     * @return string
     */
    protected static function generateAvatar(string $name): string
    {
        $firstChar = mb_substr($name, 0, 1, 'utf-8');
        $ord = mb_ord($firstChar, 'utf-8');

        // Generate color from name
        $hue = $ord % 360;

        return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100' height='100'%3E%3Crect width='100' height='100' fill='hsl({$hue},60%25,80%25)'/%3E%3Ctext x='50' y='50' text-anchor='middle' dominant-baseline='central' font-size='40' fill='white'%3E{$firstChar}%3C/text%3E%3C/svg%3E";
    }

    /**
     * Hash password
     *
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string
    {
        return Hash::make($password);
    }

    /**
     * Verify password
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return Hash::check($password, $hash);
    }
}
