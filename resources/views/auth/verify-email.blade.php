<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>メール認証 - 案件管理</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 1rem 0;
        }

        .auth-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 500px;
            margin: 0 auto;
        }

        .auth-header {
            background-color: #17a2b8;
            color: white;
            padding: 1rem;
            text-align: center;
        }

        .auth-body {
            padding: 1.5rem;
            background: white;
        }

        .btn-auth {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .logo {
            width: 50px;
            height: 50px;
            margin: 0 auto 0.5rem;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #17a2b8;
        }

        .auth-title {
            font-size: 1.25rem;
            margin-bottom: 0;
        }

        .auth-subtitle {
            font-size: 0.85rem;
            margin-bottom: 0;
            opacity: 0.9;
        }

        .alert {
            border-radius: 6px;
            border: none;
            padding: 0.75rem;
            font-size: 0.85rem;
            margin-bottom: 0.75rem;
        }

        .mb-3 {
            margin-bottom: 0.75rem !important;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="logo">
                            <i class="fas fa-envelope-check"></i>
                        </div>
                        <h4 class="auth-title">メール認証</h4>
                        <p class="auth-subtitle">アカウントの認証を完了</p>
                    </div>
                    <div class="auth-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            ご登録ありがとうございます！メールアドレスに送信された認証リンクをクリックして、認証を完了してください。
                        </div>

                        @if (session('status') == 'verification-link-sent')
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                新しい認証リンクを送信しました。
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-6 mb-3">
                                <form method="POST" action="{{ route('verification.send') }}">
                                    @csrf
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-info btn-auth">
                                            <i class="fas fa-paper-plane me-1"></i>再送信
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="col-6 mb-3">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-outline-secondary btn-auth">
                                            <i class="fas fa-sign-out-alt me-1"></i>ログアウト
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
