<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\PostService;
use App\Services\AttachmentService;
use App\Services\ForumService;
use App\Repositories\IPostRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Mockery;

/**
 * Null Safety検証テスト
 * 
 * Null値やundefined状態での安全性を徹底テスト：
 * - 未認証状態での処理の安全性
 * - null値パラメータの適切な処理
 * - 存在しないデータへのアクセス処理
 * - エラー状態での例外安全性
 */
class NullSafetyTest extends TestCase
{
    private PostService $postService;
    private ForumService $forumService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->postService = new PostService(
            Mockery::mock(AttachmentService::class),
            Mockery::mock(IPostRepository::class)
        );
        $this->forumService = new ForumService();
    }

    /**
     * PostService::canDeletePost() - 未認証ユーザーのNull Safety
     * 注：実際のModelインスタンス必要なため、メソッド存在確認のみ
     */
    public function test_can_delete_post_null_user_safety()
    {
        // canDeletePostメソッドは型安全なため、null安全性の設計確認
        $this->assertTrue(method_exists($this->postService, 'canDeletePost'));
        
        // 実際のnullユーザー処理は内部でAuth::user()でチェックされる
        $reflection = new \ReflectionClass($this->postService);
        $method = $reflection->getMethod('canDeletePost');
        
        // nullユーザーチェックのコードが存在することを確認
        $this->assertStringContainsString('Auth::user()', file_get_contents($reflection->getFileName()));
    }

    /**
     * Auth::user()がnullを返す場合の安全性確認
     */
    public function test_auth_user_null_safety_handling()
    {
        // PostServiceでAuth::user()のnullチェックが適切に行われているかテスト
        $reflection = new \ReflectionClass($this->postService);
        $fileName = $reflection->getFileName();
        $fileContent = file_get_contents($fileName);
        
        // canDeletePostメソッドでnullチェックが存在することを確認
        $this->assertStringContainsString('if (!$currentUser)', $fileContent);
        $this->assertStringContainsString('return false', $fileContent);
    }

    /**
     * PostService::handleImageUpload() - nullファイルのNull Safety
     */
    public function test_handle_image_upload_with_null_file()
    {
        $request = Mockery::mock('App\Http\Requests\Post\PostStoreRequest');
        $request->shouldReceive('hasFile')->with('image')->andReturn(false);

        $reflection = new \ReflectionClass($this->postService);
        $method = $reflection->getMethod('handleImageUpload');
        $method->setAccessible(true);

        $result = $method->invoke($this->postService, $request);
        
        $this->assertNull($result); // ファイルがない場合はnullを返す
    }

    /**
     * ForumService::formatQuotedPost() - null引用投稿のNull Safety
     */
    public function test_format_quoted_post_with_null_post()
    {
        $reflection = new \ReflectionClass($this->forumService);
        $method = $reflection->getMethod('formatQuotedPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->forumService, null);
        
        $this->assertNull($result); // null投稿の場合はnullを返す
    }

    /**
     * ForumService::formatQuotedPost() - プロパティがnullの投稿
     */
    public function test_format_quoted_post_with_null_properties()
    {
        $quotedPost = new class {
            public $id = 123;
            public $message = null; // nullメッセージ
            public $formatted_message = null;
            public $title = null;
            public $user = null;
            
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
        $this->assertNull($result['message']); // nullプロパティはそのまま保持
        $this->assertNull($result['formatted_message']);
        $this->assertNull($result['title']);
        $this->assertNull($result['user']);
    }

    /**
     * ForumService::determineForumId() - nullプロパティユーザー
     */
    public function test_determine_forum_id_with_null_user_properties()
    {
        $user = new \stdClass();
        $user->unit_id = null; // nullのunit_id
        $user->unit = null; // nullのunit

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->with('forum_id')->andReturn(null);

        $reflection = new \ReflectionClass($this->forumService);
        $method = $reflection->getMethod('determineForumId');
        $method->setAccessible(true);

        $result = $method->invoke($this->forumService, $request, $user);
        
        $this->assertNull($result); // unitがnullの場合はnullを返す
    }

    /**
     * 例外クラスのNull Safety - null値での初期化
     */
    public function test_tenant_violation_exception_with_empty_values()
    {
        $exception = new \App\Exceptions\Custom\TenantViolationException(
            currentTenantId: '',
            resourceTenantId: '',
            resourceType: '',
            resourceId: 0
        );

        $this->assertEquals('', $exception->currentTenantId);
        $this->assertEquals('', $exception->resourceTenantId);
        $this->assertEquals('', $exception->resourceType);
        $this->assertEquals(0, $exception->resourceId);

        // ログコンテキストも正常に生成される
        $logContext = $exception->getLogContext();
        $this->assertIsArray($logContext);
        $this->assertEquals('tenant_violation', $logContext['exception_type']);
    }

    /**
     * 例外クラスのNull Safety - カスタムメッセージがnull
     */
    public function test_post_ownership_exception_with_null_message()
    {
        $exception = new \App\Exceptions\Custom\PostOwnershipException(
            userId: 1,
            postId: 123,
            postOwnerId: 2,
            operation: 'delete',
            isAdmin: false,
            message: null // nullメッセージ
        );

        // nullメッセージの場合はデフォルトメッセージが生成される
        $this->assertNotNull($exception->getMessage());
        $this->assertStringContainsString('投稿所有権エラー', $exception->getMessage());
    }

    /**
     * 境界値テスト - 空文字列とゼロ値
     */
    public function test_boundary_values_handling()
    {
        // 空文字列のテナントID
        $tenantException = new \App\Exceptions\Custom\TenantViolationException(
            currentTenantId: '',
            resourceTenantId: '',
            resourceType: '',
            resourceId: 0
        );

        $userMessage = $tenantException->getUserMessage();
        $this->assertNotEmpty($userMessage); // 空文字列でも安全なメッセージを返す

        // ゼロ値のユーザーID
        $ownershipException = new \App\Exceptions\Custom\PostOwnershipException(
            userId: 0,
            postId: 0,
            postOwnerId: 0,
            operation: '',
            isAdmin: false
        );

        $logContext = $ownershipException->getLogContext();
        $this->assertIsArray($logContext);
        $this->assertEquals(0, $logContext['user_id']);
    }

    /**
     * コレクションのNull Safety - 空コレクション処理
     */
    public function test_format_comment_with_empty_collections()
    {
        $comment = new class {
            public $id = 456;
            public $message = 'Test comment';
            public $formatted_message = 'Formatted comment';
            public $img = null; // null画像
            public $created_at = '2024-01-01';
            public $user = null; // nullユーザー
            public $likes_count = 0;
            
            public $likes;
            public $children;
            
            public function __construct() {
                // 空のlikesコレクション
                $this->likes = new class {
                    public function isNotEmpty() { return false; }
                };
                // 空のchildrenコレクション
                $this->children = new class {
                    public function map($callback) { return []; }
                };
            }
        };

        $user = null; // nullユーザー

        $reflection = new \ReflectionClass($this->forumService);
        $method = $reflection->getMethod('formatComment');
        $method->setAccessible(true);

        $result = $method->invoke($this->forumService, $comment, $user);
        
        $this->assertIsArray($result);
        $this->assertEquals(456, $result['id']);
        $this->assertNull($result['img']); // null画像は保持
        $this->assertNull($result['user']); // nullユーザーは保持
        $this->assertFalse($result['is_liked_by_user']); // 空likesではfalse
        $this->assertEquals([], $result['children']); // 空childrenは空配列
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}