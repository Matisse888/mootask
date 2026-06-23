<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        ApiException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (ApiException $e) {
            return $e->render();
        });

        $this->renderable(function (AuthenticationException $e) {
            return response()->json([
                'ret' => 0,
                'msg' => '未认证，请先登录',
                'data' => null
            ], 401);
        });

        $this->renderable(function (ValidationException $e) {
            return response()->json([
                'ret' => 0,
                'msg' => '参数验证失败',
                'data' => $e->errors()
            ], 400);
        });

        $this->renderable(function (NotFoundHttpException $e) {
            return response()->json([
                'ret' => 0,
                'msg' => '资源不存在',
                'data' => null
            ], 404);
        });

        $this->renderable(function (MethodNotAllowedHttpException $e) {
            return response()->json([
                'ret' => 0,
                'msg' => '请求方法不允许',
                'data' => null
            ], 405);
        });

        $this->renderable(function (Throwable $e) {
            if (config('app.debug')) {
                return response()->json([
                    'ret' => 0,
                    'msg' => $e->getMessage(),
                    'data' => [
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]
                ], 500);
            }

            return response()->json([
                'ret' => 0,
                'msg' => '服务器内部错误',
                'data' => null
            ], 500);
        });
    }
}
