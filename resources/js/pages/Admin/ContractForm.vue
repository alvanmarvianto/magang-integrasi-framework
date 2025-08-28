<template>
  <div class="admin-container">
    <AdminNavbar :title="(contract ? 'Edit' : 'Buat') + ' Kontrak'" :showBackButton="true" backUrl="/admin/contracts" />

    <AdminForm @submit="submit">
      <AdminFormSection title="Informasi Dasar">
        <div class="admin-form-grid">
          <div class="admin-form-field-full">
            <div class="app-selection-section">
              <div class="app-section-header">
                <label class="admin-form-label">
                  Aplikasi
                </label>
                <button
                  type="button"
                  @click="addAppSelection"
                  class="app-section-add"
                >
                  <font-awesome-icon icon="fa-solid fa-plus" /> Add App
                </button>
              </div>

              <div v-if="selectedApps.length === 0" class="app-section-empty">
                Pilih aplikasi untuk kontrak ini
              </div>

              <div v-else class="app-section-items">
                <div
                  v-for="(appItem, index) in selectedApps"
                  :key="index"
                  class="app-section-item"
                >
                  <select
                    v-model="appItem.app_id"
                    class="app-section-select"
                    required
                  >
                    <option value="">Pilih Aplikasi</option>
                    <option 
                      v-for="app in sortedApps" 
                      :key="app.app_id" 
                      :value="app.app_id"
                      :disabled="isAppAlreadySelected(app.app_id, index)"
                    >
                      {{ app.app_name }}
                    </option>
                  </select>

                  <button
                    type="button"
                    @click="removeAppSelection(index)"
                    class="app-section-remove"
                  >
                    <font-awesome-icon icon="fa-solid fa-trash" />
                  </button>
                </div>
              </div>
            </div>
          </div>

          <AdminFormField label="Judul Kontrak" id="title" :required="true">
            <input
              id="title"
              v-model="form.title"
              type="text"
              class="admin-form-input"
              :class="{ 'error': hasFieldError('title') }"
              required
            />
            <div v-if="hasFieldError('title')" class="error-message">
              {{ getFieldError('title') }}
            </div>
          </AdminFormField>

          <AdminFormField label="Nomor Kontrak" id="contract_number" :required="true">
            <input
              id="contract_number"
              v-model="form.contract_number"
              type="text"
              class="admin-form-input"
              :class="{ 'error': hasFieldError('contract_number') }"
              required
            />
            <div v-if="hasFieldError('contract_number')" class="error-message">
              {{ getFieldError('contract_number') }}
            </div>
          </AdminFormField>

          <AdminFormField label="Tipe Mata Uang" id="currency_type" :required="true">
            <select
              id="currency_type"
              v-model="form.currency_type"
              class="admin-form-select"
              :class="{ 'error': hasFieldError('currency_type') }"
              required
            >
              <option value="">Pilih Mata Uang</option>
              <option value="rp">Rupiah (RP)</option>
              <option value="non_rp">Mata Uang Asing (Non-RP)</option>
            </select>
            <div v-if="hasFieldError('currency_type')" class="error-message">
              {{ getFieldError('currency_type') }}
            </div>
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
import { useFormErrors } from '@/composables/useFormErrors';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const { showSuccess, showError } = useNotification();
const { errors, setErrors, clearErrors, getFieldError, hasFieldError } = useFormErrors();

interface App {
  app_id: number;
  app_name: string;
}

interface AppSelection {
  app_id: number | string;
}

interface Contract {
  id: number;
  title: string;
  contract_number: string;
  currency_type: 'rp' | 'non_rp';
  contract_value_rp: string;
  contract_value_non_rp: string;
  lumpsum_value_rp: string;
  unit_value_rp: string;
  apps?: App[];
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
  app_ids: [] as number[],
  title: '',
  contract_number: '',
  currency_type: '',
  contract_value_rp: '',
  contract_value_non_rp: '',
  lumpsum_value_rp: '',
  unit_value_rp: ''
});

const selectedApps = ref<AppSelection[]>([]);
const contractPeriods = ref<ContractPeriod[]>([]);

onMounted(() => {
  if (props.contract) {
    form.value = {
      app_ids: props.contract.apps?.map(app => app.app_id) || [],
      title: props.contract.title || '',
      contract_number: props.contract.contract_number || '',
      currency_type: props.contract.currency_type || '',
      contract_value_rp: props.contract.contract_value_rp || '',
      contract_value_non_rp: props.contract.contract_value_non_rp || '',
      lumpsum_value_rp: props.contract.lumpsum_value_rp || '',
      unit_value_rp: props.contract.unit_value_rp || ''
    };
    
    // Initialize selectedApps from existing contract
    if (props.contract.apps && props.contract.apps.length > 0) {
      selectedApps.value = props.contract.apps.map(app => ({
        app_id: app.app_id
      }));
    }
    
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
  } else {
    // For new contracts, initialize with one empty app selection and one empty contract period
    addAppSelection();
    addContractPeriod();
  }
});

// App Selection Management
function addAppSelection() {
  selectedApps.value.push({
    app_id: ''
  });
}

function removeAppSelection(index: number) {
  selectedApps.value.splice(index, 1);
  updateFormAppIds();
}

function isAppAlreadySelected(appId: number, currentIndex: number): boolean {
  return selectedApps.value.some((item, index) => 
    index !== currentIndex && item.app_id === appId
  );
}

function updateFormAppIds() {
  form.value.app_ids = selectedApps.value
    .map(item => Number(item.app_id))
    .filter(id => !isNaN(id) && id > 0);
}

// Watch selectedApps changes to update form.app_ids
watch(selectedApps, () => {
  updateFormAppIds();
}, { deep: true });

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
  clearErrors(); // Clear previous errors

  // Validate that at least one app is selected
  if (form.value.app_ids.length === 0) {
    showError('Minimal satu aplikasi harus dipilih');
    return;
  }

  // Validate that at least one contract period exists
  if (contractPeriods.value.length === 0) {
    showError('Minimal satu periode kontrak harus dibuat');
    return;
  }

  // Validate contract periods
  for (let i = 0; i < contractPeriods.value.length; i++) {
    const period = contractPeriods.value[i];
    if (!period.period_name || !period.budget_type || !period.payment_status) {
      showError(`Periode ${i + 1}: Nama periode, tipe anggaran, dan status pembayaran wajib diisi`);
      return;
    }
  }

  // Convert form data to proper types
  const submitData = {
    ...form.value,
    app_ids: form.value.app_ids,
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
        setErrors(errors);
        showError('Failed to update contract: ' + Object.values(errors).join(', '));
      },
    });
  } else {
    router.post('/admin/contracts', submitData, {
      onSuccess: () => {
        showSuccess('Contract created successfully');
      },
      onError: (errors) => {
        setErrors(errors);
        showError('Failed to create contract: ' + Object.values(errors).join(', '));
      },
    });
  }
}
</script>

<style scoped>
@import '../../../css/admin.css';

.admin-form-select[multiple] {
  min-height: 120px;
}

.form-help {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.25rem;
  font-style: italic;
}

.admin-form-field-full {
  grid-column: 1 / -1;
}

/* App Selection Styles - Similar to TechnologySection */
.app-selection-section {
  background-color: white;
  border: 1px solid var(--border-color, #d1d5db);
  border-radius: 8px;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.app-section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--border-color, #d1d5db);
}

.app-section-add {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  color: var(--primary-color, #3b82f6);
  background-color: #f8fafc;
  border: 1px solid var(--border-color, #d1d5db);
  border-radius: 6px;
  transition: all 0.2s ease;
  cursor: pointer;
}

.app-section-add:hover {
  background-color: var(--primary-color, #3b82f6);
  color: white;
  border-color: var(--primary-color, #3b82f6);
}

.app-section-empty {
  padding: 2rem;
  text-align: center;
  color: #6b7280;
  background-color: #f8fafc;
  border-radius: 6px;
  font-size: 0.875rem;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.app-section-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  overflow-y: auto;
  flex: 1;
  padding: 0.5rem;
  margin: -0.5rem;
}

.app-section-item {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 0.75rem;
  align-items: start;
  padding: 0.75rem;
  background-color: #f8fafc;
  border-radius: 6px;
  transition: background-color 0.2s ease;
}

.app-section-item:hover {
  background-color: #f1f5f9;
}

.app-section-select {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid var(--border-color, #d1d5db);
  border-radius: 6px;
  font-size: 0.875rem;
  background-color: white;
  transition: all 0.2s ease;
}

.app-section-select:focus {
  outline: none;
  border-color: var(--primary-color, #3b82f6);
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.app-section-remove {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  color: #dc2626;
  background-color: #f8fafc;
  border: 1px solid var(--border-color, #d1d5db);
  border-radius: 6px;
  transition: all 0.2s ease;
  cursor: pointer;
}

.app-section-remove:hover {
  background-color: #dc2626;
  color: white;
  border-color: #dc2626;
}

.required-asterisk {
  color: #dc2626;
  margin-left: 0.25rem;
  font-weight: 600;
}

/* Error styles */
.admin-form-input.error,
.admin-form-select.error,
.app-section-select.error {
  border-color: var(--danger-color);
  box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.1);
}

.error-message {
  color: var(--danger-color);
  font-size: 0.75rem;
  margin-top: 0.25rem;
  font-weight: 500;
}
</style>
