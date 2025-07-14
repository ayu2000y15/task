<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>送信完了</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background-color: #4f46e5;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .content {
            padding: 30px 20px;
        }

        .content h2 {
            color: #374151;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .content p {
            margin-bottom: 15px;
        }

        .form-details {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }

        .form-details h3 {
            margin: 0 0 15px 0;
            color: #374151;
            font-size: 16px;
        }

        .detail-item {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .detail-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 14px;
        }

        .detail-value {
            color: #111827;
            margin-top: 5px;
        }

        .footer {
            background-color: #f9fafb;
            border-top: 1px solid #e5e7eb;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #6b7280;
        }

        .message-content {
            white-space: pre-line;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>{{ $formCategory->form_title ?? $formCategory->display_name }}</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9; font-size: 16px;">送信完了</p>
        </div>

        <div class="content">
            @if($userName)
                <p>{{ $userName }} 様</p>
            @else
                <p>{{ $userEmail }} 様</p>
            @endif

            <p>この度は、{{ $formCategory->display_name }}をご利用いただき、ありがとうございます。<br>
                お送りいただいた内容を正常に受信いたしました。</p>

            @if($formCategory->thank_you_message)
                <div class="message-content">{{ $formCategory->thank_you_message }}</div>
            @endif

            <div class="form-details">
                <h3>送信内容の確認</h3>

                <div class="detail-item">
                    <div class="detail-label">お名前</div>
                    <div class="detail-value">{{ $submissionData['name'] ?? '未入力' }}</div>
                </div>

                <div class="detail-item">
                    <div class="detail-label">メールアドレス</div>
                    <div class="detail-value">{{ $userEmail }}</div>
                </div>

                @if(!empty($submissionData['notes']))
                    <div class="detail-item">
                        <div class="detail-label">備考</div>
                        <div class="detail-value message-content">{{ $submissionData['notes'] }}</div>
                    </div>
                @endif

                @if(!empty($submissionData['custom_fields']))
                    @foreach($submissionData['custom_fields'] as $fieldName => $fieldValue)
                        @if(!empty($fieldValue))
                            <div class="detail-item">
                                <div class="detail-label">{{ $fieldName }}</div>
                                <div class="detail-value">
                                    @if(is_array($fieldValue))
                                        {{ implode('、', $fieldValue) }}
                                    @else
                                        <span class="message-content">{{ $fieldValue }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>

            <p>ご不明な点がございましたら、お気軽にお問い合わせください。<br>
                今後ともよろしくお願いいたします。</p>
        </div>

        <div class="footer">
            <p>このメールは自動送信されています。返信の必要はございません。</p>
        </div>
    </div>
</body>

</html>
