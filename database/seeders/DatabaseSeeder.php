<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // User::factory(10)->create();

        $user = User::factory()->firstOrCreate([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $role = Role::firstOrCreate([
            'name' => 'super admin',
            'guard_name' => 'web',
        ]);

        $user->assignRole($role);
    }
}
