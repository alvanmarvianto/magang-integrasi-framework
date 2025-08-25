<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStreamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $stream = $this->route('stream');
        
        return [
            'stream_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[^\/]*$/',
                Rule::unique('streams', 'stream_name')->ignore($stream->stream_id, 'stream_id')
            ],
            'description' => 'nullable|string|max:500',
            'is_allowed_for_diagram' => 'boolean',
            'sort_order' => 'nullable|integer|min:1|max:999',
            'color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stream_name.regex' => 'Nama stream tidak boleh mengandung karakter garis miring (/).',
            'color.regex' => 'Warna harus berupa kode warna hex yang valid (misal: #FF6B35).',];
    }
}
