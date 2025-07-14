<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>パスワード忘れ - 案件管理</title>

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
            max-width: 450px;
            margin: 0 auto;
        }

        .auth-header {
            background-color: #ffc107;
            color: #212529;
            padding: 1rem;
            text-align: center;
        }

        .auth-body {
            padding: 1.5rem;
            background: white;
        }

        .form-control {
            border-radius: 6px;
            padding: 0.5rem 0.75rem;
            border: 1px solid #ced4da;
            font-size: 0.9rem;
        }

        .form-control:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
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
            color: #ffc107;
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

        .form-label {
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .mb-3 {
            margin-bottom: 0.75rem !important;
        }

        .alert {
            border-radius: 6px;
            border: none;
            padding: 0.75rem;
            font-size: 0.85rem;
        }

        .auth-link {
            color: #ffc107;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .auth-link:hover {
            color: #e0a800;
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-4">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="logo">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h4 class="auth-title">パスワード忘れ</h4>
                        <p class="auth-subtitle">リセットリンクを送信</p>
                    </div>
                    <div class="auth-body">
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            メールアドレスを入力してください。パスワードリセットリンクをお送りします。
                        </div>

                        <!-- セッションステータス -->
                        @if (session('status'))
                            <div class="alert alert-success mb-3">
                                <i class="fas fa-check-circle me-2"></i>
                                {{ session('status') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf

                            <!-- メールアドレス -->
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>メールアドレス
                                </label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                    name="email" value="{{ old('email') }}" required autofocus
                                    placeholder="登録済みのメールアドレス">
                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- 送信ボタン -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-warning btn-auth">
                                    <i class="fas fa-paper-plane me-1"></i>リセットリンクを送信
                                </button>
                            </div>

                            <!-- ログインに戻る -->
                            <div class="text-center">
                                <a class="auth-link" href="{{ route('login') }}">
                                    <i class="fas fa-arrow-left me-1"></i>ログインに戻る
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
