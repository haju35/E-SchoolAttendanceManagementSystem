<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
                'phone' => '1234567890',
                'address' => 'Admin Office'
            ]
        );

        // Create teacher user
        User::updateOrCreate(
            ['email' => 'teacher@example.com'],
            [
                'name' => 'John Teacher',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'is_active' => true,
                'phone' => '0987654321',
                'address' => 'Teacher Room'
            ]
        );

        // Create student user
        User::updateOrCreate(
            ['email' => 'student@example.com'],
            [
                'name' => 'Alice Student',
                'password' => Hash::make('password'),
                'role' => 'student',
                'is_active' => true,
                'phone' => '1122334455',
                'address' => 'Student Hostel'
            ]
        );

        // Create family user
        User::updateOrCreate(
            ['email' => 'family@example.com'],
            [
                'name' => 'Bob Parent',
                'password' => Hash::make('password'),
                'role' => 'family',
                'is_active' => true,
                'phone' => '5566778899',
                'address' => 'Family Home'
            ]
        );

        $this->command->info('✅ Users created successfully!');
    }
}