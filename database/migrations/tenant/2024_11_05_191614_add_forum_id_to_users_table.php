<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->unsignedBigInteger('forum_id')->nullable()->after('id'); // nullable でフォーラム未所属を許容
        $table->foreign('forum_id')->references('id')->on('forums')->onDelete('cascade'); // 外部キー制約
    });
}

public function down()
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropForeign(['forum_id']);
        $table->dropColumn('forum_id');
    });
}

};
