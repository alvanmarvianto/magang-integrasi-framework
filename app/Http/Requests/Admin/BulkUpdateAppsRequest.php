<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateAppsRequest extends FormRequest
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
            'apps' => 'required|array|min:1',
            'apps.*.app_id' => 'required|exists:apps,app_id',
            'apps.*.app_name' => 'required|string|max:255|regex:/^[^\/]*$/',
            'apps.*.description' => 'nullable|string',
            'apps.*.stream_id' => 'required|exists:streams,stream_id',
            'apps.*.app_type' => 'required|in:cots,inhouse,outsource',
            'apps.*.stratification' => 'required|in:strategis,kritikal,umum',
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
            'apps.min' => 'Minimal satu aplikasi harus disediakan untuk pembaruan massal.',
            'apps.*.app_name.regex' => 'Nama aplikasi tidak boleh mengandung karakter garis miring (/).',
        ];
    }
}
