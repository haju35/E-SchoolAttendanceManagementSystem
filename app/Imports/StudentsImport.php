<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\User;
use App\Models\Family;
use App\Models\ClassRoom;
use App\Models\Section;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StudentsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithBatchInserts, WithChunkReading, SkipsEmptyRows
{
    private $successCount = 0;
    private $failures = [];
    private $rowNumber = 0;

    public function model(array $row)
    {
        $this->rowNumber++;
        
        try {
            // Normalize column names (handle both lowercase and capitalized versions)
            $name = $row['name'] ?? $row['Name'] ?? $row['name'] ?? null;
            $email = $row['email'] ?? $row['Email'] ?? null;
            $admissionNumber = $row['admission_number'] ?? $row['Admission No'] ?? $row['admission_no'] ?? null;
            $className = $row['class'] ?? $row['Class'] ?? $row['class'] ?? null;
            $sectionName = $row['section'] ?? $row['Section'] ?? $row['section'] ?? null;
            $dateOfBirth = $row['date_of_birth'] ?? $row['Date of Birth'] ?? $row['dob'] ?? null;
            $gender = $row['gender'] ?? $row['Gender'] ?? null;
            $rollNumber = $row['roll_number'] ?? $row['Roll Number'] ?? $row['roll_no'] ?? null;
            $phone = $row['phone'] ?? $row['Phone'] ?? null;
            $address = $row['address'] ?? $row['Address'] ?? null;
            $admissionDate = $row['admission_date'] ?? $row['Admission Date'] ?? null;
            $status = $row['status'] ?? $row['Status'] ?? 'active';
            
            // Skip if required fields are missing
            if (empty($name) || empty($email) || empty($admissionNumber)) {
                $this->failures[] = [
                    'row' => $this->rowNumber + 1,
                    'errors' => ['Missing required fields: Name, Email, or Admission Number']
                ];
                return null;
            }
            
            // Check if email already exists
            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                $this->failures[] = [
                    'row' => $this->rowNumber + 1,
                    'errors' => ["Email '{$email}' already exists in the system"]
                ];
                return null;
            }
            
            // Check if admission number already exists
            $existingStudent = Student::where('admission_number', $admissionNumber)->first();
            if ($existingStudent) {
                $this->failures[] = [
                    'row' => $this->rowNumber + 1,
                    'errors' => ["Admission number '{$admissionNumber}' already exists"]
                ];
                return null;
            }
            
            // Generate random password
            $password = Str::random(10);
            
            // Format date of birth
            $formattedDateOfBirth = null;
            if (!empty($dateOfBirth)) {
                try {
                    $formattedDateOfBirth = Carbon::parse($dateOfBirth)->format('Y-m-d');
                } catch (\Exception $e) {
                    $formattedDateOfBirth = '2000-01-01';
                }
            } else {
                $formattedDateOfBirth = '2000-01-01';
            }
            
            // Format admission date
            $formattedAdmissionDate = null;
            if (!empty($admissionDate)) {
                try {
                    $formattedAdmissionDate = Carbon::parse($admissionDate)->format('Y-m-d');
                } catch (\Exception $e) {
                    $formattedAdmissionDate = now()->format('Y-m-d');
                }
            } else {
                $formattedAdmissionDate = now()->format('Y-m-d');
            }
            
            // Create user account
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'role' => 'student',
                'phone' => $phone ? (string)$phone : null,
                'address' => $address,
                'is_active' => true,
                'password' => Hash::make($password)
            ]);

            // Get class ID if provided
            $classId = null;
            if (!empty($className)) {
                $class = ClassRoom::where('name', $className)->first();
                $classId = $class ? $class->id : null;
            }

            // Get section ID if provided
            $sectionId = null;
            if (!empty($sectionName)) {
                $section = Section::where('name', $sectionName)->first();
                $sectionId = $section ? $section->id : null;
            }

            // Create student
            Student::create([
                'user_id' => $user->id,
                'family_id' => null,
                'admission_number' => $admissionNumber,
                'roll_number' => $rollNumber ? (string)$rollNumber : null,
                'date_of_birth' => $formattedDateOfBirth,
                'gender' => $gender ?? 'female',
                'current_class_id' => $classId,
                'current_section_id' => $sectionId,
                'admission_date' => $formattedAdmissionDate,
                'status' => $status,
            ]);

            $this->successCount++;
            
            return null;
            
        } catch (\Exception $e) {
            Log::error('Error importing student row ' . ($this->rowNumber + 1) . ': ' . $e->getMessage());
            $this->failures[] = [
                'row' => $this->rowNumber + 1,
                'errors' => [$e->getMessage()]
            ];
            return null;
        }
    }

    public function rules(): array
    {
        // Make all rules optional since we handle validation manually
        return [
            '*.student_name' => 'nullable',
            '*.Name' => 'nullable',
            '*.email' => 'nullable',
            '*.Email' => 'nullable',
            '*.admission_number' => 'nullable',
            '*.Admission No' => 'nullable',
        ];
    }

    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->failures[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
            ];
        }
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getFailures(): array
    {
        return $this->failures;
    }

    public function getResults(): array
    {
        $totalRows = $this->successCount + count($this->failures);
        
        return [
            'total_rows' => $totalRows,
            'success_count' => $this->successCount,
            'failure_count' => count($this->failures),
            'failures' => $this->failures
        ];
    }
}