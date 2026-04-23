<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Family;

class UpdateFamilyRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules()
    {
        $familyId = $this->route('id') ?? $this->family;

        $family =Family::find($familyId);
        $userId = $family ? $family->user_id : null;
        
        return [
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId)
            ],
            'phone' => ['nullable', 'string', 'max:20',
                Rule::unique('users', 'phone')->ignore($userId)
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