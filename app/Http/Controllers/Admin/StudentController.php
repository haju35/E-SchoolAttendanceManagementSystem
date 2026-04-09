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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Mail\StudentCredentialsMail;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;

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
            $password = Str::random(10);
            
            // Create User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'address' => $request->address,
                'is_active' => true,
                'password' => Hash::make($password)
            ]);

            $user->assignRole('student');
            
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
                'admission_date' => $request->admission_date ?? now(),
                'status' => $request->status ?? 'active'
            ]);

            // Send email with credentials (don't fail if email doesn't send)
            $emailSent = false;
            try {
                Mail::to($user->email)->send(new StudentCredentialsMail(
                    $user,
                    $password
                ));
                $emailSent = true;
            } catch (\Exception $e) {
                \Log::warning('Failed to send credentials email: ' . $e->getMessage());
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => $emailSent ? 'Student created successfully. Credentials sent to email.' : 'Student created successfully. Save the password below.',
                'data' => [
                    'user' => $user,
                    'student' => $student->load(['user', 'currentClass', 'currentSection']),
                    'credentials' => [
                        'email' => $user->email,
                        'password' => $password
                    ]
                ],
                'email_sent' => $emailSent
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create student: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240'
        ]);
        
        try {
            $import = new StudentsImport();
            Excel::import($import, $request->file('file'));
            
            $results = $import->getResults();
            
            $message = "Import completed. ";
            $message .= "Successfully imported: {$results['success_count']}, ";
            $message .= "Failed: {$results['failure_count']}";
            
            if (isset($results['emails_sent']) && $results['emails_sent'] > 0) {
            $message .= " Emails sent: {$results['emails_sent']}";
            }
            
            if (!empty($results['email_errors'])) {
                $message .= " Email errors: " . count($results['email_errors']);
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'total' => $results['total_rows'],
                    'success' => $results['success_count'],
                    'failed' => $results['failure_count'],
                    'errors' => $results['failures']
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Import failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $student = Student::with([
            'user', 
            'currentClass', 
            'currentSection', 
            'family', 
            'attendances' => function($q) {
                $q->latest()->take(10);
            }
        ])->find($id);
        
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
            
            if (!empty($userData)) {
                $student->user->update($userData);
            }
            
            // Update Student
            $studentData = $request->only([
                'family_id', 
                'roll_number', 
                'current_class_id', 
                'current_section_id', 
                'status'
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

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids');
        if (!$ids) {
            $content = json_decode($request->getContent(), true);
            $ids = $content['ids'] ?? null;
        }
        
        if (!$ids || !is_array($ids) || empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'No student IDs provided'
            ], 400);
        }
        
        // Check if students exist
        $students = Student::whereIn('id', $ids)->get();
        
        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No students found to delete'
            ], 404);
        }
        
        DB::beginTransaction();
        
        try {
            $deletedCount = 0;
            
            foreach ($students as $student) {
                // IMPORTANT: Delete the user FIRST (this will cascade delete the student)
                if ($student->user_id) {
                    $user = User::find($student->user_id);
                    if ($user) {
                        $user->delete(); // This should delete the user AND cascade to student
                        $deletedCount++;
                    }
                }
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} student(s) and their user accounts deleted successfully"
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete students: ' . $e->getMessage()
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
            $userId = $student->user_id;
            // Delete student (user will be cascade deleted)
            $student->delete();

            if ($userId) {
                User::find($userId)?->delete();
            }
            
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