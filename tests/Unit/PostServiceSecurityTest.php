<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Post;
use App\Services\PostService;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Auth;
use Mockery;

class PostServiceSecurityTest extends TestCase
{
    private PostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PostService(Mockery::mock(AttachmentService::class));
    }

    public function test_can_delete_post_returns_false_when_unauthenticated(): void
    {
        Auth::shouldReceive('user')->andReturn(null);

        $post = new Post();
        $post->user_id = 123;

        $this->assertFalse($this->service->canDeletePost($post));
    }

    public function test_can_delete_post_returns_true_for_owner(): void
    {
        $user = new class {
            public $id = 123;
            public function hasRole($role) { return false; }
        };
        Auth::shouldReceive('user')->andReturn($user);

        $post = new Post();
        $post->user_id = 123;

        $this->assertTrue($this->service->canDeletePost($post));
    }

    public function test_can_delete_post_returns_true_for_admin(): void
    {
        $user = new class {
            public $id = 999;
            public function hasRole($role) { return $role === 'admin'; }
        };
        Auth::shouldReceive('user')->andReturn($user);

        $post = new Post();
        $post->user_id = 123; // 別ユーザーの投稿

        $this->assertTrue($this->service->canDeletePost($post));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

