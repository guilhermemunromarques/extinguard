<?php

namespace App\Policies;

use App\Models\Floor;
use App\Models\User;

class FloorPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('floors.view');
    }

    public function view(User $user, Floor $floor): bool
    {
        return $user->can('floors.view');
    }

    public function create(User $user): bool
    {
        return $user->can('floors.create');
    }

    public function update(User $user, Floor $floor): bool
    {
        return $user->can('floors.update');
    }

    public function delete(User $user, Floor $floor): bool
    {
        return $user->can('floors.delete');
    }
}
