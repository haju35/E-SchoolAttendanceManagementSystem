<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class RoleAndAdminSeeder extends Seeder
{
    public function run()
    {
        // =====================
        // 1. CREATE PERMISSIONS
        // =====================
        $permissions = [
            'manage_users',
            'manage_students',
            'manage_teachers',
            'manage_families',
            'mark_attendance',
            'view_attendance',
            'view_reports',
            'system_config',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'api'
            ]);
        }

        // =====================
        // 2. CREATE ROLES
        // =====================
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher', 'guard_name' => 'api']);
        $studentRole = Role::firstOrCreate(['name' => 'student', 'guard_name' => 'api']);
        $familyRole = Role::firstOrCreate(['name' => 'family', 'guard_name' => 'api']);

        // =====================
        // 3. ASSIGN PERMISSIONS TO ROLES
        // =====================

        // ADMIN → ALL PERMISSIONS
        $adminRole->syncPermissions(Permission::all());

        // TEACHER
        $teacherRole->syncPermissions([
            'mark_attendance',
            'view_attendance',
            'view_reports'
        ]);

        // STUDENT
        $studentRole->syncPermissions([
            'view_attendance'
        ]);

        // FAMILY
        $familyRole->syncPermissions([
            'view_attendance'
        ]);

        // =====================
        // 4. CREATE ADMIN USER
        // =====================
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        if (!$admin->hasRole('admin')) {
            $admin->assignRole($adminRole);
        }

        $this->command->info('Roles, Permissions & Admin created successfully!');
    }
}