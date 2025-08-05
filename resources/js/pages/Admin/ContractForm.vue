<template>
  <div class="admin-container">
    <AdminNavbar :title="(contract ? 'Edit' : 'Buat') + ' Kontrak'" :showBackButton="true" backUrl="/admin/contracts" />

    <AdminForm @submit="submit">
      <AdminFormSection title="Informasi Dasar">
        <div class="admin-form-grid">
          <AdminFormField label="Aplikasi" id="app_id">
            <select
              id="app_id"
              v-model="form.app_id"
              class="admin-form-select"
              required
            >
              <option value="">Pilih Aplikasi</option>
              <option v-for="app in sortedApps" :key="app.app_id" :value="app.app_id">
                {{ app.app_name }}
              </option>
            </select>
          </AdminFormField>

          <AdminFormField label="Judul Kontrak" id="title">
            <input
              id="title"
              v-model="form.title"
              type="text"
              class="admin-form-input"
              required
            />
          </AdminFormField>

          <AdminFormField label="Nomor Kontrak" id="contract_number">
            <input
              id="contract_number"
              v-model="form.contract_number"
              type="text"
              class="admin-form-input"
              required
            />
          </AdminFormField>

          <AdminFormField label="Tipe Mata Uang" id="currency_type">
            <select
              id="currency_type"
              v-model="form.currency_type"
              class="admin-form-select"
              required
            >
              <option value="">Pilih Mata Uang</option>
              <option value="rp">Rupiah (RP)</option>
              <option value="non_rp">Mata Uang Asing (Non-RP)</option>
            </select>
          </AdminFormField>
        </div>
      </AdminFormSection>

      <AdminFormSection title="Informasi Finansial">
        <div class="admin-form-grid">
          <AdminFormField 
            v-if="form.currency_type === 'rp'" 
            label="Nilai Kontrak (RP)" 
            id="contract_value_rp"
          >
            <input
              id="contract_value_rp"
              v-model="form.contract_value_rp"
              type="number"
              step="0.01"
              class="admin-form-input"
              placeholder="0.00"
            />
          </AdminFormField>

          <AdminFormField 
            v-if="form.currency_type === 'non_rp'" 
            label="Nilai Kontrak (Non-RP)" 
            id="contract_value_non_rp"
          >
            <input
              id="contract_value_non_rp"
              v-model="form.contract_value_non_rp"
              type="number"
              step="0.01"
              class="admin-form-input"
              placeholder="0.00"
            />
          </AdminFormField>

          <AdminFormField 
            v-if="form.currency_type === 'rp'" 
            label="Nilai Kontrak Lumpsum (RP)" 
            id="lumpsum_value_rp"
          >
            <input
              id="lumpsum_value_rp"
              v-model="form.lumpsum_value_rp"
              type="number"
              step="0.01"
              class="admin-form-input"
              placeholder="0.00"
            />
          </AdminFormField>

          <AdminFormField 
            v-if="form.currency_type === 'rp'" 
            label="Nilai Kontrak Satuan (RP)" 
            id="unit_value_rp"
          >
            <input
              id="unit_value_rp"
              v-model="form.unit_value_rp"
              type="number"
              step="0.01"
              class="admin-form-input"
              placeholder="0.00"
            />
          </AdminFormField>
        </div>
      </AdminFormSection>

      <AdminFormSection title="Periode Kontrak">
        <ContractPeriodSection
          :periods="contractPeriods"
          :contract-currency-type="form.currency_type"
          @add="addContractPeriod"
          @remove="removeContractPeriod"
          @addMultiple="addMultipleContractPeriods"
        />
      </AdminFormSection>

      <div class="flex justify-end">
        <button type="submit" class="admin-form-submit">
          <font-awesome-icon icon="fa-solid fa-file-contract" class="mr-2" />
          {{ contract ? 'Ubah' : 'Buat' }} Kontrak
        </button>
      </div>
    </AdminForm>
  </div>
</template>

<script setup lang="ts">
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';
import { ref, onMounted, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminForm from '@/components/Admin/AdminForm.vue';
import AdminFormSection from '@/components/Admin/AdminFormSection.vue';
import AdminFormField from '@/components/Admin/AdminFormField.vue';
import ContractPeriodSection from '@/components/Admin/ContractPeriodSection.vue';
import { useNotification } from '@/composables/useNotification';

const { showSuccess, showError } = useNotification();

interface App {
  app_id: number;
  app_name: string;
}

interface Contract {
  id: number;
  app_id: number;
  title: string;
  contract_number: string;
  currency_type: 'rp' | 'non_rp';
  contract_value_rp: string;
  contract_value_non_rp: string;
  lumpsum_value_rp: string;
  unit_value_rp: string;
  contract_periods?: ContractPeriod[];
}

interface ContractPeriod {
  id?: number;
  period_name: string;
  budget_type: string;
  start_date?: string;
  end_date?: string;
  payment_value_rp?: string;
  payment_value_non_rp?: string;
  payment_status: string;
}

interface FormData {
  apps: App[];
}

interface Props {
  contract?: Contract;
  formData: FormData;
  error?: string;
}

const props = defineProps<Props>();

// Computed property to sort apps alphabetically
const sortedApps = computed(() => {
  if (!props.formData?.apps) return [];
  return [...props.formData.apps].sort((a, b) => a.app_name.localeCompare(b.app_name));
});

const form = ref({
  app_id: '',
  title: '',
  contract_number: '',
  currency_type: '',
  contract_value_rp: '',
  contract_value_non_rp: '',
  lumpsum_value_rp: '',
  unit_value_rp: ''
});

const contractPeriods = ref<ContractPeriod[]>([]);

onMounted(() => {
  console.log('ContractForm mounted with props:', props);
  console.log('FormData apps:', props.formData?.apps);
  
  if (props.contract) {
    form.value = {
      app_id: props.contract.app_id?.toString() || '',
      title: props.contract.title || '',
      contract_number: props.contract.contract_number || '',
      currency_type: props.contract.currency_type || '',
      contract_value_rp: props.contract.contract_value_rp || '',
      contract_value_non_rp: props.contract.contract_value_non_rp || '',
      lumpsum_value_rp: props.contract.lumpsum_value_rp || '',
      unit_value_rp: props.contract.unit_value_rp || ''
    };
    
    // Load existing contract periods if available
    if (props.contract.contract_periods && Array.isArray(props.contract.contract_periods)) {
      const periods = props.contract.contract_periods.map(period => ({
        id: period.id,
        period_name: period.period_name || '',
        budget_type: period.budget_type || '',
        start_date: period.start_date || '',
        end_date: period.end_date || '',
        payment_value_rp: period.payment_value_rp || '',
        payment_value_non_rp: period.payment_value_non_rp || '',
        payment_status: period.payment_status || ''
      }));
      
      // Keep the original order from backend (sorted by ID)
      contractPeriods.value = periods;
    }
  }
});

// Watch currency type changes to clear inappropriate fields
watch(() => form.value.currency_type, (newType) => {
  if (newType === 'rp') {
    form.value.contract_value_non_rp = '';
  } else if (newType === 'non_rp') {
    form.value.contract_value_rp = '';
    form.value.lumpsum_value_rp = '';
    form.value.unit_value_rp = '';
  }
});

// Contract Period Management
function addContractPeriod() {
  contractPeriods.value.push({
    period_name: '',
    budget_type: '',
    start_date: '',
    end_date: '',
    payment_value_rp: '',
    payment_value_non_rp: '',
    payment_status: ''
  });
}

function removeContractPeriod(index: number) {
  contractPeriods.value.splice(index, 1);
}

function addMultipleContractPeriods(periods: ContractPeriod[]) {
  contractPeriods.value.push(...periods);
}

function submit() {
  // Convert form data to proper types
  const submitData = {
    ...form.value,
    app_id: parseInt(form.value.app_id),
    contract_value_rp: form.value.contract_value_rp ? parseFloat(form.value.contract_value_rp) : null,
    contract_value_non_rp: form.value.contract_value_non_rp ? parseFloat(form.value.contract_value_non_rp) : null,
    lumpsum_value_rp: form.value.lumpsum_value_rp ? parseFloat(form.value.lumpsum_value_rp) : null,
    unit_value_rp: form.value.unit_value_rp ? parseFloat(form.value.unit_value_rp) : null,
    contract_periods: contractPeriods.value.map(period => ({
      ...period,
      payment_value_rp: period.payment_value_rp ? parseFloat(period.payment_value_rp) : null,
      payment_value_non_rp: period.payment_value_non_rp ? parseFloat(period.payment_value_non_rp) : null,
    }))
  };

  if (props.contract) {
    router.put(`/admin/contracts/${props.contract.id}`, submitData, {
      onSuccess: () => {
        showSuccess('Contract updated successfully');
      },
      onError: (errors) => {
        showError('Failed to update contract: ' + Object.values(errors).join(', '));
      },
    });
  } else {
    router.post('/admin/contracts', submitData, {
      onSuccess: () => {
        showSuccess('Contract created successfully');
      },
      onError: (errors) => {
        showError('Failed to create contract: ' + Object.values(errors).join(', '));
      },
    });
  }
}
</script>

<style scoped>
@import '../../../css/admin.css';
</style>
