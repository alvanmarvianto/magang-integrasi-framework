<?php

namespace App\DTOs;

use App\Models\Contract;
use Illuminate\Support\Collection;

class ContractDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $appId,
        public readonly string $title,
        public readonly string $contractNumber,
        public readonly string $currencyType,
        public readonly ?string $contractValueRp,
        public readonly ?string $contractValueNonRp,
        public readonly ?string $lumpsumValueRp,
        public readonly ?string $unitValueRp,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?AppDTO $app = null,
        public readonly ?Collection $contractPeriods = null,
    ) {}

    public static function fromModel(Contract $contract): self
    {
        return new self(
            id: $contract->id,
            appId: $contract->app_id,
            title: $contract->title,
            contractNumber: $contract->contract_number,
            currencyType: $contract->currency_type,
            contractValueRp: $contract->contract_value_rp?->toString(),
            contractValueNonRp: $contract->contract_value_non_rp?->toString(),
            lumpsumValueRp: $contract->lumpsum_value_rp?->toString(),
            unitValueRp: $contract->unit_value_rp?->toString(),
            createdAt: $contract->created_at->toISOString(),
            updatedAt: $contract->updated_at->toISOString(),
            app: $contract->relationLoaded('app') ? AppDTO::fromModel($contract->app) : null,
            contractPeriods: $contract->relationLoaded('contractPeriods') 
                ? $contract->contractPeriods->map(fn($period) => ContractPeriodDTO::fromModel($period))
                : null,
        );
    }

    /**
     * Check if contract uses Rupiah currency
     */
    public function isRpContract(): bool
    {
        return $this->currencyType === 'rp';
    }

    /**
     * Check if contract uses non-Rupiah currency
     */
    public function isNonRpContract(): bool
    {
        return $this->currencyType === 'non_rp';
    }

    /**
     * Get the contract value based on currency type
     */
    public function getContractValue(): ?string
    {
        return $this->isRpContract() ? $this->contractValueRp : $this->contractValueNonRp;
    }

    /**
     * Get formatted contract value with currency symbol
     */
    public function getFormattedContractValue(): string
    {
        $value = $this->getContractValue();
        if ($value === null) {
            return 'N/A';
        }

        $numericValue = (float) $value;

        if ($this->isRpContract()) {
            return 'Rp ' . number_format($numericValue, 2, ',', '.');
        }

        return number_format($numericValue, 2, '.', ',');
    }

    /**
     * Get currency type label
     */
    public function getCurrencyTypeLabel(): string
    {
        return $this->isRpContract() ? 'Rupiah' : 'Non-Rupiah';
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'app_id' => $this->appId,
            'title' => $this->title,
            'contract_number' => $this->contractNumber,
            'currency_type' => $this->currencyType,
            'currency_type_label' => $this->getCurrencyTypeLabel(),
            'contract_value_rp' => $this->contractValueRp,
            'contract_value_non_rp' => $this->contractValueNonRp,
            'contract_value' => $this->getContractValue(),
            'formatted_contract_value' => $this->getFormattedContractValue(),
            'lumpsum_value_rp' => $this->lumpsumValueRp,
            'unit_value_rp' => $this->unitValueRp,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'app' => $this->app?->toArray(),
            'contract_periods' => $this->contractPeriods?->map(fn($period) => $period->toArray())->toArray(),
        ];
    }
}
