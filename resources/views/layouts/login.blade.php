<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'HP管理者画面')</title>
</head>
<body>
    <div class="admin-container">
        <main class="admin-main">
            <div class="admin-container">
                @yield('content')
            </div>
        </main>

    </div>
</body>
</html>
