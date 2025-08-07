<template>
  <div 
    class="contract-card"
    :class="[`alert-${contract.alertStatus}`]"
    @click="$emit('click')"
  >
    <!-- Alert Indicator -->
    <div v-if="contract.alertStatus !== 'none'" class="alert-indicator">
      <font-awesome-icon 
        :icon="alertIcon" 
        :class="`alert-icon-${contract.alertStatus}`"
      />
    </div>

    <!-- Contract Header -->
    <div class="contract-header">
      <h3 class="contract-title">{{ contract.title }}</h3>
      <p class="contract-number">{{ contract.contract_number }}</p>
    </div>

    <!-- Contract Apps -->
    <div v-if="contract.apps && contract.apps.length > 0" class="contract-apps">
      <div class="apps-label">Aplikasi:</div>
      <div class="apps-list">
        <span 
          v-for="(app, index) in contract.apps.slice(0, 2)" 
          :key="app.app_id" 
          class="app-tag"
        >
          {{ app.app_name }}
        </span>
        <span v-if="contract.apps.length > 2" class="app-more">
          +{{ contract.apps.length - 2 }} lainnya
        </span>
      </div>
    </div>

    <!-- Financial Info -->
    <div class="contract-financial">
      <div class="financial-item">
        <span class="financial-label">Mata Uang:</span>
        <span class="financial-value">{{ currencyLabel }}</span>
      </div>
      <div v-if="totalValue" class="financial-item">
        <span class="financial-label">Nilai Total:</span>
        <span class="financial-value">{{ totalValue }}</span>
      </div>
    </div>

    <!-- Alert Message -->
    <div v-if="alertMessage" class="alert-message">
      <font-awesome-icon :icon="alertIcon" />
      <span>{{ alertMessage }}</span>
    </div>

    <!-- Periods Summary -->
    <div v-if="periodsCount > 0" class="periods-summary">
      <font-awesome-icon icon="fa-solid fa-calendar-alt" />
      <span>{{ periodsCount }} periode kontrak</span>
    </div>

    <!-- Action Indicator -->
    <div class="action-indicator">
      <font-awesome-icon icon="fa-solid fa-arrow-right" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import type { AlertStatus } from '../../utils/contractAlerts';

interface App {
  app_id: number;
  app_name: string;
}

interface Contract {
  id: number;
  title: string;
  contract_number: string;
  currency_type: 'rp' | 'non_rp';
  currency_type_label?: string;
  contract_value_rp?: string;
  contract_value_non_rp?: string;
  lumpsum_value_rp?: string;
  unit_value_rp?: string;
  apps?: App[];
  contract_periods?: any[];
  alertStatus: AlertStatus;
  alertPriority: {
    status: AlertStatus;
    priority: number;
    message?: string;
  };
}

interface Props {
  contract: Contract;
}

defineEmits<{
  click: [];
}>();

const props = defineProps<Props>();

const alertIcon = computed(() => {
  switch (props.contract.alertStatus) {
    case 'danger':
      return 'fa-solid fa-exclamation-triangle';
    case 'warning':
      return 'fa-solid fa-clock';
    default:
      return 'fa-solid fa-check-circle';
  }
});

const alertMessage = computed(() => {
  return props.contract.alertPriority.message || null;
});

const currencyLabel = computed(() => {
  return props.contract.currency_type_label || 
         (props.contract.currency_type === 'rp' ? 'Rupiah (RP)' : 'Mata Uang Asing (Non-RP)');
});

const totalValue = computed(() => {
  const contract = props.contract;
  
  if (contract.currency_type === 'rp') {
    const value = parseFloat(contract.contract_value_rp || '0');
    if (value > 0) {
      return `Rp ${value.toLocaleString('id-ID')}`;
    }
  } else {
    const value = parseFloat(contract.contract_value_non_rp || '0');
    if (value > 0) {
      return value.toLocaleString('id-ID');
    }
  }
  
  return null;
});

const periodsCount = computed(() => {
  return props.contract.contract_periods?.length || 0;
});
</script>

<style scoped>
.contract-card {
  position: relative;
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow);
  padding: var(--spacing-4);
  cursor: pointer;
  transition: all 0.2s ease;
  border-left: 4px solid transparent;
  overflow: hidden;
}

.contract-card:hover {
  box-shadow: var(--shadow-lg);
  transform: translateY(-2px);
}

/* Alert-based styling */
.contract-card.alert-danger {
  border-left-color: var(--danger-color);
  background: #fef2f2;
}

.contract-card.alert-warning {
  border-left-color: var(--warning-color);
  background: #fffbeb;
}

.contract-card.alert-none {
  border-left-color: var(--border-color);
  background: #f0fdf4;
}

/* Alert Indicator */
.alert-indicator {
  position: absolute;
  top: var(--spacing-3);
  right: var(--spacing-3);
  font-size: 1.25rem;
}

.alert-icon-danger {
  color: var(--danger-color);
}

.alert-icon-warning {
  color: var(--warning-color);
}

/* Contract Header */
.contract-header {
  margin-bottom: var(--spacing-3);
  padding-right: 2rem; /* Space for alert indicator */
}

.contract-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-color);
  margin: 0 0 var(--spacing-1) 0;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 4;
  line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

.contract-number {
  font-size: 0.875rem;
  color: var(--text-muted);
  margin: 0;
  font-family: 'Courier New', monospace;
}

/* Contract Apps */
.contract-apps {
  margin-bottom: var(--spacing-3);
}

.apps-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: var(--spacing-1);
}

.apps-list {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-1);
}

.app-tag {
  background: var(--primary-color);
  color: white;
  padding: 0.25rem 0.5rem;
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  font-weight: 500;
}

.app-more {
  background: var(--border-color);
  color: var(--text-muted);
  padding: 0.25rem 0.5rem;
  border-radius: var(--radius-sm);
  font-size: 0.75rem;
  font-style: italic;
}

/* Financial Info */
.contract-financial {
  margin-bottom: var(--spacing-3);
}

.financial-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-1);
}

.financial-item:last-child {
  margin-bottom: 0;
}

.financial-label {
  font-size: 0.875rem;
  color: var(--text-muted);
}

.financial-value {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-color);
}

/* Alert Message */
.alert-message {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  padding: var(--spacing-2);
  border-radius: var(--radius-sm);
  font-size: 0.875rem;
  font-weight: 500;
  margin-bottom: var(--spacing-3);
}

.contract-card.alert-danger .alert-message {
  background: rgba(220, 53, 69, 0.1);
  color: var(--danger-color);
}

.contract-card.alert-warning .alert-message {
  background: rgba(255, 193, 7, 0.1);
  color: #856404;
}

/* Periods Summary */
.periods-summary {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  font-size: 0.875rem;
  color: var(--text-muted);
  margin-bottom: var(--spacing-3);
}

/* Action Indicator */
.action-indicator {
  position: absolute;
  bottom: var(--spacing-3);
  right: var(--spacing-3);
  color: var(--primary-color);
  opacity: 0;
  transition: opacity 0.2s ease;
}

.contract-card:hover .action-indicator {
  opacity: 1;
}

/* Responsive */
@media (max-width: 768px) {
  .contract-card {
    padding: var(--spacing-3);
  }

  .contract-header {
    padding-right: 1.5rem;
  }

  .contract-title {
    font-size: 1rem;
  }

  .apps-list {
    flex-direction: column;
    gap: 0.5rem;
  }

  .financial-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
}
</style>
