<template>
  <span :class="['payment-badge', badgeClass]">
    {{ statusLabel }}
  </span>
</template>

<script setup lang="ts">
interface Props {
  status: string;
}

const props = defineProps<Props>();

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

const statusLabel = computed(() => statusLabels[props.status] || props.status);
const badgeClass = computed(() => statusClasses[props.status] || 'status-default');
</script>

<script lang="ts">
import { computed } from 'vue';
</script>

<style scoped>
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
.status-default { background: var(--bg-alt); color: var(--text-color-light); }
</style>
