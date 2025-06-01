<?php
// config/project_forms.php
return [
    'default_custom_fields' => [
        [
            'name' => 'project_company_name',
            'label' => '会社名（案件依頼）',
            'type' => 'text',
            'required' => false,
            'order' => 100,
            'placeholder' => '株式会社案件依頼プロジェクト',
            'options' => '',
            'maxlength' => 255,
        ],
        [
            'name' => 'project_contact_phone',
            'label' => '電話番号（案件依頼）',
            'type' => 'tel',
            'required' => false,
            'order' => 101,
            'placeholder' => '03-1234-5678',
            'options' => '',
            'maxlength' => 20,
        ]
        // 必要に応じて他のデフォルト案件依頼フィールドを追加
    ],
];
