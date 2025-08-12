<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\ForumService;
use Illuminate\Http\Request;
use Mockery;

/**
 * ForumServiceの包括的テスト
 * 
 * 複雑なビジネスロジックをテスト：
 * - フォーラムデータ取得とページネーション
 * - 投稿データの変換処理
 * - 引用投稿のフォーマット処理
 * - Null Safety（認証状態、データ存在チェック）
 */
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
        $this->assertInstanceOf(ForumService::class, $this->forumService);
    }

    public function test_service_has_required_methods()
    {
        $this->assertTrue(method_exists($this->forumService, 'getForumData'));
        $this->assertTrue(method_exists($this->forumService, 'determineForumId'));
        $this->assertTrue(method_exists($this->forumService, 'getFormattedPosts'));
        $this->assertTrue(method_exists($this->forumService, 'formatQuotedPost'));
        $this->assertTrue(method_exists($this->forumService, 'formatComment'));
    }

    /**
     * Null Safetyテスト：引用投稿がない場合
     */
    public function test_format_quoted_post_returns_null_when_no_post()
    {
        $reflection = new \ReflectionClass($this->forumService);
        $method = $reflection->getMethod('formatQuotedPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->forumService, null);
        
        $this->assertNull($result);
    }

    /**
     * 引用投稿のフォーマット処理テスト（削除済み投稿）
     */
    public function test_format_quoted_post_handles_trashed_post()
    {
        // 削除済み投稿のモック
        $quotedPost = new class {
            public $id = 123;
            public $message = 'Original message';
            public $formatted_message = 'Formatted message';
            public $title = 'Original title';
            public $user = 'original_user';
            
            public function trashed() {
                return true; // 削除済み
            }
        };

        $reflection = new \ReflectionClass($this->forumService);
        $method = $reflection->getMethod('formatQuotedPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->forumService, $quotedPost);
        
        $this->assertIsArray($result);
        $this->assertEquals(123, $result['id']);
        $this->assertNull($result['message']); // 削除済みのためnull
        $this->assertNull($result['formatted_message']);
        $this->assertNull($result['title']);
        $this->assertNull($result['user']);
    }

    /**
     * 引用投稿のフォーマット処理テスト（通常投稿）
     */
    public function test_format_quoted_post_handles_normal_post()
    {
        // 通常投稿のモック
        $quotedPost = new class {
            public $id = 123;
            public $message = 'Original message';
            public $formatted_message = 'Formatted message';
            public $title = 'Original title';
            public $user = 'original_user';
            
            public function trashed() {
                return false; // 削除されていない
            }
        };

        $reflection = new \ReflectionClass($this->forumService);
        $method = $reflection->getMethod('formatQuotedPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->forumService, $quotedPost);
        
        $this->assertIsArray($result);
        $this->assertEquals(123, $result['id']);
        $this->assertEquals('Original message', $result['message']);
        $this->assertEquals('Formatted message', $result['formatted_message']);
        $this->assertEquals('Original title', $result['title']);
        $this->assertEquals('original_user', $result['user']);
    }

    /**
     * フォーラムIDの決定処理テスト（リクエストに指定あり）
     */
    public function test_determine_forum_id_from_request()
    {
        $user = new \stdClass();
        $user->unit_id = 1;

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->with('forum_id')->andReturn(999);

        $reflection = new \ReflectionClass($this->forumService);
        $method = $reflection->getMethod('determineForumId');
        $method->setAccessible(true);

        $result = $method->invoke($this->forumService, $request, $user);
        
        $this->assertEquals(999, $result);
    }

    /**
     * Null Safetyテスト：ユーザーにユニットが設定されていない場合
     */
    public function test_determine_forum_id_with_user_no_unit()
    {
        $user = new \stdClass();
        $user->unit = null; // ユニット未設定

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->with('forum_id')->andReturn(null);

        $reflection = new \ReflectionClass($this->forumService);
        $method = $reflection->getMethod('determineForumId');
        $method->setAccessible(true);

        $result = $method->invoke($this->forumService, $request, $user);
        
        $this->assertNull($result);
    }

    /**
     * エラーレスポンス構築メソッドの存在確認テスト
     */
    public function test_build_error_response_method_exists()
    {
        // buildErrorResponseメソッドはEloquentモデルにアクセスするため、
        // 実際の実行テストはFeatureテストで実装予定
        $this->assertTrue(method_exists($this->forumService, 'buildErrorResponse'));
    }

    /**
     * コメントフォーマット処理の基本構造テスト
     */
    public function test_format_comment_basic_structure()
    {
        // コメントオブジェクトのモック
        $comment = new class {
            public $id = 456;
            public $message = 'Test comment';
            public $formatted_message = 'Formatted comment';
            public $img = 'test.jpg';
            public $created_at = '2024-01-01';
            public $user = 'test_user';
            public $likes_count = 5;
            
            public $likes;
            public $children;
            
            public function __construct() {
                $this->likes = new class {
                    public function isNotEmpty() { return true; }
                };
                $this->children = new class {
                    public function map($callback) { return []; }
                };
            }
        };

        $user = new \stdClass();

        $reflection = new \ReflectionClass($this->forumService);
        $method = $reflection->getMethod('formatComment');
        $method->setAccessible(true);

        $result = $method->invoke($this->forumService, $comment, $user);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('formatted_message', $result);
        $this->assertArrayHasKey('img', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('like_count', $result);
        $this->assertArrayHasKey('is_liked_by_user', $result);
        $this->assertArrayHasKey('children', $result);
        
        $this->assertEquals(456, $result['id']);
        $this->assertEquals('Test comment', $result['message']);
        $this->assertTrue($result['is_liked_by_user']);
    }

    /**
     * プライベートメソッドのアクセシビリティテスト
     */
    public function test_private_methods_accessibility()
    {
        $reflection = new \ReflectionClass($this->forumService);
        
        // ビジネスロジックメソッドがprivateで保護されていることを確認
        $determineMethod = $reflection->getMethod('determineForumId');
        $this->assertTrue($determineMethod->isPrivate());
        
        $formatPostsMethod = $reflection->getMethod('getFormattedPosts');
        $this->assertTrue($formatPostsMethod->isPrivate());
        
        $buildQueryMethod = $reflection->getMethod('buildPostQuery');
        $this->assertTrue($buildQueryMethod->isPrivate());
        
        $transformMethod = $reflection->getMethod('transformPosts');
        $this->assertTrue($transformMethod->isPrivate());
        
        $formatQuotedMethod = $reflection->getMethod('formatQuotedPost');
        $this->assertTrue($formatQuotedMethod->isPrivate());
        
        $formatCommentMethod = $reflection->getMethod('formatComment');
        $this->assertTrue($formatCommentMethod->isPrivate());
        
        $errorResponseMethod = $reflection->getMethod('buildErrorResponse');
        $this->assertTrue($errorResponseMethod->isPrivate());
        
        $successResponseMethod = $reflection->getMethod('buildSuccessResponse');
        $this->assertTrue($successResponseMethod->isPrivate());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}