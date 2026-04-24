<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Student;

class UpdateStudentRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules()
    {
        $studentId = $this->route('id') ?? $this->student;

        $student = Student::find($studentId);
        $userId = $student ? $student->user_id : null;

        return [
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', 
                Rule::unique('users')->ignore($userId, 'id')
            ],
            'phone' => ['nullable', 'string', 'max:20',
                Rule::unique('users')->ignore($userId, 'id')
            ],
            'address' => 'nullable|string',
            'profile_photo' => 'nullable|image|max:2048',
            'password' => ['nullable', 'confirmed', 'min:8'],
            
            'admission_number' => ['sometimes', 'string', 'max:50',
                Rule::unique('students')->ignore($userId, 'user_id')
            ],
            'roll_number' => 'nullable|string|max:20',
            'date_of_birth' => 'sometimes|date|before:today',
            'gender' => 'sometimes|in:male,female,other',
            'current_class_id' => 'sometimes|exists:class_rooms,id',
            'current_section_id' => 'sometimes|exists:sections,id',
            'admission_date' => 'sometimes|date',
            'status' => 'sometimes|in:active,inactive,graduated,transferred',
            'family_id' => 'nullable|exists:families,id',
        ];
    }
}