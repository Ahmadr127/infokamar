<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;

class Store extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:30|unique:rooms,code',
            'name' => 'required|string|max:255',
            'care_class' => 'required|string|max:50',
            'is_active' => 'sometimes|boolean',
            'pin' => 'nullable|string|digits:4',
            'bed' => 'nullable|string|max:30',
            'doctor_name' => 'nullable|string|max:255',
            'patient_name' => 'nullable|string|max:255',
            'patient_mrn' => 'nullable|string|max:50',
            'greeting' => 'nullable|string|max:500',
            'logo' => 'nullable|image|max:2048',
            'background' => 'nullable|image|max:4096',
            'visit_session_1' => 'nullable|string|max:50',
            'visit_session_2' => 'nullable|string|max:50',
            'visit_note' => 'nullable|string|max:1000',
            'theme_mode' => 'nullable|string|in:light,dark,auto',
            'light_start' => 'nullable|string|date_format:H:i',
            'dark_start' => 'nullable|string|date_format:H:i',
            'timezone' => 'nullable|string|max:50|timezone',
            'health_message' => 'nullable|string|max:1000',
            'visit_schedule' => 'nullable|string|max:1000',
            'announcement' => 'nullable|string|max:1000',
            'rotation_seconds' => 'nullable|integer|min:5|max:300',
        ];
    }
}
