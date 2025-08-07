<template>
  <div 
    :class="[
      'period-card',
      alertInfo.classes
    ]"
  >
    <div class="period-header">
      <div class="period-name-container">
        <h4 
          class="period-name"
          :title="period.period_name"
          tabindex="0"
        >
          {{ period.period_name }}
        </h4>
      </div>
      <div class="period-badges">
        <!-- Alert Icon -->
        <font-awesome-icon 
          v-if="alertInfo.status !== 'none'" 
          :icon="alertInfo.icon" 
          :class="['alert-icon-badge', `alert-icon-${alertInfo.status}`]"
        />
        <span :class="['budget-badge', budgetClass]">
          {{ period.budget_type }}
        </span>
      </div>
    </div>
    
    <div class="period-details">
      <div class="period-dates">
        <div v-if="period.start_date" class="date-item">
          <span>Mulai: {{ formatDate(period.start_date) }}</span>
        </div>
        <div v-if="period.end_date" class="date-item">
          <span>Selesai: {{ formatDate(period.end_date) }}</span>
        </div>
      </div>
      
      <div class="period-payment">
        <div class="payment-status">
          <span class="payment-label">Status:</span>
          <PaymentStatusBadge :status="period.payment_status" />
        </div>
        
        <div v-if="paymentValue" class="payment-value">
          <span class="payment-label">Nilai Termin:</span>
          <FinancialValue 
            :value="paymentValue" 
            :is-rupiah="isRupiah" 
            class="payment-amount"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import PaymentStatusBadge from './PaymentStatusBadge.vue';
import FinancialValue from './FinancialValue.vue';
import { 
  getContractPeriodAlertStatus, 
  getAlertClasses, 
  getContractPeriodAlertMessage,
  type AlertStatus 
} from '@/utils/contractAlerts';

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

interface Props {
  period: ContractPeriod;
  currencyType: 'rp' | 'non_rp';
}

const props = defineProps<Props>();

// Alert information computed properties
const alertStatus = computed(() => getContractPeriodAlertStatus(props.period));
const alertMessage = computed(() => getContractPeriodAlertMessage(props.period));
const alertClasses = computed(() => getAlertClasses(alertStatus.value));

// Alert icon based on status
const alertIcon = computed(() => {
  switch (alertStatus.value) {
    case 'danger':
      return ['fas', 'exclamation-triangle'];
    case 'warning':
      return ['fas', 'exclamation-circle'];
    default:
      return ['fas', 'info-circle'];
  }
});

// Combined alert info object for template
const alertInfo = computed(() => ({
  status: alertStatus.value,
  message: alertMessage.value,
  classes: alertClasses.value,
  icon: alertIcon.value
}));

const budgetClass = computed(() => 
  props.period.budget_type === 'AO' ? 'budget-ao' : 'budget-ri'
);

const isRupiah = computed(() => props.currencyType === 'rp');

const paymentValue = computed(() => {
  if (props.currencyType === 'rp' && props.period.payment_value_rp) {
    return props.period.payment_value_rp;
  } else if (props.currencyType === 'non_rp' && props.period.payment_value_non_rp) {
    return props.period.payment_value_non_rp;
  }
  return null;
});

function formatDate(dateString: string): string {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}
</script>

<style scoped>
.period-card {
  background: var(--bg-alt);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  padding: var(--spacing-4);
  height: 175px;
  display: flex;
  flex-direction: column;
  position: relative;
  transition: all 0.3s ease;
}

/* Alert styles */
.period-card.alert-danger {
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.4);
}

.period-card.alert-warning {
  background: rgba(245, 158, 11, 0.15);
  border-color: rgba(245, 158, 11, 0.4);
}

/* Alert icon badge styles */
.period-badges {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-shrink: 0;
  margin-left: 0.5rem;
}

.alert-icon-badge {
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.alert-icon-danger {
  color: #dc2626;
}

.alert-icon-warning {
  color: #d97706;
}

.period-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: var(--spacing-3);
  flex-shrink: 0;
}

.period-name-container {
  flex: 1;
  margin-right: var(--spacing-2);
  position: relative;
}

.period-name {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-color);
  margin: 0;
  line-height: 1.4;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
  word-wrap: break-word;
  hyphens: auto;
  cursor: help;
  min-height: 2.8rem;
}

.period-name:focus {
  display: block;
  overflow: visible;
  white-space: normal;
  background: var(--bg-color);
  border: 1px solid var(--primary-color);
  border-radius: var(--radius-sm);
  padding: var(--spacing-1);
  z-index: 10;
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  box-shadow: var(--shadow);
  -webkit-line-clamp: unset;
  line-clamp: unset;
  -webkit-box-orient: unset;
  min-height: auto;
}

.budget-badge {
  font-size: 0.75rem;
  font-weight: 600;
  padding: var(--spacing-1) var(--spacing-2);
  border-radius: var(--radius-sm);
  flex-shrink: 0;
}

.budget-ao {
  background: #fed7d7;
  color: #c53030;
}

.budget-ri {
  background: #bee3f8;
  color: #2b6cb0;
}

.period-details {
  display: flex;
  flex-direction: column;
  flex: 1;
  justify-content: space-between;
}

.period-dates {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-1);
  flex-grow: 1;
}

.date-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  font-size: 0.875rem;
  color: var(--text-color-light);
}

.period-payment {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-2);
  margin-top: auto;
  flex-shrink: 0;
}

.payment-status, .payment-value {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.payment-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: var(--text-muted);
}

.payment-amount {
  font-size: 0.875rem;
  font-weight: 600;
}
</style>
