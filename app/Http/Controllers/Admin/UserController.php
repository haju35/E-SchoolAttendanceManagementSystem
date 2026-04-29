<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = User::query();
            
            // Filter by role using Spatie
            if ($request->has('role') && $request->role) {
                $query->role($request->role);
            }
            
            // Search by name or email
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }
            
            // Filter by status
            if (!is_null($request->status)) {
                $query->where('is_active', $request->status);
            }
            
            $users = $query->paginate($request->get('per_page', 10));
            // Load roles for each user
            $users->getCollection()->transform(function ($user) {
                $user->role = $user->roles->first()?->name ?? 'no role';
                return $user;
            });
            
            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->role = $user->roles->first()?->name;
            
            return response()->json([
                'success' => true,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
    
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'role' => 'required|exists:roles,name',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $password = Str::random(10);
            
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($password),
                'phone' => $request->phone,
                'address' => $request->address,
                'is_active' => true
            ]);
            
            // Assign role using Spatie
            $user->assignRole($request->role);

            $this->sendWelcomeEmail($user, $password);
            
            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function sendWelcomeEmail(User $user, $password)
    {
      $data = [
          'name' => $user->name,
          'email' => $user->email,
          'password' => $password,
          'role' => $user->roles->first()?->name,
          'login_url' => url('/login')
      ];

      Mail::send('emails.welcome', $data, function($message) use ($user) {
          $message->to($user->email, $user->name)
                  ->subject('Welcome to E-School Attendance Management System');
      });
    }
    
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
                'password' => 'nullable|string|min:6',
                'role' => 'sometimes|exists:roles,name',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $data = $request->except('password', 'role');
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }
            
            $user->update($data);
            
            // Update role if provided
            if ($request->has('role')) {
                $user->syncRoles([$request->role]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            if ($user->id == auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 403);
            }
            
            $user->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
    
    public function toggleStatus($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->is_active = !$user->is_active;
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'data' => ['is_active' => $user->is_active]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
    
    public function getRoles()
    {
        $roles = Role::where('guard_name', 'api')->get(['name', 'id']);
        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    public function resetPassword($id)
    {
        try {
            $user = User::findOrFail($id);
            $newPassword = Str::random(10);
            $user->password = Hash::make($newPassword);
            $user->save();
            
            // Send email with new password
            $this->sendPasswordResetEmail($user, $newPassword);
            
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully. New password sent to email.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function sendPasswordResetEmail($user, $newPassword)
    {
        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => $newPassword,
            'login_url' => url('/login')
        ];
        
        Mail::send('emails.password-reset', $data, function ($message) use ($user) {
            $message->to($user->email, $user->name)
                    ->subject('Your Password Has Been Reset');
        });
    }
}