<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueConstraintToUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // `username_id` と `tenant_id` に複合ユニーク制約を追加
        Schema::table('users', function (Blueprint $table) {
            $table->unique(['username_id', 'tenant_id']);  // 複合ユニーク制約
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // `username_id` と `tenant_id` の複合ユニーク制約を削除
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username_id', 'tenant_id']);  // 複合ユニーク制約を削除
        });
    }
}
