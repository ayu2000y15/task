<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $formCategory->form_title }} - 新規申請</title>
    <style>
        body {
            font-family: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Yu Gothic', 'Meiryo', sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            color: #2563eb;
            font-size: 24px;
        }

        .submission-info {
            background-color: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-row {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #f3f4f6;
            padding-bottom: 10px;
        }

        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .info-label {
            font-weight: bold;
            width: 120px;
            color: #6b7280;
            font-size: 14px;
        }

        .info-value {
            flex: 1;
            color: #111827;
        }

        .custom-fields {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .custom-fields h2 {
            margin-top: 0;
            color: #374151;
            font-size: 18px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .field-row {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .field-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .field-label {
            font-weight: bold;
            color: #4b5563;
            margin-bottom: 5px;
        }

        .field-value {
            color: #111827;
            white-space: pre-wrap;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-approved {
            background-color: #d1fae5;
            color: #059669;
        }

        .footer {
            background-color: #f3f4f6;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }

        .admin-link {
            display: inline-block;
            background-color: #2563eb;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>{{ $formCategory->form_title }} - 新規申請</h1>
        <p>{{ config('app.name') }}に新しい申請が届きました。</p>
    </div>

    <div class="submission-info">
        <div class="info-row">
            <div class="info-label">申請ID:</div>
            <div class="info-value">#{{ $submission->id }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">フォーム種別:</div>
            <div class="info-value">{{ $formCategory->display_name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">申請日時:</div>
            <div class="info-value">{{ $submission->created_at->format('Y年n月j日 H:i') }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">ステータス:</div>
            <div class="info-value">
                @if($submission->status === 'pending')
                    <span class="status-badge status-pending">承認待ち</span>
                @elseif($submission->status === 'approved')
                    <span class="status-badge status-approved">承認済み</span>
                @elseif($submission->status === 'new')
                    <span class="status-badge" style="background-color:#e0e7ff;color:#3730a3;">新規</span>
                @elseif($submission->status === 'rejected')
                    <span class="status-badge" style="background-color:#fee2e2;color:#b91c1c;">却下</span>
                @else
                    <span class="status-badge">{{ $submission->status }}</span>
                @endif
            </div>
        </div>
    </div>

    @if($submission->custom_field_data)
        <div class="custom-fields">
            <h2>申請内容</h2>
            @foreach($submission->custom_field_data as $fieldName => $value)
                @if($value)
                    <div class="field-row">
                        <div class="field-label">{{ $fieldName }}:</div>
                        <div class="field-value">
                            @if(is_array($value))
                                {{ implode(', ', $value) }}
                            @else
                                {{ $value }}
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif

    <div class="footer">
        <p>この申請の詳細を確認するには、管理画面にログインしてください。</p>
        <a href="{{ route('admin.external-submissions.show', $submission) }}" class="admin-link">
            申請詳細を確認
        </a>
        <p style="margin-top: 20px; font-size: 12px;">
            このメールは {{ config('app.name') }} から自動送信されています。
        </p>
    </div>
</body>

</html>
