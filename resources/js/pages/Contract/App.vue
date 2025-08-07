<template>
  <div id="container">
    <Sidebar 
      :title="sidebarTitle" 
      icon="fa-solid fa-file-contract"
      :show-close-button="true"
      @close="closeSidebar"
    >
      <SidebarNavigation :links="navigationLinks"/>
      
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
        <ErrorState 
          v-if="error || !app"
          :title="!app || (error && error.includes('Application not found')) ? 'Aplikasi tidak ditemukan' : 'Terjadi kesalahan'"
          :app="app"
        />

        <ErrorState 
          v-else-if="!contract"
          title="Kontrak tidak ditemukan"
          :show-back-button="false"
        />

        <ErrorState 
          v-else-if="!hasAnyContractData"
          :show-back-button="false"
        />

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
              <DetailSection title="Informasi Dasar" icon="fa-solid fa-info-circle">
                <DetailItem label="Aplikasi">
                  <div v-if="contract.apps && contract.apps.length > 0" class="apps-list">
                    <span v-for="(contractApp, index) in contract.apps" :key="contractApp.app_id" class="app-item">
                      {{ contractApp.app_name }}<span v-if="index < contract.apps.length - 1">, </span>
                    </span>
                  </div>
                  <span v-else-if="app">{{ app.app_name }}</span>
                  <span v-else class="text-muted">Tidak ada aplikasi terkait</span>
                </DetailItem>
                <DetailItem label="Nomor Kontrak" :value="contract.contract_number" />
                <DetailItem label="Tipe Mata Uang" :value="contract.currency_type_label" />
              </DetailSection>

              <!-- Financial Information -->
              <DetailSection title="Informasi Keuangan" icon="fa-solid fa-dollar-sign">
                <template v-for="field in financialFields" :key="field.label">
                  <DetailItem :label="field.label">
                    <FinancialValue :value="field.value" :is-rupiah="field.isRupiah" />
                  </DetailItem>
                </template>
              </DetailSection>
            </div>

            <!-- Contract Periods -->
            <div v-if="contract.contract_periods && contract.contract_periods.length > 0" class="periods-section">
              <h3 class="section-title">
                <font-awesome-icon icon="fa-solid fa-calendar-alt" />
                Periode Kontrak
              </h3>
              <div class="periods-grid">
                <PeriodCard
                  v-for="(period, index) in contract.contract_periods"
                  :key="index"
                  :ref="(el) => setPeriodCardRef(el, index)"
                  :period="period"
                  :currency-type="contract.currency_type"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useSidebar } from '../../composables/useSidebar';
import { useRoutes } from '../../composables/useRoutes';
import Sidebar from '../../components/Sidebar/Sidebar.vue';
import SidebarNavigation from '../../components/Sidebar/SidebarNavigation.vue';
import SidebarContractPeriod from '../../components/Sidebar/SidebarContractPeriod.vue';
import ErrorState from '../../components/ErrorState.vue';
import DetailSection from '../../components/Contract/DetailSection.vue';
import DetailItem from '../../components/Contract/DetailItem.vue';
import FinancialValue from '../../components/Contract/FinancialValue.vue';
import PeriodCard from '../../components/Contract/PeriodCard.vue';
import { getAppDisplayText, getFinancialFields } from '../../utils/contractHelpers';
import { getContractPeriodAlertStatus, type AlertStatus } from '../../utils/contractAlerts';

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
}

interface Props {
  contract?: Contract;
  app?: App;
  allContracts: Contract[];
  error?: string;
}

const props = defineProps<Props>();

const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
const { visitRoute } = useRoutes();
const loading = ref(false);

// Refs for period cards and auto-scroll functionality
const periodCardRefs = ref<(any)[]>([]);
const setPeriodCardRef = (el: any, index: number) => {
  if (el) {
    periodCardRefs.value[index] = el;
  }
};

const navigationLinks = [
  {
    icon: 'fa-solid fa-home',
    text: 'Halaman Utama',
    onClick: () => visitRoute('index'),
  },
  {
    icon: 'fa-solid fa-file-contract',
    text: 'Semua Kontrak',
    onClick: () => visitRoute('contract.index'),
  },
  {
    icon: 'fa-solid fa-project-diagram',
    text: 'Halaman Integrasi',
    onClick: () => {
      // If we have a single app context, go to that app's integration
      if (props.app) {
        visitRoute('appIntegration', { app_id: props.app.app_id });
      } else if (props.contract?.apps && props.contract.apps.length === 1) {
        // If contract has only one app, use that
        visitRoute('appIntegration', { app_id: props.contract.apps[0].app_id });
      } else {
        // Otherwise go to general integration page or disable
        visitRoute('index');
      }
    },
  },
  {
    icon: 'fa-solid fa-microchip',
    text: 'Halaman Teknologi',
    onClick: () => {
      if (props.app) {
        visitRoute('technology.app', { app_id: props.app.app_id });
      } else if (props.contract?.apps && props.contract.apps.length === 1) {
        visitRoute('technology.app', { app_id: props.contract.apps[0].app_id });
      } else {
        visitRoute('technology.index');
      }
    },
  },
];

// Computed property for sidebar title
const sidebarTitle = computed(() => {
  if (props.app) {
    return `Kontrak ${props.app.app_name}`;
  } else if (props.contract?.apps && props.contract.apps.length === 1) {
    return `Kontrak ${props.contract.apps[0].app_name}`;
  } else if (props.contract?.apps && props.contract.apps.length > 1) {
    return `Kontrak Multi-Aplikasi`;
  } else {
    return 'Kontrak Aplikasi';
  }
});

const hasAnyContractData = computed(() => {
  if (!props.contract) return false;
  
  return Boolean(
    props.contract.title ||
    props.contract.contract_number ||
    props.contract.contract_value_rp ||
    props.contract.contract_value_non_rp ||
    props.contract.lumpsum_value_rp ||
    props.contract.unit_value_rp ||
    (props.contract.contract_periods && props.contract.contract_periods.length > 0)
  );
});

const financialFields = computed(() => {
  return getFinancialFields(props.contract);
});

// Auto-scroll to first alert period card
const scrollToFirstAlert = async () => {
  if (!props.contract?.contract_periods) return;
  
  await nextTick();
  
  // Find the first period with danger or warning alert (in original order)
  const firstAlertIndex = props.contract.contract_periods.findIndex(period => {
    const alertStatus = getContractPeriodAlertStatus(period);
    return alertStatus === 'danger' || alertStatus === 'warning';
  });
  
  if (firstAlertIndex !== -1 && periodCardRefs.value[firstAlertIndex]) {
    const cardElement = periodCardRefs.value[firstAlertIndex].$el || periodCardRefs.value[firstAlertIndex];
    if (cardElement && cardElement.scrollIntoView) {
      cardElement.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
      });
    }
  }
};

// Trigger auto-scroll on mount
onMounted(() => {
  scrollToFirstAlert();
});

const backToAppUrl = computed(() => {
  return props.app ? `/technology/${props.app.app_id}` : '/';
});
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

.periods-section {
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

.apps-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.app-item {
  color: var(--text-color);
  font-weight: 600;
}

.text-muted {
  color: var(--text-muted);
  font-style: italic;
}

.periods-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: var(--spacing-4);
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

<style scoped src="../../../css/app.css"></style>
