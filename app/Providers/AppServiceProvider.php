<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\View\Composers\SidebarComposer;
use App\View\Composers\UnreadFeedbackComposer;
use App\View\Composers\PendingStockOrdersComposer; // ★ 追加: 新しいコンポーザをuse
// StockOrderモデルはコンポーザ内でuseするので、ここでは不要な場合もありますが、念のため残しても問題ありません。
// use App\Models\StockOrder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', SidebarComposer::class);
        View::composer(['layouts.app', 'admin.*'], UnreadFeedbackComposer::class);

        // ★ 新しいビューコンポーザを登録
        // '*' ですべてのビューに適用するか、'admin.*' や 'layouts.app' など特定のビュー群に適用します。
        // 管理メニューに表示するので、'layouts.app' (全ページで使われるレイアウト) と
        // 'admin.*' (管理者用ページ群) に適用するのが適切でしょう。
        View::composer(['layouts.app', 'admin.*'], PendingStockOrdersComposer::class);
    }
}
