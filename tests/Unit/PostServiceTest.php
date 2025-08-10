<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\PostService;

class PostServiceTest extends TestCase
{
    private PostService $postService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = new PostService();
    }

    public function test_service_class_instantiation()
    {
        // PostServiceクラスが正しくインスタンス化できることをテスト
        $this->assertInstanceOf(PostService::class, $this->postService);
    }

    public function test_service_has_required_methods()
    {
        // 必要なメソッドが存在することをテスト
        $this->assertTrue(method_exists($this->postService, 'createPost'));
        $this->assertTrue(method_exists($this->postService, 'deletePost'));
        $this->assertTrue(method_exists($this->postService, 'canDeletePost'));
        $this->assertTrue(method_exists($this->postService, 'getPostsByForum'));
        $this->assertTrue(method_exists($this->postService, 'getPostDetails'));
    }
}