<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FormFieldCategory;

class FormFieldCategorySeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // 既存のFormFieldDefinition::CATEGORIESから詳細なカテゴリを作成
        $categories = [
            [
                'name' => 'project',
                'display_name' => '案件依頼',
                'description' => '制作などの案件依頼フォーム用カテゴリ',
                'type' => 'board',
                'slug' => null,
                'form_title' => null,
                'form_description' => null,
                'thank_you_title' => null,
                'thank_you_message' => null,
                'is_external_form' => false,
                'requires_approval' => true,
                'notification_emails' => null,
                'order' => 1,
                'is_enabled' => true,
            ],
            [
                'name' => 'board',
                'display_name' => '掲示板',
                'description' => '掲示板投稿用フォームフィールドカテゴリ',
                'type' => 'board',
                'slug' => null,
                'form_title' => null,
                'form_description' => null,
                'thank_you_title' => null,
                'thank_you_message' => null,
                'is_external_form' => false,
                'requires_approval' => false,
                'notification_emails' => null,
                'order' => 2,
                'is_enabled' => true,
            ],
            [
                'name' => 'announcement',
                'display_name' => 'お知らせ',
                'description' => 'お知らせ投稿用フォームフィールドカテゴリ',
                'type' => 'board',
                'slug' => null,
                'form_title' => null,
                'form_description' => null,
                'thank_you_title' => null,
                'thank_you_message' => null,
                'is_external_form' => false,
                'requires_approval' => false,
                'notification_emails' => null,
                'order' => 3,
                'is_enabled' => true,
            ],
            [
                'name' => 'proposal',
                'display_name' => '企画書',
                'description' => '企画書投稿用フォームフィールドカテゴリ',
                'type' => 'board',
                'slug' => null,
                'form_title' => null,
                'form_description' => null,
                'thank_you_title' => null,
                'thank_you_message' => null,
                'is_external_form' => false,
                'requires_approval' => false,
                'notification_emails' => null,
                'order' => 4,
                'is_enabled' => true,
            ],
            [
                'name' => 'costume_request',
                'display_name' => '制作依頼',
                'description' => '制作などの案件依頼フォーム用カテゴリ',
                'type' => 'form',
                'slug' => 'costume-request',
                'form_title' => '制作依頼フォーム',
                'form_description' => 'コスプレの制作依頼をお受けしております。下記フォームに必要事項をご記入の上、送信してください。',
                'thank_you_title' => 'お申し込みありがとうございました',
                'thank_you_message' => "制作依頼を承りました。\n\n内容を確認の上、3営業日以内に担当者よりご連絡させていただきます。\nお急ぎの場合は、お電話でもお問い合わせください。",
                'is_external_form' => true,
                'requires_approval' => true,
                'notification_emails' => ['admin@example.com'],
                'order' => 1,
                'is_enabled' => true,
            ],
            [
                'name' => 'contact',
                'display_name' => 'お問い合わせ',
                'description' => '一般的なお問い合わせフォーム用カテゴリ',
                'type' => 'form',
                'slug' => 'contact',
                'form_title' => 'お問い合わせフォーム',
                'form_description' => 'ご質問やお問い合わせがございましたら、下記フォームよりお気軽にお送りください。',
                'thank_you_title' => 'お問い合わせありがとうございました',
                'thank_you_message' => "お問い合わせを承りました。\n\n内容を確認の上、2営業日以内にご回答させていただきます。\nお急ぎの場合は、お電話でもお問い合わせください。",
                'is_external_form' => true,
                'requires_approval' => false,
                'notification_emails' => ['support@example.com'],
                'order' => 2,
                'is_enabled' => true,
            ],
        ];

        foreach ($categories as $categoryData) {
            FormFieldCategory::updateOrCreate(
                ['name' => $categoryData['name']],
                $categoryData
            );
        }

        $this->command->info('フォームカテゴリのシードが完了しました。');
    }
}
