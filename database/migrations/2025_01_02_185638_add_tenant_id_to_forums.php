<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('forums', function (Blueprint $table) {
            // tenant_idの追加
            $table->string('tenant_id')->after('id')->index();

            // tenant_idとslugの組み合わせでユニーク制約を追加
            $table->unique(['tenant_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('forums', function (Blueprint $table) {
            // 制約とカラムの削除
            $table->dropUnique(['tenant_id', 'slug']);
            $table->dropColumn('tenant_id');
        });
    }
};
