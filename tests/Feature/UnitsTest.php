<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitsTest extends TestCase
{
    use RefreshDatabase;

    public function testDatabase()
    {
        // データベースに挿入されたデータを確認
        $this->assertDatabaseHas('units', ['name' => '事務所']);
        $this->assertDatabaseHas('units', ['name' => '看護師']);
        $this->assertDatabaseHas('units', ['name' => 'デイサービス']);
        $this->assertDatabaseHas('units', ['name' => 'ショートステイ']);
        $this->assertDatabaseHas('units', ['name' => 'さくら']);
        $this->assertDatabaseHas('units', ['name' => 'つばき']);
        $this->assertDatabaseHas('units', ['name' => 'さつき']);
        $this->assertDatabaseHas('units', ['name' => 'ぼたん']);
    }
}