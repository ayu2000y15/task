<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('projects.viewAny');
    }

    public function view(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('projects.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('projects.create');
    }

    public function update(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('projects.update');
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('projects.delete');
    }

    public function manageMeasurements(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('measurements.manage');
    }

    public function manageMeasurementTemplates(User $user): bool
    {
        return $user->hasPermissionTo('measurements.manage');
    }


    public function updateMeasurements(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('measurements.update');
    }

    public function updateMeasurementTemplates(User $user): bool
    {
        return $user->hasPermissionTo('measurements.update');
    }

    public function deleteMeasurements(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('measurements.delete');
    }

    public function deleteMeasurementTemplates(User $user): bool
    {
        return $user->hasPermissionTo('measurements.delete');
    }

    public function manageMaterials(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('materials.manage');
    }

    public function updateMaterials(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('materials.update');
    }

    public function deleteMaterials(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('materials.delete');
    }

    public function manageCosts(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('costs.manage');
    }
    public function updateCosts(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('costs.update');
    }

    public function deleteCosts(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('costs.delete');
    }

    public function viewFormDefinition(User $user): bool
    {
        return $user->hasPermissionTo('form-definition.view');
    }

    public function updateFormDefinition(User $user): bool
    {
        return $user->hasPermissionTo('form-definition.update');
    }

    public function deleteFormDefinition(User $user): bool
    {
        return $user->hasPermissionTo('form-definition.delete');
    }
}
