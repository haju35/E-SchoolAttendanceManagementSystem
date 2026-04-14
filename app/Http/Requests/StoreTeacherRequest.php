<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() !== null;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'qualification' => 'nullable|string',
            'joining_date' => 'nullable|date_format:Y-m-d',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Teacher name is required',
            'email.required' => 'Email address is required',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email is already registered',
            'joining_date.date_format' => 'Joining date must be in YYYY-MM-DD format',
        ];
    }
}