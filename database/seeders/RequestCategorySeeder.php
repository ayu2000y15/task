<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RequestCategory;

class RequestCategorySeeder extends Seeder
{
    public function run(): void
    {
        RequestCategory::create(['name' => '打合せ']);
        RequestCategory::create(['name' => '面接']);
        RequestCategory::create(['name' => '面接準備']);
        RequestCategory::create(['name' => '資料作成']);
        RequestCategory::create(['name' => 'その他']);
    }
}
