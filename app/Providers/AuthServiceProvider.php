<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerPolicies();
        
        // Define scopes based on your project requirements
        Passport::tokensCan([
            // Student scopes
            'student:view-attendance' => 'View personal attendance records',
            'student:view-profile' => 'View personal profile',
            'student:update-profile' => 'Update personal profile',
            
            // Teacher scopes
            'teacher:mark-attendance' => 'Mark student attendance',
            'teacher:edit-attendance' => 'Edit attendance records',
            'teacher:view-class' => 'View assigned classes',
            'teacher:view-reports' => 'View class reports',
            
            // family scopes
            'family:view-children' => 'View children information',
            'family:view-child-attendance' => 'View children attendance',
            'family:receive-notifications' => 'Receive notifications',
            
            // Admin scopes
            'admin:manage-users' => 'Manage all users',
            'admin:manage-students' => 'Manage students',
            'admin:manage-teachers' => 'Manage teachers',
            'admin:manage-classes' => 'Manage classes and subjects',
            'admin:view-all-attendance' => 'View all attendance records',
            'admin:generate-reports' => 'Generate system reports',
            'admin:configure-system' => 'Configure system settings',
        ]);
    }
}