<?php

namespace App\Policies;

use App\Models\FormFieldDefinition;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FormFieldDefinitionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('form-definition.viewAny');
    }

    /**
     * Determine whether the user can view the model.
     * (今回は一覧表示と編集画面のみなので、個別のviewはviewAnyで代用可)
     */
    public function view(User $user, FormFieldDefinition $formFieldDefinition): bool
    {
        return $user->hasPermissionTo('form-definition.viewAny');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // 「更新」権限で作成も許可する想定
        return $user->hasPermissionTo('form-definition.update');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FormFieldDefinition $formFieldDefinition): bool
    {
        return $user->hasPermissionTo('form-definition.update');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FormFieldDefinition $formFieldDefinition): bool
    {
        return $user->hasPermissionTo('form-definition.delete');
    }
}
