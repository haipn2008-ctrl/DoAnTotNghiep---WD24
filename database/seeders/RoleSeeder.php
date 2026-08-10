<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(['id' => 1], ['role_name' => 'Admin']);
        Role::updateOrCreate(['id' => 2], ['role_name' => 'User']);

        // Deliberately unsupported role used to verify fail-closed authorization.
        Role::firstOrCreate(['role_name' => 'Auditor']);
    }
}
