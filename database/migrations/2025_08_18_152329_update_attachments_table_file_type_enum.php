<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Services\AttachmentService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL以外（SQLite等）ではスキップ
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }
        // AttachmentServiceの定数と一致するようにENUMを更新
        $supportedTypes = AttachmentService::getSupportedFileTypes();
        $enumValues = "'" . implode("','", $supportedTypes) . "'";

        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('file_type');
        });

        DB::statement("ALTER TABLE attachments ADD file_type ENUM($enumValues) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }
        Schema::table('attachments', function (Blueprint $table) {
            $table->dropColumn('file_type');
        });

        Schema::table('attachments', function (Blueprint $table) {
            $table->enum('file_type', ['image', 'pdf', 'document', 'excel', 'text']);
        });
    }
};
