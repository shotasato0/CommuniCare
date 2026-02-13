<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\AttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttachmentServiceUnitTest extends TestCase
{
    private AttachmentService $attachmentService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->attachmentService = new AttachmentService();
        Storage::fake('public');
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
        $this->assertTrue($method->invoke($this->attachmentService, 'jpeg'));
        $this->assertTrue($method->invoke($this->attachmentService, 'png'));
        $this->assertTrue($method->invoke($this->attachmentService, 'gif'));
        $this->assertTrue($method->invoke($this->attachmentService, 'pdf'));
        $this->assertTrue($method->invoke($this->attachmentService, 'docx'));
        $this->assertTrue($method->invoke($this->attachmentService, 'doc'));
        $this->assertTrue($method->invoke($this->attachmentService, 'xlsx'));
        $this->assertTrue($method->invoke($this->attachmentService, 'xls'));
        $this->assertTrue($method->invoke($this->attachmentService, 'txt'));
        $this->assertTrue($method->invoke($this->attachmentService, 'csv'));
        
        // サポートされていない拡張子
        $this->assertFalse($method->invoke($this->attachmentService, 'exe'));
        $this->assertFalse($method->invoke($this->attachmentService, 'bat'));
        $this->assertFalse($method->invoke($this->attachmentService, 'sh'));
        $this->assertFalse($method->invoke($this->attachmentService, 'php'));
        $this->assertFalse($method->invoke($this->attachmentService, 'js'));
    }

    public function test_file_type_detection()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('getFileType');
        $method->setAccessible(true);
        
        $this->assertEquals('image', $method->invoke($this->attachmentService, 'jpg'));
        $this->assertEquals('image', $method->invoke($this->attachmentService, 'jpeg'));
        $this->assertEquals('image', $method->invoke($this->attachmentService, 'png'));
        $this->assertEquals('image', $method->invoke($this->attachmentService, 'gif'));
        $this->assertEquals('pdf', $method->invoke($this->attachmentService, 'pdf'));
        $this->assertEquals('document', $method->invoke($this->attachmentService, 'docx'));
        $this->assertEquals('document', $method->invoke($this->attachmentService, 'doc'));
        $this->assertEquals('excel', $method->invoke($this->attachmentService, 'xlsx'));
        $this->assertEquals('excel', $method->invoke($this->attachmentService, 'xls'));
        $this->assertEquals('text', $method->invoke($this->attachmentService, 'txt'));
        $this->assertEquals('text', $method->invoke($this->attachmentService, 'csv'));
        $this->assertEquals('document', $method->invoke($this->attachmentService, 'unknown'));
    }

    public function test_mime_type_detection()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('getMimeType');
        $method->setAccessible(true);
        
        $this->assertEquals('image/jpeg', $method->invoke($this->attachmentService, 'jpg'));
        $this->assertEquals('image/jpeg', $method->invoke($this->attachmentService, 'jpeg'));
        $this->assertEquals('image/png', $method->invoke($this->attachmentService, 'png'));
        $this->assertEquals('image/gif', $method->invoke($this->attachmentService, 'gif'));
        $this->assertEquals('application/pdf', $method->invoke($this->attachmentService, 'pdf'));
        $this->assertEquals('application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
            $method->invoke($this->attachmentService, 'docx'));
        $this->assertEquals('application/msword', $method->invoke($this->attachmentService, 'doc'));
        $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 
            $method->invoke($this->attachmentService, 'xlsx'));
        $this->assertEquals('application/vnd.ms-excel', $method->invoke($this->attachmentService, 'xls'));
        $this->assertEquals('text/plain', $method->invoke($this->attachmentService, 'txt'));
        $this->assertEquals('text/csv', $method->invoke($this->attachmentService, 'csv'));
        $this->assertEquals('application/octet-stream', $method->invoke($this->attachmentService, 'unknown'));
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
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]+_\d+_[a-zA-Z0-9]{8}\.jpg$/', $result);
        
        // 空のファイル名の場合
        $result = $method->invoke($this->attachmentService, '.jpg', 'jpg');
        $this->assertStringContainsString('file_', $result);
        $this->assertStringContainsString('.jpg', $result);
        
        // 特殊文字を含むファイル名
        $result = $method->invoke($this->attachmentService, 'test@#$%file.pdf', 'pdf');
        $this->assertStringContainsString('test-at-file_', $result); // Str::slug()の動作に合わせて修正
        $this->assertStringContainsString('.pdf', $result);
    }

    public function test_file_validation_with_large_file()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('validateFile');
        $method->setAccessible(true);
        
        // 大きなファイルをモック
        $largeFile = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $largeFile->method('getSize')->willReturn(11 * 1024 * 1024); // 11MB
        $largeFile->method('getClientOriginalExtension')->willReturn('jpg');
        
        $result = $method->invoke($this->attachmentService, $largeFile);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('ファイルサイズが制限を超えています', $result['error']);
    }

    public function test_file_validation_with_acceptable_file_size()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('validateFile');
        $method->setAccessible(true);
        
        // 適切なサイズのファイルをモック
        $acceptableFile = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $acceptableFile->method('getSize')->willReturn(5 * 1024 * 1024); // 5MB
        $acceptableFile->method('getClientOriginalExtension')->willReturn('jpg');
        $acceptableFile->method('getMimeType')->willReturn('image/jpeg');
        
        $result = $method->invoke($this->attachmentService, $acceptableFile);
        
        $this->assertTrue($result['valid']);
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

    public function test_file_validation_with_mime_type_mismatch()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('validateFile');
        $method->setAccessible(true);
        
        // MIMEタイプが一致しないファイル（偽装攻撃）
        $mismatchFile = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $mismatchFile->method('getSize')->willReturn(1024);
        $mismatchFile->method('getClientOriginalExtension')->willReturn('jpg');
        $mismatchFile->method('getMimeType')->willReturn('application/pdf'); // JPGなのにPDF MIME
        
        $result = $method->invoke($this->attachmentService, $mismatchFile);
        
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('ファイル形式が一致しません', $result['error']);
    }

    public function test_security_scan_with_safe_file()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('performSecurityScan');
        $method->setAccessible(true);
        
        // 安全なファイルの一時ファイルを作成
        $tempFile = tempnam(sys_get_temp_dir(), 'test_safe');
        file_put_contents($tempFile, 'safe file content');
        
        $safeFile = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $safeFile->method('getRealPath')->willReturn($tempFile);
        
        $result = $method->invoke($this->attachmentService, $safeFile);
        
        $this->assertTrue($result);
        
        unlink($tempFile);
    }

    public function test_security_scan_with_pe_executable()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('performSecurityScan');
        $method->setAccessible(true);
        
        // PE実行ファイルのヘッダーを持つファイルを作成
        $tempFile = tempnam(sys_get_temp_dir(), 'test_pe');
        file_put_contents($tempFile, "\x4D\x5A" . 'fake PE executable content');
        
        $peFile = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $peFile->method('getRealPath')->willReturn($tempFile);
        
        $result = $method->invoke($this->attachmentService, $peFile);
        
        $this->assertFalse($result);
        
        unlink($tempFile);
    }

    public function test_security_scan_with_elf_executable()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $method = $reflectionClass->getMethod('performSecurityScan');
        $method->setAccessible(true);
        
        // ELF実行ファイルのヘッダーを持つファイルを作成
        $tempFile = tempnam(sys_get_temp_dir(), 'test_elf');
        file_put_contents($tempFile, "\x7F\x45\x4C\x46" . 'fake ELF executable content');
        
        $elfFile = $this->getMockBuilder(UploadedFile::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $elfFile->method('getRealPath')->willReturn($tempFile);
        
        $result = $method->invoke($this->attachmentService, $elfFile);
        
        $this->assertFalse($result);
        
        unlink($tempFile);
    }

    public function test_all_supported_file_type_constants()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $supportedTypes = $reflectionClass->getConstant('SUPPORTED_TYPES');
        
        // 定義された全ファイルタイプが存在することを確認
        $this->assertArrayHasKey('image', $supportedTypes);
        $this->assertArrayHasKey('pdf', $supportedTypes);
        $this->assertArrayHasKey('document', $supportedTypes);
        $this->assertArrayHasKey('excel', $supportedTypes);
        $this->assertArrayHasKey('text', $supportedTypes);
        
        // 各タイプに拡張子が定義されていることを確認
        $this->assertIsArray($supportedTypes['image']);
        $this->assertContains('jpg', $supportedTypes['image']);
        $this->assertContains('png', $supportedTypes['image']);
        
        $this->assertIsArray($supportedTypes['pdf']);
        $this->assertContains('pdf', $supportedTypes['pdf']);
        
        $this->assertIsArray($supportedTypes['document']);
        $this->assertContains('doc', $supportedTypes['document']);
        $this->assertContains('docx', $supportedTypes['document']);
        
        $this->assertIsArray($supportedTypes['excel']);
        $this->assertContains('xls', $supportedTypes['excel']);
        $this->assertContains('xlsx', $supportedTypes['excel']);
        
        $this->assertIsArray($supportedTypes['text']);
        $this->assertContains('txt', $supportedTypes['text']);
        $this->assertContains('csv', $supportedTypes['text']);
    }

    public function test_file_size_limit_constant()
    {
        $reflectionClass = new \ReflectionClass($this->attachmentService);
        $maxFileSize = $reflectionClass->getConstant('MAX_FILE_SIZE');
        
        $this->assertEquals(10 * 1024 * 1024, $maxFileSize); // 10MB
    }
}