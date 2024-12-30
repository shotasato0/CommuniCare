<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 初期のusersテーブル作成
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('forum_id')->nullable();  // 掲示板ID
            $table->string('username_id')->unique()->nullable();  // ユーザーID
            $table->unsignedBigInteger('unit_id')->nullable();  // ユニットID
            $table->string('tenant_id');  // テナントID
            $table->string('icon')->nullable();  // アイコン
            $table->string('tel')->nullable();  // 電話番号
            $table->string('name');
            $table->string('email')->nullable()->unique();  // メールアドレス
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('guest_session_id')->nullable()->comment('セッションIDを格納してゲストを特定する');  // ゲストセッションID
            $table->rememberToken();
            $table->timestamps();
        });

        // 外部キー制約を追加
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('forum_id')->references('id')->on('forums')->onDelete('cascade');
        });

        // パスワードリセットトークンテーブル
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // セッションテーブル
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 外部キーと関連カラムを削除
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['forum_id']);
            $table->dropColumn([
                'forum_id',
                'username_id',
                'unit_id',
                'tenant_id',
                'icon',
                'tel',
                'guest_session_id',
            ]);
        });

        // テーブル自体を削除
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
