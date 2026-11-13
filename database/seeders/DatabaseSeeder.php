<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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

        $role = Role::firstOrCreate([
            'name' => 'super admin',
            'guard_name' => 'web',
        ]);

        $user = User::query()->firstOrCreate(
            ['email' => 'text@example.com'],
            [
                'name' => 'Test User',
                'username' => 'test-user',
            ],
        );

        if ($user->username !== 'test-user') {
            $user->username = 'test-user';
            $user->save();
        }

        $user->assignRole($role);
    }
}
