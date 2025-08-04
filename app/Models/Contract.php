<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_id',
        'title',
        'contract_number',
        'currency_type',
        'contract_value_rp',
        'contract_value_non_rp',
        'lumpsum_value_rp',
        'unit_value_rp',
    ];

    protected $casts = [
        'contract_value_rp' => 'decimal:2',
        'contract_value_non_rp' => 'decimal:2',
        'lumpsum_value_rp' => 'decimal:2',
        'unit_value_rp' => 'decimal:2',
    ];

    /**
     * Get the app that owns the contract.
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id', 'app_id');
    }

    /**
     * Get the contract periods for the contract.
     */
    public function contractPeriods(): HasMany
    {
        return $this->hasMany(ContractPeriod::class);
    }

    /**
     * Check if contract uses Rupiah currency
     */
    public function isRpContract(): bool
    {
        return $this->currency_type === 'rp';
    }

    /**
     * Check if contract uses non-Rupiah currency
     */
    public function isNonRpContract(): bool
    {
        return $this->currency_type === 'non_rp';
    }

    /**
     * Get the contract value based on currency type
     */
    public function getContractValueAttribute()
    {
        return $this->isRpContract() ? $this->contract_value_rp : $this->contract_value_non_rp;
    }
}
