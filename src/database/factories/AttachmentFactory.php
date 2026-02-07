<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileTypes = ['image', 'pdf', 'document', 'excel', 'text'];
        $fileType = $this->faker->randomElement($fileTypes);
        
        $originalName = $this->getOriginalNameByType($fileType);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $fileName = $this->generateFileName($originalName, $extension);
        
        return [
            'attachable_type' => 'App\Models\Post',
            'attachable_id' => 1, // デフォルト値、テストで上書きされる
            'original_name' => $originalName,
            'file_name' => $fileName,
            'file_path' => $this->getFilePathByType($fileType) . '/' . $fileName,
            'file_size' => $this->faker->numberBetween(1024, 5 * 1024 * 1024), // 1KB〜5MB
            'mime_type' => $this->getMimeTypeByType($fileType),
            'file_type' => $fileType,
            'tenant_id' => '1', // デフォルト値、テストで上書きされる
            'uploaded_by' => 1, // デフォルト値、テストで上書きされる
            'hash' => hash('sha256', $this->faker->text()),
            'is_safe' => true,
        ];
    }

    /**
     * 画像ファイル用のstate
     */
    public function image(): static
    {
        return $this->state(function (array $attributes) {
            $originalName = $this->faker->randomElement([
                'photo.jpg',
                'image.png',
                'picture.gif',
                'graphic.webp'
            ]);
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $fileName = $this->generateFileName($originalName, $extension);
            
            return [
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => 'attachments/images/' . $fileName,
                'file_size' => $this->faker->numberBetween(50000, 2 * 1024 * 1024), // 50KB〜2MB
                'mime_type' => $this->getMimeTypeByExtension($extension),
                'file_type' => 'image',
            ];
        });
    }

    /**
     * PDFファイル用のstate
     */
    public function pdf(): static
    {
        return $this->state(function (array $attributes) {
            $originalName = $this->faker->randomElement([
                'document.pdf',
                'report.pdf',
                'manual.pdf',
                'specification.pdf'
            ]);
            $fileName = $this->generateFileName($originalName, 'pdf');
            
            return [
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => 'attachments/pdfs/' . $fileName,
                'file_size' => $this->faker->numberBetween(100000, 10 * 1024 * 1024), // 100KB〜10MB
                'mime_type' => 'application/pdf',
                'file_type' => 'pdf',
            ];
        });
    }

    /**
     * Documentファイル用のstate
     */
    public function document(): static
    {
        return $this->state(function (array $attributes) {
            $originalName = $this->faker->randomElement([
                'document.docx',
                'report.doc',
                'memo.docx',
                'letter.doc'
            ]);
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $fileName = $this->generateFileName($originalName, $extension);
            
            return [
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => 'attachments/documents/' . $fileName,
                'file_size' => $this->faker->numberBetween(20000, 5 * 1024 * 1024), // 20KB〜5MB
                'mime_type' => $this->getMimeTypeByExtension($extension),
                'file_type' => 'document',
            ];
        });
    }

    /**
     * Excelファイル用のstate
     */
    public function excel(): static
    {
        return $this->state(function (array $attributes) {
            $originalName = $this->faker->randomElement([
                'spreadsheet.xlsx',
                'data.xls',
                'report.xlsx',
                'calculation.xls'
            ]);
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $fileName = $this->generateFileName($originalName, $extension);
            
            return [
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => 'attachments/excel/' . $fileName,
                'file_size' => $this->faker->numberBetween(15000, 3 * 1024 * 1024), // 15KB〜3MB
                'mime_type' => $this->getMimeTypeByExtension($extension),
                'file_type' => 'excel',
            ];
        });
    }

    /**
     * テキストファイル用のstate
     */
    public function text(): static
    {
        return $this->state(function (array $attributes) {
            $originalName = $this->faker->randomElement([
                'readme.txt',
                'notes.txt',
                'data.csv',
                'config.txt'
            ]);
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $fileName = $this->generateFileName($originalName, $extension);
            
            return [
                'original_name' => $originalName,
                'file_name' => $fileName,
                'file_path' => 'attachments/text/' . $fileName,
                'file_size' => $this->faker->numberBetween(1000, 1024 * 1024), // 1KB〜1MB
                'mime_type' => $this->getMimeTypeByExtension($extension),
                'file_type' => 'text',
            ];
        });
    }

    /**
     * セキュリティ上安全でないファイル用のstate
     */
    public function unsafe(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'is_safe' => false,
            ];
        });
    }

    /**
     * 投稿に関連付けられたAttachment用のstate
     */
    public function forPost($post = null): static
    {
        return $this->state(function (array $attributes) use ($post) {
            $postId = $post ? $post->id : Post::factory()->create()->id;
            
            return [
                'attachable_type' => 'App\Models\Post',
                'attachable_id' => $postId,
            ];
        });
    }

    /**
     * コメントに関連付けられたAttachment用のstate
     */
    public function forComment($comment = null): static
    {
        return $this->state(function (array $attributes) use ($comment) {
            $commentId = $comment ? $comment->id : Comment::factory()->create()->id;
            
            return [
                'attachable_type' => 'App\Models\Comment',
                'attachable_id' => $commentId,
            ];
        });
    }

    /**
     * ファイルタイプに応じた元ファイル名を生成
     */
    private function getOriginalNameByType(string $fileType): string
    {
        return match($fileType) {
            'image' => $this->faker->randomElement(['photo.jpg', 'image.png', 'picture.gif']),
            'pdf' => $this->faker->randomElement(['document.pdf', 'report.pdf', 'manual.pdf']),
            'document' => $this->faker->randomElement(['document.docx', 'report.doc', 'memo.docx']),
            'excel' => $this->faker->randomElement(['spreadsheet.xlsx', 'data.xls', 'report.xlsx']),
            'text' => $this->faker->randomElement(['readme.txt', 'notes.txt', 'data.csv']),
            default => 'file.txt'
        };
    }

    /**
     * ファイルタイプに応じたパスを生成
     */
    private function getFilePathByType(string $fileType): string
    {
        return match($fileType) {
            'image' => 'attachments/images',
            'pdf' => 'attachments/pdfs',
            'document' => 'attachments/documents',
            'excel' => 'attachments/excel',
            'text' => 'attachments/text',
            default => 'attachments/misc'
        };
    }

    /**
     * ファイルタイプに応じたMIMEタイプを取得
     */
    private function getMimeTypeByType(string $fileType): string
    {
        return match($fileType) {
            'image' => $this->faker->randomElement(['image/jpeg', 'image/png', 'image/gif']),
            'pdf' => 'application/pdf',
            'document' => $this->faker->randomElement([
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/msword'
            ]),
            'excel' => $this->faker->randomElement([
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel'
            ]),
            'text' => $this->faker->randomElement(['text/plain', 'text/csv']),
            default => 'application/octet-stream'
        };
    }

    /**
     * 拡張子に応じたMIMEタイプを取得
     */
    private function getMimeTypeByExtension(string $extension): string
    {
        return match(strtolower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            default => 'application/octet-stream'
        };
    }

    /**
     * 安全なファイル名を生成
     */
    private function generateFileName(string $originalName, string $extension): string
    {
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $name) ?: 'file';
        return $safeName . '_' . time() . '_' . $this->faker->randomNumber(4) . '.' . $extension;
    }
}