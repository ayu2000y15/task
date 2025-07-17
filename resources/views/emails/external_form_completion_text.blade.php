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

----------------------------------------
■ 送信内容の確認
----------------------------------------

お名前: {{ $submissionData['name'] ?? '未入力' }}
メールアドレス: {{ $userEmail }}

@if(!empty($submissionData['notes']))
    備考:
    {{ $submissionData['notes'] }}
@endif

@if(!empty($submissionData['custom_fields']))
    @foreach($submissionData['custom_fields'] as $field)

        {{ $field['label'] }}:
        @php $fieldValue = $field['value']; @endphp
        @if(in_array($field['type'], ['file', 'file_multiple']) && !empty($fieldValue))
            @php
                // 単一ファイルも複数ファイルも同じように扱えるよう配列に統一
                $files = ($field['type'] === 'file' && is_array($fieldValue)) ? [$fieldValue] : $fieldValue;
            @endphp
            @foreach($files as $fileInfo)
                @if(is_array($fileInfo) && isset($fileInfo['original_name']))
                    - {{ $fileInfo['original_name'] }}
                @endif
            @endforeach
        @elseif(is_array($fieldValue))
            {{ implode('、', $fieldValue) }}
        @else
            {{ $fieldValue }}
        @endif
    @endforeach
@endif

----------------------------------------

ご不明な点がございましたら、お気軽にお問い合わせください。
今後ともよろしくお願いいたします。

※このメールは自動送信されています。返信の必要はございません。