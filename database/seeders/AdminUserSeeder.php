<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AdminUser::create([
            'name' => 'Super Admin',
            'email' => 'admin@trivichain.com',
            'password' => Hash::make('password123'), // Change this in production!
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        AdminUser::create([
            'name' => 'Admin User',
            'email' => 'admin2@trivichain.com',
            'password' => Hash::make('password123'), // Change this in production!
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
