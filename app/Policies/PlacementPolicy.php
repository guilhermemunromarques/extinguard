<?php

namespace App\Policies;

use App\Models\Placement;
use App\Models\User;

class PlacementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('placements.view');
    }

    public function view(User $user, Placement $placement): bool
    {
        return $user->can('placements.view');
    }

    public function create(User $user): bool
    {
        return $user->can('placements.create');
    }

    public function update(User $user, Placement $placement): bool
    {
        return $user->can('placements.update');
    }

    public function delete(User $user, Placement $placement): bool
    {
        return $user->can('placements.delete');
    }
}
