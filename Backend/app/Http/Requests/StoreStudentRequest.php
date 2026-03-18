<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreStudentRequest extends FormRequest
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
            
            // Student table fields
            'admission_number' => 'required|string|max:50|unique:students,admission_number',
            'roll_number' => 'nullable|string|max:20',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|in:male,female,other',
            'current_class_id' => 'required|exists:class_rooms,id',
            'current_section_id' => 'required|exists:sections,id',
            'admission_date' => 'required|date',
            'status' => 'sometimes|in:active,inactive,graduated,transferred',
            
            // Family relationship
            'family_id' => 'nullable|exists:families,id',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Student name is required',
            'email.unique' => 'This email is already registered',
            'admission_number.unique' => 'This admission number is already taken',
            'current_class_id.required' => 'Please select a class',
            'current_section_id.required' => 'Please select a section',
            'date_of_birth.before' => 'Date of birth must be in the past',
        ];
    }
}