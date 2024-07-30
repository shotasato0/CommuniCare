<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username_id')->unique()->nullable()->after('id');
            $table->unsignedBigInteger('unit_id')->nullable()->after('username_id');
            $table->string('tenant_id')->after('unit_id');
            $table->string('icon')->nullable()->after('tenant_id');
            $table->string('tel')->nullable()->after('icon');
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
            $table->dropColumn('username_id');
            $table->dropColumn('unit_id');
            $table->dropColumn('tenant_id');
            $table->dropColumn('icon');
            $table->dropColumn('tel');
        });
    }
};
