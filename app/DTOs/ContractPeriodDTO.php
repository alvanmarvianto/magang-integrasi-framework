<?php

namespace App\DTOs;

use App\Models\ContractPeriod;

class ContractPeriodDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $contractId,
        public readonly string $periodName,
        public readonly string $budgetType,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly ?string $paymentValueRp,
        public readonly ?string $paymentValueNonRp,
        public readonly string $paymentStatus,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly ?ContractDTO $contract = null,
    ) {}

    public static function fromModel(ContractPeriod $contractPeriod): self
    {
        return new self(
            id: $contractPeriod->id,
            contractId: $contractPeriod->contract_id,
            periodName: $contractPeriod->period_name,
            budgetType: $contractPeriod->budget_type,
            startDate: (string) $contractPeriod->start_date,
            endDate: (string) $contractPeriod->end_date,
            paymentValueRp: $contractPeriod->payment_value_rp?->toString(),
            paymentValueNonRp: $contractPeriod->payment_value_non_rp?->toString(),
            paymentStatus: $contractPeriod->payment_status,
            createdAt: $contractPeriod->created_at->toISOString(),
            updatedAt: $contractPeriod->updated_at->toISOString(),
            contract: $contractPeriod->relationLoaded('contract') ? ContractDTO::fromModel($contractPeriod->contract) : null,
        );
    }

    /**
     * Get payment value based on contract currency type
     */
    public function getPaymentValue(): ?string
    {
        if ($this->contract && $this->contract->isRpContract()) {
            return $this->paymentValueRp;
        }
        return $this->paymentValueNonRp;
    }

    /**
     * Get formatted payment value
     */
    public function getFormattedPaymentValue(): string
    {
        $value = $this->getPaymentValue();
        if ($value === null) {
            return 'N/A';
        }

        $numericValue = (float) $value;
        
        if ($this->contract && $this->contract->isRpContract()) {
            return 'Rp ' . number_format($numericValue, 2, ',', '.');
        }

        return number_format($numericValue, 2, '.', ',');
    }

    /**
     * Get human readable payment status
     */
    public function getPaymentStatusLabel(): string
    {
        return match($this->paymentStatus) {
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
    public function getBudgetTypeLabel(): string
    {
        return $this->budgetType === 'AO' ? 'Anggaran Operasional' : 'Realisasi Investasi';
    }

    /**
     * Get period duration in days
     */
    public function getPeriodDurationDays(): int
    {
        $start = new \DateTime($this->startDate);
        $end = new \DateTime($this->endDate);
        return $end->diff($start)->days;
    }

    /**
     * Check if period is active (current date is within period)
     */
    public function isActivePeriod(): bool
    {
        $now = new \DateTime();
        $start = new \DateTime($this->startDate);
        $end = new \DateTime($this->endDate);
        
        return $now >= $start && $now <= $end;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'contract_id' => $this->contractId,
            'period_name' => $this->periodName,
            'budget_type' => $this->budgetType,
            'budget_type_label' => $this->getBudgetTypeLabel(),
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'period_duration_days' => $this->getPeriodDurationDays(),
            'is_active_period' => $this->isActivePeriod(),
            'payment_value_rp' => $this->paymentValueRp,
            'payment_value_non_rp' => $this->paymentValueNonRp,
            'payment_value' => $this->getPaymentValue(),
            'formatted_payment_value' => $this->getFormattedPaymentValue(),
            'payment_status' => $this->paymentStatus,
            'payment_status_label' => $this->getPaymentStatusLabel(),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
