<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('tenant_id')->after('id');
            $table->string('icon')->nullable();
            $table->string('tel', 20)->nullable();

            // 外部キー制約を追加
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 外部キー制約を削除
            $table->dropForeign(['tenant_id']);

            // カラムを削除
            $table->dropColumn('tenant_id');
            $table->dropColumn('icon');
            $table->dropColumn('tel');
        });
    }
}
