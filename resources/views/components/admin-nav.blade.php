<!-- サイドバー -->
<style>
    .nav-admin a {
        background-color: rgb(1, 17, 73);
    }
</style>
<div class="sidebar">
    <div class="sidebar-header">
        <h4 class="mb-0">管理者画面</h4>
    </div>
    <div class="sidebar-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                    href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-tachometer-alt"></i> ダッシュボード
                </a>
            </li>
            <!-- 開発者のみ -->
            @if (session('access_id') == '0')
                <div class="nav-admin">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.content-schema*') ? 'active' : '' }}"
                            href="{{ route('admin.content-schema') }}">
                            <i class="fas fa-table"></i> スキーマ設定
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.definition*') ? 'active' : '' }}"
                            href="{{ route('admin.definition') }}">
                            <i class="fas fa-table"></i> 汎用テーブル管理</a>
                    </li>
                </div>
            @endif
            <!-- 開発者 または サイト管理者 -->
            @if (session('access_id') == '0' || session('access_id') == '1')
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('admin.hptext*') ? 'active' : '' }}"
                        href="{{ route('admin.hptext') }}">
                        <i class="fas fa-table"></i> HPテキスト管理</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.photo*') ? 'active' : '' }}"
                        href="{{ route('admin.photo') }}">
                        <i class="fas fa-cloud-upload-alt"></i> HP画像管理
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.content-data*') ? 'active' : '' }}"
                        href="{{ route('admin.content-data') }}">
                        <i class="fas fa-database"></i> データ管理
                    </a>
                </li>
            @endif
        </ul>
    </div>
    <div class="sidebar-footer">
        <a class="nav-link text-danger" href="{{ route('logout') }}"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fas fa-sign-out-alt"></i> ログアウト
        </a>
        <form id="logout-form" action="{{ route('logout') }}" style="display: none;">
            @csrf
        </form>
    </div>
</div>