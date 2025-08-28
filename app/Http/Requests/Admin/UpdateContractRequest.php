<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContractRequest extends FormRequest
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
            'title' => 'required|string|max:255',
            'contract_number' => 'required|string|max:255',
            'currency_type' => 'required|in:rp,non_rp',
            'contract_value_rp' => 'nullable|numeric|min:0',
            'contract_value_non_rp' => 'nullable|numeric|min:0',
            'lumpsum_value_rp' => 'nullable|numeric|min:0',
            'unit_value_rp' => 'nullable|numeric|min:0',
            'app_ids' => 'required|array|min:1',
            'app_ids.*' => 'exists:apps,app_id',
            
            // Contract periods validation
            'contract_periods' => 'required|array|min:1',
            'contract_periods.*.period_name' => 'required|string|max:255',
            'contract_periods.*.budget_type' => 'required|in:AO,RI',
            'contract_periods.*.start_date' => 'nullable|date',
            'contract_periods.*.end_date' => 'nullable|date|after_or_equal:contract_periods.*.start_date',
            'contract_periods.*.payment_value_rp' => 'nullable|numeric|min:0',
            'contract_periods.*.payment_value_non_rp' => 'nullable|numeric|min:0',
            'contract_periods.*.payment_status' => 'required|in:paid,ba_process,mka_process,settlement_process,addendum_process,not_due,has_issue,unpaid,reserved_hr,contract_moved',
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
            'title.required' => 'Judul kontrak wajib diisi.',
            'contract_number.required' => 'Nomor kontrak wajib diisi.',
            'currency_type.required' => 'Tipe mata uang wajib dipilih.',
            'currency_type.in' => 'Tipe mata uang harus RP atau Non-RP.',
            'app_ids.required' => 'Minimal satu aplikasi harus dipilih.',
            'app_ids.min' => 'Minimal satu aplikasi harus dipilih.',
            'contract_periods.required' => 'Minimal satu periode kontrak harus dibuat.',
            'contract_periods.min' => 'Minimal satu periode kontrak harus dibuat.',
            'contract_periods.*.period_name.required' => 'Nama periode wajib diisi.',
            'contract_periods.*.budget_type.required' => 'Tipe anggaran wajib dipilih.',
            'contract_periods.*.budget_type.in' => 'Tipe anggaran harus AO atau RI.',
            'contract_periods.*.payment_status.required' => 'Status pembayaran wajib dipilih.',
            'contract_periods.*.end_date.after_or_equal' => 'Tanggal akhir harus setelah atau sama dengan tanggal mulai.',
            'contract_value_rp.min' => 'Nilai kontrak harus berupa angka positif.',
            'contract_value_non_rp.min' => 'Nilai kontrak harus berupa angka positif.',
            'lumpsum_value_rp.min' => 'Nilai lumpsum harus berupa angka positif.',
            'unit_value_rp.min' => 'Nilai satuan harus berupa angka positif.',
        ];
    }
}
