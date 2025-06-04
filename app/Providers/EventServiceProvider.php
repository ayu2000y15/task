<?php

namespace App\Providers;

use App\Models\Project;
use App\Observers\ProjectObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;      // ★ 追加
use Illuminate\Auth\Events\Logout;     // ★ 追加
use App\Listeners\LogSuccessfulLogin;  // ★ 追加
use App\Listeners\LogSuccessfulLogout; // ★ 追加
use App\Listeners\LogUserRegistered;   // ★ 追加
use App\Listeners\UpdateSentEmailLogStatus; // ★ 追加
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Events\JobProcessed;
use Illuminate\Bus\Events\JobFailed;


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
        // Registered::class => [
        //     SendEmailVerificationNotification::class,
        //     LogUserRegistered::class, // ★ 追加
        // ],
        // Login::class => [ // ★ 追加
        //     LogSuccessfulLogin::class,
        // ],
        // Logout::class => [ // ★ 追加
        //     LogSuccessfulLogout::class,
        // ],
    ];

    /**
     * The model observers for your application.
     *
     * @var array
     */
    protected $observers = [
        Project::class => [ProjectObserver::class],
    ];


    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
        // Event::listen(JobProcessed::class, function (JobProcessed $event) {
        //     Log::info('JobProcessed event fired for job: ' . $event->job->resolveName());
        // });

        // Event::listen(JobFailed::class, function (JobFailed $event) {
        //     Log::info('JobFailed event fired for job: ' . $event->job->resolveName() . ' with exception: ' . $event->exception->getMessage());
        // });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [ // ★ $subscribe プロパティにリスナーを登録
        UpdateSentEmailLogStatus::class,
    ];
}
