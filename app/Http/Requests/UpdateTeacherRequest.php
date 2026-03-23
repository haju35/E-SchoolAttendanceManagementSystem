<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules()
    {
        $teacherId = $this->route('id') ?? $this->teacher;
        
        return [
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($teacherId, 'id')
            ],
            'phone' => ['nullable', 'string', 'max:20',
                Rule::unique('users')->ignore($teacherId, 'id')
            ],
            'address' => 'nullable|string',
            'profile_photo' => 'nullable|image|max:2048',
            'password' => ['nullable', 'confirmed', 'min:8'],
            
            'employee_id' => ['sometimes', 'string', 'max:50',
                Rule::unique('teachers')->ignore($teacherId, 'user_id')
            ],
            'qualification' => 'nullable|string',
            'joining_date' => 'sometimes|date',
        ];
    }
}