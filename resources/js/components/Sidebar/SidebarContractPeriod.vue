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
            <button v-for="contractItem in sortedContracts" :key="contractItem.id"
                @click="navigateToContract(contractItem.id)" :class="[
                    'contract-button',
                    ...getContractAlertClasses(contractItem)
                ]">

                <div class="contract-header">
                    <div class="contract-main-info">
                        <h4 class="contract-title">{{ contractItem.title }}</h4>
                        <p class="contract-number">{{ contractItem.contract_number }}</p>
                    </div>

                    <div class="contract-badges">
                        <!-- Alert Icon -->
                        <font-awesome-icon 
                          v-if="getContractAlertStatusFromContract(contractItem) !== 'none'" 
                          :icon="getContractAlertIcon(contractItem)" 
                          :class="['alert-icon-badge', `alert-icon-${getContractAlertStatusFromContract(contractItem)}`]"
                        />
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
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router } from '@inertiajs/vue3';
import { getContractAlertStatus, getContractPeriodAlertStatus, getAlertClasses, type AlertStatus } from '@/utils/contractAlerts';

interface ContractPeriod {
    period_name: string;
    budget_type: string;
    start_date?: string;
    end_date?: string;
    payment_value_rp?: string;
    payment_value_non_rp?: string;
    payment_status: string;
    alert_status?: string;
    alert_message?: string;
}

interface Contract {
  id: number;
  title: string;
  contract_number: string;
  currency_type: 'rp' | 'non_rp';
  currency_type_label: string;
  contract_value_rp?: string;
  contract_value_non_rp?: string;
  lumpsum_value_rp?: string;
  unit_value_rp?: string;
  apps?: App[]; // Changed from app_id to apps array
  contract_periods?: ContractPeriod[];
  alert_status?: string; // Added for backend compatibility
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

// Computed property to sort contracts by alert status priority
const sortedContracts = computed(() => {
    return [...props.contracts].sort((a, b) => {
        const alertStatusA = getContractAlertStatusFromContract(a);
        const alertStatusB = getContractAlertStatusFromContract(b);
        
        // Define priority order: danger = 0, warning = 1, none = 2
        const getPriority = (status: AlertStatus) => {
            switch (status) {
                case 'danger': return 0;
                case 'warning': return 1;
                default: return 2;
            }
        };
        
        const priorityA = getPriority(alertStatusA);
        const priorityB = getPriority(alertStatusB);
        
        // Sort by priority (lower number = higher priority)
        return priorityA - priorityB;
    });
});

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

// Contract alert helper functions  
function getContractAlertStatusFromContract(contract: Contract): AlertStatus {
    // Use the same logic as PeriodCard: check backend alert_status first
    if (contract.alert_status) {
        return contract.alert_status as AlertStatus;
    }
    
    // Fallback: Check contract periods using the same logic as PeriodCard
    if (contract.contract_periods && contract.contract_periods.length > 0) {
        // Check each period individually using the same function as PeriodCard
        let hasWarning = false;
        let hasDanger = false;
        
        for (const period of contract.contract_periods) {
            const periodAlert = getContractPeriodAlertStatus(period);
            
            if (periodAlert === 'danger') {
                hasDanger = true;
            } else if (periodAlert === 'warning') {
                hasWarning = true;
            }
        }
        
        // Return the highest priority alert found (same logic as contractAlerts.ts)
        return hasDanger ? 'danger' : (hasWarning ? 'warning' : 'none');
    }
    
    return 'none';
}

function getContractAlertClasses(contract: Contract): string[] {
    const alertStatus = getContractAlertStatusFromContract(contract);
    return getAlertClasses(alertStatus);
}

function getContractAlertIcon(contract: Contract): string[] {
    const alertStatus = getContractAlertStatusFromContract(contract);
    switch (alertStatus) {
        case 'danger':
            return ['fas', 'exclamation-triangle'];
        case 'warning':
            return ['fas', 'exclamation-circle'];
        default:
            return ['fas', 'info-circle'];
    }
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
    position: relative;
}

/* Alert styles for contract buttons */
.contract-button.alert-danger {
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.4);
}

.contract-button.alert-warning {
    background: rgba(245, 158, 11, 0.15);
    border: 1px solid rgba(245, 158, 11, 0.4);
}

/* Alert icon styles */
.alert-icon-badge {
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 0.5rem;
}

.alert-icon-danger {
  color: #dc2626;
}

.alert-icon-warning {
  color: #d97706;
}

.contract-button:hover {
    background: rgba(255, 255, 255, 0.5);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    color: var(--text-color, #333);
}

.contract-button.alert-danger:hover {
    background: rgba(239, 68, 68, 0.25);
}

.contract-button.alert-warning:hover {
    background: rgba(245, 158, 11, 0.25);
}

/* Ensure button text stays dark */
.contract-button .contract-title,
.contract-button .contract-number,
.contract-button .contract-value,
.contract-button .periods-count {
    color: var(--text-color, #333);
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
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    overflow: hidden;
    text-overflow: ellipsis;
    max-height: calc(1.3em * 2); /* 2 lines * line-height */}

.contract-number {
    font-size: 0.75re;
    color: var(--text-muted, #666);
    margin: 0;
    opacity: 0.8;
}

.contract-badges {
    display: flex;
    align-items: center;
    gap: 0.5rem;
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
