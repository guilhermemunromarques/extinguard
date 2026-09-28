<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Extinguisher;
use App\Models\ExtinguisherBrand;
use App\Models\ExtinguisherSupplier;
use App\Models\ExtinguisherType;
use App\Models\Floor;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = [
            'Administrator' => [
                'dashboard.view',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'users.manage',
                'buildings.view',
                'buildings.create',
                'buildings.update',
                'buildings.delete',
                'floors.view',
                'floors.create',
                'floors.update',
                'floors.delete',
                'placements.view',
                'placements.create',
                'placements.update',
                'placements.delete',
                'placements.assign_extinguisher',
                'blueprints.view',
                'blueprints.create',
                'blueprints.update',
                'blueprints.delete',
                'extinguishers.view',
                'extinguishers.create',
                'extinguishers.update',
                'extinguishers.delete',
                'extinguishers.batch_create',
                'inspections.view',
                'inspections.create',
                'inspections.update',
                'inspections.approve',
                'qr_codes.view',
                'qr_codes.generate',
                'qr_codes.print',
                'reports.view',
                'documents.view',
                'audit.view',
                'sync.use',
            ],
            'Technician' => [
                'dashboard.view',
                'users.view',
                'buildings.view',
                'floors.view',
                'placements.view',
                'placements.assign_extinguisher',
                'blueprints.view',
                'extinguishers.view',
                'extinguishers.update',
                'extinguishers.create',
                'inspections.view',
                'inspections.create',
                'inspections.update',
                'qr_codes.view',
                'qr_codes.generate',
                'qr_codes.print',
                'reports.view',
                'documents.view',
                'sync.use',
            ],
            'Maintenance' => [
                'dashboard.view',
                'buildings.view',
                'floors.view',
                'placements.view',
                'extinguishers.view',
                'extinguishers.update',
                'inspections.view',
                'inspections.create',
                'inspections.update',
                'documents.view',
                'sync.use',
            ],
            'Viewer' => [
                'dashboard.view',
                'buildings.view',
                'floors.view',
                'placements.view',
                'blueprints.view',
                'extinguishers.view',
                'inspections.view',
                'inspections.create',
                'inspections.update',
                'qr_codes.view',
                'reports.view',
                'documents.view',
                'audit.view',
                'sync.use',
            ],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            foreach ($permissions as $permission) {
                Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
                $role->givePermissionTo($permission);
            }
        }

        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles('Administrator');

        foreach (['admin' => 'Administrator', 'technician' => 'Technician', 'maintenance' => 'Maintenance', 'viewer' => 'Viewer'] as $username => $roleName) {
            $roleUser = User::firstOrCreate(
                ['email' => "{$username}@example.com"],
                [
                    'name' => ucfirst($username).' User',
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            $roleUser->syncRoles($roleName);
        }

        // Seed domain data
        $this->seedDomainData();
    }

    private function seedDomainData(): void
    {
        // Create extinguisher types, brands and suppliers
        $types = [
            'PQS-ABC',
            'PQS-BC',
            'CO2',
            'AP',
            'Espuma',
            'FE-36',
        ];

        $brands = [
            'Mocelin',
            'Metalcasty',
            'Resil',
            'Extinpel',
            'Lifeguard',
        ];

        $suppliers = [
            'Bucka',
            'Prevale',
            'Sul Brasil',
        ];

        foreach ($types as $type) {
            ExtinguisherType::firstOrCreate(['name' => $type]);
        }

        foreach ($brands as $brand) {
            ExtinguisherBrand::firstOrCreate(['name' => $brand]);
        }

        foreach ($suppliers as $supplier) {
            ExtinguisherSupplier::firstOrCreate(['name' => $supplier]);
        }

        // Create sample buildings with floors, placements and extinguishers
        $building = Building::factory()
            ->has(
                Floor::factory()
                    ->count(3)
                    ->has(Placement::factory()->count(8), 'placements')
            )
            ->create();

        // Assign some extinguishers to placements
        $placements = $building->floors[0]->placements;
        $types = ExtinguisherType::all();
        $brands = ExtinguisherBrand::all();
        $suppliers = ExtinguisherSupplier::all();

        foreach ($placements->take(5) as $placement) {
            $extinguisher = Extinguisher::factory()
                ->for($types->random(), 'type')
                ->for($brands->random(), 'brand')
                ->for($suppliers->random(), 'supplier')
                ->create(['placement_id' => $placement->id]);
        }

        // Create some reserve extinguishers
        Extinguisher::factory()
            ->count(3)
            ->for($types->random(), 'type')
            ->for($brands->random(), 'brand')
            ->for($suppliers->random(), 'supplier')
            ->create();
    }
}
