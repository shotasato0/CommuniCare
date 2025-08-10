<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ForumService;

class ForumServiceTest extends TestCase
{
    private ForumService $forumService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forumService = new ForumService();
    }

    public function test_service_class_instantiation()
    {
        // ForumServiceクラスが正しくインスタンス化できることをテスト
        $this->assertInstanceOf(ForumService::class, $this->forumService);
    }

    public function test_service_has_required_methods()
    {
        // 必要なメソッドが存在することをテスト
        $this->assertTrue(method_exists($this->forumService, 'getForumData'));
    }

    public function test_format_quoted_post_returns_null_when_no_post()
    {
        // 引用投稿がない場合のテスト（nullを渡す）
        $reflection = new \ReflectionClass($this->forumService);
        $method = $reflection->getMethod('formatQuotedPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->forumService, null);
        
        $this->assertNull($result);
    }
}