<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlayerProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if user is editing their own profile or is admin
        return $this->user()->id == $this->route('player')->id || $this->user()->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'nationality' => 'nullable|string|max:100',
            'school' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:players,email,' . $this->route('player')->id,
            'phone' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'medical_notes' => 'nullable|string|max:1000',
            'parent_name' => 'nullable|string|max:255',
            'parent_email' => 'nullable|email|max:255',
            'parent_phone' => 'nullable|string|max:20',
            'jersey_number' => 'nullable|integer|between:1,99',
            'height' => 'nullable|string|max:10',
            'weight' => 'nullable|string|max:10',
            'primary_position' => 'nullable|string|max:50',
            'secondary_position' => 'nullable|string|max:50|different:primary_position',
            'third_position' => 'nullable|string|max:50|different:primary_position,secondary_position',
            'teams' => 'required|array|min:1',
            'teams.*' => 'integer|exists:teams,id',
            'profile_picture' => 'nullable|mimes:jpeg,jpg,png,gif|max:5120',
            'document_type' => 'nullable|in:id,passport,birth_certificate',
            'document_file' => 'nullable|mimes:pdf,jpeg,jpg,png|max:10240',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Player name is required.',
            'name.max' => 'Player name must not exceed 255 characters.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'email.unique' => 'This email is already used by another player.',
            'emergency_contact_phone.regex' => 'Emergency contact phone format is invalid.',
            'jersey_number.between' => 'Jersey number must be between 1 and 99.',
            'secondary_position.different' => 'Secondary position must be different from primary position.',
            'third_position.different' => 'Third position must be different from primary and secondary positions.',
            'teams.required' => 'At least one team must be selected.',
            'teams.min' => 'Please select at least one team.',
            'teams.*.exists' => 'One or more selected teams do not exist.',
            'profile_picture.max' => 'Profile picture must not exceed 5MB.',
            'profile_picture.mimes' => 'Profile picture must be a JPEG, PNG, or GIF image.',
            'document_file.max' => 'Document must not exceed 10MB.',
            'document_file.mimes' => 'Document must be a PDF or image file.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert positions to lowercase/standardized format if needed
        if ($this->filled('primary_position')) {
            $this->merge([
                'primary_position' => strtoupper($this->primary_position),
            ]);
        }

        if ($this->filled('secondary_position')) {
            $this->merge([
                'secondary_position' => strtoupper($this->secondary_position),
            ]);
        }

        if ($this->filled('third_position')) {
            $this->merge([
                'third_position' => strtoupper($this->third_position),
            ]);
        }
    }
}
