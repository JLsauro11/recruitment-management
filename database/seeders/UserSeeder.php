<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'System Administrator',
                'email' => 'admin@rs8.com',
                'password' => 'password',
                'role' => 'admin',
                'status' => 'active',
            ],
            [
                'name' => 'HR Officer',
                'email' => 'hr@rs8.com',
                'password' => 'password',
                'role' => 'hr',
                'status' => 'active',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                    'role' => $user['role'],
                    'status' => $user['status'],
                ]
            );
        }
    }
}
