<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\RoleRegistry;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'gillyimo2009@gmail.com'],
            [
                'name' => 'GILBERT ISIDORY',
                'password' => bcrypt('bengah1318'),
                'email_verified_at' => now(),
                'status' => 'active',
                'timezone' => 'UTC',
                'locale' => 'en',
            ],
        );

        $superAdmin->assignRole(RoleRegistry::SUPER_ADMIN);
    }
}
