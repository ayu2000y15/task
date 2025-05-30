<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\ProcessTemplate;
use App\Models\Role;
use App\Policies\ProjectPolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use App\Policies\ProcessTemplatePolicy;
use App\Policies\RolePolicy;
use App\Models\FormFieldDefinition;
use App\Policies\FormFieldDefinitionPolicy;
use App\Models\Feedback;
use App\Policies\FeedbackPolicy;
use App\Models\FeedbackCategory;
use App\Policies\FeedbackCategoryPolicy;
use Spatie\Activitylog\Models\Activity; // ★ 追加: ActivityLogモデル
use App\Policies\LogPolicy;               // ★ 追加: LogPolicy

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
        User::class => UserPolicy::class,
        ProcessTemplate::class => ProcessTemplatePolicy::class,
        Role::class => RolePolicy::class,
        FormFieldDefinition::class => FormFieldDefinitionPolicy::class,
        Feedback::class => FeedbackPolicy::class,
        FeedbackCategory::class => FeedbackCategoryPolicy::class,
        Activity::class => LogPolicy::class, // ★ 追加: ActivityLogモデルとLogPolicyを紐付け
    ];


    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function ($user, $ability) {
            if ($user->roles->contains('name', 'system_developer')) {
                return true;
            }
        });
    }
}
