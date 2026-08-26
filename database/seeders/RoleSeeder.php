<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::updateOrCreate(['id' => 1], ['role_name' => 'Admin']);
        Role::updateOrCreate(['id' => 2], ['role_name' => 'User']);

        $unsupportedRoleIds = Role::query()
            ->whereNotIn('role_name', ['Admin', 'User'])
            ->pluck('id');

        User::query()->whereIn('role_id', $unsupportedRoleIds)->update(['role_id' => User::ROLE_USER]);
        Role::query()->whereIn('id', $unsupportedRoleIds)->delete();
    }
}
