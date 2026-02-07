<?php

namespace Tests\Unit;

use Tests\DatabaseTestCase;
use App\Services\AttachmentService;
use App\Models\Attachment;
use App\Models\Post;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AttachmentServiceSimpleTest extends DatabaseTestCase
{
    private AttachmentService $attachmentService;
    private User $user;
    private Tenant $tenant;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 安全なデータベース初期化
        $this->initializeSafeTestData();
        
        $this->attachmentService = new AttachmentService();
        Storage::fake('public');
    }

    private function initializeSafeTestData(): void
    {
        // テナント作成
        $this->tenant = new Tenant();
        $this->tenant->id = 'test-tenant-' . time();
        $this->tenant->save();
        
        // ユーザー作成
        $this->user = new User();
        $this->user->id = 1;
        $this->user->tenant_id = $this->tenant->id;
        $this->user->name = 'Test User';
        $this->user->email = 'test@example.com';
        $this->user->password = 'password';
        $this->user->save();
        
        // 投稿作成
        $this->post = new Post();
        $this->post->id = 1;
        $this->post->user_id = $this->user->id;
        $this->post->tenant_id = $this->tenant->id;
        $this->post->title = 'Test Post';
        $this->post->message = 'Test message';
        $this->post->save();
        
        Auth::login($this->user);
    }

    public function test_attachment_service_can_be_instantiated()
    {
        $this->assertInstanceOf(AttachmentService::class, $this->attachmentService);
    }

    public function test_attachment_service_has_required_methods()
    {
        $this->assertTrue(method_exists($this->attachmentService, 'uploadFiles'));
        $this->assertTrue(method_exists($this->attachmentService, 'uploadSingleFile'));
        $this->assertTrue(method_exists($this->attachmentService, 'deleteAttachment'));
    }

    public function test_supported_file_types_validation()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('isSupportedExtension');
        $method->setAccessible(true);
        
        // サポートされている拡張子
        $this->assertTrue($method->invoke($this->attachmentService, 'jpg'));
        $this->assertTrue($method->invoke($this->attachmentService, 'pdf'));
        $this->assertTrue($method->invoke($this->attachmentService, 'docx'));
        $this->assertTrue($method->invoke($this->attachmentService, 'xlsx'));
        $this->assertTrue($method->invoke($this->attachmentService, 'txt'));
        
        // サポートされていない拡張子
        $this->assertFalse($method->invoke($this->attachmentService, 'exe'));
        $this->assertFalse($method->invoke($this->attachmentService, 'bat'));
        $this->assertFalse($method->invoke($this->attachmentService, 'sh'));
    }

    public function test_file_type_detection()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('getFileType');
        $method->setAccessible(true);
        
        $this->assertEquals('image', $method->invoke($this->attachmentService, 'jpg'));
        $this->assertEquals('pdf', $method->invoke($this->attachmentService, 'pdf'));
        $this->assertEquals('document', $method->invoke($this->attachmentService, 'docx'));
        $this->assertEquals('excel', $method->invoke($this->attachmentService, 'xlsx'));
        $this->assertEquals('text', $method->invoke($this->attachmentService, 'txt'));
    }

    public function test_mime_type_detection()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('getMimeType');
        $method->setAccessible(true);
        
        $this->assertEquals('image/jpeg', $method->invoke($this->attachmentService, 'jpg'));
        $this->assertEquals('application/pdf', $method->invoke($this->attachmentService, 'pdf'));
        $this->assertEquals('text/plain', $method->invoke($this->attachmentService, 'txt'));
    }

    public function test_storage_path_generation()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('getStoragePath');
        $method->setAccessible(true);
        
        $this->assertEquals('attachments/images', $method->invoke($this->attachmentService, 'image'));
        $this->assertEquals('attachments/pdfs', $method->invoke($this->attachmentService, 'pdf'));
        $this->assertEquals('attachments/documents', $method->invoke($this->attachmentService, 'document'));
        $this->assertEquals('attachments/excel', $method->invoke($this->attachmentService, 'excel'));
        $this->assertEquals('attachments/text', $method->invoke($this->attachmentService, 'text'));
        $this->assertEquals('attachments/misc', $method->invoke($this->attachmentService, 'unknown'));
    }

    public function test_safe_filename_generation()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('generateSafeFileName');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->attachmentService, '危険な ファイル名.jpg', 'jpg');
        
        $this->assertStringContainsString('.jpg', $result);
        $this->assertStringNotContainsString('危険', $result);
        $this->assertStringNotContainsString(' ', $result);
    }

    public function test_file_validation_with_large_file()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('validateFile');
        $method->setAccessible(true);
        
        // 大きなファイルをモック（実際には作成しない）
        $largeFile = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $largeFile->method('getSize')->willReturn(11 * 1024 * 1024); // 11MB
        $largeFile->method('getClientOriginalExtension')->willReturn('jpg');
        
        $result = $method->invoke($this->attachmentService, $largeFile);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('ファイルサイズが制限を超えています', $result['error']);
    }

    public function test_file_validation_with_unsupported_extension()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('validateFile');
        $method->setAccessible(true);
        
        // 危険な拡張子のファイルをモック
        $dangerousFile = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $dangerousFile->method('getSize')->willReturn(1024);
        $dangerousFile->method('getClientOriginalExtension')->willReturn('exe');
        
        $result = $method->invoke($this->attachmentService, $dangerousFile);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('サポートされていないファイル形式です', $result['error']);
    }

    public function test_duplicate_file_detection()
    {
        $hash = 'test_hash_12345';
        
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('findDuplicateFile');
        $method->setAccessible(true);
        
        // 重複ファイルが見つからない場合
        $result = $method->invoke($this->attachmentService, $hash, $this->tenant->id);
        $this->assertNull($result);
    }

    public function test_security_scan_basic_functionality()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('performSecurityScan');
        $method->setAccessible(true);
        
        // 安全なファイルのモック
        $safeFile = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        // 一時的なテストファイルを作成
        $tempFile = tempnam(sys_get_temp_dir(), 'test_image');
        file_put_contents($tempFile, 'fake image content');
        
        $safeFile->method('getRealPath')->willReturn($tempFile);
        
        $result = $method->invoke($this->attachmentService, $safeFile);
        
        // セキュリティスキャンは実装レベルに依存するため、基本的な動作確認のみ
        $this->assertIsBool($result);
        
        unlink($tempFile);
    }
}