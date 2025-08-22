<?php

namespace Tests\Unit;

use Tests\DatabaseTestCase;
use App\Models\Attachment;
use App\Models\Post;
use App\Models\Comment;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;

class AttachmentPolymorphicRelationshipTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private Post $post;
    private Comment $comment;

    protected function setUp(): void
    {
        parent::setUp();
        
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
        
        // コメント作成
        $this->comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'tenant_id' => $this->tenant->id
        ]);
    }

    public function test_post_has_many_attachments()
    {
        // 投稿に複数のAttachmentを作成
        $attachment1 = Attachment::factory()->image()->create([
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $this->post->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $attachment2 = Attachment::factory()->pdf()->create([
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $this->post->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $attachment3 = Attachment::factory()->document()->create([
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $this->post->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        // リレーションシップのテスト
        $attachments = $this->post->attachments;
        
        $this->assertInstanceOf(Collection::class, $attachments);
        $this->assertCount(3, $attachments);
        
        // 各AttachmentがPostに正しく関連付けられていることを確認
        foreach ($attachments as $attachment) {
            $this->assertEquals('App\Models\Post', $attachment->attachable_type);
            $this->assertEquals($this->post->id, $attachment->attachable_id);
            $this->assertEquals($this->tenant->id, $attachment->tenant_id);
        }
        
        // 特定のAttachmentが含まれていることを確認
        $this->assertTrue($attachments->contains($attachment1));
        $this->assertTrue($attachments->contains($attachment2));
        $this->assertTrue($attachments->contains($attachment3));
    }

    public function test_comment_has_many_attachments()
    {
        // コメントに複数のAttachmentを作成
        $attachment1 = Attachment::factory()->image()->create([
            'attachable_type' => 'App\Models\Comment',
            'attachable_id' => $this->comment->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $attachment2 = Attachment::factory()->text()->create([
            'attachable_type' => 'App\Models\Comment',
            'attachable_id' => $this->comment->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        // リレーションシップのテスト
        $attachments = $this->comment->attachments;
        
        $this->assertInstanceOf(Collection::class, $attachments);
        $this->assertCount(2, $attachments);
        
        // 各AttachmentがCommentに正しく関連付けられていることを確認
        foreach ($attachments as $attachment) {
            $this->assertEquals('App\Models\Comment', $attachment->attachable_type);
            $this->assertEquals($this->comment->id, $attachment->attachable_id);
            $this->assertEquals($this->tenant->id, $attachment->tenant_id);
        }
    }

    public function test_attachment_belongs_to_correct_polymorphic_model()
    {
        // Post用のAttachment
        $postAttachment = Attachment::factory()->create([
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $this->post->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        // Comment用のAttachment
        $commentAttachment = Attachment::factory()->create([
            'attachable_type' => 'App\Models\Comment',
            'attachable_id' => $this->comment->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        // PostAttachmentの関連確認
        $this->assertInstanceOf(Post::class, $postAttachment->attachable);
        $this->assertEquals($this->post->id, $postAttachment->attachable->id);
        
        // CommentAttachmentの関連確認
        $this->assertInstanceOf(Comment::class, $commentAttachment->attachable);
        $this->assertEquals($this->comment->id, $commentAttachment->attachable->id);
    }

    public function test_filtering_attachments_by_file_type()
    {
        // 投稿に異なるタイプのAttachmentを作成
        $imageAttachment = Attachment::factory()->image()->create([
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $this->post->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $pdfAttachment = Attachment::factory()->pdf()->create([
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $this->post->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $documentAttachment = Attachment::factory()->document()->create([
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $this->post->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        // ファイルタイプごとのフィルタリング
        $imageAttachments = $this->post->attachments()->where('file_type', 'image')->get();
        $pdfAttachments = $this->post->attachments()->where('file_type', 'pdf')->get();
        $documentAttachments = $this->post->attachments()->where('file_type', 'document')->get();
        
        $this->assertCount(1, $imageAttachments);
        $this->assertCount(1, $pdfAttachments);
        $this->assertCount(1, $documentAttachments);
        
        $this->assertEquals('image', $imageAttachments->first()->file_type);
        $this->assertEquals('pdf', $pdfAttachments->first()->file_type);
        $this->assertEquals('document', $documentAttachments->first()->file_type);
    }

    public function test_eager_loading_attachments()
    {
        // 複数の投稿とそれぞれのAttachmentを作成
        $post2 = Post::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant->id
        ]);
        
        // 各投稿にAttachmentを作成
        Attachment::factory()->count(2)->create([
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $this->post->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        Attachment::factory()->count(3)->create([
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $post2->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        // Eager Loadingのテスト
        $posts = Post::with('attachments')->get();
        
        $this->assertCount(2, $posts);
        
        foreach ($posts as $post) {
            $this->assertInstanceOf(Collection::class, $post->attachments);
            
            if ($post->id === $this->post->id) {
                $this->assertCount(2, $post->attachments);
            } elseif ($post->id === $post2->id) {
                $this->assertCount(3, $post->attachments);
            }
        }
    }

    public function test_attachment_cascade_deletion()
    {
        // 投稿にAttachmentを作成
        $attachment = Attachment::factory()->create([
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $this->post->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        $attachmentId = $attachment->id;
        
        // Attachmentが存在することを確認
        $this->assertDatabaseHas('attachments', ['id' => $attachmentId]);
        
        // Attachmentを削除
        $attachment->delete();
        
        // Attachmentが削除されたことを確認
        $this->assertDatabaseMissing('attachments', ['id' => $attachmentId]);
        
        // 投稿は残っていることを確認
        $this->assertDatabaseHas('posts', ['id' => $this->post->id]);
    }

    public function test_multiple_attachments_same_file_different_models()
    {
        // 同じファイルハッシュで異なるモデルに関連付けられたAttachmentを作成
        $hash = 'same_file_hash_123456789';
        
        $postAttachment = Attachment::factory()->create([
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $this->post->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id,
            'hash' => $hash
        ]);
        
        $commentAttachment = Attachment::factory()->create([
            'attachable_type' => 'App\Models\Comment',
            'attachable_id' => $this->comment->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id,
            'hash' => $hash
        ]);
        
        // 同じファイルハッシュだが、異なるモデルに関連付けられていることを確認
        $this->assertEquals($hash, $postAttachment->hash);
        $this->assertEquals($hash, $commentAttachment->hash);
        
        $this->assertInstanceOf(Post::class, $postAttachment->attachable);
        $this->assertInstanceOf(Comment::class, $commentAttachment->attachable);
        
        $this->assertNotEquals($postAttachment->attachable_type, $commentAttachment->attachable_type);
    }

    public function test_attachment_count_per_model()
    {
        // 投稿に3つのAttachment
        Attachment::factory()->count(3)->create([
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => $this->post->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        // コメントに2つのAttachment
        Attachment::factory()->count(2)->create([
            'attachable_type' => 'App\Models\Comment',
            'attachable_id' => $this->comment->id,
            'tenant_id' => $this->tenant->id,
            'uploaded_by' => $this->user->id
        ]);
        
        // カウントの確認
        $this->assertEquals(3, $this->post->attachments()->count());
        $this->assertEquals(2, $this->comment->attachments()->count());
        
        // 全体のAttachment数
        $this->assertEquals(5, Attachment::count());
    }
}