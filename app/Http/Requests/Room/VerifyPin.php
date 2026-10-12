<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPin extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pin' => 'required|string|digits:4',
            'device_name' => 'nullable|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'pin.required' => 'PIN wajib diisi.',
            'pin.digits' => 'PIN harus 4 digit angka.',
        ];
    }
}
