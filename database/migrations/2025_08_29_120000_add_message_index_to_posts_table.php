<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQLのときだけ部分インデックスを付与（SQLiteはスキップ）
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // 既に存在する場合はスキップ（idempotent）
            $exists = DB::table('information_schema.statistics')
                ->whereRaw('table_schema = database()')
                ->where('table_name', 'posts')
                ->where('index_name', 'message_index')
                ->exists();

            if (!$exists) {
                DB::statement('ALTER TABLE posts ADD INDEX message_index (message(255))');
            }
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // ある場合だけDROP
            $exists = DB::table('information_schema.statistics')
                ->whereRaw('table_schema = database()')
                ->where('table_name', 'posts')
                ->where('index_name', 'message_index')
                ->exists();

            if ($exists) {
                DB::statement('ALTER TABLE posts DROP INDEX message_index');
            }
        }
    }
};

