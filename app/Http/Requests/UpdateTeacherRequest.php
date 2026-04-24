<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Teacher;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules()
    {
        $teacherId = $this->route('id') ?? $this->teacher;

        $teacher = Teacher::find($teacherId);
        $userId = $teacher ? $teacher->user_id : null;
        
        return [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'qualification' => 'nullable|string',
            'joining_date' => 'nullable|date_format:Y-m-d',
        ];
    }

    public function messages()
    {
        return [
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email is already registered',
            'joining_date.date_format' => 'Joining date must be in YYYY-MM-DD format',
        ];
    }
}