<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RemoveNursingHomeIdFromUsersTable extends Migration
{
    public function up()
    {
        // 外部キー制約を削除
        $foreignKeyExists = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'users' 
            AND COLUMN_NAME = 'nursing_home_id' 
            AND CONSTRAINT_SCHEMA = DATABASE()
        ");

        if (!empty($foreignKeyExists)) {
            $foreignKeyName = $foreignKeyExists[0]->CONSTRAINT_NAME;
            Schema::table('users', function (Blueprint $table) use ($foreignKeyName) {
                $table->dropForeign([$foreignKeyName]);
            });
        }

        // カラムを削除
        if (Schema::hasColumn('users', 'nursing_home_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('nursing_home_id');
            });
        }
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'nursing_home_id')) {
                $table->unsignedBigInteger('nursing_home_id')->nullable();

                if (Schema::hasTable('nursing_homes')) {
                    $table->foreign('nursing_home_id')->references('id')->on('nursing_homes')->onDelete('cascade');
                }
            }
        });
    }
}





