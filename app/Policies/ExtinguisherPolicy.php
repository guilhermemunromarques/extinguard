<?php

namespace App\Policies;

use App\Models\Extinguisher;
use App\Models\User;

class ExtinguisherPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('extinguishers.view');
    }

    public function view(User $user, Extinguisher $extinguisher): bool
    {
        return $user->can('extinguishers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('extinguishers.create');
    }

    public function update(User $user, Extinguisher $extinguisher): bool
    {
        return $user->can('extinguishers.update');
    }

    public function updateFields(User $user): array
    {
        if ($user->hasRole('Administrator')) {
            return [
                'serial_number',
                'placement_id',
                'extinguisher_type_id',
                'extinguisher_brand_id',
                'supplier_id',
                'capacity',
                'capacity_unit',
                'extinguishing_capacity',
                'maintenance_seal',
                'status',
                'next_refill_date',
                'next_maintenance_year',
                'notes',
            ];
        }

        if ($user->hasRole('Technician')) {
            return [
                'serial_number',
                'placement_id',
                'extinguisher_type_id',
                'extinguisher_brand_id',
                'supplier_id',
                'capacity',
                'capacity_unit',
                'extinguishing_capacity',
                'maintenance_seal',
                'status',
                'next_refill_date',
                'next_maintenance_year',
                'notes',
            ];
        }

        if ($user->hasRole('Maintenance')) {
            return [
                'maintenance_seal',
                'status',
                'next_refill_date',
                'next_maintenance_year',
                'notes',
            ];
        }

        return [];
    }
}
