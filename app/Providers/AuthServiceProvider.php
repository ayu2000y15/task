<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User; // 追加
use App\Models\ProcessTemplate; // 追加
use App\Models\Role; // 追加
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy; // 追加
use App\Policies\ProcessTemplatePolicy; // 追加
use App\Policies\RolePolicy; // 追加
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
        User::class => UserPolicy::class, // 追加
        ProcessTemplate::class => ProcessTemplatePolicy::class, // 追加
        Role::class => RolePolicy::class, // 追加
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
