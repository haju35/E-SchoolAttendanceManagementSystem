<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ========== DEFINE ALL PERMISSIONS ==========
        $permissions = [
            // Dashboard
            'view dashboard',
            
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Role & Permission Management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',
            'manage permissions',
            
            // Student Management
            'view students',
            'create students',
            'edit students',
            'delete students',
            'import students',
            'export students',
            
            // Teacher Management
            'view teachers',
            'create teachers',
            'edit teachers',
            'delete teachers',
            
            // Family Management
            'view families',
            'create families',
            'edit families',
            'delete families',
            
            // Class Management
            'view classes',
            'create classes',
            'edit classes',
            'delete classes',
            
            // Section Management
            'view sections',
            'create sections',
            'edit sections',
            'delete sections',
            
            // Subject Management
            'view subjects',
            'create subjects',
            'edit subjects',
            'delete subjects',
            
            // Attendance Management
            'view attendance',
            'mark attendance',
            'edit attendance',
            'delete attendance',
            
            // Teacher Assignment
            'view teacher assignments',
            'create teacher assignments',
            'edit teacher assignments',
            'delete teacher assignments',
            
            // Reports
            'view reports',
            'generate reports',
            
            // Academic Management
            'manage academic years',
            'manage terms',
            
            // System Settings
            'manage settings',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api'
            ]);
        }

        // ========== CREATE OR UPDATE ROLES ==========
        
        // Admin Role - gets ALL permissions
        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'api'
        ]);
        $adminRole->syncPermissions(Permission::all());
        
        // Teacher Role
        $teacherRole = Role::firstOrCreate([
            'name' => 'teacher',
            'guard_name' => 'api'
        ]);
        $teacherRole->syncPermissions([
            'view dashboard',
            'view subjects',
            'view attendance',
            'mark attendance',
            'edit attendance',
            'view students',
            'view classes',
            'view reports',
            'view teacher assignments',
        ]);
        
        // Student Role
        $studentRole = Role::firstOrCreate([
            'name' => 'student',
            'guard_name' => 'api'
        ]);
        $studentRole->syncPermissions([
            'view dashboard',
            'view attendance',
            'view reports',
        ]);
        
        // Family Role
        $familyRole = Role::firstOrCreate([
            'name' => 'family',
            'guard_name' => 'api'
        ]);
        $familyRole->syncPermissions([
            'view dashboard',
            'view students',
            'view attendance',
        ]);
        
        // ========== CREATE OR UPDATE DEFAULT ADMIN USER ==========
        $admin = User::firstOrCreate(
            ['email' => 'admin@school.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
        
        // ========== CREATE OR UPDATE TEST TEACHER ==========
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@school.com'],
            [
                'name' => 'Test Teacher',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );
        if (!$teacher->hasRole('teacher')) {
            $teacher->assignRole('teacher');
        }
        
        // ========== CREATE OR UPDATE TEST STUDENT ==========
        $student = User::firstOrCreate(
            ['email' => 'student@school.com'],
            [
                'name' => 'Test Student',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );
        if (!$student->hasRole('student')) {
            $student->assignRole('student');
        }
        
        // ========== CREATE OR UPDATE TEST FAMILY ==========
        $family = User::firstOrCreate(
            ['email' => 'family@school.com'],
            [
                'name' => 'Test Family',
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]
        );
        if (!$family->hasRole('family')) {
            $family->assignRole('family');
        }
        
        $this->command->info('====================================');
        $this->command->info('Permissions and Roles seeded successfully!');
        $this->command->info('====================================');
        $this->command->info('Admin Email: admin@school.com');
        $this->command->info('Admin Password: password123');
        $this->command->info('------------------------------------');
        $this->command->info('Teacher Email: teacher@school.com');
        $this->command->info('Teacher Password: password123');
        $this->command->info('------------------------------------');
        $this->command->info('Student Email: student@school.com');
        $this->command->info('Student Password: password123');
        $this->command->info('------------------------------------');
        $this->command->info('Family Email: family@school.com');
        $this->command->info('Family Password: password123');
        $this->command->info('====================================');
    }
}