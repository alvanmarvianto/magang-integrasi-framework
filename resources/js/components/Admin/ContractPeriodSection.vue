<template>
  <div class="period-section">
    <div class="period-section-header">
      <h3 class="period-section-title">Periode Kontrak</h3>
      <button
        type="button"
        @click="$emit('add')"
        class="period-section-add"
      >
        <font-awesome-icon icon="fa-solid fa-plus" /> Tambah Periode
      </button>
    </div>

    <div v-if="periods.length === 0" class="period-section-empty">
      Belum ada periode kontrak yang ditambahkan
    </div>

    <div v-else class="period-section-items">
      <div
        v-for="(period, index) in periods"
        :key="index"
        class="period-section-item"
      >
        <div class="period-form-grid">
          <!-- Row 1: Period Name, Budget Type -->
          <div class="period-form-field">
            <label :for="`period_name_${index}`" class="period-form-label">Nama Periode</label>
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
            <label :for="`budget_type_${index}`" class="period-form-label">Tipe Anggaran</label>
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
            <label :for="`payment_status_${index}`" class="period-form-label">Status Pembayaran</label>
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
    
    <!-- Bottom Add Button for better UX -->
    <div v-if="periods.length > 0" class="period-section-bottom">
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

interface ContractPeriod {
  period_name: string;
  budget_type: string;
  start_date?: string;
  end_date?: string;
  payment_value_rp?: string;
  payment_value_non_rp?: string;
  payment_status: string;
}

defineProps<{
  periods: ContractPeriod[];
  contractCurrencyType: string;
}>();

defineEmits<{
  (e: 'add'): void;
  (e: 'remove', index: number): void;
}>();
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
</style>
