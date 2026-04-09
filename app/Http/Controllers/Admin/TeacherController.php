<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeacherRequest;
use App\Http\Requests\UpdateTeacherRequest;
use App\Models\User;
use App\Models\Teacher;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    public function index(Request $request)
    {
        $teachers = Teacher::with('user')
            ->when($request->search, function($query, $search) {
                $query->whereHas('user', function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
            });
            })

            ->paginate(15);
        
        return response()->json([
            'success' => true,
            'data' => $teachers
        ]);
    }

    public function store(StoreTeacherRequest $request)
    {
        DB::beginTransaction();
        
        try {
            // Create User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'is_active' => true
            ]);

            $user->assignRole('teacher');
            
            // Create Teacher
            $teacher = Teacher::create([
                'user_id' => $user->id,
                'qualification' => $request->qualification,
                'joining_date' => $request->joining_date
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Teacher created successfully',
                'data' => [
                    'user' => $user,
                    'teacher' => $teacher
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $teacher = Teacher::with(['user', 'assignments.subject', 'assignments.classRoom', 'assignments.section'])
            ->find($id);
        
        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $teacher
        ]);
    }

    public function update(UpdateTeacherRequest $request, $id)
    {
        $teacher = Teacher::find($id);
        
        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }
        
        DB::beginTransaction();
        
        try {
            // Update User
            $userData = [];
            if ($request->has('name')) $userData['name'] = $request->name;
            if ($request->has('email')) $userData['email'] = $request->email;
            if ($request->has('phone')) $userData['phone'] = $request->phone;
            if ($request->has('address')) $userData['address'] = $request->address;
            
            if (!empty($userData)) {
                $teacher->user->update($userData);
            }
            
            // Update Teacher
            $teacherData = $request->only(['employee_id', 'qualification', 'joining_date']);
            if (!empty($teacherData)) {
                $teacher->update($teacherData);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Teacher updated successfully',
                'data' => $teacher->fresh('user')
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $teacher = Teacher::find($id);
        
        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found'
            ], 404);
        }
        
        DB::beginTransaction();
        
        try {
            $teacher->delete(); // user will be cascade deleted
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Teacher deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete teacher',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}