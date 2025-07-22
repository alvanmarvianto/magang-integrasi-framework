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
  connection_type_id: props.integration?.connection_type_id || ''
});

function submit() {
  if (isEditing.value) {
    router.put(`/admin/integrations/${props.integration.integration_id}`, form);
  } else {
    router.post('/admin/integrations', form);
  }
}
</script>

<style scoped>
@import '@/../css/admin.css';
</style>
