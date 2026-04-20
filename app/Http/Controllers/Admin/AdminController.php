<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminController extends Controller
{
    // =========================
    // CREATE ADMIN
    // =========================
    public function createAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        Role::firstOrCreate(['name' => 'admin']);
        $user->assignRole('admin');

        return response()->json([
            'success' => true,
            'message' => 'Admin created successfully',
            'user' => $user
        ]);
    }

    // =========================
    // GET USERS
    // =========================
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->role) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->status === 'active') {
            $query->where('is_active', true);
        } elseif ($request->status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->latest()->paginate(20)
        ]);
    }

    // =========================
    // CREATE USER
    // =========================
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'address' => $request->address,
            'is_active' => true
        ]);

        // ✅ FIX: define role properly
        $user->assignRole($request->role);

        return response()->json([
            'success' => true,
            'message' => ucfirst($request->role) . ' created successfully',
            'data' => $user
        ]);
    }

    // =========================
    // RESET PASSWORD
    // =========================
    public function resetPassword(Request $request, $id)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed'
        ]);

        $user = User::findOrFail($id);
        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully'
        ]);
    }

    // =========================
    // TOGGLE STATUS
    // =========================
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot change your own status'
            ], 400);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
    }

    // =========================
    // DELETE USER
    // =========================
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete yourself'
            ], 400);
        }

        if ($user->hasRole('admin')) {
            $adminCount = User::role('admin')->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete last admin'
                ], 400);
            }
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'api'
        ]);

        return response()->json([
            'message' => 'Role created successfully',
            'role' => $role
        ]);
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id,
        ]);

        $role = Role::findById($id);

        $role->update([
            'name' => $request->name,
        ]);

        return response()->json([
            'message' => 'Role updated successfully',
            'role' => $role
        ]);
    }

    // =========================
    // ROLES + PERMISSIONS UI SUPPORT
    // =========================

    public function getRoles()
    {
        return response()->json(
            Role::with('permissions')->get()
        );
    }

    public function getPermissions()
    {
        return response()->json(
            Permission::all()
        );
    }

    public function rolesPermissions()
    {
        return response()->json([
            'roles' => Role::with('permissions')->get(),
            'permissions' => Permission::all()
        ]);
    }

    // =========================
    // ASSIGN ROLE + PERMISSIONS TO USER
    // =========================
    public function assignRolePermissions(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|string',
            'permissions' => 'array'
        ]);

        $user = User::findOrFail($id);

        $user->syncRoles([$request->role]);
        $user->syncPermissions($request->permissions ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Role and permissions updated successfully'
        ]);
    }

    public function assignPermissionsToRole(Request $request, $id)
    {
        try {
            $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'integer|exists:permissions,id'
            ]);

            $role = Role::findById($id, 'api');

            if (!$role) {
                return response()->json([
                    'message' => 'Role not found'
                ], 404);
            }

            // CLEAN INPUT
            $permissionIds = collect($request->permissions)
                ->filter()
                ->map(fn($p) => (int) $p)
                ->values()
                ->toArray();

            // IMPORTANT: allow empty permissions (remove all)
            if (empty($permissionIds)) {
                $role->syncPermissions([]);
                
                return response()->json([
                    'message' => 'All permissions removed',
                    'role' => $role->load('permissions')
                ]);
            }

            // ✅ USE CLEAN DATA HERE
            $permissions = Permission::whereIn('id', $permissionIds)->get();

            $role->syncPermissions($permissions);

            return response()->json([
                'message' => 'Permissions assigned successfully',
                'role' => $role->load('permissions')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Remove all permissions from a role
    public function removeAllPermissions($id)
    {
        try {
            $role = Role::findById($id, 'api');
            $role->syncPermissions([]);
            
            return response()->json([
                'message' => 'All permissions removed successfully',
                'role' => $role->load('permissions')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove permissions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // deleting roles
    public function deleteRole($id)
    {
        try {
            $role = Role::findById($id, 'api');
            
            // Prevent deleting critical system roles
            $protectedRoles = ['admin', 'superadmin'];
            if (in_array($role->name, $protectedRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete protected system role: ' . $role->name
                ], 400);
            }
            
            // Check if role has users assigned
            $usersWithRole = User::role($role->name)->count();
            if ($usersWithRole > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role because it has ' . $usersWithRole . ' user(s) assigned. Please reassign users first.'
                ], 400);
            }
            
            $role->delete();
            
            // Clear permission cache
            app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            
            return response()->json([
                'success' => true,
                'message' => 'Role "' . $role->name . '" deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role: ' . $e->getMessage()
            ], 500);
        }
    }

}