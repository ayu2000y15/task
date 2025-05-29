<?php

namespace App\Policies;

use App\Models\ProcessTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProcessTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('process_templates.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProcessTemplate $processTemplate): bool
    {
        // viewAny権限があれば個別の閲覧も許可する（または process_templates.view を別途定義）
        return $user->hasPermissionTo('process_templates.viewAny');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('process_templates.update'); // 作成・更新・削除は 'update' 権限で統一
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        return $user->hasPermissionTo('process_templates.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('process_templates.delete'); // 作成・更新・削除は 'update' 権限で統一
    }
}
