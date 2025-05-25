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

    public function manageMaterials(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('materials.manage');
    }

    public function manageCosts(User $user, Project $project): bool
    {
        return $user->hasPermissionTo('costs.manage');
    }
}
