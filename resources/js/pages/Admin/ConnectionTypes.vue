<template>
  <div class="admin-container">
    <AdminNavbar 
      title="Manajemen Tipe Koneksi"
      :showBackButton="true"
    />

    <div class="connection-type-card">
      <div class="connection-type-header">
        <h2 class="connection-type-title">Tipe Koneksi</h2>
        <button @click="addConnectionType" class="add-button">
          <font-awesome-icon icon="fa-solid fa-plus" />
          Tambah
        </button>
      </div>

      <AdminTable
        :columns="tableColumns"
        :items="tableItems"
      >
        <template #column:name="{ item }">
          <div class="connection-type-display">
            <div 
              class="color-indicator" 
              :style="{ backgroundColor: item.color }"
            ></div>
            {{ item.name }}
          </div>
        </template>
        
        <template #column:actions="{ item }">
          <div class="flex justify-center gap-2">
            <button 
              @click="editConnectionType(item)"
              class="action-button edit-button"
              title="Edit"
            >
              <font-awesome-icon icon="fa-solid fa-pencil" />
            </button>
            <button 
              @click="deleteConnectionType(item)"
              class="action-button delete-button"
              title="Hapus"
            >
              <font-awesome-icon icon="fa-solid fa-trash" />
            </button>
          </div>
        </template>
      </AdminTable>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="modal-backdrop" @click="closeModal">
      <div class="modal-content" @click.stop>
        <h3 class="modal-title">
          {{ isEditing ? 'Edit' : 'Tambah' }} Tipe Koneksi
        </h3>
        
        <form @submit.prevent="saveConnectionType" class="modal-form">
          <div class="form-group">
            <label for="name" class="form-label">Nama</label>
            <input
              type="text"
              id="name"
              v-model="formData.name"
              class="form-input"
              :class="{ 'error': hasFieldError('name') }"
              required
              placeholder="Masukkan nama tipe koneksi"
            />
            <div v-if="hasFieldError('name')" class="error-message">
              {{ getFieldError('name') }}
            </div>
          </div>

          <div class="form-group">
            <label for="color" class="form-label">Warna</label>
            <div class="color-input-container">
              <input
                type="color"
                id="color"
                v-model="formData.color"
                class="color-input"
                :class="{ 'error': hasFieldError('color') }"
                required
              />
              <input
                type="text"
                v-model="formData.color"
                class="form-input color-text-input"
                :class="{ 'error': hasFieldError('color') }"
                placeholder="#000000"
                pattern="^#[0-9A-Fa-f]{6}$"
              />
            </div>
            <div v-if="hasFieldError('color')" class="error-message">
              {{ getFieldError('color') }}
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeModal" class="button-secondary">
              Batal
            </button>
            <button type="submit" class="button-primary">
              {{ isEditing ? 'Simpan' : 'Tambah' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useNotification } from '@/composables/useNotification';
import { useFormErrors } from '@/composables/useFormErrors';
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';
import AdminTable from '@/components/Admin/AdminTable.vue';

interface ConnectionType {
  id: number;
  name: string;
  color: string;
}

interface FormData {
  name: string;
  color: string;
}

interface Props {
  connectionTypes: ConnectionType[];
}

interface PageProps {
  flash: {
    success?: string;
    error?: string;
    [key: string]: any;
  };
  [key: string]: any;
}

const props = defineProps<Props>();

const tableColumns = [
  { key: 'name', label: 'Nama', sortable: true },
  { key: 'actions', label: 'Aksi', centered: true }
];

const tableItems = computed(() => 
  props.connectionTypes.map(connectionType => ({
    id: connectionType.id,
    name: connectionType.name,
    color: connectionType.color
  }))
);

const showModal = ref(false);
const isEditing = ref(false);
const selectedConnectionType = ref<ConnectionType | null>(null);
const formData = ref<FormData>({ name: '', color: '#000000' });

const page = usePage<PageProps>();
const { showSuccess, showError, showConfirm } = useNotification();
const { errors, setErrors, clearErrors, getFieldError, hasFieldError } = useFormErrors();

async function checkConnectionTypeUsage(id: number): Promise<any> {
  const response = await fetch(`/admin/connection-types/${id}/check`);
  return await response.json();
}

function addConnectionType() {
  isEditing.value = false;
  selectedConnectionType.value = null;
  formData.value = { name: '', color: '#000000' };
  showModal.value = true;
}

async function editConnectionType(connectionType: ConnectionType) {
  isEditing.value = true;
  selectedConnectionType.value = connectionType;
  formData.value = { 
    name: connectionType.name, 
    color: connectionType.color 
  };
  showModal.value = true;
}

async function deleteConnectionType(connectionType: ConnectionType) {
  try {
    const usage = await checkConnectionTypeUsage(connectionType.id);
    
    if (usage.is_used && usage.count > 0) {
      showError(`Tidak dapat menghapus tipe koneksi "${connectionType.name}" karena sedang digunakan oleh ${usage.count} integrasi.`);
      return;
    }

    const confirmed = await showConfirm(
      `Apakah anda yakin ingin menghapus tipe koneksi "${connectionType.name}"?`
    );
    
    if (confirmed) {
      router.delete(`/admin/connection-types/${connectionType.id}`, {
        preserveScroll: true,
      });
    }
  } catch (error) {
    showError('Terjadi kesalahan saat menghapus tipe koneksi');
    console.error('Delete error:', error);
  }
}

function saveConnectionType() {
  clearErrors(); // Clear previous errors

  if (isEditing.value && selectedConnectionType.value) {
    router.put(`/admin/connection-types/${selectedConnectionType.value.id}`, formData.value, {
      preserveScroll: true,
      onSuccess: () => {
        closeModal();
      },
      onError: (errors) => {
        setErrors(errors);
      },
    });
  } else {
    router.post('/admin/connection-types', formData.value, {
      preserveScroll: true,
      onSuccess: () => {
        closeModal();
      },
      onError: (errors) => {
        setErrors(errors);
      },
    });
  }
}

function closeModal() {
  showModal.value = false;
  selectedConnectionType.value = null;
  formData.value = { name: '', color: '#000000' };
}

// Handle flash messages
onMounted(() => {
  const flash = page.props.flash;
  if (flash?.success) {
    showSuccess(flash.success);
  }
  if (flash?.error) {
    showError(flash.error);
  }
});

// Watch for flash message changes
watch(() => page.props.flash, (newFlash) => {
  if (newFlash?.success) {
    showSuccess(newFlash.success);
  }
  if (newFlash?.error) {
    showError(newFlash.error);
  }
}, { deep: true });
</script>

<style scoped>
@import '@/../css/admin.css';

.connection-type-card {
  background-color: white;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  overflow: hidden;
  margin-top: 2rem;
}

.connection-type-header {
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color);
  background-color: var(--bg-alt);
}

.connection-type-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-color);
}

.add-button {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background-color: var(--primary-color);
  color: white;
  border-radius: var(--radius);
  font-size: 0.875rem;
  transition: opacity var(--transition-fast);
}

.add-button:hover {
  opacity: 0.9;
}

.connection-type-display {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.color-indicator {
  width: 1.5rem;
  height: 1.5rem;
  border-radius: var(--radius);
  border: 1px solid var(--border-color);
  flex-shrink: 0;
}

/* Modal Styles */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: var(--z-modal);
}

.modal-content {
  background-color: white;
  border-radius: var(--radius-lg);
  padding: 2rem;
  width: 100%;
  max-width: 500px;
  box-shadow: var(--shadow-lg);
}

.modal-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--text-color);
  margin-bottom: 1.5rem;
}

.modal-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--text-color);
}

.form-input {
  padding: 0.625rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  font-size: 0.875rem;
}

.form-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px var(--primary-color-light);
}

.color-input-container {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.color-input {
  width: 3rem;
  height: 2.5rem;
  padding: 0.25rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  cursor: pointer;
}

.color-text-input {
  flex: 1;
  font-family: monospace;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1rem;
}

.button-primary {
  padding: 0.5rem 1rem;
  background-color: var(--primary-color);
  color: white;
  border-radius: var(--radius);
  font-size: 0.875rem;
  transition: opacity var(--transition-fast);
  text-decoration: none;
}

.button-primary:hover {
  opacity: 0.9;
}

.button-secondary {
  padding: 0.5rem 1rem;
  background-color: var(--bg-alt);
  color: var(--text-color);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  font-size: 0.875rem;
  transition: all var(--transition-fast);
}

.button-secondary:hover {
  background-color: var(--bg-hover);
}

/* Action buttons */
.action-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: var(--radius);
  font-size: 0.875rem;
  transition: all var(--transition-fast);
}

.edit-button {
  background-color: var(--warning-color);
  color: white;
}

.edit-button:hover {
  opacity: 0.9;
}

.delete-button {
  background-color: var(--danger-color);
  color: white;
}

.delete-button:hover {
  opacity: 0.9;
}

.flex {
  display: flex;
}

.justify-center {
  justify-content: center;
}

.gap-2 {
  gap: 0.5rem;
}

@media (max-width: 768px) {
  .modal-content {
    margin: 1rem;
    padding: 1.5rem;
  }

  .color-input-container {
    flex-direction: column;
    align-items: flex-start;
  }

  .color-input {
    width: 100%;
  }
}

/* Error styles */
.form-input.error,
.color-input.error {
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
