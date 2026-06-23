<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\ApiException;
use App\Module\Auth as AuthModule;

class AuthToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            throw ApiException::unauthorized('缺少认证令牌');
        }

        $auth = AuthModule::validateToken($token);

        if (!$auth) {
            throw ApiException::unauthorized('认证令牌无效或已过期');
        }

        // Add user to request
        $request->attributes->set('user_id', $auth['user_id']);
        $request->attributes->set('user', $auth['user']);

        return $next($request);
    }

    /**
     * Get token from request
     *
     * @param Request $request
     * @return string|null
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        // Try Authorization header first
        $token = $request->header('Authorization');

        if ($token && str_starts_with($token, 'Bearer ')) {
            return substr($token, 7);
        }

        // Try X-Token header
        $token = $request->header('X-Token');

        if ($token) {
            return $token;
        }

        // Try query parameter
        return $request->query('token');
    }
}
