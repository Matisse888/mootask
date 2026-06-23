<?php

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;

class AbstractController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Current user ID
     *
     * @var int|null
     */
    protected ?int $userId = null;

    /**
     * Current user
     *
     * @var object|null
     */
    protected ?object $user = null;

    /**
     * Initialize controller
     */
    public function __construct()
    {
        $this->userId = request()->attributes->get('user_id');
        $this->user = request()->attributes->get('user');
    }

    /**
     * Return success response
     *
     * @param string $msg
     * @param mixed $data
     * @param int $code
     * @return JsonResponse
     */
    protected function success($data = null, string $msg = '操作成功', int $code = 200): JsonResponse
    {
        return response()->json([
            'ret' => 1,
            'msg' => $msg,
            'data' => $data
        ], $code);
    }

    /**
     * Return error response
     *
     * @param string $msg
     * @param mixed $data
     * @param int $code
     * @return JsonResponse
     */
    protected function error(string $msg = '操作失败', $data = null, int $code = 400): JsonResponse
    {
        return response()->json([
            'ret' => 0,
            'msg' => $msg,
            'data' => $data
        ], $code);
    }

    /**
     * Get validated data
     *
     * @param array $rules
     * @param array $messages
     * @return array
     */
    protected function validateData(array $rules, array $messages = []): array
    {
        $data = request()->validate($rules, $messages);
        return $data ?? [];
    }

    /**
     * Get current user ID
     *
     * @return int|null
     */
    protected function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * Get current user
     *
     * @return object|null
     */
    protected function getUser(): ?object
    {
        return $this->user;
    }
}
