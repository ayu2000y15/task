<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProjectCategory;

class ProjectCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'default', 'display_name' => 'デフォルト', 'description' => 'デフォルトカテゴリ'],
            ['name' => 'costume', 'display_name' => '', 'description' => '案件用カテゴリ'],
            ['name' => 'prop', 'display_name' => '小道具', 'description' => '小道具案件用カテゴリ'],
        ];
        foreach ($categories as $cat) {
            ProjectCategory::firstOrCreate(['name' => $cat['name']], $cat);
        }
    }
}
