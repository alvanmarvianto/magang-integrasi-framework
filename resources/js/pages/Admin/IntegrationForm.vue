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
          </AdminFormField>

          <AdminFormField label="Target App" id="target_app_id">
            <select
              id="target_app_id"
              v-model="form.target_app_id"
              class="admin-form-select"
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
          </AdminFormField>

          <AdminFormField label="Connection Type" id="connection_type_id">
            <select
              id="connection_type_id"
              v-model="form.connection_type_id"
              class="admin-form-select"
              required
            >
              <option value="">Select Connection Type</option>
              <option
                v-for="type in connectionTypes"
                :key="type.connection_type_id"
                :value="type.connection_type_id"
              >
                {{ type.type_name }}
              </option>
            </select>
          </AdminFormField>

          <AdminFormField label="Direction" id="direction">
            <select
              id="direction"
              v-model="form.direction"
              class="admin-form-select"
              required
            >
              <option value="">Select Direction</option>
              <option value="one_way">One Way (Unidirectional)</option>
              <option value="both_ways">Both Ways (Bidirectional)</option>
            </select>
          </AdminFormField>

          <AdminFormField label="Inbound Description" id="inbound">
            <textarea
              id="inbound"
              v-model="form.inbound"
              class="admin-form-textarea description-textarea"
              rows="3"
            ></textarea>
          </AdminFormField>

          <AdminFormField label="Outbound Description" id="outbound">
            <textarea
              id="outbound"
              v-model="form.outbound"
              class="admin-form-textarea description-textarea"
              rows="3"
            ></textarea>
          </AdminFormField>

          <!-- <AdminFormField label="Connection Endpoint" id="connection_endpoint">
            <input
              type="url"
              id="connection_endpoint"
              v-model="form.connection_endpoint"
              class="admin-form-input"
            />
          </AdminFormField> -->

          <!-- Switch Source & Target (Edit mode only) -->
          <div v-if="isEditing" class="admin-form-field switch-field">
            <label class="admin-form-label">Quick Actions</label>
            <button 
              type="button"
              @click="switchSourceTarget"
              class="switch-source-target-btn"
              title="Switch Source and Target Applications"
            >
              <font-awesome-icon icon="fa-solid fa-exchange-alt" />
              Switch Source & Target
            </button>
          </div>
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
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useNotification } from '@/composables/useNotification';

const { showSuccess, showError, showConfirm } = useNotification();

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
  connection_type_id: props.integration?.connection_type_id || '',
  inbound: props.integration?.inbound || '',
  outbound: props.integration?.outbound || '',
  connection_endpoint: props.integration?.connection_endpoint || '',
  direction: props.integration?.direction || 'one_way'
});

// No need to watch direction anymore since starting_point is removed

function getAppName(appId) {
  if (!appId) return null;
  const app = props.apps.find(app => app.app_id == appId);
  return app ? app.app_name : null;
}

function switchSourceTarget() {
  if (!isEditing.value || !props.integration?.integration_id) return;
  
  showConfirm(`Apakah anda yakin ingin menukar source dan target antara ${getAppName(form.source_app_id)} dan ${getAppName(form.target_app_id)}?`)
    .then((confirmed) => {
      if (confirmed) {
        router.patch(`/admin/integrations/${props.integration.integration_id}/switch`, {}, {
          onSuccess: () => {
            showSuccess('Source dan target berhasil ditukar');
            // Update the form data to reflect the switch
            const tempSourceId = form.source_app_id;
            const tempInbound = form.inbound;
            
            // Switch source and target apps
            form.source_app_id = form.target_app_id;
            form.target_app_id = tempSourceId;
            
            // Switch inbound and outbound descriptions
            form.inbound = form.outbound;
            form.outbound = tempInbound;
          },
          onError: (errors) => {
            const errorMessage = typeof errors === 'object' && errors !== null 
              ? Object.values(errors).flat().join(', ')
              : 'Gagal menukar source dan target';
            showError(errorMessage);
          },
        });
      }
    });
}

function submit() {
  if (isEditing.value) {
    router.put(`/admin/integrations/${props.integration.integration_id}`, form, {
      onSuccess: () => {
        showSuccess('Koneksi berhasil diperbarui');
      },
      onError: (errors) => {
        const errorMessage = typeof errors === 'object' && errors !== null 
          ? Object.values(errors).flat().join(', ')
          : 'Gagal memperbarui koneksi';
        showError(errorMessage);
      },
    });
  } else {
    router.post('/admin/integrations', form, {
      onSuccess: () => {
        showSuccess('Koneksi berhasil dibuat');
      },
      onError: (errors) => {
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

.switch-field {
  grid-column: 1 / -1;
}

.switch-source-target-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.3);
  color: rgb(29, 78, 216);
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  text-decoration: none;
}

.switch-source-target-btn:hover {
  background: rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.4);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
}

.switch-source-target-btn:active {
  transform: translateY(0);
}
</style>
