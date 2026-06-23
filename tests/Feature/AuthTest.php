<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试用户注册
     */
    public function test_user_can_register()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@mootask.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'ret',
                'msg',
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email']
                ]
            ])
            ->assertJson(['ret' => 1]);
    }

    /**
     * 测试用户登录
     */
    public function test_user_can_login()
    {
        $user = \App\Models\User::factory()->create([
            'email' => 'test@mootask.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@mootask.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['ret' => 1])
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    /**
     * 测试登录失败
     */
    public function test_user_cannot_login_with_wrong_password()
    {
        $user = \App\Models\User::factory()->create([
            'email' => 'test@mootask.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@mootask.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(200)
            ->assertJson(['ret' => 0]);
    }

    /**
     * 测试需要认证的接口
     */
    public function test_authenticated_routes_require_token()
    {
        $response = $this->getJson('/api/user/info');

        $response->assertStatus(401);
    }
}
