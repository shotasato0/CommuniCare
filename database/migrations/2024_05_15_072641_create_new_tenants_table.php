<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewTenantsTable extends Migration
{
    public function up()
    {
        Schema::create('new_tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('new_tenants');
    }
}
