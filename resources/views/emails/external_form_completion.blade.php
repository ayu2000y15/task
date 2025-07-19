<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>送信完了</title>
</head>

<body
    style="margin: 0; padding: 0; background-color: #f8f9fa; font-family: 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 20px 10px;">
                <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0"
                    style="max-width: 100%; padding: 10px; margin: 0 auto; background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <tr>
                        <td
                            style="background-color: #4f46e5; color: white; padding: 30px 20px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: 600;">
                                {{ $formCategory->form_title ?? $formCategory->display_name }}
                            </h1>
                            <p style="margin: 10px 0 0 0; opacity: 0.9; font-size: 16px;">送信完了</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 30px 0;">
                            <p style="margin-bottom: 15px;">
                                @if($userName)
                                    {{ $userName }} 様
                                @else
                                    {{ $userEmail }} 様
                                @endif
                            </p>
                            <p style="margin-bottom: 15px;">
                                この度は{{ $formCategory->display_name }}をご利用いただき、<br>ありがとうございます。<br>お送りいただいた内容を正常に受信いたしました。
                            </p>
                            @if($formCategory->thank_you_message)
                                <p style="margin-bottom: 20px;">
                                    {!! nl2br($formCategory->thank_you_message) !!}
                                </p>
                            @endif

                            @if($formCategory->delivery_estimate_text)
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
                                    style="margin-bottom: 20px;">
                                    <tr>
                                        <td
                                            style="background-color: #eef2ff; border-left: 4px solid #4f46e5; padding: 15px;">
                                            <p style="margin: 0; color: #4338ca; font-weight: bold;">
                                                <span style="font-size: 18px; vertical-align: middle;">🚚</span> 納品予定のお知らせ
                                            </p>
                                            <p style="margin: 5px 0 0 0; color: #4338ca;">
                                                お申込みいただいた製品は、<strong>{{ $formCategory->delivery_estimate_text }}</strong>ごろの到着を予定しております。
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
                                style="background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 20px;">
                                <tr>
                                    <td>
                                        <h3
                                            style="margin: 0 0 15px 0; color: #374151; font-size: 16px; font-weight: bold;">
                                            送信内容の確認</h3>
                                        <table role="presentation" border="0" cellpadding="0" cellspacing="0"
                                            width="100%">
                                            <tr style="vertical-align: top;">
                                                <td
                                                    style="padding-bottom: 10px; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb;">
                                                    <div style="font-weight: 600; color: #6b7280; font-size: 14px;">お名前
                                                    </div>
                                                    <div style="color: #111827; margin-top: 5px;">
                                                        {{ $submissionData['name'] ?? '未入力' }}
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr style="vertical-align: top;">
                                                <td
                                                    style="padding-top: 10px; padding-bottom: 10px; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb;">
                                                    <div style="font-weight: 600; color: #6b7280; font-size: 14px;">
                                                        メールアドレス</div>
                                                    <div style="color: #111827; margin-top: 5px;">{{ $userEmail }}</div>
                                                </td>
                                            </tr>
                                            @if(!empty($submissionData['notes']))
                                                <tr style="vertical-align: top;">
                                                    <td
                                                        style="padding-top: 10px; padding-bottom: 10px; margin-bottom: 10px; border-bottom: 1px solid #e5e7eb;">
                                                        <div style="font-weight: 600; color: #6b7280; font-size: 14px;">備考
                                                        </div>
                                                        <div style="color: #111827; margin-top: 5px;">
                                                            {!! nl2br($submissionData['notes']) !!}
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endif
                                            @if(!empty($submissionData['custom_fields']))
                                                @foreach($submissionData['custom_fields'] as $field)
                                                    <tr style="vertical-align: top;">
                                                        <td
                                                            style="padding-top: 10px; padding-bottom: 10px; margin-bottom: 10px; {{ !$loop->last ? 'border-bottom: 1px solid #e5e7eb;' : '' }}">
                                                            <div style="font-weight: 600; color: #6b7280; font-size: 14px;">
                                                                {{ $field['label'] }}
                                                            </div>
                                                            <div
                                                                style="color: #111827; margin-top: 5px; word-break: break-all;">
                                                                @php $fieldValue = $field['value']; @endphp
                                                                @if(in_array($field['type'], ['file', 'file_multiple']) && !empty($fieldValue))
                                                                    @php $files = ($field['type'] === 'file' && is_array($fieldValue)) ? [$fieldValue] : $fieldValue; @endphp
                                                                    @foreach($files as $fileInfo)
                                                                        @if(is_array($fileInfo) && isset($fileInfo['original_name']))
                                                                            <span
                                                                                style="display: block;">{{ $fileInfo['original_name'] }}</span>
                                                                        @endif
                                                                    @endforeach
                                                                @elseif($field['type'] === 'image_select' && is_array($fieldValue) && !empty($fieldValue))
                                                                    @foreach($fieldValue as $item)
                                                                        <div
                                                                            style="margin-top: 5px; {{ !$loop->last ? 'padding-bottom: 10px; border-bottom: 1px dashed #dddddd; margin-bottom:10px;' : '' }}">
                                                                            <span style="display: block; margin-bottom: 5px;">選択:
                                                                                {{ $item['label'] }}</span>
                                                                            <img src="{{ $item['url'] }}" alt="{{ $item['label'] }}"
                                                                                style="max-width: 150px; height: auto; border-radius: 4px; border: 1px solid #dddddd;">
                                                                        </div>
                                                                    @endforeach
                                                                @elseif(is_array($fieldValue))
                                                                    {{ implode('、', $fieldValue) }}
                                                                @else
                                                                    {!! nl2br($fieldValue) !!}
                                                                @endif
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin-top: 30px;">ご不明な点がございましたら、<br>お気軽にお問い合わせください。<br>今後ともよろしくお願いいたします。</p>
                        </td>
                    </tr>
                    <tr>
                        <td
                            style="background-color: #f9fafb; border-top: 1px solid #e5e7eb; padding: 20px; text-align: center; font-size: 14px; color: #6b7280; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0;">このメールは、送信専用のメールアドレスから送信されているため返信できません。</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>