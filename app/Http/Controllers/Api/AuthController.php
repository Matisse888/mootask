<?php

namespace App\Http\Controllers\Api;

use App\Module\Auth;
use App\Module\Cache;
use App\Models\User;
use App\Exceptions\ApiException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Authentication Controller
 *
 * @package App\Http\Controllers\Api
 */
class AuthController extends AbstractController
{
    /**
     * User login
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ], [
            'email.required' => '邮箱不能为空',
            'email.email' => '邮箱格式不正确',
            'password.required' => '密码不能为空',
            'password.min' => '密码至少6位',
        ]);

        try {
            $data = Auth::login($request->input('email'), $request->input('password'));

            // Update last login
            User::where('id', $data['user']['id'])->update([
                'last_login_at' => now()
            ]);

            return $this->success($data, '登录成功');
        } catch (ApiException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * User registration
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $this->validate($request, [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'name' => 'required|string|max:50',
        ], [
            'email.required' => '邮箱不能为空',
            'email.email' => '邮箱格式不正确',
            'email.unique' => '邮箱已被注册',
            'password.required' => '密码不能为空',
            'password.min' => '密码至少6位',
            'password.confirmed' => '两次密码输入不一致',
            'name.required' => '昵称不能为空',
            'name.max' => '昵称最多50个字符',
        ]);

        try {
            $data = Auth::register(
                $request->input('email'),
                $request->input('password'),
                $request->input('name')
            );

            return $this->success($data, '注册成功');
        } catch (ApiException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Logout
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        $token = request()->header('Authorization');

        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }

        Auth::logout($this->getUserId(), $token);

        return $this->success(null, '退出登录成功');
    }

    /**
     * Refresh token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function refresh(Request $request): JsonResponse
    {
        $this->validate($request, [
            'refresh_token' => 'required|string',
        ], [
            'refresh_token.required' => '刷新令牌不能为空',
        ]);

        try {
            $data = Auth::refreshToken($request->input('refresh_token'));

            return $this->success($data, '令牌刷新成功');
        } catch (ApiException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * Get captcha
     *
     * @return JsonResponse
     */
    public function captcha(): JsonResponse
    {
        // Generate a simple captcha
        $captcha = [
            'key' => md5(uniqid()),
            'expire' => time() + 300,
        ];

        // Store in cache
        Cache::set('captcha:' . $captcha['key'], 1, 300);

        return $this->success([
            'key' => $captcha['key'],
            // In production, this would return an actual captcha image
            'image' => 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="120" height="40"><rect fill="#f0f0f0" width="120" height="40"/><text x="60" y="25" text-anchor="middle" font-size="20">CAPTCHA</text></svg>')
        ]);
    }

    /**
     * Send verification code
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sendCode(Request $request): JsonResponse
    {
        $this->validate($request, [
            'email' => 'required|email',
            'type' => 'required|in:register,reset',
        ], [
            'email.required' => '邮箱不能为空',
            'email.email' => '邮箱格式不正确',
            'type.required' => '类型不能为空',
        ]);

        $email = $request->input('email');
        $type = $request->input('type');

        // Check rate limit
        $rateKey = 'verify_code:' . $email;
        $count = Cache::get($rateKey, 0);

        if ($count >= 3) {
            return $this->error('发送次数过多，请稍后再试');
        }

        // Generate code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store in cache
        Cache::set('verify_code:' . $type . ':' . $email, $code, 600);

        // Increment rate limit
        Cache::set($rateKey, $count + 1, 3600);

        // In production, send email here
        // Mail::to($email)->send(new VerifyCodeMail($code));

        return $this->success([
            'email' => substr($email, 0, 3) . '***' . substr($email, -4),
            'expire' => 600,
        ], '验证码已发送');
    }

    /**
     * Verify code
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $this->validate($request, [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'type' => 'required|in:register,reset',
        ]);

        $email = $request->input('email');
        $code = $request->input('code');
        $type = $request->input('type');

        $storedCode = Cache::get('verify_code:' . $type . ':' . $email);

        if (!$storedCode || $storedCode !== $code) {
            return $this->error('验证码错误');
        }

        // Clear code
        Cache::delete('verify_code:' . $type . ':' . $email);

        return $this->success(['verified' => true], '验证成功');
    }
}
