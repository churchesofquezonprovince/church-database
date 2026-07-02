<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_USER_EMAIL', 'quezonprovince.activities@gmail.com');
        $name = env('ADMIN_USER_NAME', 'Administrator');
        $password = env('ADMIN_USER_PASSWORD');

        if (blank($password)) {
            throw new RuntimeException('ADMIN_USER_PASSWORD must be set in .env before running AdminUserSeeder.');
        }

        User::updateOrCreate(
            [
                'email' => $email,
            ],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'role' => User::ROLE_ADMIN,
            ]
        );
    }
}
