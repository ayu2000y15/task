<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Project::class => ProjectPolicy::class,
        Task::class => TaskPolicy::class,
    ];


    public function boot(): void
    {
        $this->registerPolicies();

        // スーパー管理者（システム開発者）は全ての権限を持つ
        Gate::before(function ($user, $ability) {
            if ($user->roles->contains('name', 'system_developer')) {
                return true;
            }
        });
    }
}
