<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AttachmentService;
use App\Models\Attachment;
use App\Models\Post;
use App\Models\User;
use App\Models\Tenant;
use App\Exceptions\Custom\TenantViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AttachmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttachmentService $attachmentService;
    private User $user;
    private Tenant $tenant;
    private Post $post;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->attachmentService = new AttachmentService();
        
        // テナント作成
        $this->tenant = Tenant::factory()->create();
        tenancy()->initialize($this->tenant);
        
        // ユーザー作成
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        // 投稿作成
        $this->post = Post::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id
        ]);
        
        Auth::login($this->user);
        Storage::fake('public');
    }

    public function test_upload_single_image_file_successfully()
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600)->size(1000);
        
        $attachment = $this->attachmentService->uploadSingleFile(
            $file,
            'App\Models\Post',
            $this->post->id
        );
        
        $this->assertInstanceOf(Attachment::class, $attachment);
        $this->assertEquals('test.jpg', $attachment->original_name);
        $this->assertEquals('image', $attachment->file_type);
        $this->assertEquals('image/jpeg', $attachment->mime_type);
        $this->assertEquals($this->tenant->id, $attachment->tenant_id);
        $this->assertEquals($this->user->id, $attachment->uploaded_by);
        $this->assertTrue($attachment->is_safe);
        
        Storage::disk('public')->assertExists($attachment->file_path);
    }

    public function test_upload_single_pdf_file_successfully()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf');
        
        $attachment = $this->attachmentService->uploadSingleFile(
            $file,
            'App\Models\Post',
            $this->post->id
        );
        
        $this->assertEquals('pdf', $attachment->file_type);
        $this->assertEquals('application/pdf', $attachment->mime_type);
        $this->assertEquals('document.pdf', $attachment->original_name);
    }

    public function test_upload_multiple_files_successfully()
    {
        $files = [
            UploadedFile::fake()->image('image1.jpg'),
            UploadedFile::fake()->image('image2.png'),
            UploadedFile::fake()->create('document.pdf', 1000, 'application/pdf')
        ];
        
        $attachments = $this->attachmentService->uploadFiles(
            $files,
            'App\Models\Post',
            $this->post->id
        );
        
        $this->assertCount(3, $attachments);
        
        foreach ($attachments as $attachment) {
            $this->assertInstanceOf(Attachment::class, $attachment);
            $this->assertEquals($this->tenant->id, $attachment->tenant_id);
        }
    }

    public function test_upload_file_with_invalid_size_throws_exception()
    {
        // 10MB超のファイル作成
        $file = UploadedFile::fake()->create('large_file.jpg', 11 * 1024); // 11MB
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ファイルサイズが制限を超えています');
        
        $this->attachmentService->uploadSingleFile(
            $file,
            'App\Models\Post',
            $this->post->id
        );
    }

    public function test_upload_file_with_unsupported_extension_throws_exception()
    {
        $file = UploadedFile::fake()->create('malicious.exe', 1000, 'application/octet-stream');
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('サポートされていないファイル形式です');
        
        $this->attachmentService->uploadSingleFile(
            $file,
            'App\Models\Post',
            $this->post->id
        );
    }

    public function test_tenant_boundary_violation_throws_exception()
    {
        // 異なるテナントの投稿を作成
        $otherTenant = Tenant::factory()->create();
        $otherPost = Post::factory()->create([
            'tenant_id' => $otherTenant->id
        ]);
        
        $file = UploadedFile::fake()->image('test.jpg');
        
        $this->expectException(TenantViolationException::class);
        
        $this->attachmentService->uploadSingleFile(
            $file,
            'App\Models\Post',
            $otherPost->id
        );
    }

    public function test_duplicate_file_handling()
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        // 最初のアップロード
        $attachment1 = $this->attachmentService->uploadSingleFile(
            $file,
            'App\Models\Post',
            $this->post->id
        );
        
        // 同じファイルの2回目のアップロード
        $file2 = UploadedFile::fake()->image('test.jpg', 800, 600);
        $attachment2 = $this->attachmentService->uploadSingleFile(
            $file2,
            'App\Models\Post',
            $this->post->id
        );
        
        // 異なるAttachmentインスタンスだが、同じファイルパスとハッシュ
        $this->assertNotEquals($attachment1->id, $attachment2->id);
        $this->assertEquals($attachment1->file_path, $attachment2->file_path);
        $this->assertEquals($attachment1->hash, $attachment2->hash);
    }

    public function test_delete_attachment_successfully()
    {
        $file = UploadedFile::fake()->image('test.jpg');
        $attachment = $this->attachmentService->uploadSingleFile(
            $file,
            'App\Models\Post',
            $this->post->id
        );
        
        $result = $this->attachmentService->deleteAttachment($attachment->id);
        
        $this->assertTrue($result);
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('public')->assertMissing($attachment->file_path);
    }

    public function test_delete_attachment_tenant_violation()
    {
        // 異なるテナントのユーザー作成
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);
        
        $file = UploadedFile::fake()->image('test.jpg');
        $attachment = $this->attachmentService->uploadSingleFile(
            $file,
            'App\Models\Post',
            $this->post->id
        );
        
        // 異なるテナントのユーザーでログイン
        Auth::login($otherUser);
        
        $this->expectException(TenantViolationException::class);
        
        $this->attachmentService->deleteAttachment($attachment->id);
    }

    public function test_delete_attachment_permission_denied()
    {
        // 別のユーザー作成（同じテナント内）
        $otherUser = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        $file = UploadedFile::fake()->image('test.jpg');
        $attachment = $this->attachmentService->uploadSingleFile(
            $file,
            'App\Models\Post',
            $this->post->id
        );
        
        // 別のユーザーでログイン（管理者権限なし）
        Auth::login($otherUser);
        
        $result = $this->attachmentService->deleteAttachment($attachment->id);
        
        $this->assertFalse($result);
        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);
    }

    public function test_file_validation_mime_type_mismatch()
    {
        // PDF MIMEタイプでJPG拡張子のファイル（偽装攻撃）
        $file = UploadedFile::fake()->create('fake.jpg', 1000, 'application/pdf');
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ファイル形式が一致しません');
        
        $this->attachmentService->uploadSingleFile(
            $file,
            'App\Models\Post',
            $this->post->id
        );
    }

    public function test_security_scan_detects_executable()
    {
        // 実際のテストでは、SecurityValidationTraitとTenantBoundaryCheckTraitの
        // メソッドをモックして、セキュリティスキャンの動作をテストする
        
        $this->markTestIncomplete('セキュリティスキャンのテストは、実際のTraitが実装された後に完成させる');
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

    public function test_supported_file_types()
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
}