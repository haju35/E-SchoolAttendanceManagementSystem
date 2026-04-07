<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\StudentCredentialsMail;

class BulkImportController extends Controller
{
    public function students(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240'
        ]);

        $file = $request->file('file');
        $data = $this->parseCsv($file);

        $results = [
            'total' => count($data),
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        DB::beginTransaction();

        try {
            foreach ($data as $index => $row) {
                $rowNumber = $index + 2; // +2 for header row and 1-index

                // Generate password if not provided
                $password = isset($row['password']) && !empty($row['password']) 
                    ? $row['password'] 
                    : Str::random(10);

                // Validate row data
                $validator = Validator::make($row, [
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email',
                    'admission_number' => 'required|string|unique:students,admission_number',
                    'date_of_birth' => 'required|date',
                    'gender' => 'required|in:male,female,other',
                    'current_class_id' => 'required|exists:class_rooms,id',
                    'current_section_id' => 'required|exists:sections,id',
                    'admission_date' => 'required|date',
                ]);

                if ($validator->fails()) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'row' => $rowNumber,
                        'errors' => $validator->errors()->all()
                    ];
                    continue;
                }

                // Create user
                $user = User::create([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => Hash::make($password),
                    'role' => 'student',
                    'phone' => $row['phone'] ?? null,
                    'address' => $row['address'] ?? null,
                    'is_active' => true
                ]);

                // Create student
                $student = Student::create([
                    'user_id' => $user->id,
                    'admission_number' => $row['admission_number'],
                    'roll_number' => $row['roll_number'] ?? null,
                    'date_of_birth' => $row['date_of_birth'],
                    'gender' => $row['gender'],
                    'current_class_id' => $row['current_class_id'],
                    'current_section_id' => $row['current_section_id'],
                    'admission_date' => $row['admission_date'],
                    'status' => $row['status'] ?? 'active'
                ]);

                // Send email with credentials
                try {
                    Mail::to($user->email)->send(new StudentCredentialsMail(
                        $user->name,
                        $user->email,
                        $password
                    ));
                } catch (\Exception $mailError) {
                    // Log email error but don't fail the import
                    \Log::error('Failed to send credentials email to: ' . $user->email, [
                        'error' => $mailError->getMessage()
                    ]);
                    $results['errors'][] = [
                        'row' => $rowNumber,
                        'errors' => ["User created but email sending failed: " . $mailError->getMessage()]
                    ];
                }

                $results['success']++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Bulk import completed. Success: {$results['success']}, Failed: {$results['failed']}",
                'data' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Import failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function studentTemplate()
    {
        $headers = [
            'name',
            'email',
            'admission_number',
            'roll_number',
            'date_of_birth',
            'gender',
            'current_class_id',
            'current_section_id',
            'admission_date',
            'phone',
            'address',
            'status',
            'password' // Optional - if empty, system will generate
        ];

        $filename = "student_import_template.csv";
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        fputcsv($handle, $headers);
        
        // Add sample row
        fputcsv($handle, [
            'John Doe',
            'john.doe@example.com',
            'ADM2024001',
            '01',
            '2010-01-01',
            'male',
            '1',
            '1',
            '2024-01-15',
            '1234567890',
            '123 Main St',
            'active',
            '' // Leave empty to auto-generate
        ]);
        
        fclose($handle);
        exit;
    }

    private function parseCsv($file)
    {
        $path = $file->getRealPath();
        $data = [];
        $rowNumber = 1; // Start after header
        
        if (($handle = fopen($path, 'r')) !== false) {
            $headers = fgetcsv($handle);
            // Clean headers (remove BOM and trim)
            $headers = array_map(function($header) {
                return trim($header, "\xEF\xBB\xBF");
            }, $headers);
            
            $headerCount = count($headers);
            
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $rowCount = count($row);
                
                // Check for mismatch
                if ($rowCount !== $headerCount) {
                    \Log::warning("CSV row {$rowNumber} has column mismatch", [
                        'headers' => $headerCount,
                        'row_columns' => $rowCount,
                        'file' => $file->getClientOriginalName()
                    ]);
                    
                    if ($rowCount < $headerCount) {
                        $row = array_pad($row, $headerCount, null);
                    } else {
                        $row = array_slice($row, 0, $headerCount);
                    }
                }
                
                // Safe to combine now
                $data[] = array_combine($headers, $row);
            }
            
            fclose($handle);
        }
        
        return $data;
    }
}