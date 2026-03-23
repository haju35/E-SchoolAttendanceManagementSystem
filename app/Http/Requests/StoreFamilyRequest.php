<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreFamilyRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules()
    {
        return [
            // User table fields
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'address' => 'nullable|string',
            'profile_photo' => 'nullable|image|max:2048',
            
            // Family table fields
            'occupation' => 'nullable|string|max:255',
            'emergency_contact' => 'nullable|string|max:20',
            
            // Student relationships
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ];
    }
}