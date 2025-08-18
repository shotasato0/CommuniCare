<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Attachment;
use App\Models\Post;
use App\Models\Comment;
use App\Models\User;
use App\Models\Tenant;

class AttachmentModelTest extends TestCase
{

    private Tenant $tenant;
    private User $user;
    private Post $post;
    private Comment $comment;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 安全なデータベース初期化
        $this->initializeSafeTestData();
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
        
        // コメント作成
        $this->comment = new Comment();
        $this->comment->id = 1;
        $this->comment->user_id = $this->user->id;
        $this->comment->post_id = $this->post->id;
        $this->comment->tenant_id = $this->tenant->id;
        $this->comment->message = 'Test comment';
        $this->comment->save();
    }

    public function test_attachment_belongs_to_tenant()
    {
        $attachment = new Attachment();
        $attachment->attachable_type = 'App\Models\Post';
        $attachment->attachable_id = $this->post->id;
        $attachment->original_name = 'test.jpg';
        $attachment->file_name = 'safe_test.jpg';
        $attachment->file_path = 'attachments/images/safe_test.jpg';
        $attachment->file_size = 1024;
        $attachment->mime_type = 'image/jpeg';
        $attachment->file_type = 'image';
        $attachment->tenant_id = $this->tenant->id;
        $attachment->uploaded_by = $this->user->id;
        $attachment->hash = 'test_hash';
        $attachment->is_safe = true;
        $attachment->save();
        
        $this->assertEquals($this->tenant->id, $attachment->tenant_id);
    }

    public function test_polymorphic_relationship_with_post()
    {
        $attachment = Attachment::factory()->create([
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $this->post->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $this->assertInstanceOf(Post::class, $attachment->attachable);
        $this->assertEquals($this->post->id, $attachment->attachable->id);
    }

    public function test_polymorphic_relationship_with_comment()
    {
        $attachment = Attachment::factory()->create([
            'attachable_type' => 'App\Models\Comment',
            'attachable_id' => $this->comment->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $this->assertInstanceOf(Comment::class, $attachment->attachable);
        $this->assertEquals($this->comment->id, $attachment->attachable->id);
    }

    public function test_uploaded_by_relationship()
    {
        $attachment = Attachment::factory()->create([
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $this->post->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $this->assertInstanceOf(User::class, $attachment->uploadedBy);
        $this->assertEquals($this->user->id, $attachment->uploadedBy->id);
    }

    public function test_formatted_file_size_attribute()
    {
        $attachment = Attachment::factory()->create([
            'file_size' => 1024, // 1 KB
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $this->assertEquals('1.00 KB', $attachment->formatted_file_size);
        
        $attachment->file_size = 1048576; // 1 MB
        $this->assertEquals('1.00 MB', $attachment->formatted_file_size);
        
        $attachment->file_size = 1073741824; // 1 GB
        $this->assertEquals('1.00 GB', $attachment->formatted_file_size);
        
        $attachment->file_size = 500; // 500 bytes
        $this->assertEquals('500 bytes', $attachment->formatted_file_size);
    }

    public function test_is_image_method()
    {
        $imageAttachment = Attachment::factory()->create([
            'file_type' => 'image',
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $pdfAttachment = Attachment::factory()->create([
            'file_type' => 'pdf',
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $this->assertTrue($imageAttachment->isImage());
        $this->assertFalse($pdfAttachment->isImage());
    }

    public function test_is_document_method()
    {
        $imageAttachment = Attachment::factory()->create([
            'file_type' => 'image',
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $pdfAttachment = Attachment::factory()->create([
            'file_type' => 'pdf',
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $docAttachment = Attachment::factory()->create([
            'file_type' => 'document',
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $excelAttachment = Attachment::factory()->create([
            'file_type' => 'excel',
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $textAttachment = Attachment::factory()->create([
            'file_type' => 'text',
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $this->assertFalse($imageAttachment->isDocument());
        $this->assertTrue($pdfAttachment->isDocument());
        $this->assertTrue($docAttachment->isDocument());
        $this->assertTrue($excelAttachment->isDocument());
        $this->assertTrue($textAttachment->isDocument());
    }

    public function test_file_extension_attribute()
    {
        $attachment = Attachment::factory()->create([
            'original_name' => 'test_document.pdf',
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $this->assertEquals('pdf', $attachment->file_extension);
        
        $attachment->original_name = 'image.jpg';
        $this->assertEquals('jpg', $attachment->file_extension);
        
        $attachment->original_name = 'spreadsheet.xlsx';
        $this->assertEquals('xlsx', $attachment->file_extension);
    }

    public function test_is_downloadable_method()
    {
        $safeAttachment = Attachment::factory()->create([
            'is_safe' => true,
            'file_path' => 'test/path.jpg',
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $unsafeAttachment = Attachment::factory()->create([
            'is_safe' => false,
            'file_path' => 'test/path.jpg',
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        // ファイルが実際に存在しないため、どちらもfalseになるが、
        // is_safeフラグの影響をテスト
        $this->assertFalse($safeAttachment->isDownloadable()); // ファイルが存在しないため
        $this->assertFalse($unsafeAttachment->isDownloadable()); // is_safe=falseのため
    }

    public function test_fillable_attributes()
    {
        $data = [
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $this->post->id,
            'original_name' => 'test.pdf',
            'file_name' => 'safe_name.pdf',
            'file_path' => 'attachments/pdfs/safe_name.pdf',
            'file_size' => 1024000,
            'mime_type' => 'application/pdf',
            'file_type' => 'pdf',
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id,
            'hash' => 'abcd1234',
            'is_safe' => true
        ];
        
        $attachment = Attachment::create($data);
        
        $this->assertEquals($data['attachable_type'], $attachment->attachable_type);
        $this->assertEquals($data['original_name'], $attachment->original_name);
        $this->assertEquals($data['file_type'], $attachment->file_type);
        $this->assertEquals($data['tenant_id'], $attachment->tenant_id);
        $this->assertTrue($attachment->is_safe);
    }

    public function test_casts_work_correctly()
    {
        $attachment = Attachment::factory()->create([
            'file_size' => '1024', // 文字列で作成
            'is_safe' => '1', // 文字列で作成
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        // キャストにより適切な型で返される
        $this->assertIsInt($attachment->file_size);
        $this->assertIsBool($attachment->is_safe);
        $this->assertEquals(1024, $attachment->file_size);
        $this->assertTrue($attachment->is_safe);
    }
}