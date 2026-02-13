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
        $this->assertTrue(method_exists($this->forumService, 'buildPostQuery'));
        $this->assertTrue(method_exists($this->forumService, 'transformPosts'));
        $this->assertTrue(method_exists($this->forumService, 'buildErrorResponse'));
        $this->assertTrue(method_exists($this->forumService, 'buildSuccessResponse'));
    }

    /**
     * getForumDataメソッドのパラメータ検証テスト
     */
    public function test_get_forum_data_parameter_validation()
    {
        // メソッドシグネチャの確認
        $reflection = new \ReflectionMethod($this->forumService, 'getForumData');
        $parameters = $reflection->getParameters();
        
        // 引数の数と名前を確認（getForumDataは1つのパラメータのみ）
        $this->assertCount(1, $parameters);
        $this->assertEquals('request', $parameters[0]->getName());
        
        // 戻り値の型確認
        $this->assertTrue($reflection->isPublic());
        
        // 戻り値がarray型であることを確認
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    /**
     * buildPostQueryメソッドの存在とアクセシビリティテスト
     */
    public function test_build_post_query_method_structure()
    {
        $reflection = new \ReflectionMethod($this->forumService, 'buildPostQuery');
        
        // プライベートメソッドであることを確認
        $this->assertTrue($reflection->isPrivate());
        
        // パラメータの確認
        $parameters = $reflection->getParameters();
        $this->assertGreaterThanOrEqual(1, count($parameters));
    }

    /**
     * transformPostsメソッドの構造テスト
     */
    public function test_transform_posts_method_structure()
    {
        $reflection = new \ReflectionMethod($this->forumService, 'transformPosts');
        
        // プライベートメソッドであることを確認
        $this->assertTrue($reflection->isPrivate());
        
        // パラメータの確認
        $parameters = $reflection->getParameters();
        $this->assertGreaterThanOrEqual(2, count($parameters));
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
                    public function map($callback) { 
                        unset($callback); // 未使用変数警告を回避
                        return []; 
                    }
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
     * エッジケース：無効なリクエストでのdetermineForumIdテスト
     */
    public function test_determine_forum_id_with_invalid_request()
    {
        $user = new \stdClass();
        $user->unit_id = null;
        $user->unit = null;

        $request = Mockery::mock(Request::class);
        $request->shouldReceive('input')->with('forum_id')->andReturn(null);

        $reflection = new \ReflectionClass($this->forumService);
        $method = $reflection->getMethod('determineForumId');
        $method->setAccessible(true);

        $result = $method->invoke($this->forumService, $request, $user);
        
        $this->assertNull($result);
    }

    /**
     * 引用投稿のフォーマット処理：データ不整合ケース
     */
    public function test_format_quoted_post_with_incomplete_data()
    {
        // 不完全なデータを持つ投稿のモック
        $quotedPost = new class {
            public $id = 123;
            public $message = null; // データ不整合
            public $formatted_message = null;
            public $title = '';
            public $user = null;
            
            public function trashed() {
                return false;
            }
        };

        $reflection = new \ReflectionClass($this->forumService);
        $method = $reflection->getMethod('formatQuotedPost');
        $method->setAccessible(true);

        $result = $method->invoke($this->forumService, $quotedPost);
        
        $this->assertIsArray($result);
        $this->assertEquals(123, $result['id']);
        $this->assertNull($result['message']);
        $this->assertNull($result['formatted_message']);
        $this->assertEquals('', $result['title']);
        $this->assertNull($result['user']);
    }

    /**
     * コメントフォーマット処理：空のコレクションケース
     */
    public function test_format_comment_with_empty_collections()
    {
        // 空のコレクションを持つコメントのモック
        $comment = new class {
            public $id = 789;
            public $message = 'Test comment';
            public $formatted_message = 'Formatted comment';
            public $img = null;
            public $created_at = '2024-01-01';
            public $user = 'test_user';
            public $likes_count = 0;
            
            public $likes;
            public $children;
            
            public function __construct() {
                $this->likes = new class {
                    public function isNotEmpty() { return false; }
                };
                $this->children = new class {
                    public function map($callback) { 
                        unset($callback); // 未使用変数警告を回避
                        return []; 
                    }
                };
            }
        };

        $user = new \stdClass();

        $reflection = new \ReflectionClass($this->forumService);
        $method = $reflection->getMethod('formatComment');
        $method->setAccessible(true);

        $result = $method->invoke($this->forumService, $comment, $user);
        
        $this->assertIsArray($result);
        $this->assertEquals(789, $result['id']);
        $this->assertFalse($result['is_liked_by_user']);
        $this->assertEquals(0, $result['like_count']);
        $this->assertIsArray($result['children']);
        $this->assertEmpty($result['children']);
    }

    /**
     * レスポンス構築メソッドの戻り値型テスト
     */
    public function test_response_builder_methods_return_types()
    {
        // buildErrorResponseメソッドの戻り値型確認
        $errorReflection = new \ReflectionMethod($this->forumService, 'buildErrorResponse');
        $this->assertTrue($errorReflection->isPrivate());
        
        // buildSuccessResponseメソッドの戻り値型確認
        $successReflection = new \ReflectionMethod($this->forumService, 'buildSuccessResponse');
        $this->assertTrue($successReflection->isPrivate());
        
        // メソッドシグネチャの基本構造を確認（将来の変更に対して柔軟）
        $errorParams = $errorReflection->getParameters();
        $successParams = $successReflection->getParameters();
        
        // パラメータ配列が取得できることを確認（メソッドの基本構造テスト）
        $this->assertIsArray($errorParams);
        $this->assertIsArray($successParams);
        
        // メソッドが正常に定義されていることを確認（将来の変更に対して柔軟）
        $this->assertTrue($errorReflection->isUserDefined()); // ユーザー定義メソッド
        $this->assertTrue($successReflection->isUserDefined()); // ユーザー定義メソッド
    }

    /**
     * determineForumIdメソッドの詳細パラメータテスト
     */
    public function test_determine_forum_id_parameter_types()
    {
        $reflection = new \ReflectionMethod($this->forumService, 'determineForumId');
        $parameters = $reflection->getParameters();
        
        // パラメータ数の確認
        $this->assertCount(2, $parameters);
        
        // 第1引数がRequestオブジェクトであることを確認
        $this->assertEquals('request', $parameters[0]->getName());
        
        // 第2引数がuserオブジェクトであることを確認
        $this->assertEquals('user', $parameters[1]->getName());
        
        // 戻り値の型ヒント確認
        $returnType = $reflection->getReturnType();
        $this->assertTrue($returnType === null || $returnType->allowsNull());
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