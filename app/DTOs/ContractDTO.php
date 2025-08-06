<?php

namespace App\DTOs;

use App\Models\Contract;
use Illuminate\Support\Collection;

class ContractDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $contractNumber,
        public readonly string $currencyType,
        public readonly ?string $contractValueRp,
        public readonly ?string $contractValueNonRp,
        public readonly ?string $lumpsumValueRp,
        public readonly ?string $unitValueRp,
        public readonly ?Collection $apps = null,
        public readonly ?Collection $contractPeriods = null,
    ) {}

    public static function fromModel(Contract $contract): self
    {
        return new self(
            id: $contract->id,
            title: $contract->title,
            contractNumber: $contract->contract_number,
            currencyType: $contract->currency_type,
            contractValueRp: $contract->contract_value_rp ? (string) $contract->contract_value_rp : null,
            contractValueNonRp: $contract->contract_value_non_rp ? (string) $contract->contract_value_non_rp : null,
            lumpsumValueRp: $contract->lumpsum_value_rp ? (string) $contract->lumpsum_value_rp : null,
            unitValueRp: $contract->unit_value_rp ? (string) $contract->unit_value_rp : null,
            apps: $contract->relationLoaded('apps') 
                ? $contract->apps->map(fn($app) => AppDTO::fromModel($app))
                : null,
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
     * Get all app names as a string
     */
    public function getAppNamesString(): string
    {
        if (!$this->apps || $this->apps->isEmpty()) {
            return 'No Apps';
        }

        return $this->apps->pluck('appName')->join(', ');
    }

    /**
     * Get first app name (for compatibility)
     */
    public function getFirstAppName(): ?string
    {
        return $this->apps?->first()?->appName;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
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
            'apps' => $this->apps?->map(fn($app) => $app->toArray())->toArray() ?? [],
            'app_names' => $this->getAppNamesString(),
            'first_app_name' => $this->getFirstAppName(),
            'contract_periods' => $this->contractPeriods?->map(fn($period) => $period->toArray())->toArray(),
        ];
    }
}
