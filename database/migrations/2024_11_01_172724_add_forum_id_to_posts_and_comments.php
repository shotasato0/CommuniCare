<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('forum_id')->constrained()->onDelete('cascade')->after('id');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->foreignId('forum_id')->constrained()->onDelete('cascade')->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['forum_id']);
            $table->dropColumn('forum_id');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropForeign(['forum_id']);
            $table->dropColumn('forum_id');
        });
    }
};
