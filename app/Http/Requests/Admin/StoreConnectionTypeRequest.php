<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreConnectionTypeRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255|regex:/^[^\/]*$/|unique:connectiontypes,type_name',
            'color' => 'required|string|regex:/^#[0-9A-F]{6}$/i',
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
            'name.regex' => 'Nama tipe koneksi tidak boleh mengandung karakter garis miring (/).',
            'color.regex' => 'Warna harus berupa kode warna hex yang valid (misal: #FF0000).',
        ];
    }
}
