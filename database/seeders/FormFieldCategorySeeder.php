<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FormFieldCategory;

class FormFieldCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'project',
                'display_name' => '案件依頼',
                'description' => '案件依頼に関連するカスタム項目',
                'order' => 1,
                'is_enabled' => true,
            ],
            [
                'name' => 'proposal',
                'display_name' => '企画書',
                'description' => '企画書投稿に関連するカスタム項目',
                'order' => 4,
                'is_enabled' => true,
            ],
        ];

        foreach ($categories as $category) {
            FormFieldCategory::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }
    }
}
