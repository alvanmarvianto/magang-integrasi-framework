<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreIntegrationRequest extends FormRequest
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
            'source_app_id' => 'required|exists:apps,app_id',
            'target_app_id' => 'required|exists:apps,app_id|different:source_app_id',
            'connections' => 'nullable|array',
            'connections.*.connection_type_id' => 'nullable|distinct|exists:connectiontypes,connection_type_id',
            'connections.*.source_inbound' => 'nullable|string|max:1000',
            'connections.*.source_outbound' => 'nullable|string|max:1000',
            'connections.*.target_inbound' => 'nullable|string|max:1000',
            'connections.*.target_outbound' => 'nullable|string|max:1000',
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
            'target_app_id.different' => 'Aplikasi target harus berbeda dengan aplikasi sumber.',
            'connections.*.connection_type_id.distinct' => 'Setiap jenis koneksi hanya dapat digunakan sekali per integrasi.',];
    }
}
