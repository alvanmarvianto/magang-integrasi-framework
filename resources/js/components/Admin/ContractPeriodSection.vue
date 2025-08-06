<template>
  <div class="period-section">
    <div class="period-section-header">
      <h3 class="period-section-title">Periode Kontrak</h3>
      <div class="period-section-actions">
        <div class="period-mode-toggle">
          <button
            type="button"
            @click="isQueryMode = false"
            :class="['toggle-btn', { active: !isQueryMode }]"
          >
            <font-awesome-icon icon="fa-solid fa-list" /> Normal
          </button>
          <button
            type="button"
            @click="isQueryMode = true"
            :class="['toggle-btn', { active: isQueryMode }]"
          >
            <font-awesome-icon icon="fa-solid fa-table" /> Query
          </button>
        </div>
        <button
          v-if="!isQueryMode"
          type="button"
          @click="$emit('add')"
          class="period-section-add"
        >
          <font-awesome-icon icon="fa-solid fa-plus" /> Tambah Periode
        </button>
      </div>
    </div>

    <div v-if="periods.length === 0 && !isQueryMode" class="period-section-empty">
      Belum ada periode kontrak yang ditambahkan
    </div>

    <!-- Normal Form Mode -->
    <div v-if="!isQueryMode" v-show="periods.length > 0" class="period-section-items">
      <div
        v-for="(period, index) in periods"
        :key="index"
        class="period-section-item"
      >
        <div class="period-form-grid">
          <!-- Row 1: Period Name, Budget Type -->
          <div class="period-form-field">
            <label :for="`period_name_${index}`" class="period-form-label">
              Nama Periode
              <span class="required-asterisk">*</span>
            </label>
            <input
              :id="`period_name_${index}`"
              v-model="period.period_name"
              type="text"
              placeholder="Nama periode"
              class="period-form-input"
              required
            />
          </div>

          <div class="period-form-field">
            <label :for="`budget_type_${index}`" class="period-form-label">
              Tipe Anggaran
              <span class="required-asterisk">*</span>
            </label>
            <select
              :id="`budget_type_${index}`"
              v-model="period.budget_type"
              class="period-form-select"
              required
            >
              <option value="">Pilih Tipe Anggaran</option>
              <option value="AO">AO</option>
              <option value="RI">RI</option>
            </select>
          </div>

          <!-- Row 2: Start Date, End Date -->
          <div class="period-form-field">
            <label :for="`start_date_${index}`" class="period-form-label">Tanggal Mulai</label>
            <input
              :id="`start_date_${index}`"
              v-model="period.start_date"
              type="date"
              class="period-form-input"
            />
          </div>

          <div class="period-form-field">
            <label :for="`end_date_${index}`" class="period-form-label">Tanggal Selesai</label>
            <input
              :id="`end_date_${index}`"
              v-model="period.end_date"
              type="date"
              class="period-form-input"
            />
          </div>

          <!-- Row 3: Payment Status, Payment Value -->
          

          <div class="period-form-field" v-if="contractCurrencyType === 'rp'">
            <label :for="`payment_value_rp_${index}`" class="period-form-label">Nilai Termin (RP)</label>
            <input
              :id="`payment_value_rp_${index}`"
              v-model="period.payment_value_rp"
              type="number"
              step="0.01"
              placeholder="0.00"
              class="period-form-input"
            />
          </div>

          <div class="period-form-field">
            <label :for="`payment_status_${index}`" class="period-form-label">
              Status Pembayaran
              <span class="required-asterisk">*</span>
            </label>
            <select
              :id="`payment_status_${index}`"
              v-model="period.payment_status"
              class="period-form-select"
              required
            >
              <option value="">Pilih Status</option>
              <option value="paid">1. Sudah bayar</option>
              <option value="ba_process">2. Proses BA</option>
              <option value="mka_process">3. Proses di MKA</option>
              <option value="settlement_process">4. Proses Settlement</option>
              <option value="addendum_process">5. Proses Addendum</option>
              <option value="not_due">6. Belum Jatuh Tempo/belum ada kebutuhan</option>
              <option value="has_issue">7. Terdapat Isu</option>
              <option value="unpaid">8. Tidak bayar</option>
              <option value="reserved_hr">9. Dicadangkan (HR)</option>
              <option value="contract_moved">10. Kontrak dipindahkan</option>
            </select>
          </div>

          <div class="period-form-field" v-if="contractCurrencyType === 'non_rp'">
            <label :for="`payment_value_non_rp_${index}`" class="period-form-label">Nilai Termin (Non-RP)</label>
            <input
              :id="`payment_value_non_rp_${index}`"
              v-model="period.payment_value_non_rp"
              type="number"
              step="0.01"
              placeholder="0.00"
              class="period-form-input"
            />
          </div>
        </div>

        <div class="period-actions">
          <button
            type="button"
            @click="$emit('remove', index)"
            class="period-section-remove"
            title="Remove Period"
          >
            <font-awesome-icon icon="fa-solid fa-trash" />
          </button>
        </div>
      </div>
    </div>

    <!-- Query Mode -->
    <div v-if="isQueryMode" class="query-mode-section">
      <div class="query-mode-header">
        <h4 class="query-mode-title">Input Data Massal</h4>
        <p class="query-mode-description">
          Masukkan data untuk setiap periode pada baris terpisah. Baris kosong akan diisi dengan null untuk field opsional.
        </p>
      </div>

      <div class="query-mode-grid">
        <div class="query-form-field">
          <label class="query-form-label">Nama Periode <span class="required">*</span></label>
          <textarea
            v-model="queryData.periodNames"
            class="query-form-textarea"
            placeholder="Pemeliharaan Tahap 1&#10;Pemeliharaan Tahap 2&#10;Pemeliharaan Tahap 3"
            rows="6"
          ></textarea>
          <small class="query-form-help">Wajib diisi untuk setiap periode</small>
        </div>

        <div class="query-form-field">
          <label class="query-form-label">Tipe Anggaran <span class="required">*</span></label>
          <textarea
            v-model="queryData.budgetTypes"
            class="query-form-textarea"
            placeholder="AO&#10;AO&#10;RI"
            rows="6"
          ></textarea>
          <small class="query-form-help">Gunakan AO atau RI</small>
        </div>

        <div class="query-form-field">
          <label class="query-form-label">Tanggal Mulai</label>
          <textarea
            v-model="queryData.startDates"
            class="query-form-textarea"
            placeholder="01-Nov-21&#10;01-Feb-22&#10;01-May-22"
            rows="6"
          ></textarea>
          <small class="query-form-help">Format: DD-MMM-YY (contoh: 01-Nov-21)</small>
        </div>

        <div class="query-form-field">
          <label class="query-form-label">Tanggal Selesai</label>
          <textarea
            v-model="queryData.endDates"
            class="query-form-textarea"
            placeholder="31-Jan-22&#10;30-Apr-22&#10;31-Jul-23"
            rows="6"
          ></textarea>
          <small class="query-form-help">Format: DD-MMM-YY (contoh: 31-Jan-22)</small>
        </div>

        <div class="query-form-field">
          <label class="query-form-label">
            Nilai Pembayaran {{ contractCurrencyType === 'rp' ? '(RP)' : '(Non-RP)' }}
          </label>
          <textarea
            v-model="queryData.paymentValues"
            class="query-form-textarea"
            placeholder="10.000,12&#10;132.000,12&#10;10.012.300,12"
            rows="6"
          ></textarea>
          <small class="query-form-help">Gunakan koma untuk desimal dan titik untuk pemisah ribuan (opsional)</small>
        </div>

        <div class="query-form-field">
          <label class="query-form-label">Status Pembayaran <span class="required">*</span></label>
          <textarea
            v-model="queryData.paymentStatuses"
            class="query-form-textarea"
            placeholder="1&#10;1. Sudah bayar&#10;10. Kontrak dipindahkan&#10;3. Proses di MKA&#10;6. Belum Jatuh Tempo/belum ada kebutuhan"
            rows="6"
          ></textarea>
          <small class="query-form-help">
            Gunakan nomor atau teks lengkap. Mendukung 1-2 digit: 1=Sudah bayar, 2=Proses BA, 3=Proses MKA, 4=Settlement, 5=Addendum, 6=Belum jatuh tempo, 7=Ada isu, 8=Tidak bayar, 9=Dicadangkan, 10=Dipindahkan
          </small>
        </div>
      </div>

      <div class="query-mode-actions">
        <button
          type="button"
          @click="processQueryData"
          class="query-process-btn"
        >
          <font-awesome-icon icon="fa-solid fa-magic" /> Proses Data
        </button>
        <button
          type="button"
          @click="clearQueryData"
          class="query-clear-btn"
        >
          <font-awesome-icon icon="fa-solid fa-trash" /> Bersihkan
        </button>
      </div>

      <div v-if="queryErrors.length > 0" class="query-errors">
        <h5>Error:</h5>
        <ul>
          <li v-for="error in queryErrors" :key="error">{{ error }}</li>
        </ul>
      </div>
    </div>
    
    <!-- Bottom Add Button for better UX -->
    <div v-if="periods.length > 0 && !isQueryMode" class="period-section-bottom">
      <button
        type="button"
        @click="$emit('add')"
        class="period-section-add-bottom"
      >
        <font-awesome-icon icon="fa-solid fa-plus" /> Tambah Periode Lainnya
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ref, reactive } from 'vue';

interface ContractPeriod {
  period_name: string;
  budget_type: string;
  start_date?: string;
  end_date?: string;
  payment_value_rp?: string;
  payment_value_non_rp?: string;
  payment_status: string;
}

const props = defineProps<{
  periods: ContractPeriod[];
  contractCurrencyType: string;
}>();

const emit = defineEmits<{
  (e: 'add'): void;
  (e: 'remove', index: number): void;
  (e: 'addMultiple', periods: ContractPeriod[]): void;
}>();

// Reactive data for query mode
const isQueryMode = ref(false);
const queryErrors = ref<string[]>([]);

const queryData = reactive({
  periodNames: '',
  budgetTypes: '',
  startDates: '',
  endDates: '',
  paymentValues: '',
  paymentStatuses: ''
});

// Payment status mapping
const paymentStatusMap: Record<string, string> = {
  '1': 'paid',
  '2': 'ba_process',
  '3': 'mka_process',
  '4': 'settlement_process',
  '5': 'addendum_process',
  '6': 'not_due',
  '7': 'has_issue',
  '8': 'unpaid',
  '9': 'reserved_hr',
  '10': 'contract_moved'
};

// Month mapping for date conversion
const monthMap: Record<string, string> = {
  'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04',
  'May': '05', 'Jun': '06', 'Jul': '07', 'Aug': '08',
  'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'
};

// Helper function to convert DD-MMM-YY to YYYY-MM-DD
function convertDateFormat(dateStr: string): string | null {
  if (!dateStr.trim()) return null;
  
  try {
    // Parse DD-MMM-YY format
    const parts = dateStr.trim().split('-');
    if (parts.length !== 3) throw new Error('Invalid date format');
    
    const day = parts[0].padStart(2, '0');
    const monthAbbr = parts[1];
    const year = parts[2];
    
    // Convert month abbreviation to number
    const month = monthMap[monthAbbr];
    if (!month) throw new Error('Invalid month abbreviation');
    
    // Convert 2-digit year to 4-digit year
    let fullYear = parseInt(year);
    if (fullYear < 50) {
      fullYear += 2000; // 00-49 -> 2000-2049
    } else {
      fullYear += 1900; // 50-99 -> 1950-1999
    }
    
    return `${fullYear}-${month}-${day}`;
  } catch (error) {
    throw new Error(`Invalid date format: ${dateStr}. Use DD-MMM-YY (e.g., 01-Nov-21)`);
  }
}

// Process query data and convert to contract periods
function processQueryData() {
  queryErrors.value = [];
  
  try {
    // Split each field by lines
    const periodNames = queryData.periodNames.split('\n');
    const budgetTypes = queryData.budgetTypes.split('\n');
    const startDates = queryData.startDates.split('\n');
    const endDates = queryData.endDates.split('\n');
    const paymentValues = queryData.paymentValues.split('\n');
    const paymentStatuses = queryData.paymentStatuses.split('\n');
    
    // Find the maximum number of periods based on required fields
    const maxPeriods = Math.max(
      periodNames.filter(name => name.trim()).length,
      budgetTypes.filter(type => type.trim()).length,
      paymentStatuses.filter(status => status.trim()).length
    );
    
    if (maxPeriods === 0) {
      queryErrors.value.push('Minimal harus ada satu periode dengan nama periode, tipe anggaran, dan status pembayaran yang diisi');
      return;
    }
    
    const newPeriods: ContractPeriod[] = [];
    
    for (let i = 0; i < maxPeriods; i++) {
      const periodName = periodNames[i]?.trim() || '';
      const budgetType = budgetTypes[i]?.trim() || '';
      const startDate = startDates[i]?.trim() || '';
      const endDate = endDates[i]?.trim() || '';
      const paymentValue = paymentValues[i]?.trim() || '';
      const paymentStatus = paymentStatuses[i]?.trim() || '';
      
      // Validate required fields
      if (!periodName) {
        queryErrors.value.push(`Baris ${i + 1}: Nama periode wajib diisi`);
        continue;
      }
      
      if (!budgetType) {
        queryErrors.value.push(`Baris ${i + 1}: Tipe anggaran wajib diisi`);
        continue;
      }
      
      if (!['AO', 'RI'].includes(budgetType.toUpperCase())) {
        queryErrors.value.push(`Baris ${i + 1}: Tipe anggaran harus AO atau RI, ditemukan: ${budgetType}`);
        continue;
      }
      
      if (!paymentStatus) {
        queryErrors.value.push(`Baris ${i + 1}: Status pembayaran wajib diisi`);
        continue;
      }
      
      // Extract the payment status number (handle both single and double digits)
      let paymentStatusNumber: string;
      if (paymentStatus.length >= 2 && paymentStatus.charAt(1) === '.') {
        // If second character is dot, only take first character (e.g., "1." -> "1")
        paymentStatusNumber = paymentStatus.charAt(0);
      } else if (paymentStatus.length >= 2 && /\d/.test(paymentStatus.charAt(1))) {
        // If second character is also a digit, take first two characters (e.g., "10" -> "10")
        paymentStatusNumber = paymentStatus.substring(0, 2);
      } else {
        // Otherwise, just take the first character
        paymentStatusNumber = paymentStatus.charAt(0);
      }
      
      if (!paymentStatusMap[paymentStatusNumber]) {
        queryErrors.value.push(`Baris ${i + 1}: Status pembayaran tidak valid: ${paymentStatus}. Harus dimulai dengan nomor 1-10`);
        continue;
      }
      
      // Convert dates
      let convertedStartDate: string | null = null;
      let convertedEndDate: string | null = null;
      
      try {
        if (startDate) {
          convertedStartDate = convertDateFormat(startDate);
        }
      } catch (error) {
        queryErrors.value.push(`Baris ${i + 1}: ${error instanceof Error ? error.message : 'Invalid date format'}`);
        continue;
      }
      
      try {
        if (endDate) {
          convertedEndDate = convertDateFormat(endDate);
        }
      } catch (error) {
        queryErrors.value.push(`Baris ${i + 1}: ${error instanceof Error ? error.message : 'Invalid date format'}`);
        continue;
      }
      
      // Validate payment value if provided
      let parsedPaymentValue: string | undefined = undefined;
      if (paymentValue) {
        // Remove dots (thousands separators) and convert comma to dot for parsing
        const normalizedValue = paymentValue.replace(/\./g, '').replace(',', '.');
        const numericValue = parseFloat(normalizedValue);
        if (isNaN(numericValue) || numericValue < 0) {
          queryErrors.value.push(`Baris ${i + 1}: Nilai pembayaran harus berupa angka positif: ${paymentValue}`);
          continue;
        }
        parsedPaymentValue = numericValue.toString();
      }
      
      // Create the period object
      const period: ContractPeriod = {
        period_name: periodName,
        budget_type: budgetType.toUpperCase(),
        start_date: convertedStartDate || undefined,
        end_date: convertedEndDate || undefined,
        payment_status: paymentStatusMap[paymentStatusNumber]
      };
      
      // Add payment value based on contract currency type
      if (parsedPaymentValue) {
        if (props.contractCurrencyType === 'rp') {
          period.payment_value_rp = parsedPaymentValue;
        } else {
          period.payment_value_non_rp = parsedPaymentValue;
        }
      }
      
      newPeriods.push(period);
    }
    
    // If no errors, emit the new periods
    if (queryErrors.value.length === 0) {
      emit('addMultiple', newPeriods);
      clearQueryData();
      isQueryMode.value = false;
    }
  } catch (error) {
    queryErrors.value.push(`Error processing data: ${error instanceof Error ? error.message : 'Unknown error occurred'}`);
  }
}

// Clear query data
function clearQueryData() {
  queryData.periodNames = '';
  queryData.budgetTypes = '';
  queryData.startDates = '';
  queryData.endDates = '';
  queryData.paymentValues = '';
  queryData.paymentStatuses = '';
  queryErrors.value = [];
}
</script>

<style scoped>
@import '../../../css/components.css';

.period-section {
  margin-top: 2rem;
  background-color: white;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  padding: 1.25rem;
  margin-bottom: 1.5rem;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.period-section:last-child {
  margin-bottom: 0;
}

.period-section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  position: sticky;
  top: 0;
  background: white;
  z-index: var(--z-10);
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--border-color);
}

.period-section-title {
  font-size: 1rem;
  font-weight: 500;
  color: var(--text-color);
}

.period-section-add {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  color: var(--primary-color);
  background-color: var(--bg-alt);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  transition: all var(--transition-fast);
  cursor: pointer;
}

.period-section-add:hover {
  background-color: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

.period-section-empty {
  padding: 2rem;
  text-align: center;
  color: var(--text-muted);
  background-color: var(--bg-alt);
  border-radius: var(--radius);
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.period-section-items {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  overflow-y: auto;
  flex: 1;
}

.period-section-item {
  background-color: var(--bg-alt);
  border-radius: var(--radius);
  padding: 1rem;
  border: 1px solid var(--border-color);
  transition: background-color var(--transition-fast);
  position: relative;
}

.period-section-item:hover {
  background-color: var(--bg-hover);
}

.period-form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
}

.period-form-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.period-form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-color);
}

.required-asterisk {
  color: #dc2626;
  margin-left: 0.25rem;
  font-weight: 600;
}

.period-form-input,
.period-form-select {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  font-size: 0.875rem;
  background-color: white;
  transition: all var(--transition-fast);
}

.period-form-input:focus,
.period-form-select:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px var(--primary-color-light);
}

.period-form-input::placeholder {
  color: var(--text-muted);
}

.period-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.period-section-remove {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  color: var(--danger-color);
  background-color: white;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  transition: all var(--transition-fast);
  cursor: pointer;
}

.period-section-remove:hover {
  background-color: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

.period-section-bottom {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: center;
}

.period-section-add-bottom {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: white;
  background-color: var(--primary-color);
  border: 1px solid var(--primary-color);
  border-radius: var(--radius);
  transition: all var(--transition-fast);
  cursor: pointer;
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

.period-section-add-bottom:hover {
  background-color: var(--primary-color-dark, #1e40af);
  border-color: var(--primary-color-dark, #1e40af);
  box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
}

.period-section-add-bottom:active {
  transform: translateY(1px);
}

/* Query Mode Styles */
.period-section-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.period-mode-toggle {
  display: flex;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  overflow: hidden;
  background-color: white;
}

.toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  background-color: white;
  border: none;
  cursor: pointer;
  transition: all var(--transition-fast);
  color: var(--text-muted);
}

.toggle-btn:first-child {
  border-right: 1px solid var(--border-color);
}

.toggle-btn.active {
  background-color: var(--primary-color);
  color: white;
}

.toggle-btn:hover:not(.active) {
  background-color: var(--bg-hover);
  color: var(--text-color);
}

.query-mode-section {
  background-color: var(--bg-alt);
  border-radius: var(--radius);
  padding: 1.5rem;
  border: 1px solid var(--border-color);
}

.query-mode-header {
  margin-bottom: 1.5rem;
}

.query-mode-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-color);
  margin-bottom: 0.5rem;
}

.query-mode-description {
  font-size: 0.875rem;
  color: var(--text-muted);
  line-height: 1.5;
  margin: 0;
}

.query-mode-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.query-form-field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.query-form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-color);
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.query-form-label .required {
  color: var(--danger-color);
  font-weight: 600;
}

.query-form-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  font-size: 0.875rem;
  font-family: 'Courier New', Monaco, monospace;
  background-color: white;
  resize: vertical;
  min-height: 120px;
  transition: all var(--transition-fast);
}

.query-form-textarea:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px var(--primary-color-light);
}

.query-form-textarea::placeholder {
  color: var(--text-muted);
  font-style: italic;
}

.query-form-help {
  font-size: 0.75rem;
  color: var(--text-muted);
  font-style: italic;
  line-height: 1.4;
}

.query-mode-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--border-color);
}

.query-process-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: white;
  background-color: var(--primary-color);
  border: 1px solid var(--primary-color);
  border-radius: var(--radius);
  cursor: pointer;
  transition: all var(--transition-fast);
  box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);
}

.query-process-btn:hover {
  background-color: var(--primary-color-dark, #1e40af);
  border-color: var(--primary-color-dark, #1e40af);
  box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
}

.query-clear-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--danger-color);
  background-color: white;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.query-clear-btn:hover {
  background-color: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

.query-errors {
  margin-top: 1rem;
  padding: 1rem;
  background-color: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: var(--radius);
  color: #dc2626;
}

.query-errors h5 {
  margin: 0 0 0.5rem 0;
  font-size: 0.875rem;
  font-weight: 600;
}

.query-errors ul {
  margin: 0;
  padding-left: 1.25rem;
  list-style-type: disc;
}

.query-errors li {
  font-size: 0.875rem;
  line-height: 1.4;
  margin-bottom: 0.25rem;
}

.query-errors li:last-child {
  margin-bottom: 0;
}
</style>
