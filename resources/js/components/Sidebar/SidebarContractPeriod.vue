<template>
    <div class="contract-periods-section">
        <!-- Section with title and description -->
        <div v-if="title || description" class="nav-section">
            <h3 v-if="title">{{ title }}</h3>
            <p v-if="description">{{ description }}</p>
        </div>
        <div v-if="contracts.length === 0" class="no-contracts">
            <FontAwesomeIcon icon="fa-solid fa-info-circle" />
            <span>Tidak ada kontrak tersedia</span>
        </div>



        <div v-else class="contracts-list">
            <button v-for="contractItem in contracts" :key="contractItem.id"
                @click="navigateToContract(contractItem.id)" :class="[
                    'contract-button',
                    { 'contract-active': contractItem.id === currentContract?.id }
                ]">
                <div class="contract-header">
                    <div class="contract-main-info">
                        <h4 class="contract-title">{{ contractItem.title }}</h4>
                        <p class="contract-number">{{ contractItem.contract_number }}</p>
                    </div>

                    <div class="contract-badges">
                        <span :class="[
                            'currency-badge',
                            contractItem.currency_type === 'rp' ? 'badge-rp' : 'badge-non-rp'
                        ]">
                            {{ contractItem.currency_type?.toUpperCase() }}
                        </span>
                    </div>
                </div>

                <div class="contract-details">
                    <div class="contract-value">
                        <FontAwesomeIcon icon="fa-solid fa-coins" />
                        <span>{{ formatContractValue(contractItem) }}</span>
                    </div>

                    <div v-if="contractItem.contract_periods && contractItem.contract_periods.length > 0"
                        class="periods-count">
                        <FontAwesomeIcon icon="fa-solid fa-calendar-alt" />
                        <span>{{ contractItem.contract_periods.length }} periode</span>
                    </div>
                </div>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router } from '@inertiajs/vue3';

interface ContractPeriod {
    period_name: string;
    budget_type: string;
    start_date?: string;
    end_date?: string;
    payment_value_rp?: string;
    payment_value_non_rp?: string;
    payment_status: string;
}

interface Contract {
  id: number;
  app_id: number;
  title: string;
  contract_number: string;
  currency_type: 'rp' | 'non_rp';
  currency_type_label: string;
  contract_value_rp?: string;
  contract_value_non_rp?: string;
  lumpsum_value_rp?: string;
  unit_value_rp?: string;
  contract_periods?: ContractPeriod[];
}interface App {
    app_id: number;
    app_name: string;
}

interface Props {
    title?: string;
    description?: string;
    contracts: Contract[];
    currentContract?: Contract;
    app?: App;
}

const props = defineProps<Props>();

function navigateToContract(contractId: number) {
    if (props.app) {
        router.get(`/contract/${props.app.app_id}/${contractId}`);
    }
}

function formatContractValue(contract: Contract): string {
    if (contract.currency_type === 'rp' && contract.contract_value_rp) {
        const value = parseFloat(contract.contract_value_rp);
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
            notation: 'compact',
            compactDisplay: 'short'
        }).format(value);
    } else if (contract.currency_type === 'non_rp' && contract.contract_value_non_rp) {
        const value = parseFloat(contract.contract_value_non_rp);
        return new Intl.NumberFormat('en-US', {
            style: 'decimal',
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
            notation: 'compact',
            compactDisplay: 'short'
        }).format(value);
    }
    return 'N/A';
}
</script>

<style scoped>
.contract-periods-section {
    margin-bottom: 1.5rem;
}

.periods-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-color, #333);
    margin: 0 0 1rem 0;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    backdrop-filter: blur(10px);
}

.no-contracts {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    text-align: center;
    color: var(--text-muted, #666);
    font-size: 0.875rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 0.5rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.contracts-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.contract-button {
    width: 100%;
    background: rgba(255, 255, 255, 0.3);
    border: none;
    border-radius: 0.5rem;
    padding: 0.75rem;
    text-align: left;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    color: var(--text-color, #333);
    font-weight: 500;
}

.contract-button:hover {
    background: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    color: var(--text-color, #333);
}

.contract-active {
    background: rgba(255, 255, 255, 0.5) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.contract-active:hover {
    background: rgba(255, 255, 255, 0.6) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

.contract-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.contract-main-info {
    flex: 1;
}

.contract-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: inherit;
    margin: 0 0 0.25rem 0;
    line-height: 1.3;
}

.contract-number {
    font-size: 0.75rem;
    color: var(--text-muted, #666);
    margin: 0;
    opacity: 0.8;
}

.contract-badges {
    flex-shrink: 0;
    margin-left: 0.5rem;
}

.currency-badge {
    font-size: 0.625rem;
    font-weight: 600;
    padding: 0.125rem 0.375rem;
    border-radius: 9999px;
    text-transform: uppercase;
}

.badge-rp {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.badge-non-rp {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}

.contract-details {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
}

.contract-value,
.periods-count {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: var(--text-muted, #666);
}

.contract-value svg,
.periods-count svg {
    font-size: 0.75rem;
    opacity: 0.7;
}

.contract-value span {
    font-weight: 600;
    font-family: 'Courier New', monospace;
}

.periods-count span {
    font-weight: 500;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .contract-button {
        padding: 0.5rem 0.75rem;
    }

    .contract-title {
        font-size: 0.8rem;
    }

    .contract-number {
        font-size: 0.7rem;
    }

    .contract-details {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .contract-value,
    .periods-count {
        font-size: 0.7rem;
    }
}
</style>
