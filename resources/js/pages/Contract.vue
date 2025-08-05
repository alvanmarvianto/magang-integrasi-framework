<template>
  <div id="container">
    <Sidebar 
      :title="'Kontrak ' + (app?.app_name || 'Kontrak Aplikasi')" 
      icon="fa-solid fa-file-contract"
      :show-close-button="true"
      @close="closeSidebar"
    >
      <SidebarNavigation :links="navigationLinks"  title="Navigasi"/>
      
      <SidebarContractPeriod
        :title="'Kontrak Aplikasi'"
        :contracts="allContracts"
        :current-contract="contract"
        :app="app"
      />
    </Sidebar>

    <main id="main-content">
      <div id="menu-toggle" v-show="isMobile && !visible" :class="{ active: visible }" @click.stop="toggleSidebar">
        <FontAwesomeIcon icon="fa-solid fa-bars" />
      </div>
      <div id="loader" v-if="loading"></div>
      <div id="body">
        <div v-if="!contract" class="contract-not-found">
          <div class="not-found-content">
            <font-awesome-icon icon="fa-solid fa-file-contract" class="not-found-icon" />
            <h2>Kontrak tidak ditemukan</h2>
            <p>Pilih kontrak dari sidebar untuk melihat detail.</p>
          </div>
        </div>

        <div v-else class="contract-content">
          <!-- Contract Header -->
          <div class="contract-header">
            <div class="contract-title-section">
              <h1>{{ contract.title }}</h1>
              <p class="contract-info">{{ contract.contract_number }}</p>
            </div>
          </div>

          <!-- Contract Details -->
          <div class="contract-details">
            <div class="detail-grid">
              <!-- Basic Information -->
              <div class="detail-section">
                <h3 class="section-title">
                  <font-awesome-icon icon="fa-solid fa-info-circle" />
                  Informasi Dasar
                </h3>
                <div class="detail-items">
                  <div class="detail-item">
                    <span class="detail-label">Aplikasi:</span>
                    <span class="detail-value">{{ app?.app_name }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Nomor Kontrak:</span>
                    <span class="detail-value">{{ contract.contract_number }}</span>
                  </div>
                  <div class="detail-item">
                    <span class="detail-label">Tipe Mata Uang:</span>
                    <span class="detail-value">{{ contract.currency_type_label }}</span>
                  </div>
                </div>
              </div>

              <!-- Financial Information -->
              <div class="detail-section">
                <h3 class="section-title">
                  <font-awesome-icon icon="fa-solid fa-dollar-sign" />
                  Informasi Keuangan
                </h3>
                <div class="detail-items">
                  <div v-if="contract.currency_type === 'rp'" class="financial-group">
                    <div v-if="contract.contract_value_rp" class="detail-item">
                      <span class="detail-label">Nilai Kontrak (RP):</span>
                      <span class="detail-value financial-value">{{ formatCurrency(contract.contract_value_rp) }}</span>
                    </div>
                    <div v-if="contract.lumpsum_value_rp" class="detail-item">
                      <span class="detail-label">Nilai Lumpsum (RP):</span>
                      <span class="detail-value financial-value">{{ formatCurrency(contract.lumpsum_value_rp) }}</span>
                    </div>
                    <div v-if="contract.unit_value_rp" class="detail-item">
                      <span class="detail-label">Nilai Satuan (RP):</span>
                      <span class="detail-value financial-value">{{ formatCurrency(contract.unit_value_rp) }}</span>
                    </div>
                  </div>
                  <div v-else class="financial-group">
                    <div v-if="contract.contract_value_non_rp" class="detail-item">
                      <span class="detail-label">Nilai Kontrak (Non-RP):</span>
                      <span class="detail-value financial-value">{{ formatCurrency(contract.contract_value_non_rp, false) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Contract Periods -->
            <div v-if="contract.contract_periods && contract.contract_periods.length > 0" class="periods-section">
              <h3 class="section-title">
                <font-awesome-icon icon="fa-solid fa-calendar-alt" />
                Periode Kontrak
              </h3>
              <div class="periods-grid">
                <div
                  v-for="(period, index) in contract.contract_periods"
                  :key="index"
                  class="period-card"
                >
                  <div class="period-header">
                    <h4 class="period-name">{{ period.period_name }}</h4>
                    <span :class="[
                      'budget-badge',
                      period.budget_type === 'AO' ? 'budget-ao' : 'budget-ri'
                    ]">
                      {{ period.budget_type }}
                    </span>
                  </div>
                  
                  <div class="period-details">
                    <div class="period-dates">
                      <div v-if="period.start_date" class="date-item">
                        <font-awesome-icon icon="fa-solid fa-play" />
                        <span>Mulai: {{ formatDate(period.start_date) }}</span>
                      </div>
                      <div v-if="period.end_date" class="date-item">
                        <font-awesome-icon icon="fa-solid fa-stop" />
                        <span>Selesai: {{ formatDate(period.end_date) }}</span>
                      </div>
                    </div>
                    
                    <div class="period-payment">
                      <div class="payment-status">
                        <span class="payment-label">Status:</span>
                        <span :class="['payment-badge', getPaymentStatusClass(period.payment_status)]">
                          {{ getPaymentStatusLabel(period.payment_status) }}
                        </span>
                      </div>
                      
                      <div v-if="getPeriodPaymentValue(period)" class="payment-value">
                        <span class="payment-label">Nilai Termin:</span>
                        <span class="payment-amount">{{ getPeriodPaymentValue(period) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useSidebar } from '../composables/useSidebar';
import { useRoutes } from '../composables/useRoutes';
import Sidebar from '../components/Sidebar/Sidebar.vue';
import SidebarNavigation from '../components/Sidebar/SidebarNavigation.vue';
import SidebarContractPeriod from '../components/Sidebar/SidebarContractPeriod.vue';

interface App {
  app_id: number;
  app_name: string;
  description?: string;
}

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
}

interface Props {
  contract?: Contract;
  app?: App;
  allContracts: Contract[];
}

const props = defineProps<Props>();

const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
const { visitRoute } = useRoutes();
const loading = ref(false);

const navigationLinks = [
  {
    icon: 'fa-solid fa-home',
    text: 'Halaman Utama',
    onClick: () => visitRoute('index'),
  },
  {
    icon: 'fa-solid fa-project-diagram',
    text: 'Halaman Integrasi',
    onClick: () => props.app ? visitRoute('appIntegration', { app_id: props.app.app_id }) : null,
  },
  {
    icon: 'fa-solid fa-microchip',
    text: 'Halaman Teknologi',
    onClick: () => props.app ? visitRoute('technology.app', { app_id: props.app.app_id }) : visitRoute('technology.index'),
  },
];

const backToAppUrl = computed(() => {
  return props.app ? `/technology/${props.app.app_id}` : '/';
});

function formatContractValue(contract: Contract): string {
  if (contract.currency_type === 'rp' && contract.contract_value_rp) {
    return formatCurrency(contract.contract_value_rp);
  } else if (contract.currency_type === 'non_rp' && contract.contract_value_non_rp) {
    return formatCurrency(contract.contract_value_non_rp, false);
  }
  return 'N/A';
}

function formatCurrency(value: string | number, isRupiah: boolean = true): string {
  const numValue = typeof value === 'string' ? parseFloat(value) : value;
  if (isNaN(numValue)) return 'N/A';
  
  if (isRupiah) {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(numValue);
  }
  
  return new Intl.NumberFormat('en-US', {
    style: 'decimal',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(numValue);
}

function formatDate(dateString: string): string {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

function getPaymentStatusLabel(status: string): string {
  const statusLabels: Record<string, string> = {
    'paid': 'Sudah bayar',
    'ba_process': 'Proses BA',
    'mka_process': 'Proses di MKA',
    'settlement_process': 'Proses Settlement',
    'addendum_process': 'Proses Addendum',
    'not_due': 'Belum Jatuh Tempo',
    'has_issue': 'Terdapat Isu',
    'unpaid': 'Tidak bayar',
    'reserved_hr': 'Dicadangkan (HR)',
    'contract_moved': 'Kontrak dipindahkan'
  };
  return statusLabels[status] || status;
}

function getPaymentStatusClass(status: string): string {
  const statusClasses: Record<string, string> = {
    'paid': 'status-paid',
    'ba_process': 'status-process',
    'mka_process': 'status-process',
    'settlement_process': 'status-process',
    'addendum_process': 'status-process',
    'not_due': 'status-pending',
    'has_issue': 'status-issue',
    'unpaid': 'status-unpaid',
    'reserved_hr': 'status-reserved',
    'contract_moved': 'status-moved'
  };
  return statusClasses[status] || 'status-default';
}

function getPeriodPaymentValue(period: ContractPeriod): string {
  if (props.contract?.currency_type === 'rp' && period.payment_value_rp) {
    return formatCurrency(period.payment_value_rp);
  } else if (props.contract?.currency_type === 'non_rp' && period.payment_value_non_rp) {
    return formatCurrency(period.payment_value_non_rp, false);
  }
  return '';
}
</script>

<style scoped>
/* Loader */
#loader {
  position: absolute;
  left: 50%;
  top: 50%;
  z-index: 1;
  width: 60px;
  height: 60px;
  margin: -30px 0 0 -30px;
  border: 8px solid rgba(0, 0, 0, 0.1);
  border-radius: 50%;
  border-top-color: var(--primary-color);
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Contract specific styles */
.contract-not-found {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 80vh;
  padding: var(--spacing-8);
}

.not-found-content {
  text-align: center;
  color: var(--text-muted);
}

.not-found-icon {
  font-size: 4rem;
  margin-bottom: var(--spacing-4);
  opacity: 0.5;
}

.contract-content {
  padding: 1rem;
  height: 96%;
  max-height: 100%;
  overflow-y: auto;
}

.contract-header {
  display: flex;
  justify-content: center;
  align-items: center;
  margin-bottom: var(--spacing-8);
  padding-bottom: var(--spacing-4);
  border-bottom: 1px solid var(--border-color);
}

.contract-title-section {
  text-align: center;
}

.contract-title-section h1 {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-color);
  margin: 0 0 var(--spacing-2) 0;
}

.contract-info {
  margin: 0;
  color: var(--primary-color);
  font-size: 1.1rem;
  font-weight: 500;
  text-align: center;
}

.contract-details {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-8);
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: var(--spacing-8);
}

.detail-section, .periods-section, .metadata-section {
  background: white;
  border-radius: var(--radius-md);
  padding: var(--spacing-6);
  box-shadow: var(--shadow);
}

.section-title {
  display: flex;
  align-items: center;
  gap: var(--spacing-2);
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-color);
  margin: 0 0 var(--spacing-4) 0;
}

.detail-items {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-3);
}

.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: var(--spacing-2) 0;
}

.detail-label {
  font-weight: 500;
  color: var(--text-muted);
  font-size: 0.875rem;
}

.detail-value {
  font-weight: 600;
  color: var(--text-color);
  font-size: 0.875rem;
}

.financial-value {
  color: var(--success-color);
  font-family: 'Courier New', monospace;
}

.periods-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--spacing-4);
}

.period-card {
  background: var(--bg-alt);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  padding: var(--spacing-4);
  height: 175px;
  display: flex;
  flex-direction: column;
}

.period-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-3);
  flex-shrink: 0;
}

.period-name {
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-color);
  margin: 0;
}

.budget-badge {
  font-size: 0.75rem;
  font-weight: 600;
  padding: var(--spacing-1) var(--spacing-2);
  border-radius: var(--radius-sm);
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

.payment-badge {
  font-size: 0.75rem;
  font-weight: 500;
  padding: 0.125rem var(--spacing-2);
  border-radius: var(--radius-sm);
}

.status-paid { background: #dcfce7; color: #166534; }
.status-process { background: #fef3c7; color: #92400e; }
.status-pending { background: #e0e7ff; color: #3730a3; }
.status-issue { background: #fecaca; color: #dc2626; }
.status-unpaid { background: var(--bg-alt); color: var(--text-color-light); }
.status-reserved { background: #e879f9; color: #86198f; }
.status-moved { background: #d1fae5; color: #059669; }

.payment-amount {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--success-color);
  font-family: 'Courier New', monospace;
}

/* Responsive */
@media (max-width: 768px) {
  .contract-content {
    padding: var(--spacing-4);
  }
  
  .detail-grid {
    grid-template-columns: 1fr;
  }
  
  .periods-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<style scoped src="../../css/app.css"></style>
