<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            [
                'email' => 'quezonprovince.activities@gmail.com',
            ],
            [
                'name' => 'Administrator',
                'password' => Hash::make('Overcomers123!'),
                'role' => User::ROLE_ADMIN,
            ]
        );
    }
}
