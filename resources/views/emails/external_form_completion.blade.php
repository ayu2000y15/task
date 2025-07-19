件名: {{ $formCategory->form_title ?? $formCategory->display_name }} - 送信完了

@if($userName)
{{ $userName }} 様
@else
{{ $userEmail }} 様
@endif

この度は、{{ $formCategory->display_name }}をご利用いただき、ありがとうございます。
お送りいただいた内容を正常に受信いたしました。

@if($formCategory->thank_you_message)
{{ $formCategory->thank_you_message }}
@endif

@if($formCategory->delivery_estimate_text)

■ 納品予定のお知らせ
本日のお申込みの製品は、【{{ $formCategory->delivery_estimate_text }}】ごろの到着を予定しております。
@endif

----------------------------------------
■ 送信内容の確認
----------------------------------------

{{-- ★★★ ここから下のブロックを修正 ★★★ --}}

@php
    // 送信データからラベルと値を整理
    $displayItems = [];
    $displayItems['お名前'] = $submissionData['name'] ?? '未入力';
    $displayItems['メールアドレス'] = $userEmail;
    if (!empty($submissionData['notes'])) {
        $displayItems['備考'] = $submissionData['notes'];
    }
    if (!empty($submissionData['custom_fields'])) {
        foreach($submissionData['custom_fields'] as $field) {
            $fieldValue = $field['value'];
            $valueText = '';
            if (in_array($field['type'], ['file', 'file_multiple']) && !empty($fieldValue)) {
                $files = ($field['type'] === 'file' && isset($fieldValue['original_name'])) ? [$fieldValue] : $fieldValue;
                $fileNames = [];
                if (is_array($files)) {
                    foreach($files as $fileInfo) {
                        if (is_array($fileInfo) && isset($fileInfo['original_name'])) {
                           $fileNames[] = "- " . $fileInfo['original_name'];
                        }
                    }
                }
                $valueText = "\n" . implode("\n", $fileNames);
            } elseif ($field['type'] === 'image_select' && is_array($fieldValue)) {
                $selectionTexts = [];
                foreach($fieldValue as $item) {
                   $selectionTexts[] = "- 選択: " . $item['label'];
                }
                $valueText = "\n" . implode("\n", $selectionTexts);
            } elseif (is_array($fieldValue)) {
                $valueText = implode('、', $fieldValue);
            } else {
                $valueText = $fieldValue;
            }
            $displayItems[$field['label']] = $valueText;
        }
    }

    // 全てのラベルの中から最も幅の広いものの幅を計算
    $maxLabelWidth = 0;
    foreach ($displayItems as $label => $value) {
        $width = mb_strwidth($label); // 全角文字を2、半角を1として幅を計算
        if ($width > $maxLabelWidth) {
            $maxLabelWidth = $width;
        }
    }
@endphp

{{-- 計算した最大幅を元に、パディングを加えて整形して表示 --}}
@foreach($displayItems as $label => $value)
@php
    $labelWidth = mb_strwidth($label);
    $padding = str_repeat(' ', $maxLabelWidth - $labelWidth);
@endphp
{{ $label }}{{ $padding }} : {{ $value }}
@endforeach
{{-- ★★★ 修正はここまで ★★★ --}}


----------------------------------------

ご不明な点がございましたら、お気軽にお問い合わせください。
今後ともよろしくお願いいたします。

※このメールは送信専用のメールアドレスから送信されているため、返信できません
