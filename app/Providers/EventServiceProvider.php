<?php

namespace App\Providers;

use App\Models\Project; // Projectモデルをuse
use App\Observers\ProjectObserver; // 作成したProjectObserverをuse
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * The model observers for your application.
     *
     * @var array
     */
    protected $observers = [ // ここに追記または作成
        Project::class => [ProjectObserver::class],
    ];


    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Project::observe(ProjectObserver::class); // $observersプロパティを使う場合はこちらは不要
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
