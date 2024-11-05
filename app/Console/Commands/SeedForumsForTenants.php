<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Database\Seeders\ForumsTableSeeder;
use Stancl\Tenancy\Facades\Tenancy;

class SeedForumsForTenants extends Command
{
    protected $signature = 'tenants:seed-forums';
    protected $description = '全テナントのデータベースにフォーラムデータをシードします';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // 全テナントをループして処理
        Tenancy::query()->each(function ($tenant) {
            Tenancy::initialize($tenant); // テナントコンテキストを開始
            
            // テナントのデータベースでシーダーを実行
            $this->call(ForumsTableSeeder::class);
            
            Tenancy::end(); // テナントコンテキストを終了
        });
        
        return Command::SUCCESS;
    }
}
