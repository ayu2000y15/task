<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>パスワードリセット - 衣装案件管理</title>

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
            background-color: #dc3545;
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
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
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
            color: #dc3545;
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
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-5">
                <div class="auth-card">
                    <div class="auth-header">
                        <div class="logo">
                            <i class="fas fa-key"></i>
                        </div>
                        <h4 class="auth-title">パスワードリセット</h4>
                        <p class="auth-subtitle">新しいパスワードを設定</p>
                    </div>
                    <div class="auth-body">
                        <form method="POST" action="{{ route('password.store') }}">
                            @csrf

                            <!-- パスワードリセットトークン -->
                            <input type="hidden" name="token" value="{{ $request->route('token') }}">

                            <!-- メールアドレス -->
                            <div class="mb-3">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope me-1"></i>メールアドレス
                                </label>
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                    name="email" value="{{ old('email', $request->email) }}" required autofocus
                                    autocomplete="username" readonly>
                                @error('email')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <!-- 新しいパスワード -->
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-1"></i>新しいパスワード <span class="text-danger">*</span>
                                </label>
                                <input id="password" type="password"
                                    class="form-control @error('password') is-invalid @enderror" name="password"
                                    required autocomplete="new-password" placeholder="8文字以上の新しいパスワード">
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

                            <!-- リセットボタン -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-danger btn-auth">
                                    <i class="fas fa-key me-1"></i>パスワードをリセット
                                </button>
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
