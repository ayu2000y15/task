<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ガントチャート - @yield('title')</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- カラーピッカー -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css">
    <!-- jQuery UI CSS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <!-- FullCalendar CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
        }

        tr.task-overdue:hover {
            background-color: rgba(220, 53, 69, 0.15) !important;
        }

        tr.task-due-soon:hover {
            background-color: rgba(255, 193, 7, 0.15) !important;
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* サイドバー */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background-color: #333;
            color: #fff;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        /* モバイル用サイドバー */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0 !important;
            }

            .toggle-sidebar {
                display: block !important;
            }
        }

        .sidebar-header {
            padding: 15px;
            background-color: #222;
            border-bottom: 1px solid #444;
        }

        .sidebar-section {
            margin-bottom: 20px;
        }

        .sidebar-section-title {
            padding: 10px 15px;
            font-size: 14px;
            color: #aaa;
            text-transform: uppercase;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: #fff;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .sidebar-item:hover {
            background-color: #444;
            color: #fff;
        }

        .sidebar-item.active {
            background-color: #007bff;
        }

        .project-icon {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            margin-right: 10px;
            font-weight: bold;
        }

        .favorite-icon {
            margin-left: auto;
            color: #ffc107;
        }

        .add-project-btn {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            color: #007bff;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .add-project-btn:hover {
            background-color: #444;
            color: #007bff;
        }

        /* メインコンテンツ */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        /* モバイル用トグルボタン */
        .toggle-sidebar {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1001;
            display: none;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 8px 12px;
        }

        /* フォーム中央配置 */
        .centered-form {
            max-width: 800px;
            margin: 0 auto;
        }

        /* ガントチャート */
        .gantt-container {
            overflow-x: auto;
            max-width: 100%;
        }

        .gantt-header {
            position: sticky;
            top: 0;
            background-color: #f8f9fa;
            z-index: 10;
        }

        .gantt-row {
            height: 40px;
        }

        .gantt-cell {
            min-width: 30px;
            height: 40px;
            border-right: 1px solid #dee2e6;
            text-align: center;
            padding: 0;
        }

        .weekend {
            background-color: #f8f9fa;
        }

        .saturday {
            background-color: #e6f2ff !important;
            /* 土曜日は青色背景 */
        }

        .sunday,
        .holiday {
            background-color: #ffe6e6 !important;
            /* 日曜・祝日は赤色背景 */
        }

        .today {
            background-color: #fffbcc !important;
            /* 今日の日付は黄色背景 */
            font-weight: bold;
            border: 2px solid #ffc107;
        }

        .task-bar {
            height: 30px;
            margin-top: 5px;
            border-radius: 3px;
            position: relative;
        }

        .project-header {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .month-header {
            background-color: #e9ecef;
            font-weight: bold;
        }

        .task-folder {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .task-milestone {
            text-align: center;
        }

        .milestone-diamond {
            width: 20px;
            height: 20px;
            transform: rotate(45deg);
            margin: 10px auto;
        }

        .toggle-children {
            cursor: pointer;
            width: 20px;
            display: inline-block;
            text-align: center;
        }

        .task-name {
            display: flex;
            align-items: center;
        }

        .task-actions {
            visibility: hidden;
        }

        tr:hover .task-actions {
            visibility: visible;
        }

        .color-picker-wrapper {
            position: relative;
            display: inline-block;
        }

        .color-preview {
            width: 30px;
            height: 30px;
            border-radius: 5px;
            cursor: pointer;
            border: 1px solid #ced4da;
        }

        /* 階層構造 */
        .task-indent {
            margin-left: 20px;
            border-left: 1px solid #ddd;
            padding-left: 10px;
        }

        /* フィルターパネル */
        .filter-panel {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border: 1px solid #dee2e6;
            position: relative;
        }

        .filter-close {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            font-size: 1.2rem;
        }

        /* タブメニュー */
        .main-nav {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }

        .main-nav .nav-link {
            color: #495057;
            padding: 0.75rem 1rem;
            border-bottom: 2px solid transparent;
        }

        .main-nav .nav-link:hover {
            color: #007bff;
        }

        .main-nav .nav-link.active {
            color: #007bff;
            border-bottom: 2px solid #007bff;
        }

        .sub-nav {
            background-color: #fff;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }

        .sub-nav .nav-link {
            color: #6c757d;
            padding: 0.5rem 1rem;
        }

        .sub-nav .nav-link:hover {
            color: #007bff;
        }

        .sub-nav .nav-link.active {
            color: #007bff;
            font-weight: 500;
        }

        /* ToDoリスト */
        .todo-section {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .todo-header {
            padding: 10px 15px;
            border-bottom: 1px solid #eee;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 5px 5px 0 0;
        }

        .todo-header.todo {
            background-color: #007bff;
            color: white;
        }

        .todo-header.in-progress {
            background-color: #28a745;
            color: white;
        }

        .todo-header.review {
            background-color: #6c757d;
            color: white;
        }

        .todo-header.completed {
            background-color: #dc3545;
            color: white;
        }

        .todo-item {
            padding: 10px 15px;
            border-bottom: 1px solid #d0cfcf;
            display: flex;
            align-items: center;
        }

        .todo-item:last-child {
            border-bottom: none;
        }

        .todo-checkbox {
            margin-right: 10px;
        }

        .todo-text {
            flex-grow: 1;
        }

        .todo-project {
            font-size: 12px;
            color: #6c757d;
            margin-left: 10px;
        }

        .todo-actions {
            visibility: hidden;
        }

        .todo-item:hover .todo-actions {
            visibility: visible;
        }

        /* ヘッダー表示/非表示切り替え */
        .toggle-details {
            cursor: pointer;
            padding: 5px 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 3px;
            margin-right: 10px;
        }

        .toggle-details:hover {
            background-color: #e9ecef;
        }

        .details-hidden .detail-column {
            display: none;
        }

        /* カレンダービュー */
        .calendar-container {
            max-width: 100%;
            margin: 0 auto;
        }

        .fc-event {
            cursor: pointer;
        }

        .fc-day-sat {
            background-color: #e6f2ff;
            color: #0066cc;
        }

        .fc-day-sun {
            background-color: #ffe6e6;
            color: #cc0000;
        }

        .fc-holiday {
            background-color: #ffe6e6;
            color: #cc0000;
        }

        .fc-today {
            background-color: #fffbcc !important;
        }

        /* レスポンシブ対応 */
        @media (max-width: 768px) {
            .card-body {
                padding: 0.5rem;
            }

            .table th,
            .table td {
                padding: 0.5rem;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.75rem;
            }

            .filter-panel .row {
                margin-right: -5px;
                margin-left: -5px;
            }

            .filter-panel .col-md-3,
            .filter-panel .col-md-4,
            .filter-panel .col-md-6,
            .filter-panel .col-md-12 {
                padding-right: 5px;
                padding-left: 5px;
            }

            h1 {
                font-size: 1.5rem;
            }

            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 10px;
            }

            .d-flex.justify-content-between .btn-group {
                display: flex;
                width: 100%;
            }

            .d-flex.justify-content-between .btn {
                flex: 1;
            }
        }

        /* 編集可能なセル */
        .editable-cell {
            cursor: pointer;
            position: relative;
        }

        .editable-cell:hover::after {
            content: "\f044";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 0.8rem;
        }

        /* インラインフォーム */
        .inline-form {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 100;
        }

        .inline-form input,
        .inline-form select {
            width: 100%;
            height: 100%;
            padding: 0.25rem;
            border: 1px solid #007bff;
            border-radius: 0;
        }

        /* ガントチャートの週表示 */
        .week-view .gantt-cell {
            min-width: 60px;
        }

        /* ガントチャートの日付ヘッダー */
        .date-header {
            display: flex;
            flex-direction: column;
            font-size: 0.8rem;
        }

        .date-header .date {
            font-weight: bold;
        }

        .date-header .day {
            font-size: 0.7rem;
            color: #6c757d;
        }

        .task-bar {
            position: absolute;
            height: 24px;
            margin-top: 8px;
            border-radius: 3px;
            z-index: 10;
        }

        .task-progress {
            height: 100%;
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .milestone-diamond {
            position: absolute;
            width: 16px;
            height: 16px;
            transform: rotate(45deg);
            margin: 12px;
            z-index: 10;
        }

        /* 詳細を隠す機能のスタイル修正 */
        #ganttTable.details-hidden .detail-column {
            display: none;
        }

        /* 階層構造のインデント */
        .task-indent {
            padding-left: 20px;
            border-left: 1px solid #ddd;
        }

        /* 期限切れ・期限間近のスタイル */
        .task-overdue .task-bar {
            border: 2px solid #dc3545;
        }

        .task-due-soon .task-bar {
            border: 2px solid #ffc107;
        }

        /* 週表示モード */
        .week-view .gantt-cell {
            min-width: 60px !important;
        }

        .day-view .gantt-cell {
            min-width: 30px !important;
        }

        /* 日付ヘッダーのスタイル */
        .date-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.8rem;
        }

        .date-header .day {
            font-size: 0.7rem;
            color: #6c757d;
        }

        /* ガントチャートの階層表示スタイル */
        .task-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            margin-right: 8px;
        }

        .task-icon .fa-folder {
            color: #4a86e8;
        }

        .toggle-children {
            cursor: pointer;
            width: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 4px;
        }

        .toggle-children i {
            transition: transform 0.2s;
        }

        /* フォルダ階層のスタイル */
        .folder-structure .folder-item {
            position: relative;
            padding-left: 20px;
        }

        .folder-structure .folder-item::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 1px;
            background-color: #ddd;
        }

        .folder-structure .folder-item::after {
            content: "";
            position: absolute;
            left: 0;
            top: 12px;
            width: 10px;
            height: 1px;
            background-color: #ddd;
        }

        .folder-structure .folder-item:last-child::before {
            height: 12px;
        }

        /* ファイルアップロードエリアのスタイル */
        .dropzone {
            border: 2px dashed #4a86e8;
            border-radius: 5px;
            background: #f8f9fa;
            min-height: 150px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dropzone .dz-message {
            text-align: center;
            margin: 2em 0;
        }

        .dropzone .dz-preview .dz-image {
            border-radius: 5px;
        }

        /* ファイルアイコンのスタイル */
        .file-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            margin-right: 8px;
        }

        /* 階層表示のスタイル */
        .hierarchy-line {
            position: relative;
        }

        .hierarchy-line::before {
            content: "";
            position: absolute;
            left: -10px;
            top: 0;
            bottom: 0;
            width: 1px;
            background-color: #ddd;
        }

        .hierarchy-line::after {
            content: "";
            position: absolute;
            left: -10px;
            top: 12px;
            width: 10px;
            height: 1px;
            background-color: #ddd;
        }

        .hierarchy-line:last-child::before {
            height: 12px;
        }

        /* ステータスカラーバーのスタイル */
        .status-color-bar {
            display: inline-block;
            width: 4px;
            height: 20px;
            margin-right: 8px;
            border-radius: 2px;
        }

        /* マイルストーンのスタイル改善 */
        .milestone-diamond {
            position: absolute;
            width: 16px;
            height: 16px;
            background-color: #dc3545;
            transform: rotate(45deg);
            top: 7px;
            z-index: 10;
        }

        /* マイルストーンのホバー効果 */
        .milestone-diamond:hover {
            width: 20px;
            height: 20px;
            top: 5px;
            transition: all 0.2s ease;
        }
    </style>
    @yield('styles')
</head>

<body>
    <!-- モバイル用サイドバートグルボタン -->
    <button class="toggle-sidebar" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <!-- サイドバー -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h5 class="mb-0">プロジェクト管理</h5>
        </div>

        <a href="{{ route('projects.create') }}" class="add-project-btn">
            <i class="fas fa-plus me-2"></i> 新規プロジェクト
        </a>

        <div class="sidebar-section">
            <div class="sidebar-section-title">
                お気に入り <span class="badge bg-secondary">{{ App\Models\Project::where('is_favorite', true)->count() }}
                    プロジェクト</span>
            </div>
            @foreach(App\Models\Project::where('is_favorite', true)->get() as $project)
                <a href="{{ route('projects.show', $project) }}"
                    class="sidebar-item {{ request('project_id') == $project->id ? 'active' : '' }}">
                    <div class="project-icon" style="background-color: {{ $project->color }};">
                        {{ mb_substr($project->title, 0, 1) }}
                    </div>
                    {{ $project->title }}
                    <i class="fas fa-star favorite-icon"></i>
                </a>
            @endforeach
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">
                プロジェクト <span class="badge bg-secondary">{{ App\Models\Project::where('is_favorite', false)->count() }}
                    プロジェクト</span>
            </div>
            @foreach(App\Models\Project::where('is_favorite', false)->get() as $project)
                <a href="{{ route('projects.show', $project) }}"
                    class="sidebar-item {{ request('project_id') == $project->id ? 'active' : '' }}">
                    <div class="project-icon" style="background-color: {{ $project->color }};">
                        {{ mb_substr($project->title, 0, 1) }}
                    </div>
                    {{ $project->title }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- メインコンテンツ -->
    <div class="main-content">
        <!-- メインナビゲーション -->
        <ul class="nav nav-tabs main-nav">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('home.*') ? 'active' : '' }}" href="{{ route('home.index') }}">
                    <i class="fas fa-home"></i> ホーム
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}"
                    href="{{ route('tasks.index') }}">
                    <i class="fas fa-tasks"></i> タスク
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('gantt.*') ? 'active' : '' }}"
                    href="{{ route('gantt.index') }}">
                    <i class="fas fa-chart-gantt"></i> ガントチャート
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('calendar.*') ? 'active' : '' }}"
                    href="{{ route('calendar.index') }}">
                    <i class="fas fa-calendar-alt"></i> カレンダー
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('projects.*') && !request()->routeIs('projects.*.tasks.*') ? 'active' : '' }}"
                    href="{{ route('projects.index') }}">
                    <i class="fas fa-project-diagram"></i> プロジェクト
                </a>
            </li>
        </ul>

        <!-- フラッシュメッセージ -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- メインコンテンツ -->
        @yield('content')
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- jQuery UI -->
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- カラーピッカー -->
    <script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/pickr.min.js"></script>
    <!-- FullCalendar -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js"></script>

    <script>
        // サイドバートグル
        document.getElementById('toggleSidebar').addEventListener('click', function () {
            document.getElementById('sidebar').classList.toggle('show');
        });

        // 画面サイズが変わったときにサイドバーの表示を調整
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('show');
            }
        });

        // モバイルでサイドバー外をクリックしたら閉じる
        document.addEventListener('click', function (event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleSidebar');

            if (window.innerWidth <= 768 &&
                sidebar.classList.contains('show') &&
                !sidebar.contains(event.target) &&
                event.target !== toggleBtn) {
                sidebar.classList.remove('show');
            }
        });
    </script>

    @yield('scripts')
</body>

</html>