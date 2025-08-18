<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Attachment;

class AttachmentModelUnitTest extends TestCase
{
    public function test_attachment_model_has_correct_fillable_attributes()
    {
        $attachment = new Attachment();
        
        $expectedFillable = [
            'attachable_type',
            'attachable_id', 
            'original_name',
            'file_name',
            'file_path',
            'file_size',
            'mime_type',
            'file_type',
            'tenant_id',
            'uploaded_by',
            'hash',
            'is_safe'
        ];
        
        $this->assertEquals($expectedFillable, $attachment->getFillable());
    }

    public function test_attachment_model_has_correct_casts()
    {
        $attachment = new Attachment();
        
        $expectedCasts = [
            'file_size' => 'integer',
            'is_safe' => 'boolean',
        ];
        
        $this->assertEquals($expectedCasts, $attachment->getCasts());
    }

    public function test_is_image_method_logic()
    {
        $attachment = new Attachment();
        
        // 画像タイプの場合
        $attachment->file_type = 'image';
        $this->assertTrue($attachment->isImage());
        
        // 非画像タイプの場合
        $attachment->file_type = 'pdf';
        $this->assertFalse($attachment->isImage());
        
        $attachment->file_type = 'document';
        $this->assertFalse($attachment->isImage());
    }

    public function test_is_document_method_logic()
    {
        $attachment = new Attachment();
        
        // ドキュメントタイプの場合
        $attachment->file_type = 'pdf';
        $this->assertTrue($attachment->isDocument());
        
        $attachment->file_type = 'document';
        $this->assertTrue($attachment->isDocument());
        
        $attachment->file_type = 'excel';
        $this->assertTrue($attachment->isDocument());
        
        $attachment->file_type = 'text';
        $this->assertTrue($attachment->isDocument());
        
        // 非ドキュメントタイプの場合
        $attachment->file_type = 'image';
        $this->assertFalse($attachment->isDocument());
    }

    public function test_formatted_file_size_attribute()
    {
        $attachment = new Attachment();
        
        // バイト単位
        $attachment->file_size = 500;
        $this->assertEquals('500 bytes', $attachment->getFormattedFileSizeAttribute());
        
        // KB単位
        $attachment->file_size = 1024;
        $this->assertEquals('1.00 KB', $attachment->getFormattedFileSizeAttribute());
        
        $attachment->file_size = 2048;
        $this->assertEquals('2.00 KB', $attachment->getFormattedFileSizeAttribute());
        
        // MB単位
        $attachment->file_size = 1048576; // 1MB
        $this->assertEquals('1.00 MB', $attachment->getFormattedFileSizeAttribute());
        
        $attachment->file_size = 5242880; // 5MB
        $this->assertEquals('5.00 MB', $attachment->getFormattedFileSizeAttribute());
        
        // GB単位
        $attachment->file_size = 1073741824; // 1GB
        $this->assertEquals('1.00 GB', $attachment->getFormattedFileSizeAttribute());
        
        $attachment->file_size = 2147483648; // 2GB
        $this->assertEquals('2.00 GB', $attachment->getFormattedFileSizeAttribute());
    }

    public function test_file_extension_attribute()
    {
        $attachment = new Attachment();
        
        // 一般的な拡張子
        $attachment->original_name = 'test_document.pdf';
        $this->assertEquals('pdf', $attachment->getFileExtensionAttribute());
        
        $attachment->original_name = 'image.jpg';
        $this->assertEquals('jpg', $attachment->getFileExtensionAttribute());
        
        $attachment->original_name = 'spreadsheet.xlsx';
        $this->assertEquals('xlsx', $attachment->getFileExtensionAttribute());
        
        $attachment->original_name = 'data.csv';
        $this->assertEquals('csv', $attachment->getFileExtensionAttribute());
        
        // 拡張子なしの場合
        $attachment->original_name = 'filename_without_extension';
        $this->assertEquals('', $attachment->getFileExtensionAttribute());
        
        // 複数ドットを含む場合
        $attachment->original_name = 'file.backup.tar.gz';
        $this->assertEquals('gz', $attachment->getFileExtensionAttribute());
    }

    public function test_is_downloadable_method_with_safe_file()
    {
        $attachment = new Attachment();
        $attachment->is_safe = true;
        $attachment->file_path = 'nonexistent/path.jpg'; // 実際には存在しないパス
        
        // ファイルが存在しないため、is_safe=trueでもfalseになる
        $this->assertFalse($attachment->isDownloadable());
    }

    public function test_is_downloadable_method_with_unsafe_file()
    {
        $attachment = new Attachment();
        $attachment->is_safe = false;
        $attachment->file_path = 'any/path.jpg';
        
        // is_safe=falseの場合は常にfalse
        $this->assertFalse($attachment->isDownloadable());
    }

    public function test_attachment_model_relationships_exist()
    {
        $attachment = new Attachment();
        
        // 必要なリレーションシップメソッドが存在することを確認
        $this->assertTrue(method_exists($attachment, 'attachable'));
        $this->assertTrue(method_exists($attachment, 'uploadedBy'));
    }

    public function test_attachment_uses_correct_traits()
    {
        $attachment = new Attachment();
        
        // 使用しているトレイトを確認
        $traits = class_uses($attachment);
        
        $this->assertContains('Illuminate\Database\Eloquent\Factories\HasFactory', $traits);
        $this->assertContains('Stancl\Tenancy\Database\Concerns\BelongsToTenant', $traits);
    }

    public function test_file_type_enum_values()
    {
        // ファイルタイプとして許可される値をテスト
        $validFileTypes = ['image', 'pdf', 'document', 'excel', 'text'];
        
        foreach ($validFileTypes as $fileType) {
            $attachment = new Attachment();
            $attachment->file_type = $fileType;
            
            // 例外が発生しないことを確認
            $this->assertEquals($fileType, $attachment->file_type);
        }
    }

    public function test_attachment_model_table_name()
    {
        $attachment = new Attachment();
        
        // テーブル名が正しく設定されていることを確認
        $this->assertEquals('attachments', $attachment->getTable());
    }

    public function test_attachment_model_primary_key()
    {
        $attachment = new Attachment();
        
        // 主キーが正しく設定されていることを確認
        $this->assertEquals('id', $attachment->getKeyName());
        $this->assertTrue($attachment->getIncrementing());
        $this->assertEquals('int', $attachment->getKeyType());
    }

    public function test_attachment_model_timestamps()
    {
        $attachment = new Attachment();
        
        // タイムスタンプが有効になっていることを確認
        $this->assertTrue($attachment->usesTimestamps());
    }
}