<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // MySQL専用のinformation_schema参照が含まれるため、SQLite等ではスキップ
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }
        $tables = ['users', 'units', 'posts', 'forums'];

        foreach ($tables as $tableName) {
            // 既存の外部キー制約の名前を取得
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND COLUMN_NAME = 'tenant_id' 
                AND REFERENCED_TABLE_NAME = 'tenants'", 
                [env('DB_DATABASE'), $tableName]
            );

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $constraints) {
                // 既存の制約がある場合は、その名前で削除
                if (!empty($constraints)) {
                    $table->dropForeign($constraints[0]->CONSTRAINT_NAME);
                }
                
                // カスケード削除の制約を追加
                $table->foreign('tenant_id')
                      ->references('id')
                      ->on('tenants')
                      ->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }
        $tables = ['users', 'units', 'posts', 'forums'];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                // down処理では単純に制約を削除
                try {
                    $table->dropForeign(['tenant_id']);
                } catch (\Exception $e) {
                    // 制約が存在しない場合は無視
                }
            });
        }
    }
};
