<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\View\Composers\SidebarComposer;
use App\View\Composers\UnreadFeedbackComposer;
use App\View\Composers\PendingStockOrdersComposer; // ★ 追加: 新しいコンポーザをuse
use App\View\Composers\InventoryAlertComposer; // ★ 追加: 新しいコンポーザをuse
use App\View\Composers\NewExternalSubmissionsComposer; // ★ 追加: 新しいコンポーザをuse
use App\View\Composers\UnreadBoardPostComposer;
use Illuminate\Support\Carbon;
use App\View\Composers\PendingRequestComposer; // ★ この行を追加
use Illuminate\Support\Facades\Auth;
use App\Services\ProductivityService;
use App\View\Composers\PendingShiftRequestComposer;
use App\Models\BoardPostType;
use App\Observers\BoardPostTypeObserver;
use Illuminate\Support\Facades\URL;

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
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url'));
        }

        // オブザーバーを登録
        BoardPostType::observe(BoardPostTypeObserver::class);

        Carbon::setLocale(config('app.locale'));
        View::composer('layouts.app', SidebarComposer::class);
        View::composer(['layouts.app', 'admin.*'], UnreadFeedbackComposer::class);
        View::composer(['layouts.app', 'admin.*'], PendingStockOrdersComposer::class);
        View::composer(['layouts.app', 'admin.*'], InventoryAlertComposer::class); // ★ 追加
        View::composer('layouts.app', NewExternalSubmissionsComposer::class);
        View::composer('layouts.app', UnreadBoardPostComposer::class);
        View::composer('*', PendingRequestComposer::class);
        View::composer(['layouts.app', 'admin.*'], PendingShiftRequestComposer::class);

        View::composer('layouts.app', function ($view) {
            if (Auth::check()) {
                $view->with('currentAttendanceStatus', Auth::user()->getCurrentAttendanceStatus());
            } else {
                $view->with('currentAttendanceStatus', 'clocked_out');
            }
        });

        View::composer('layouts.app', function ($view) {
            if (Auth::check()) {
                // 勤怠ステータス（既存のロジック）
                $view->with('currentAttendanceStatus', Auth::user()->getCurrentAttendanceStatus());

                // 生産性サマリー
                $productivityService = new ProductivityService();
                $view->with('currentUserProductivitySummary', $productivityService->getSummaryForCurrentUser());
            } else {
                $view->with('currentAttendanceStatus', 'clocked_out');
                $view->with('productivitySummaries', collect());
            }
        });
    }
}
