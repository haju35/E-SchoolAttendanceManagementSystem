<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFamilyRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules()
    {
        $familyId = $this->route('id') ?? $this->family;
        
        return [
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($familyId, 'id')
            ],
            'phone' => ['nullable', 'string', 'max:20',
                Rule::unique('users')->ignore($familyId, 'id')
            ],
            'address' => 'nullable|string',
            'profile_photo' => 'nullable|image|max:2048',
            'password' => ['nullable', 'confirmed', 'min:8'],
            
            'occupation' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:20',
            
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ];
    }
}