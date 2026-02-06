<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public upload
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array'],
            'images.*' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:10240', // 10MB in kilobytes
            ],
            'session_token' => ['nullable', 'string', 'max:64'],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'images.required' => 'Please select at least one image to upload.',
            'images.*.image' => 'Each file must be a valid image.',
            'images.*.mimes' => 'Images must be in JPEG, PNG, JPG, GIF, or WEBP format.',
            'images.*.max' => 'Each image must not exceed 10MB in size.',
        ];
    }
}
