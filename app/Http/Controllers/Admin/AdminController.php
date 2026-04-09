<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    // Create admin user (your existing method)
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
            'role' => 'admin',
            'is_active' => 1,
        ]);

        // Create role if not exists
        Role::firstOrCreate(['name' => 'admin']);
        $user->assignRole('admin');

        return response()->json([
            'success' => true,
            'message' => 'Admin created successfully',
            'user' => $user
        ]);
    }

    // NEW: Get all users (with filters)
    public function index(Request $request)
    {
        try {
            $query = User::query();
            
            // Filter by role
            if ($request->role) {
                $query->where('role', $request->role);
            }
            
            // Filter by status
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
            
            // Search by name or email
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%");
                });
            }
            
            $users = $query->latest()->paginate(20);
            
            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch users: ' . $e->getMessage()
            ], 500);
        }
    }

    // NEW: Create any user (teacher, student, family)
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
                'role' => 'required|in:teacher,student,family',
                'phone' => 'nullable|string',
                'address' => 'nullable|string'
            ]);
            
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'phone' => $request->phone,
                'address' => $request->address,
                'is_active' => true
            ]);
            
            // Assign Spatie role
            $role = Role::firstOrCreate(['name' => $request->role]);
            $user->assignRole($role);
            
            return response()->json([
                'success' => true,
                'message' => ucfirst($request->role) . ' created successfully',
                'data' => $user
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage()
            ], 500);
        }
    }

    // NEW: Reset user password
    public function resetPassword(Request $request, $id)
    {
        try {
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
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password: ' . $e->getMessage()
            ], 500);
        }
    }

    // NEW: Toggle user active/inactive status
    public function toggleStatus($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent deactivating yourself
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
                'message' => $user->is_active ? 'User activated successfully' : 'User deactivated successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update user status: ' . $e->getMessage()
            ], 500);
        }
    }

    // NEW: Delete user
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent deleting yourself
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete your own account'
                ], 400);
            }
            
            // Prevent deleting the last admin
            if ($user->role === 'admin') {
                $adminCount = User::where('role', 'admin')->count();
                if ($adminCount <= 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete the last admin user'
                    ], 400);
                }
            }
            
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user: ' . $e->getMessage()
            ], 500);
        }
    }
}