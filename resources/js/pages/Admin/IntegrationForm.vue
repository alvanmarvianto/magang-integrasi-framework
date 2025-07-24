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

          <AdminFormField label="Description" id="description">
            <textarea
              id="description"
              v-model="form.description"
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

          <AdminFormField label="Starting Point" id="starting_point" v-if="form.direction === 'one_way'">
            <select
              id="starting_point"
              v-model="form.starting_point"
              class="admin-form-select"
              required
            >
              <option value="">Select Starting Point</option>
              <option value="source">{{ getAppName(form.source_app_id) || 'Source App' }}</option>
              <option value="target">{{ getAppName(form.target_app_id) || 'Target App' }}</option>
            </select>
          </AdminFormField>
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

const { showSuccess, showError } = useNotification();

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
  description: props.integration?.description || '',
  connection_endpoint: props.integration?.connection_endpoint || '',
  direction: props.integration?.direction || 'one_way',
  starting_point: props.integration?.starting_point || ''
});

// Clear starting_point when direction is set to both_ways
watch(() => form.direction, (newDirection) => {
  if (newDirection === 'both_ways') {
    form.starting_point = '';
  }
});

function getAppName(appId) {
  if (!appId) return null;
  const app = props.apps.find(app => app.app_id == appId);
  return app ? app.app_name : null;
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
</style>
