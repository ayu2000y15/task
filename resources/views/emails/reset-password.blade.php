@extends('layouts.mail')

@section('content')
    <div class="email-header"
        style="background-color: #007bff; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: #ffffff; padding: 20px; text-align: center;">
        <div class="email-logo"
            style="width: 40px; height: 40px; background-color: #ffffff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; color: #007bff; margin-bottom: 10px; line-height: 40px; text-align: center;">
            🔑
        </div>
        <h1 class="email-title"
            style="font-size: 20px; margin: 0 0 5px 0; font-weight: 600; color: #ffffff; text-align: center;">パスワードリセット</h1>
        <p class="email-subtitle" style="font-size: 13px; margin: 0; opacity: 0.9; color: #ffffff; text-align: center;">
            {{ config('app.name') }}</p>
    </div>

    <div class="email-body" style="padding: 25px 20px; background-color: #ffffff; color: #333333;">
        <div class="email-content" style="font-size: 15px; line-height: 1.6; margin-bottom: 20px; color: #333333;">
            <p style="margin: 0 0 15px 0; color: #333333;">こんにちは、</p>

            <p style="margin: 0 0 15px 0; color: #333333;">
                あなたのアカウントでパスワードリセットのリクエストを受け付けました。下記のボタンをクリックして、新しいパスワードを設定してください。</p>

            <div class="text-center" style="text-align: center;">
                <a href="{{ $url }}" class="email-button"
                    style="display: inline-block; background-color: #007bff; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: 600; font-size: 15px; text-align: center; margin: 15px 0; border: none;">
                    パスワードをリセット
                </a>
            </div>

            <div class="email-info"
                style="background-color: #f8f9fa; border-left: 3px solid #007bff; padding: 12px 15px; margin: 15px 0; border-radius: 0 6px 6px 0; font-size: 14px; color: #333333;">
                <strong style="color: #333333;">📋 重要な情報</strong><br>
                <span style="color: #333333;">• このリンクは{{ $count }}分間有効です</span><br>
                <span style="color: #333333;">• セキュリティのため、リンクは一度のみ使用可能です</span><br>
                <span style="color: #333333;">• パスワードは8文字以上で設定してください</span>
            </div>

            <p style="margin: 0; color: #333333;">もしパスワードリセットをリクエストしていない場合は、このメールを無視してください。</p>
        </div>

        <div class="email-warning"
            style="background-color: #fff3cd; border-left: 3px solid #ffc107; padding: 12px 15px; margin: 15px 0; border-radius: 0 6px 6px 0; color: #856404; font-size: 14px;">
            <strong style="color: #856404;">⚠️ セキュリティについて</strong><br>
            <span style="color: #856404;">このメールに心当たりがない場合は、すぐに管理者にお知らせください。</span>
        </div>

        <div class="url-section text-center small text-muted"
            style="background-color: #f8f9fa; padding: 12px; border-radius: 6px; margin: 15px 0; word-break: break-all; color: #6c757d; text-align: center; font-size: 12px;">
            <p style="margin: 0 0 10px 0; color: #6c757d;">ボタンがクリックできない場合は、以下のURLをコピーしてブラウザに貼り付けてください：</p>
            <div class="email-link" style="color: #007bff; text-decoration: none;">{{ $url }}</div>
        </div>
    </div>

    <div class="email-footer"
        style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6; color: #6c757d;">
        <p class="email-footer-text" style="font-size: 13px; color: #6c757d; margin: 0 0 10px 0; line-height: 1.4;">
            このメールは {{ config('app.name') }} から自動送信されています。<br>
            ご質問がございましたら、<a href="mailto:{{ config('mail.from.address') }}" class="email-link"
                style="color: #007bff; text-decoration: none;">サポート</a>までお問い合わせください。
        </p>
        <p class="email-footer-text small text-muted" style="font-size: 12px; color: #6c757d; margin: 0; line-height: 1.4;">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </p>
    </div>
@endsection