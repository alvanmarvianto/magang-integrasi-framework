<template>
  <div class="admin-stats-grid">
    <div v-for="stat in statsData" :key="stat.key" class="admin-stat-card">
      <div class="stat-icon" :class="stat.iconClasses">
        <font-awesome-icon :icon="stat.icon" />
      </div>
      <div class="stat-content">
        <div class="stat-label">{{ stat.label }}</div>
        <div class="stat-value">{{ formatValue(stat.value, stat.type) }}</div>
        <div v-if="stat.subtitle" class="stat-subtitle">{{ stat.subtitle }}</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

interface StatItem {
  key: string;
  label: string;
  value: number | string;
  type?: 'number' | 'currency' | 'percentage';
  icon: string;
  iconClasses: string;
  subtitle?: string;
}

interface Props {
  statistics: Record<string, any>;
  type: 'apps' | 'contracts' | 'integrations';
}

const props = defineProps<Props>();

const statsData = computed(() => {
  if (!props.statistics) return [];
  
  switch (props.type) {
    case 'apps':
      return getAppStats(props.statistics);
    case 'contracts':
      return getContractStats(props.statistics);
    case 'integrations':
      return getIntegrationStats(props.statistics);
    default:
      return [];
  }
});

function getAppStats(stats: any): StatItem[] {
  return [
    {
      key: 'total_apps',
      label: 'Total Applications',
      value: stats.total_apps || 0,
      type: 'number',
      icon: 'fa-solid fa-desktop',
      iconClasses: 'bg-blue-100 text-blue-600'
    },
    {
      key: 'cots_apps',
      label: 'COTS Applications',
      value: stats.apps_by_type?.cots || 0,
      type: 'number',
      icon: 'fa-solid fa-cube',
      iconClasses: 'bg-green-100 text-green-600'
    },
    {
      key: 'inhouse_apps',
      label: 'In-House Applications',
      value: stats.apps_by_type?.inhouse || 0,
      type: 'number',
      icon: 'fa-solid fa-house',
      iconClasses: 'bg-purple-100 text-purple-600'
    },
    {
      key: 'outsource_apps',
      label: 'Outsource Applications',
      value: stats.apps_by_type?.outsource || 0,
      type: 'number',
      icon: 'fa-solid fa-handshake',
      iconClasses: 'bg-orange-100 text-orange-600'
    }
  ];
}

function getContractStats(stats: any): StatItem[] {
  return [
    {
      key: 'total_contracts',
      label: 'Total Contracts',
      value: stats.total_contracts || 0,
      type: 'number',
      icon: 'fa-solid fa-file-contract',
      iconClasses: 'bg-blue-100 text-blue-600'
    },
    {
      key: 'total_value_rp',
      label: 'Total Value (RP)',
      value: stats.total_value_rp || 0,
      type: 'currency',
      icon: 'fa-solid fa-coins',
      iconClasses: 'bg-green-100 text-green-600'
    },
    {
      key: 'total_value_non_rp',
      label: 'Total Value (Non-RP)',
      value: stats.total_value_non_rp || 0,
      type: 'currency',
      icon: 'fa-solid fa-dollar-sign',
      iconClasses: 'bg-purple-100 text-purple-600'
    },
    {
      key: 'apps_with_contracts',
      label: 'Apps with Contracts',
      value: stats.apps_with_contracts || 0,
      type: 'number',
      icon: 'fa-solid fa-building',
      iconClasses: 'bg-orange-100 text-orange-600'
    }
  ];
}

function getIntegrationStats(stats: any): StatItem[] {
  const connectionTypes = stats.integrations_by_connection_type || {};
  const connectionTypeEntries = Object.entries(connectionTypes);
  
  // Base stats
  const baseStats: StatItem[] = [
    {
      key: 'total_integrations',
      label: 'Total Integrations',
      value: stats.total_integrations || 0,
      type: 'number',
      icon: 'fa-solid fa-share-nodes',
      iconClasses: 'bg-blue-100 text-blue-600'
    },
    {
      key: 'sftp_connections',
      label: 'SFTP Connections',
      value: stats.integrations_by_connection_type?.sftp || stats.integrations_by_connection_type?.SFTP || 0,
      type: 'number',
      icon: 'fa-solid fa-server',
      iconClasses: 'bg-green-100 text-green-600'
    }
  ];

  // Add connection type stats dynamically
  const connectionTypeStats: StatItem[] = connectionTypeEntries.slice(0, 2).map(([typeName, count], index) => {
    const iconMap: Record<string, string> = {
      'soa': 'fa-solid fa-cloud',
      'SOA': 'fa-solid fa-cloud',
      'direct': 'fa-solid fa-link',
      'Direct': 'fa-solid fa-link',
      'sftp': 'fa-solid fa-server',
      'SFTP': 'fa-solid fa-server',
      'api': 'fa-solid fa-code',
      'API': 'fa-solid fa-code',
      'database': 'fa-solid fa-database',
      'Database': 'fa-solid fa-database'
    };
    
    const colorMap: Record<number, { bg: string, text: string }> = {
      0: { bg: 'bg-purple-100', text: 'text-purple-600' },
      1: { bg: 'bg-orange-100', text: 'text-orange-600' }
    };
    
    const colors = colorMap[index] || { bg: 'bg-gray-100', text: 'text-gray-600' };
    const icon = iconMap[typeName] || iconMap[typeName.toLowerCase()] || 'fa-solid fa-plug';
    const displayName = typeName.toUpperCase();
    
    return {
      key: `connection_type_${typeName.toLowerCase()}`,
      label: `${displayName} Connections`,
      value: count as number,
      type: 'number',
      icon: icon,
      iconClasses: `${colors.bg} ${colors.text}`
    };
  });

  return [...baseStats, ...connectionTypeStats];
}

function formatValue(value: number | string, type?: string): string {
  if (typeof value === 'string') return value;
  
  switch (type) {
    case 'currency':
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
      }).format(value);
    case 'percentage':
      return `${value.toFixed(1)}%`;
    case 'number':
    default:
      return new Intl.NumberFormat('id-ID').format(value);
  }
}
</script>

<style scoped>
.admin-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.admin-stat-card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.2s ease;
}

.admin-stat-card:hover {
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  transform: translateY(-1px);
}

.stat-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.stat-content {
  flex: 1;
  min-width: 0;
}

.stat-label {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
  line-height: 1.2;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
  line-height: 1.2;
  word-break: break-word;
}

.stat-subtitle {
  font-size: 0.75rem;
  color: #9ca3af;
  margin-top: 0.25rem;
}

/* Icon color classes */
.bg-blue-100 {
  background-color: #dbeafe;
}

.text-blue-600 {
  color: #2563eb;
}

.bg-green-100 {
  background-color: #dcfce7;
}

.text-green-600 {
  color: #16a34a;
}

.bg-purple-100 {
  background-color: #f3e8ff;
}

.text-purple-600 {
  color: #9333ea;
}

.bg-orange-100 {
  background-color: #fed7aa;
}

.text-orange-600 {
  color: #ea580c;
}

.bg-red-100 {
  background-color: #fee2e2;
}

.text-red-600 {
  color: #dc2626;
}

.bg-yellow-100 {
  background-color: #fef3c7;
}

.text-yellow-600 {
  color: #d97706;
}

.bg-indigo-100 {
  background-color: #e0e7ff;
}

.text-indigo-600 {
  color: #4f46e5;
}

.bg-pink-100 {
  background-color: #fce7f3;
}

.text-pink-600 {
  color: #db2777;
}

@media (max-width: 768px) {
  .admin-stats-grid {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
  }
  
  .admin-stat-card {
    padding: 1rem;
  }
  
  .stat-icon {
    width: 2.5rem;
    height: 2.5rem;
    font-size: 1rem;
  }
  
  .stat-value {
    font-size: 1.25rem;
  }
}
</style>
