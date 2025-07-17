<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $formCategory->form_title }} - 新規申請</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8f9fa;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', Meiryo, sans-serif; color: #333;">
        <tr>
            <td style="padding: 20px 10px;">
                <table role="presentation" align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; border: 1px solid #e5e7eb;">
                    <tr>
                        <td style="padding: 20px 30px; background-color: #f8f9fa; border-bottom: 1px solid #e5e7eb; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #2563eb; font-size: 24px;">{{ $formCategory->form_title }} - 新規申請</h1>
                            <p style="margin: 10px 0 0; color: #333; font-size: 16px;">{{ config('app.name') }}に新しい申請が届きました。</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 30px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="padding: 10px 0; border-bottom: 1px solid #f3f4f6;">
                                        <b style="font-weight: bold; color: #6b7280; font-size: 14px; width: 120px; display: inline-block;">申請ID:</b>
                                        <span style="color: #111827;">#{{ $submission->id }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0; border-bottom: 1px solid #f3f4f6;">
                                        <b style="font-weight: bold; color: #6b7280; font-size: 14px; width: 120px; display: inline-block;">フォーム種別:</b>
                                        <span style="color: #111827;">{{ $formCategory->display_name }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0; border-bottom: 1px solid #f3f4f6;">
                                        <b style="font-weight: bold; color: #6b7280; font-size: 14px; width: 120px; display: inline-block;">申請日時:</b>
                                        <span style="color: #111827;">{{ $submission->created_at->format('n/j H:i') }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0;">
                                        <b style="font-weight: bold; color: #6b7280; font-size: 14px; width: 120px; display: inline-block;">ステータス:</b>
                                        <span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; text-transform: uppercase;
                                            @if($submission->status === 'pending') background-color: #fef3c7; color: #d97706;
                                            @elseif($submission->status === 'approved') background-color: #d1fae5; color: #059669;
                                            @elseif($submission->status === 'new') background-color:#e0e7ff; color:#3730a3;
                                            @elseif($submission->status === 'rejected') background-color:#fee2e2; color:#b91c1c;
                                            @else background-color:#e5e7eb; color:#4b5563; @endif">
                                            @if($submission->status === 'pending')承認待ち
                                            @elseif($submission->status === 'approved')承認済み
                                            @elseif($submission->status === 'new')新規
                                            @elseif($submission->status === 'rejected')却下
                                            @else{{ $submission->status }}
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @if($submission->custom_field_data)
                    <tr>
                        <td style="padding: 0 30px 20px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f9fafb; border-radius: 8px; padding: 20px;">
                                <tr>
                                    <td>
                                        <h2 style="margin-top: 0; color: #374151; font-size: 18px; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">申請内容</h2>
                                        @foreach($submission->custom_field_data as $fieldName => $value)
                                            @if($value)
                                            <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e5e7eb; {{ $loop->last ? 'border-bottom: none; margin-bottom: 0; padding-bottom: 0;' : '' }}">
                                                <p style="font-weight: bold; color: #4b5563; margin: 0 0 5px 0;">{{ $fieldName }}:</p>
                                                <p style="color: #111827; white-space: pre-wrap; margin: 0;">
                                                    @if(is_array($value))
                                                        {{ implode(', ', $value) }}
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </p>
                                            </div>
                                            @endif
                                        @endforeach
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding: 20px 30px; background-color: #f3f4f6; text-align: center; border-radius: 0 0 8px 8px;">
                            <p style="margin: 0; color: #6b7280; font-size: 14px;">この申請の詳細を確認するには、管理画面にログインしてください。</p>
                            <a href="{{ route('admin.external-submissions.show', $submission) }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; margin-top: 15px; font-weight: bold;">申請詳細を確認</a>
                            <p style="margin-top: 20px; font-size: 12px; color: #6b7280;">このメールは {{ config('app.name') }} から自動送信されています。</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
