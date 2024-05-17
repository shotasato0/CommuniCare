<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tenant_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained('tenant_posts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('tenant_users')->onDelete('cascade');
            $table->string('message', 200);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tenant_comments');
    }
};


