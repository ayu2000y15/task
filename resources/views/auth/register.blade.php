<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>新規登録 - タスク管理ツール</title>

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
            background-color: #28a745;
            color: white;
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
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
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
            color: #28a745;
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

        .auth-link {
            color: #28a745;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .auth-link:hover {
            color: #1e7e34;
            text-decoration: underline;
        }

        .text-muted {
            font-size: 0.8rem;
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
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h4 class="auth-title">新規登録</h4>
                        <p class="auth-subtitle">アカウントを作成</p>
                    </div>
                    <div class="auth-body">
                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <!-- 名前 -->
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    <i class="fas fa-user me-1"></i>お名前 <span class="text-danger">*</span>
                                </label>
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                                    name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                                    placeholder="山田 太郎">
                                @error('name')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- メールアドレス -->
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>メールアドレス <span class="text-danger">*</span>
                                </label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                    name="email" value="{{ old('email') }}" required autocomplete="username"
                                    placeholder="example@email.com">
                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- パスワード -->
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>パスワード <span class="text-danger">*</span>
                                </label>
                                <input id="password" type="password"
                                    class="form-control @error('password') is-invalid @enderror" name="password"
                                    required autocomplete="new-password" placeholder="8文字以上">
                                @error('password')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- パスワード確認 -->
                            <div class="mb-3">
                                <label for="password_confirmation" class="form-label">
                                    <i class="fas fa-lock me-1"></i>パスワード確認 <span class="text-danger">*</span>
                                </label>
                                <input id="password_confirmation" type="password"
                                    class="form-control @error('password_confirmation') is-invalid @enderror"
                                    name="password_confirmation" required autocomplete="new-password"
                                    placeholder="上記と同じパスワード">
                                @error('password_confirmation')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- 登録ボタン -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-success btn-auth">
                                    <i class="fas fa-user-plus me-1"></i>アカウントを作成
                                </button>
                            </div>

                            <!-- ログインリンク -->
                            <div class="text-center">
                                <span class="text-muted">既にアカウントをお持ちの方は </span>
                                <a href="{{ route('login') }}" class="auth-link">ログイン</a>
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