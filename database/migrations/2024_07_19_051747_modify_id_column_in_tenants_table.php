<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModifyIdColumnInTenantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedBigInteger('new_id')->nullable()->first();
        });

        DB::statement('UPDATE tenants SET new_id = CAST(id AS UNSIGNED)');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('id');
            $table->renameColumn('new_id', 'id');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('new_id')->nullable()->first();
        });

        DB::statement('UPDATE tenants SET new_id = id');

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('id');
            $table->renameColumn('new_id', 'id');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->primary('id');
        });
    }
}
