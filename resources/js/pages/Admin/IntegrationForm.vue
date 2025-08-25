<template>
  <div class="admin-container">
    <AdminNavbar :title="(isEditing ? 'Edit' : 'Buat') + ' Integrasi'" :showBackButton="true" backUrl="/admin/integrations" />

    <AdminForm @submit="submit">
      <AdminFormSection title="Detail Integrasi" class="integration-section">
        <div class="admin-form-grid integration-single-column">
          <AdminFormField label="Source App" id="source_app_id">
            <select
              id="source_app_id"
              v-model="form.source_app_id"
              class="admin-form-select"
              :class="{ 'error': hasFieldError('source_app_id') }"
              required
              :disabled="isEditing"
            >
              <option value="">Select Source App</option>
              <option
                v-for="app in apps"
                :key="app.app_id"
                :value="app.app_id"
              >
                {{ app.app_name }}
              </option>
            </select>
            <div v-if="hasFieldError('source_app_id')" class="error-message">
              {{ getFieldError('source_app_id') }}
            </div>
          </AdminFormField>

          <AdminFormField label="Target App" id="target_app_id">
            <select
              id="target_app_id"
              v-model="form.target_app_id"
              class="admin-form-select"
              :class="{ 'error': hasFieldError('target_app_id') }"
              required
              :disabled="isEditing"
            >
              <option value="">Select Target App</option>
              <option
                v-for="app in apps"
                :key="app.app_id"
                :value="app.app_id"
                :disabled="app.app_id === form.source_app_id"
              >
                {{ app.app_name }}
              </option>
            </select>
            <div v-if="hasFieldError('target_app_id')" class="error-message">
              {{ getFieldError('target_app_id') }}
            </div>
          </AdminFormField>
          </div>

          <!-- Connection Types Section -->
          <div class="connections-section">
            <div class="connections-header">
              <h3 class="connections-title">Connection Types</h3>
              <button type="button" class="connections-add" @click="addConnection">
                + Add Connection Type
              </button>
            </div>

            <div v-if="form.connections.length === 0" class="connections-empty">
              Tidak ada connection type yang ditambahkan
            </div>

            <div v-else class="connections-items">
              <div
                v-for="(conn, index) in form.connections"
                :key="index"
                class="connections-item"
              >
                <div class="connections-row">
                  <label class="admin-form-label">Connection Type</label>
                  <select v-model="conn.connection_type_id" class="admin-form-select" :class="{ 'error': hasFieldError(`connections.${index}.connection_type_id`) }" required style="width:100%">
                    <option value="">Pilih Connection Type</option>
                    <option
                      v-for="type in filteredConnectionTypes(index)"
                      :key="type.connection_type_id"
                      :value="type.connection_type_id"
                    >
                      {{ type.type_name }}
                    </option>
                  </select>
                  <div v-if="hasFieldError(`connections.${index}.connection_type_id`)" class="error-message">
                    {{ getFieldError(`connections.${index}.connection_type_id`) }}
                  </div>
                  <button type="button" class="connections-remove" @click="removeConnection(index)">
                    Hapus
                  </button>
                </div>

                <div class="connections-grid">
                  <div class="connections-col">
                    <div class="connections-subtitle">{{ sourceAppName }}</div>
                    <label class="admin-form-label" :for="`si-${index}`">Inbound</label>
                    <textarea :id="`si-${index}`" v-model="conn.source_inbound" rows="3" class="admin-form-textarea"></textarea>
                    <label class="admin-form-label" :for="`so-${index}`">Outbound</label>
                    <textarea :id="`so-${index}`" v-model="conn.source_outbound" rows="3" class="admin-form-textarea"></textarea>
                  </div>

                  <div class="connections-col">
                    <div class="connections-subtitle">{{ targetAppName }}</div>
                    <label class="admin-form-label" :for="`ti-${index}`">Inbound</label>
                    <textarea :id="`ti-${index}`" v-model="conn.target_inbound" rows="3" class="admin-form-textarea"></textarea>
                    <label class="admin-form-label" :for="`to-${index}`">Outbound</label>
                    <textarea :id="`to-${index}`" v-model="conn.target_outbound" rows="3" class="admin-form-textarea"></textarea>
                  </div>
                </div>
              </div>
            </div>
          

          <!-- <AdminFormField label="Connection Endpoint" id="connection_endpoint">
            <input
              type="url"
              id="connection_endpoint"
              v-model="form.connection_endpoint"
              class="admin-form-input"
            />
          </AdminFormField> -->
        </div>
      </AdminFormSection>
      <div class="flex justify-end">
        <button type="submit" class="admin-form-submit integration-submit">
          {{ isEditing ? 'Perbarui Integrasi' : 'Buat Integrasi' }}
        </button>
      </div>
    </AdminForm>
  </div>
</template>

<script setup>
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';
import AdminForm from '@/components/Admin/AdminForm.vue';
import AdminFormSection from '@/components/Admin/AdminFormSection.vue';
import AdminFormField from '@/components/Admin/AdminFormField.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useNotification } from '@/composables/useNotification';
import { useFormErrors } from '@/composables/useFormErrors';

const { showSuccess, showError } = useNotification();
const { errors, setErrors, clearErrors, getFieldError, hasFieldError } = useFormErrors();

const props = defineProps({
  apps: {
    type: Array,
    required: true
  },
  connectionTypes: {
    type: Array,
    required: true
  },
  integration: {
    type: Object,
    required: false,
    default: null
  }
});

const isEditing = computed(() => !!props.integration);

const form = useForm({
  source_app_id: props.integration?.source_app_id || '',
  target_app_id: props.integration?.target_app_id || '',
  connections: (props.integration?.connections || []).map(c => ({
    connection_type_id: c.connection_type_id || '',
    source_inbound: c.source_inbound || '',
    source_outbound: c.source_outbound || '',
    target_inbound: c.target_inbound || '',
    target_outbound: c.target_outbound || '',
  })),
});

// Dynamic app names for labels
const sourceAppName = computed(() => {
  const id = form.source_app_id;
  const app = props.apps.find(a => String(a.app_id) === String(id));
  return app?.app_name || 'Source App';
});

const targetAppName = computed(() => {
  const id = form.target_app_id;
  const app = props.apps.find(a => String(a.app_id) === String(id));
  return app?.app_name || 'Target App';
});

function filteredConnectionTypes(currentIndex) {
  const selectedIds = form.connections
    .filter((_, idx) => idx !== currentIndex)
    .map(c => String(c.connection_type_id))
    .filter(Boolean);
  return props.connectionTypes.filter(t => !selectedIds.includes(String(t.connection_type_id)));
}

function addConnection() {
  form.connections.push({
    connection_type_id: '',
    source_inbound: '',
    source_outbound: '',
    target_inbound: '',
    target_outbound: '',
  });
}

function removeConnection(index) {
  form.connections.splice(index, 1);
}

function validateConnections() {
  // Only validate if there are connections to check
  if (form.connections.length === 0) {
    return true; // Allow empty connections
  }
  
  // Ensure no duplicate connection types and all selected
  const ids = form.connections.map(c => c.connection_type_id).filter(Boolean);
  if (ids.length !== new Set(ids).size) {
    showError('Connection type tidak boleh duplikat dalam satu integrasi');
    return false;
  }
  if (form.connections.some(c => !c.connection_type_id)) {
    showError('Pilih connection type untuk setiap item yang ditambahkan');
    return false;
  }
  return true;
}

function submit() {
  if (!validateConnections()) return;

  clearErrors(); // Clear previous errors

  const payload = {
    source_app_id: form.source_app_id,
    target_app_id: form.target_app_id,
    connections: form.connections, // Submit even if empty
  };

  if (isEditing.value) {
    router.put(`/admin/integrations/${props.integration.integration_id}`, payload, {
      onSuccess: () => {
        showSuccess('Koneksi berhasil diperbarui');
      },
      onError: (errors) => {
        setErrors(errors);
        const errorMessage = typeof errors === 'object' && errors !== null 
          ? Object.values(errors).flat().join(', ')
          : 'Gagal memperbarui koneksi';
        showError(errorMessage);
      },
    });
  } else {
    router.post('/admin/integrations', payload, {
      onSuccess: () => {
        showSuccess('Koneksi berhasil dibuat');
      },
      onError: (errors) => {
        setErrors(errors);
        const errorMessage = typeof errors === 'object' && errors !== null 
          ? Object.values(errors).flat().join(', ')
          : 'Gagal membuat koneksi';
        showError(errorMessage);
      },
    });
  }
}
</script>

<style scoped>
@import '@/../css/admin.css';

.description-textarea {
  max-width: 634px;
  resize: vertical;
}

/* Connections Section Styles */
.connections-section {
  margin-top: 1rem;
  border-top: 1px solid var(--border-color);
  padding-top: 1rem;
}
.connections-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}
.connections-title {
  font-size: 1rem;
  font-weight: 500;
}
.connections-add {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  color: var(--primary-color);
  border-radius: var(--radius);
  background-color: var(--bg-alt);
  border: 1px solid var(--border-color);
  cursor: pointer;
}
.connections-empty {
  padding: 1rem;
  text-align: center;
  color: var(--text-muted);
  background-color: var(--bg-alt);
  border-radius: var(--radius);
}
.connections-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}
.connections-item {
  padding: 0.75rem;
  background-color: var(--bg-alt);
  border-radius: var(--radius);
  border: 1px solid var(--border-color);
}
.connections-row {
  display: grid;
  grid-template-columns: 160px 1fr auto; /* label | select grows | remove */
  gap: 0.5rem;
  align-items: center;
}
.connections-remove {
  justify-self: end;
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  color: var(--danger-color);
  background-color: var(--bg-alt);
  border: 1px solid var(--danger-color);
  border-radius: var(--radius);
  cursor: pointer;
}
.connections-grid {
  display: grid;
  grid-template-columns: 1fr 1fr; /* left: source, right: target */
  gap: 1rem;
  margin-top: 0.75rem;
}
@media (max-width: 768px) {
  .connections-row {
    grid-template-columns: 1fr; /* stack on small screens */
  }
  .connections-grid {
    grid-template-columns: 1fr;
  }
}
.connections-subtitle {
  font-weight: 600;
  margin-bottom: 0.5rem;
}
.admin-form-textarea {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  font-size: 0.875rem;
  background-color: white;
}

/* Error styles */
.admin-form-select.error,
.admin-form-input.error,
.admin-form-textarea.error {
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
