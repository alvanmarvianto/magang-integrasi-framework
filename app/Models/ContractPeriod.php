<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'period_name',
        'budget_type',
        'start_date',
        'end_date',
        'payment_value_rp',
        'payment_value_non_rp',
        'payment_status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_value_rp' => 'decimal:2',
        'payment_value_non_rp' => 'decimal:2',
    ];

    /**
     * Get the contract that owns the contract period.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /**
     * Get payment value based on contract currency type
     */
    public function getPaymentValueAttribute()
    {
        return $this->contract->isRpContract() ? $this->payment_value_rp : $this->payment_value_non_rp;
    }

    /**
     * Get human readable payment status
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            'paid' => 'Sudah bayar',
            'ba_process' => 'Proses BA',
            'mka_process' => 'Proses di MKA',
            'settlement_process' => 'Proses Settlement (LD<=9 Des)',
            'addendum_process' => 'Proses Addendum',
            'not_due' => 'Belum Jatuh Tempo/belum ada kebutuhan',
            'has_issue' => 'Terdapat Isu',
            'unpaid' => 'Tidak bayar',
            'reserved_hr' => 'Dicadangkan (HR)',
            'contract_moved' => 'Kontrak dipindahkan',
            default => 'Unknown Status'
        };
    }

    /**
     * Get budget type label
     */
    public function getBudgetTypeLabelAttribute(): string
    {
        return $this->budget_type === 'AO' ? 'Anggaran Operasional' : 'Realisasi Investasi';
    }
}
