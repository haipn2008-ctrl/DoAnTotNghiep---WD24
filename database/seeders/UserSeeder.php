<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 1
        ]);
<<<<<<< Updated upstream
=======

        User::create([
            'name' => 'Admin 2',
            'email' => 'admin2@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 1
        ]);

        User::create([
            'name' => 'Admin 3',
            'email' => 'admin3@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 1
        ]);

        User::create([
            'name' => 'Admin 4',
            'email' => 'admin4@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 1
        ]);

        User::create([
            'name' => 'Admin 5',
            'email' => 'admin5@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 1
        ]);

        User::create([
            'name' => 'Tenant User',
            'email' => 'user@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 2
        ]);

        User::create([
            'name' => 'User 2',
            'email' => 'user2@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 2
        ]);

        User::create([
            'name' => 'User 3',
            'email' => 'user3@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 2
        ]);

        User::create([
            'name' => 'User 4',
            'email' => 'user4@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 2
        ]);

        User::create([
            'name' => 'User 5',
            'email' => 'user5@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 2
        ]);

        User::create([
            'name' => 'User 6',
            'email' => 'user6@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 2
        ]);

        User::create([
            'name' => 'User 7',
            'email' => 'user7@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 2
        ]);

        User::create([
            'name' => 'User 8',
            'email' => 'user8@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 2
        ]);

        User::create([
            'name' => 'User 9',
            'email' => 'user9@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 2
        ]);

        User::create([
            'name' => 'User 10',
            'email' => 'user10@gmail.com',
            'password' => Hash::make('123456'),
            'role_id' => 2
        ]);
>>>>>>> Stashed changes
    }
}
