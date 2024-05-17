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
        Schema::create('tenant_residents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('tenant_users')->onDelete('cascade');
            $table->string('name');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('birth_date');
            $table->string('postal_code')->nullable();
            $table->string('prefecture')->nullable();
            $table->string('city');
            $table->string('address1');
            $table->string('address2')->nullable();
            $table->enum('nursing_care_level', ['level1', 'level2', 'level3', 'level4', 'level5']);
            $table->string('memo');
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
        Schema::dropIfExists('tenant_residents');
    }
};


