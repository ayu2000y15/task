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
                        <a href="{{ route('projects.show', $project) }}"
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 {{ (request()->is('projects/'.$project->id) || (isset($currentProject) && $currentProject->id == $project->id)) ? 'bg-blue-500 text-white dark:bg-blue-600' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                            <span class="flex items-center justify-center w-5 h-5 mr-2 text-xs font-bold text-white rounded" style="background-color: {{ $project->color }};">
                                {{ mb_substr($project->title, 0, 1) }}
                            </span>
                            <span class="truncate">{{ $project->title }}</span>
                            <i class="fas fa-star text-yellow-400 ml-auto"></i>
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
                        <a href="{{ route('projects.show', $project) }}"
                           class="flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200 {{ (request()->is('projects/'.$project->id) || (isset($currentProject) && $currentProject->id == $project->id)) ? 'bg-blue-500 text-white dark:bg-blue-600' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700' }}">
                            <span class="flex items-center justify-center w-5 h-5 mr-2 text-xs font-bold text-white rounded" style="background-color: {{ $project->color }};">
                                {{ mb_substr($project->title, 0, 1) }}
                            </span>
                            <span class="truncate">{{ $project->title }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
            </nav>
        </aside>

        <div class="flex flex-col flex-1 md:ml-64">
            <header class="flex items-center justify-between h-16 px-4 bg-white border-b dark:bg-gray-800 dark:border-gray-700 sticky top-0 z-50"> <div class="md:hidden">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 dark:text-gray-300 focus:outline-none">
                        <i class="fas fa-bars w-6 h-6"></i>
                    </button>
                </div>

                <nav class="hidden md:flex flex-grow justify-start items-center space-x-1">
                    @can('viewAny', App\Models\Project::class)
                    <a class="px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('home.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('home.index') }}">
                        <i class="fas fa-home mr-1"></i> ホーム
                    </a>
                    @endcan
                    @can('viewAny', App\Models\Project::class)
                    <a class="px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('tasks.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('tasks.index') }}">
                        <i class="fas fa-tasks mr-1"></i> 工程
                    </a>
                    @endcan
                    @can('viewAny', App\Models\Project::class)
                    <a class="px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('gantt.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('gantt.index') }}">
                        <i class="fas fa-chart-gantt mr-1"></i> ガント
                    </a>
                    @endcan
                    @can('viewAny', App\Models\Project::class)
                    <a class="px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('calendar.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('calendar.index') }}">
                        <i class="fas fa-calendar-alt mr-1"></i> カレンダー
                    </a>
                    @endcan
                    @can('viewAny', App\Models\Project::class)
                    <a class="px-3 py-2 text-sm font-medium rounded-md {{ request()->routeIs('projects.*') && !request()->routeIs('projects.*.tasks.*') ? 'text-blue-600 bg-blue-100 dark:text-blue-300 dark:bg-gray-700' : 'text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                        href="{{ route('projects.index') }}">
                        <i class="fas fa-tshirt mr-1"></i> 衣装案件
                    </a>
                    @endcan
                </nav>

                <div class="flex items-center space-x-4 pl-2">
                    @can('viewAny', App\Models\ProcessTemplate::class)
                    <div x-data="{ adminMenuOpen: false }" class="relative">
                        <button @click="adminMenuOpen = !adminMenuOpen" class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 rounded-md dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none">
                            <i class="fas fa-cog mr-1"></i> 管理 <i class="fas fa-chevron-down fa-xs ml-1"></i>
                        </button>
                        <div x-show="adminMenuOpen" @click.away="adminMenuOpen = false"
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
                        </div>
                    </div>
                    @endcan
                    @auth
                    <div x-data="{ userMenuOpen: false }" class="relative">
                        <button @click="userMenuOpen = !userMenuOpen" class="flex items-center text-sm font-medium text-gray-700 rounded-md dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none p-2">
                            <span>{{ Auth::user()->name }}</span>
                            <i class="fas fa-chevron-down fa-xs ml-1"></i>
                        </button>
                        <div x-show="userMenuOpen" @click.away="userMenuOpen = false"
                             x-transition
                             class="absolute right-0 mt-2 w-48 py-1 bg-white rounded-md shadow-lg dark:bg-gray-700 ring-1 ring-black ring-opacity-5 z-50"
                             style="display: none;">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">プロフィール</a>
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
        <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
        <p>アップロード中です...</p>
    </div>

    @yield('scripts')
    @stack('scripts')
</body>
</html>