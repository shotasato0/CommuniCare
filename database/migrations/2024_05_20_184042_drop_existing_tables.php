// database/migrations/2024_05_20_184042_drop_existing_tables.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        
        Schema::dropIfExists('tenant_posts');
        Schema::dropIfExists('tenant_comments');
        Schema::dropIfExists('tenant_residents');
        Schema::dropIfExists('tenant_users');
        
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // 元に戻す処理が必要な場合はここに記載します
    }
};
