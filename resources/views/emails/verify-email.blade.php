@extends('layouts.mail')

@section('content')
    <div class="email-header"
        style="background-color: #007bff; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: #ffffff; padding: 20px; text-align: center;">
        <div class="email-logo"
            style="width: 40px; height: 40px; background-color: #ffffff; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; color: #007bff; margin-bottom: 10px; line-height: 40px; text-align: center;">
            ✉️
        </div>
        <h1 class="email-title"
            style="font-size: 20px; margin: 0 0 5px 0; font-weight: 600; color: #ffffff; text-align: center;">メールアドレス認証</h1>
        <p class="email-subtitle" style="font-size: 13px; margin: 0; opacity: 0.9; color: #ffffff; text-align: center;">
            {{ config('app.name') }}
        </p>
    </div>

    <div class="email-body" style="padding: 25px 20px; background-color: #ffffff; color: #333333;">
        <div class="email-content" style="font-size: 15px; line-height: 1.6; margin-bottom: 20px; color: #333333;">
            <p style="margin: 0 0 15px 0; color: #333333;">こんにちは、</p>

            <p style="margin: 0 0 15px 0; color: #333333;">{{ config('app.name') }} にご登録いただき、ありがとうございます！</p>

            <p style="margin: 0 0 15px 0; color: #333333;">アカウントの作成を完了するために、下記のボタンをクリックしてメールアドレスの認証を行ってください。</p>

            <div class="text-center" style="text-align: center;">
                <a href="{{ $url }}" class="email-button"
                    style="display: inline-block; background-color: #007bff; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: 600; font-size: 15px; text-align: center; margin: 15px 0; border: none;">
                    メールアドレスを認証
                </a>
            </div>

            <div class="email-info"
                style="background-color: #f8f9fa; border-left: 3px solid #007bff; padding: 12px 15px; margin: 15px 0; border-radius: 0 6px 6px 0; font-size: 14px; color: #333333;">
                <strong style="color: #333333;">🎉 認証後にできること</strong><br>
                <span style="color: #333333;">• すべての機能をご利用いただけます</span><br>
                <span style="color: #333333;">• 工程の作成・管理</span><br>
                <span style="color: #333333;">• 案件の共有</span><br>
                <span style="color: #333333;">• 通知の受信</span>
            </div>

            <p style="margin: 0; color: #333333;">認証が完了すると、すぐに工程管理ツールをご利用いただけます。</p>
        </div>

        <div class="email-warning"
            style="background-color: #fff3cd; border-left: 3px solid #ffc107; padding: 12px 15px; margin: 15px 0; border-radius: 0 6px 6px 0; color: #856404; font-size: 14px;">
            <strong style="color: #856404;">⏰ 認証について</strong><br>
            <span style="color: #856404;">このリンクは60分間有効です。期限が切れた場合は、ログイン画面から再送信してください。</span>
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
