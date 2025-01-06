<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forums', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained()->onDelete('cascade');
            $table->text('description')->nullable();
            $table->enum('visibility', ['public', 'private', 'unit_only'])->default('unit_only');
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forums');
    }
};
