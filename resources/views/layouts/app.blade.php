<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>衣装案件管理 - @yield('title', config('app.name', 'Laravel'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @yield('styles')
    @stack('styles')
    <style>
        /* サイドバーのプロジェクト名とアイコンのレイアウト調整用 */
        .project-link-content {
            display: flex;
            justify-content: space-between; /* タイトルグループとアイコン群を両端揃え */
            align-items: flex-start; /* 上揃え */
            width: 100%;
        }
        .project-title-group {
            display: flex;
            align-items: center; /* アイコン(頭文字)とタイトルを縦中央揃え */
            min-width: 0; /* トランケートが効くように */
            flex-grow: 1; /* タイトル部分が伸びるように */
            margin-right: 8px; /* アイコン群との間にスペース */
        }
        .project-title-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .project-status-icons-sidebar {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-shrink: 0;
        }
        .header-main-nav a {
            flex-shrink: 0;
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-100 dark:text-gray-100 dark:bg-gray-900">
    <div x-data="{ sidebarOpen: false }">
        <div x-show="sidebarOpen" class="fixed inset-0 z-20 bg-black opacity-50 md:hidden" @click="sidebarOpen = false" style="display: none;"></div>

        <aside
            class="fixed inset-y-0 left-0 z-30 w-64 h-screen overflow-y-auto transition duration-300 ease-in-out transform bg-white shadow-lg dark:bg-gray-800 md:translate-x-0"
            :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}">
            <div class="flex items-center justify-center h-16 bg-gray-50 dark:bg-gray-700"> <a href="{{ route('home.index') }}" class="text-xl font-semibold text-gray-700 dark:text-white">衣装案件管理</a>
            </div>
            <nav class="px-2 py-4 space-y-2">
                @can('create', App\Models\Project::class)
                <a href="{{ route('projects.create') }}" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 transition-colors duration-200 rounded-md dark:text-gray-200 hover:bg-gray-800 hover:text-white dark:hover:bg-blue-600">
                    <i class="fas fa-plus w-5 h-5 mr-2"></i> 新規衣装案件
                </a>
                @endcan
                <div>
                    <h3 class="px-3 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">
                        お気に入り ({{ $favoriteProjects->count() }})
                    </h3>
                    <div class="space-y-1">
                        @foreach($favoriteProjects as $project)
                        @php
                            $_projectStatusOptionsSb = ['' => '未設定'] + (\App\Models\Project::PROJECT_STATUS_OPTIONS ?? []);
                            $_projectStatusIconsSb = [
                                'not_started' => 'fa-minus-circle text-gray-400 dark:text-gray-500', 'in_progress' => 'fa-play-circle text-blue-400 dark:text-blue-500',
                                'completed'   => 'fa-check-circle text-green-400 dark:text-green-500', 'on_hold'     => 'fa-pause-circle text-yellow-400 dark:text-yellow-500',
                                'cancelled'   => 'fa-times-circle text-red-400 dark:text-red-500', '' => 'fa-question-circle text-gray-400 dark:text-gray-500',
                            ];
                            $_currentProjectStatusSb = $project->status ?? '';
                            $_projectStatusTooltipSb = $_projectStatusOptionsSb[$_currentProjectStatusSb] ?? $_currentProjectStatusSb;
                            $_projectStatusIconClassSb = $_projectStatusIconsSb[$_currentProjectStatusSb] ?? $_projectStatusIconsSb[''];
                            $_deliveryFlagValueSb = $project->delivery_flag ?? '0';
                            $_deliveryIconSb = $_deliveryFlagValueSb == '1' ? 'fa-check-circle text-green-400 dark:text-green-500' : 'fa-truck text-yellow-400 dark:text-yellow-500';
                            $_deliveryTooltipSb = $_deliveryFlagValueSb == '1' ? '納品済み' : '未納品';
                            $_paymentFlagOptionsSb = ['' => '未設定'] + (\App\Models\Project::PAYMENT_FLAG_OPTIONS ?? []);
                            $_paymentFlagIconsSb = [
                                'Pending'        => 'fa-clock text-yellow-400 dark:text-yellow-500', 'Processing'     => 'fa-hourglass-half text-blue-400 dark:text-blue-500',
                                'Completed'      => 'fa-check-circle text-green-400 dark:text-green-500', 'Partially Paid' => 'fa-adjust text-orange-400 dark:text-orange-500',
                                'Overdue'        => 'fa-exclamation-triangle text-red-400 dark:text-red-500', 'Cancelled'      => 'fa-ban text-gray-400 dark:text-gray-500',
                                'Refunded'       => 'fa-undo text-purple-400 dark:text-purple-500', 'On Hold'        => 'fa-pause-circle text-indigo-400 dark:text-indigo-500',
                                ''               => 'fa-question-circle text-gray-400 dark:text-gray-500',
                            ];
                            $_currentPaymentFlagSb = $project->payment_flag ?? '';
                            $_paymentFlagTooltipSb = $_paymentFlagOptionsSb[$_currentPaymentFlagSb] ?? $_currentPaymentFlagSb;
                            $_paymentFlagIconClassSb = $_paymentFlagIconsSb[$_currentPaymentFlagSb] ?? $_paymentFlagIconsSb[''];
                        @endphp
                        <a href="{{ route('projects.show', $project) }}"
                           class="block px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 {{ (request()->is('projects/'.$project->id.'*') || (isset($currentProject) && $currentProject->id == $project->id)) ? 'bg-blue-500 text-white dark:bg-blue-600' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                            <div class="project-link-content">
                                <div class="project-title-group">
                                    <span class="flex items-center justify-center w-5 h-5 mr-2 text-xs font-bold text-white rounded flex-shrink-0" style="background-color: {{ $project->color ?? '#6c757d' }};">
                                        {{ mb_substr($project->title, 0, 1) }}
                                    </span>
                                    <span class="project-title-text" title="{{ $project->title }}">{{ $project->title }}</span>
                                </div>
                                <div class="project-status-icons-sidebar text-xs">
                                    @if($project->status)<span title="ステータス: {{ $_projectStatusTooltipSb }}"><i class="fas {{ $_projectStatusIconClassSb }}"></i></span>@endif
                                    <span title="納品: {{ $_deliveryTooltipSb }}"><i class="fas {{ $_deliveryIconSb }}"></i></span>
                                    @if($project->payment_flag)<span title="支払い: {{ $_paymentFlagTooltipSb }}"><i class="fas {{ $_paymentFlagIconClassSb }}"></i></span>@endif
                                    <i class="fas fa-star text-yellow-400"></i>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h3 class="px-3 py-2 text-xs font-semibold tracking-wider text-gray-500 uppercase dark:text-gray-400">
                        衣装案件 ({{ $normalProjects->count() }})
                    </h3>
                    <div class="space-y-1">
                        @foreach($normalProjects as $project)
                        @php
                            $_projectStatusOptionsSb = ['' => '未設定'] + (\App\Models\Project::PROJECT_STATUS_OPTIONS ?? []);
                            $_projectStatusIconsSb = [
                                'not_started' => 'fa-minus-circle text-gray-400 dark:text-gray-500', 'in_progress' => 'fa-play-circle text-blue-400 dark:text-blue-500',
                                'completed'   => 'fa-check-circle text-green-400 dark:text-green-500', 'on_hold'     => 'fa-pause-circle text-yellow-400 dark:text-yellow-500',
                                'cancelled'   => 'fa-times-circle text-red-400 dark:text-red-500', '' => 'fa-question-circle text-gray-400 dark:text-gray-500',
                            ];
                            $_currentProjectStatusSb = $project->status ?? '';
                            $_projectStatusTooltipSb = $_projectStatusOptionsSb[$_currentProjectStatusSb] ?? $_currentProjectStatusSb;
                            $_projectStatusIconClassSb = $_projectStatusIconsSb[$_currentProjectStatusSb] ?? $_projectStatusIconsSb[''];
                            $_deliveryFlagValueSb = $project->delivery_flag ?? '0';
                            $_deliveryIconSb = $_deliveryFlagValueSb == '1' ? 'fa-check-circle text-green-400 dark:text-green-500' : 'fa-truck text-yellow-400 dark:text-yellow-500';
                            $_deliveryTooltipSb = $_deliveryFlagValueSb == '1' ? '納品済み' : '未納品';
                            $_paymentFlagOptionsSb = ['' => '未設定'] + (\App\Models\Project::PAYMENT_FLAG_OPTIONS ?? []);
                            $_paymentFlagIconsSb = [
                                'Pending'        => 'fa-clock text-yellow-400 dark:text-yellow-500', 'Processing'     => 'fa-hourglass-half text-blue-400 dark:text-blue-500',
                                'Completed'      => 'fa-check-circle text-green-400 dark:text-green-500', 'Partially Paid' => 'fa-adjust text-orange-400 dark:text-orange-500',
                                'Overdue'        => 'fa-exclamation-triangle text-red-400 dark:text-red-500', 'Cancelled'      => 'fa-ban text-gray-400 dark:text-gray-500',
                                'Refunded'       => 'fa-undo text-purple-400 dark:text-purple-500', 'On Hold'        => 'fa-pause-circle text-indigo-400 dark:text-indigo-500',
                                ''               => 'fa-question-circle text-gray-400 dark:text-gray-500',
                            ];
                            $_currentPaymentFlagSb = $project->payment_flag ?? '';
                            $_paymentFlagTooltipSb = $_paymentFlagOptionsSb[$_currentPaymentFlagSb] ?? $_currentPaymentFlagSb;
                            $_paymentFlagIconClassSb = $_paymentFlagIconsSb[$_currentPaymentFlagSb] ?? $_paymentFlagIconsSb[''];
                        @endphp
                        <a href="{{ route('projects.show', $project) }}"
                           class="block px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 {{ (request()->is('projects/'.$project->id.'*') || (isset($currentProject) && $currentProject->id == $project->id)) ? 'bg-blue-500 text-white dark:bg-blue-600' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                            <div class="project-link-content">
                                <div class="project-title-group">
                                    <span class="flex items-center justify-center w-5 h-5 mr-2 text-xs font-bold text-white rounded flex-shrink-0" style="background-color: {{ $project->color ?? '#6c757d' }};">
                                        {{ mb_substr($project->title, 0, 1) }}
                                    </span>
                                    <span class="project-title-text" title="{{ $project->title }}">{{ $project->title }}</span>
                                </div>
                                <div class="project-status-icons-sidebar text-xs">
                                     @if($project->status)<span title="案件ステータス: {{ $_projectStatusTooltipSb }}"><i class="fas {{ $_projectStatusIconClassSb }}"></i></span>@endif
                                    <span title="納品状況: {{ $_deliveryTooltipSb }}"><i class="fas {{ $_deliveryIconSb }}"></i></span>
                                    @if($project->payment_flag)<span title="支払い状況: {{ $_paymentFlagTooltipSb }}"><i class="fas {{ $_paymentFlagIconClassSb }}"></i></span>@endif
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            </nav>
        </aside>

        <div class="flex flex-col flex-1 md:ml-64">
            <header class="flex items-center justify-between h-16 px-2 sm:px-4 bg-white border-b dark:bg-gray-800 dark:border-gray-700 sticky top-0 z-50">
                 <div class="flex items-center"> {{-- 左側グループ: ハンバーガーメニュー(モバイルのみ) --}}
                    <div class="md:hidden">
                        <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 dark:text-gray-300 focus:outline-none p-2">
                            <i class="fas fa-bars w-6 h-6"></i>
                        </button>
                    </div>
                 </div>

                {{-- 中央グループ: メインナビゲーションリンク --}}
                <nav class="header-main-nav flex items-center overflow-x-auto space-x-1 md:space-x-2 md:flex-grow md:justify-start">
                    @can('viewAny', App\Models\Project::class)
                    <a class="inline-flex items-center p-2 text-sm font-medium rounded-md {{ request()->routeIs('home.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('home.index') }}" title="ホーム">
                        <i class="fas fa-home"></i><span class="hidden md:inline ml-2">ホーム</span>
                    </a>
                    @endcan
                    @can('viewAny', App\Models\Task::class)
                    <a class="inline-flex items-center p-2 text-sm font-medium rounded-md {{ request()->routeIs('tasks.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('tasks.index') }}" title="工程">
                        <i class="fas fa-tasks"></i><span class="hidden md:inline ml-2">工程</span>
                    </a>
                    @endcan
                    @can('viewAny', App\Models\Project::class)
                    <a class="inline-flex items-center p-2 text-sm font-medium rounded-md {{ request()->routeIs('gantt.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('gantt.index') }}" title="ガントチャート">
                        <i class="fas fa-chart-gantt"></i><span class="hidden md:inline ml-2">ガント</span>
                    </a>
                    @endcan
                    @can('viewAny', App\Models\Project::class)
                    <a class="inline-flex items-center p-2 text-sm font-medium rounded-md {{ request()->routeIs('calendar.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('calendar.index') }}" title="カレンダー">
                        <i class="fas fa-calendar-alt"></i><span class="hidden md:inline ml-2">カレンダー</span>
                    </a>
                    @endcan
                    @can('viewAny', App\Models\Project::class)
                    <a class="inline-flex items-center p-2 text-sm font-medium rounded-md {{ (request()->routeIs('projects.*') || (isset($currentProject) && request()->is('projects/*'))) && !request()->routeIs('projects.*.tasks.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('projects.index') }}" title="衣装案件">
                        <i class="fas fa-tshirt"></i><span class="hidden md:inline ml-2">衣装案件</span>
                    </a>
                    @endcan
                </nav>

                {{-- 右側グループ: 管理・ユーザーメニュー --}}
                <div class="flex items-center space-x-1 sm:space-x-2 pl-1 sm:pl-2">
                    @can('viewAny', App\Models\ProcessTemplate::class)
                    <div x-data="{ adminMenuOpenOnHeader: false }" class="relative">
                        <button @click="adminMenuOpenOnHeader = !adminMenuOpenOnHeader" class="flex items-center px-2 sm:px-3 py-2 text-sm font-medium text-gray-700 rounded-md dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
                            <i class="fas fa-cog"></i>
                            <span class="hidden sm:inline ml-1">管理</span>
                            <i class="fas fa-chevron-down fa-xs ml-1"></i>
                        </button>
                        <div x-show="adminMenuOpenOnHeader" @click.away="adminMenuOpenOnHeader = false"
                             x-transition
                             class="absolute right-0 mt-2 w-48 py-1 bg-white rounded-md shadow-lg dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-50"
                             style="display: none;">
                            @can('viewAny', App\Models\User::class)
                                <a href="{{ route('users.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">ユーザー管理</a>
                            @endcan
                            @can('viewAny', App\Models\Role::class)
                                <a href="{{ route('roles.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">権限設定</a>
                            @endcan
                            @can('viewAny', App\Models\ProcessTemplate::class)
                                <a href="{{ route('process-templates.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">工程テンプレート</a>
                            @endcan
                            @can('viewAny', App\Models\FormFieldDefinition::class) {{-- ポリシーに合わせる --}}
                                <a href="{{ route('admin.form-definitions.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">カスタム項目定義</a>
                            @endcan
                            @can('viewAny', App\Models\Feedback::class)
                            <a href="{{ route('admin.feedbacks.index') }}" class="flex justify-between items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                フィードバック管理 @if(isset($unreadFeedbackCountGlobal) && $unreadFeedbackCountGlobal > 0)<span class="ml-auto inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 bg-red-600 rounded-full">{{ $unreadFeedbackCountGlobal }}</span>@endif
                            </a>
                            @endcan
                            @can('viewAny', Spatie\Activitylog\Models\Activity::class) {{-- LogPolicy@viewAny の権限チェック --}}
                                <a href="{{ route('admin.logs.index') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">操作ログ閲覧</a>
                            @endcan
                            @can('viewAny', App\Models\User::class)
                                <a href="javascript:void(0)" onclick="copyToClipboard()" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">新規ユーザー登録リンク</a>
                                <input type="text" value="{{ url('/register') }}" id="copyTarget" class="sr-only" aria-hidden="true">
                            @endcan
                        </div>
                    </div>
                    @endcan
                    @auth
                    <div x-data="{ userMenuOpen: false }" class="relative">
                        <button @click="userMenuOpen = !userMenuOpen" class="flex items-center text-sm font-medium text-gray-700 rounded-md dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none p-2">
                            <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                            <i class="fas fa-user sm:hidden" title="{{ Auth::user()->name }}"></i>
                            <i class="fas fa-chevron-down fa-xs ml-1"></i>
                        </button>
                        <div x-show="userMenuOpen" @click.away="userMenuOpen = false"
                             x-transition
                             class="absolute right-0 mt-2 w-48 py-1 bg-white rounded-md shadow-lg dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-50"
                             style="display: none;">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">プロフィール</a>
                            @can('create', App\Models\Feedback::class)
                            <a href="{{ route('user_feedbacks.create') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">フィードバックを送信</a>
                            @endcan
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <a href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); this.closest('form').submit();"
                                   class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                    ログアウト
                                </a>
                            </form>
                        </div>
                    </div>
                    @endauth
                </div>
            </header>

            <main class="flex-1 p-4 md:p-6 overflow-y-auto h-full">
                @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md dark:bg-green-700 dark:text-green-100 dark:border-green-600" role="alert">
                    {{ session('success') }}
                </div>
                @endif
                @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md dark:bg-red-700 dark:text-red-100 dark:border-red-600" role="alert">
                    {{ session('error') }}
                </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Global Modals and Tooltips --}}
    <div id="taskDescriptionTooltip"
         class="fixed z-[100] hidden rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white shadow-lg whitespace-pre-wrap dark:bg-gray-700 max-w-xs"
         role="tooltip">
    </div>
    <div id="imagePreviewModalGlobal" class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-80 z-[1050] items-center justify-center p-4" style="display: none;">
        <img id="previewImageFullGlobal" src="" alt="Full Image" class="max-w-[90vw] max-h-[90vh] object-contain block">
        <button id="closePreviewModalBtnGlobal" class="absolute top-4 right-4 text-white text-3xl hover:text-gray-300 cursor-pointer">&times;</button>
    </div>
    <div id="upload-loading-overlay"
        class="fixed inset-0 z-[10050] flex items-center justify-center flex-col text-white bg-black bg-opacity-75"
        style="display:none;">
        <i class="fas fa-hourglass-half fa-3x mb-3"></i>
        <p>アップロード中です...</p>
    </div>

    @yield('scripts')
    @stack('scripts')

    <script>
        function copyToClipboard() {
            const copyTarget = document.getElementById("copyTarget");
            if (!copyTarget) return;
            navigator.clipboard.writeText(copyTarget.value).then(() => {
                alert("招待リンクをコピーしました : \n" + copyTarget.value);
            }).catch(err => {
                console.error('クリップボードへのコピーに失敗しました: ', err);
                try {
                    copyTarget.style.display = 'block';
                    copyTarget.select();
                    document.execCommand("Copy");
                    copyTarget.style.display = 'none';
                    alert("招待リンクをコピーしました (fallback): \n" + copyTarget.value);
                } catch (e) {
                     alert("コピーに失敗しました。手動でコピーしてください。");
                }
            });
        }
    </script>
</body>
</html>
