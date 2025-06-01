<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\View\Composers\SidebarComposer;
use App\View\Composers\UnreadFeedbackComposer;
use App\View\Composers\PendingStockOrdersComposer; // ★ 追加: 新しいコンポーザをuse
use App\View\Composers\InventoryAlertComposer; // ★ 追加: 新しいコンポーザをuse
use App\View\Composers\NewExternalSubmissionsComposer; // ★ 追加: 新しいコンポーザをuse

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
        View::composer(['layouts.app', 'admin.*'], PendingStockOrdersComposer::class);
        View::composer(['layouts.app', 'admin.*'], InventoryAlertComposer::class); // ★ 追加
        View::composer('layouts.app', NewExternalSubmissionsComposer::class);
    }
}
