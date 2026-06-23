<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected $code = 400;
    protected $errorCode = 1000;

    public function __construct(string $message = '', int $code = 400, int $errorCode = 1000, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->code = $code;
        $this->errorCode = $errorCode;
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @return JsonResponse
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'ret' => 0,
            'msg' => $this->getMessage(),
            'data' => null,
            'error_code' => $this->errorCode
        ], $this->code);
    }

    /**
     * Get error code
     *
     * @return int
     */
    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    /**
     * Common error codes
     */
    public static function invalidParams(string $message = '参数错误'): self
    {
        return new self($message, 400, 1001);
    }

    public static function unauthorized(string $message = '未授权'): self
    {
        return new self($message, 401, 1002);
    }

    public static function forbidden(string $message = '无权限'): self
    {
        return new self($message, 403, 1003);
    }

    public static function notFound(string $message = '资源不存在'): self
    {
        return new self($message, 404, 1004);
    }

    public static function serverError(string $message = '服务器内部错误'): self
    {
        return new self($message, 500, 1005);
    }

    public static function businessError(string $message, int $errorCode = 2000): self
    {
        return new self($message, 400, $errorCode);
    }
}
