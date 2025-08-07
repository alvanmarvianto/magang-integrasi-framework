<template>
  <div id="container">
    <Sidebar 
      title="Semua Kontrak" 
      icon="fa-solid fa-file-contract"
      :show-close-button="true"
      @close="closeSidebar"
    >
      <SidebarNavigation :links="navigationLinks"/>
    </Sidebar>

    <main id="main-content">
      <div id="menu-toggle" v-show="isMobile && !visible" :class="{ active: visible }" @click.stop="toggleSidebar">
        <FontAwesomeIcon icon="fa-solid fa-bars" />
      </div>
      <div id="loader" v-if="loading"></div>
      <div id="body">
        <div class="index-content">
          <!-- Header -->
          <div class="index-header">
            <div class="header-content">
              <h1>
                <font-awesome-icon icon="fa-solid fa-file-contract" />
                Daftar Kontrak
              </h1>
            </div>
          </div>

          <!-- Statistics -->
          <div class="cards-section">
            <div class="cards-grid">
              <div class="stat-card danger">
                <div class="stat-icon">
                  <font-awesome-icon icon="fa-solid fa-exclamation-triangle" />
                </div>
                <div class="stat-content">
                  <div class="stat-number">{{ stats.dangerCount }}</div>
                  <div class="stat-label">Terlambat</div>
                </div>
              </div>
              <div class="stat-card warning">
                <div class="stat-icon">
                  <font-awesome-icon icon="fa-solid fa-clock" />
                </div>
                <div class="stat-content">
                  <div class="stat-number">{{ stats.warningCount }}</div>
                  <div class="stat-label">Hampir Jatuh Tempo</div>
                </div>
              </div>
              <div class="stat-card none">
                <div class="stat-icon">
                  <font-awesome-icon icon="fa-solid fa-check-circle" />
                </div>
                <div class="stat-content">
                  <div class="stat-number">{{ stats.normalCount }}</div>
                  <div class="stat-label">Normal</div>
                </div>
              </div>
              <div class="stat-card total">
                <div class="stat-icon">
                  <font-awesome-icon icon="fa-solid fa-file-contract" />
                </div>
                <div class="stat-content">
                  <div class="stat-number">{{ stats.totalCount }}</div>
                  <div class="stat-label">Total Kontrak</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Filter Section -->
          <div class="filter-section">
            <div class="filter-controls">
              <div class="filter-item">
                <label>Filter by Alert:</label>
                <select v-model="selectedFilter" class="filter-select">
                  <option value="all">Semua</option>
                  <option value="danger">Terlambat</option>
                  <option value="warning">Hampir Jatuh Tempo</option>
                  <option value="none">Normal</option>
                </select>
              </div>
              <div class="filter-item">
                <label>Search:</label>
                <input 
                  v-model="searchQuery" 
                  type="text" 
                  placeholder="Cari kontrak atau aplikasi..." 
                  class="search-input"
                >
              </div>
            </div>
          </div>

          <!-- Contracts List -->
          <div>
            <div v-if="filteredContracts.length === 0" class="no-content">
              <font-awesome-icon icon="fa-solid fa-inbox" class="no-content-icon" />
              <h3>Tidak ada kontrak ditemukan</h3>
              <p>{{ searchQuery ? 'Coba ubah kata kunci pencarian' : 'Belum ada kontrak yang tersedia' }}</p>
            </div>
            
            <div v-else class="contracts-grid">
              <ContractIndexCard
                v-for="contract in filteredContracts"
                :key="contract.id"
                :contract="contract"
                @click="goToContract(contract)"
              />
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useSidebar } from '../../composables/useSidebar';
import { useRoutes } from '../../composables/useRoutes';
import Sidebar from '../../components/Sidebar/Sidebar.vue';
import SidebarNavigation from '../../components/Sidebar/SidebarNavigation.vue';
import ContractIndexCard from '../../components/Contract/ContractIndexCard.vue';
import { getContractAlertPriority, getContractPeriodAlertStatus, type AlertStatus } from '../../utils/contractAlerts';

interface App {
  app_id: number;
  app_name: string;
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
  apps?: App[];
  contract_periods?: ContractPeriod[];
}

interface Props {
  contracts: Contract[];
}

const props = defineProps<Props>();

const { visible, isMobile, toggleSidebar, closeSidebar } = useSidebar();
const { visitRoute } = useRoutes();
const loading = ref(false);
const selectedFilter = ref<'all' | 'danger' | 'warning' | 'none'>('all');
const searchQuery = ref('');

const navigationLinks = [
  {
    icon: 'fa-solid fa-home',
    text: 'Halaman Utama',
    onClick: () => visitRoute('index'),
  },
  {
    icon: 'fa-solid fa-microchip',
    text: 'Spesifikasi Teknologi',
    onClick: () => visitRoute('technology.index'),
  },
];

// Add alert status to each contract and sort by priority
const contractsWithAlerts = computed(() => {
  const contractsWithData = props.contracts.map(contract => {
    const alertPriority = getContractAlertPriority(contract);
    
    // Extract days from backend alert message if available
    let backendDays: number = 0;
    if (alertPriority.message) {
      const match = alertPriority.message.match(/(\d+)\s+hari/);
      if (match) {
        backendDays = parseInt(match[1]);
        // If message contains "terlambat", make it negative
        if (alertPriority.message.includes('terlambat')) {
          backendDays = -backendDays;
        }
      }
    }
    
    // Find the most critical period and calculate days difference (fallback)
    let mostCriticalDate: Date | null = null;
    let daysDifference: number = backendDays; // Use backend days if available
    
    if (backendDays === 0 && contract.contract_periods && contract.contract_periods.length > 0) {
      const activePeriods = contract.contract_periods.filter(period => period.end_date);
      if (activePeriods.length > 0) {
        // For sorting, we want the earliest end date (most urgent)
        const dates = activePeriods.map(period => new Date(period.end_date!));
        mostCriticalDate = new Date(Math.min(...dates.map(d => d.getTime())));
        
        // Calculate days difference from today
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Reset time to avoid time-based differences
        const endDate = new Date(mostCriticalDate);
        endDate.setHours(0, 0, 0, 0);
        
        const timeDiff = endDate.getTime() - today.getTime();
        daysDifference = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));
      }
    }
    
    return {
      ...contract,
      alertPriority,
      alertStatus: alertPriority.status,
      mostCriticalDate,
      daysDifference, // This now uses backend days when available
      backendDays
    };
  });

  const sorted = contractsWithData.sort((a, b) => {
    // Sort by priority first: danger (1) -> warning (2) -> normal (3)
    if (a.alertPriority.priority !== b.alertPriority.priority) {
      return a.alertPriority.priority - b.alertPriority.priority;
    }
    
    // Within same priority, sort by days difference
    if (a.alertStatus === 'danger') {
      // For danger: most overdue first (most negative daysDifference first)
      // -981 should come before -127, which should come before -38
      return a.daysDifference - b.daysDifference;
    } else if (a.alertStatus === 'warning') {
      // For warning: closer to deadline first (smaller positive numbers first)
      return a.daysDifference - b.daysDifference;
    } else {
      // For normal: sort by earliest end date (smaller positive numbers first)
      return a.daysDifference - b.daysDifference;
    }
  });

  return sorted;
});

// Filter contracts based on selected filter and search query
const filteredContracts = computed(() => {
  let filtered = contractsWithAlerts.value;

  // Filter by alert status
  if (selectedFilter.value !== 'all') {
    filtered = filtered.filter(contract => contract.alertStatus === selectedFilter.value);
  }

  // Filter by search query
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase().trim();
    filtered = filtered.filter(contract => {
      const matchTitle = contract.title.toLowerCase().includes(query);
      const matchNumber = contract.contract_number.toLowerCase().includes(query);
      const matchApps = contract.apps?.some(app => 
        app.app_name.toLowerCase().includes(query)
      ) || false;
      
      return matchTitle || matchNumber || matchApps;
    });
  }

  return filtered;
});

// Calculate statistics
const stats = computed(() => {
  const contracts = contractsWithAlerts.value;
  return {
    totalCount: contracts.length,
    dangerCount: contracts.filter(c => c.alertStatus === 'danger').length,
    warningCount: contracts.filter(c => c.alertStatus === 'warning').length,
    normalCount: contracts.filter(c => c.alertStatus === 'none').length,
  };
});

// Navigate to specific contract
const goToContract = (contract: Contract) => {
  if (contract.apps && contract.apps.length > 0) {
    // Use the first app to construct the route
    const appId = contract.apps[0].app_id;
    visitRoute('contract.show', { app_id: appId, contract_id: contract.id });
  }
};
</script>

<style scoped>
/* Contract Index specific styles */
.contracts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: var(--spacing-4);
}

/* Statistics/Cards Grid (Contract-specific) */
.cards-section {
  margin-bottom: var(--spacing-8);
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--spacing-4);
}

.stat-card {
  display: flex;
  align-items: center;
  gap: var(--spacing-4);
  padding: var(--spacing-4);
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow);
  border-left: 4px solid transparent;
  transition: all 0.3s ease;
}

/* Status-based styling for stat cards */
.stat-card.danger {
  border-left-color: var(--danger-color);
  background: #fef2f2;
}

.stat-card.warning {
  border-left-color: var(--warning-color);
  background: #fffbeb;
}

.stat-card.none,
.stat-card.success {
  border-left-color: var(--success-color);
  background: #f0fdf4;
}

.stat-card.total,
.stat-card.primary {
  border-left-color: var(--primary-color);
}

/* Icon styling */
.stat-icon {
  font-size: 2rem;
  width: 3rem;
  height: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.1);
}

.stat-card.danger .stat-icon {
  color: var(--danger-color);
  background: rgba(220, 53, 69, 0.1);
}

.stat-card.warning .stat-icon {
  color: var(--warning-color);
  background: rgba(255, 193, 7, 0.1);
}

.stat-card.none .stat-icon,
.stat-card.success .stat-icon {
  color: var(--success-color);
  background: rgba(40, 167, 69, 0.1);
}

.stat-card.total .stat-icon,
.stat-card.primary .stat-icon {
  color: var(--primary-color);
  background: rgba(0, 123, 255, 0.1);
}

/* Category content styling */
.stat-content {
  flex: 1;
}

.stat-number {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--text-color);
  margin: 0;
}

.stat-label {
  font-size: 0.875rem;
  color: var(--text-muted);
  margin: 0;
}

/* Filter Section (Contract-specific) */
.filter-section {
  margin-bottom: var(--spacing-6);
  padding: var(--spacing-4);
  background: white;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow);
}

.filter-controls {
  display: flex;
  flex-wrap: wrap;
  gap: var(--spacing-4);
  align-items: end;
}

.filter-item {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-1);
}

.filter-item label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--text-color);
}

.filter-select,
.search-input {
  padding: 0.5rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius-sm);
  font-size: 0.875rem;
  min-width: 150px;
}

.search-input {
  min-width: 250px;
}

.filter-select:focus,
.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

/* Responsive */
@media (max-width: 768px) {
  .contracts-grid {
    grid-template-columns: 1fr;
  }

  .cards-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  }

  .filter-controls {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-item {
    width: 100%;
  }

  .filter-select,
  .search-input {
    min-width: unset;
    width: 100%;
  }
}
</style>

<style scoped src="../../../css/index-shared.css"></style>
<style scoped src="../../../css/app.css"></style>
