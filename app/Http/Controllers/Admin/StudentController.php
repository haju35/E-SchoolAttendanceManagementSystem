<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStudentRequest;
use App\Http\Requests\UpdateStudentRequest;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::with([
            'user',
            'currentClass:id,name',
            'currentSection:id,name'
        ])
        ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    public function store(StoreStudentRequest $request)
    {
        DB::beginTransaction();
        
        try {
            // Create User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'student',
                'phone' => $request->phone,
                'address' => $request->address,
                'is_active' => true
            ]);
            
            // Create Student
            $student = Student::create([
                'user_id' => $user->id,
                'family_id' => $request->family_id,
                'admission_number' => $request->admission_number,
                'roll_number' => $request->roll_number,
                'date_of_birth' => $request->date_of_birth,
                'gender' => $request->gender,
                'current_class_id' => $request->current_class_id,
                'current_section_id' => $request->current_section_id,
                'admission_date' => $request->admission_date,
                'status' => $request->status ?? 'active'
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Student created successfully',
                'data' => [
                    'user' => $user,
                    'student' => $student->load(['user', 'currentClass', 'currentSection'])
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $student = Student::with(['user', 'currentClass', 'currentSection', 'family', 'attendances' => function($q) {
            $q->latest()->take(10);
        }])->find($id);
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }

    public function update(UpdateStudentRequest $request, $id)
    {
        $student = Student::find($id);
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
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
            if ($request->has('password')) $userData['password'] = Hash::make($request->password);
            
            if (!empty($userData)) {
                $student->user->update($userData);
            }
            
            // Update Student
            $studentData = $request->only([
                'family_id', 'roll_number', 'current_class_id', 
                'current_section_id', 'status'
            ]);
            
            if (!empty($studentData)) {
                $student->update($studentData);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Student updated successfully',
                'data' => $student->fresh(['user', 'currentClass', 'currentSection'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update student',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $student = Student::find($id);
        
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found'
            ], 404);
        }
        
        DB::beginTransaction();
        
        try {
            // Delete student (user will be cascade deleted)
            $student->delete();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Student deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete student',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}