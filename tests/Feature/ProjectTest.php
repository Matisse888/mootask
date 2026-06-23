<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $this->token = $loginResponse->json('data.token');
    }

    /**
     * 测试创建项目
     */
    public function test_user_can_create_project()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/project/create', [
            'name' => 'Test Project',
            'desc' => 'Test Description',
            'color' => '#409EFF',
        ]);

        $response->assertStatus(200)
            ->assertJson(['ret' => 1])
            ->assertJsonStructure(['data' => ['id', 'name']]);
    }

    /**
     * 测试获取项目列表
     */
    public function test_user_can_list_projects()
    {
        // Create a project
        $this->user->createdProjects()->create([
            'name' => 'Test Project',
            'owner_user_id' => $this->user->id,
            'color' => '#409EFF',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/project/lists');

        $response->assertStatus(200)
            ->assertJson(['ret' => 1])
            ->assertJsonStructure(['data' => ['list', 'total']]);
    }

    /**
     * 测试更新项目
     */
    public function test_owner_can_update_project()
    {
        $project = $this->user->createdProjects()->create([
            'name' => 'Original Name',
            'owner_user_id' => $this->user->id,
            'color' => '#409EFF',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/project/{$project->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJson(['ret' => 1]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * 测试删除项目
     */
    public function test_owner_can_delete_project()
    {
        $project = $this->user->createdProjects()->create([
            'name' => 'To Delete',
            'owner_user_id' => $this->user->id,
            'color' => '#409EFF',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/project/{$project->id}");

        $response->assertStatus(200)
            ->assertJson(['ret' => 1]);

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }
}
