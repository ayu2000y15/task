<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BoardPostType;
use App\Models\FormFieldDefinition;
use App\Models\FormFieldCategory;

class BoardPostTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $postTypes = [
            [
                'name' => 'announcement',
                'display_name' => 'お知らせ',
                'description' => '全社的なお知らせや重要な情報を共有するための投稿タイプ',
                'is_default' => false,
                'is_active' => true,
                'order' => 0, // 常に最上位
            ],
            [
                'name' => 'proposal',
                'display_name' => '企画書',
                'description' => '新規企画や提案書を共有するための投稿タイプ',
                'is_default' => true,
                'is_active' => true,
                'order' => 2,
            ],
        ];

        foreach ($postTypes as $typeData) {
            $postType = BoardPostType::updateOrCreate(
                ['name' => $typeData['name']],
                $typeData
            );

            // お知らせ以外の投稿タイプのみカスタム項目カテゴリを作成
            if ($postType->name !== 'announcement') {
                FormFieldCategory::updateOrCreate([
                    'name' => $postType->name,
                ], [
                    'display_name' => $postType->display_name,
                    'description' => "投稿タイプ「{$postType->display_name}」のカスタム項目カテゴリ",
                    'order' => $postType->order,
                    'is_enabled' => true,
                ]);
            }
        }

        // 企画書タイプ用のサンプルカスタム項目を作成
        $proposalCategory = FormFieldCategory::where('name', 'proposal')->first();

        if ($proposalCategory) {
            $sampleFields = [
                [
                    'name' => 'proposal_date',
                    'label' => '企画実施予定日',
                    'type' => 'date',
                    'category' => 'proposal',
                    'placeholder' => '',
                    'is_required' => true,
                    'order' => 1,
                    'is_enabled' => true
                ],
                [
                    'name' => 'budget',
                    'label' => '予算',
                    'type' => 'number',
                    'category' => 'proposal',
                    'placeholder' => '円',
                    'is_required' => false,
                    'order' => 2,
                    'is_enabled' => true
                ],
                [
                    'name' => 'department',
                    'label' => '担当部署',
                    'type' => 'select',
                    'category' => 'proposal',
                    'options' => [
                        'marketing' => 'マーケティング',
                        'sales' => '営業',
                        'development' => '開発',
                        'general_affairs' => '総務'
                    ],
                    'is_required' => true,
                    'order' => 3,
                    'is_enabled' => true
                ],
            ];

            foreach ($sampleFields as $fieldData) {
                FormFieldDefinition::updateOrCreate(
                    [
                        'name' => $fieldData['name'],
                        'category' => $fieldData['category']
                    ],
                    $fieldData
                );
            }
        }
    }
}
