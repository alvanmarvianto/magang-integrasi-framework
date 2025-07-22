<template>
  <div class="admin-container">
    <div class="admin-header">
      <h1 class="admin-title">{{ isEditing ? 'Edit Integration' : 'Create Integration' }}</h1>
      <a href="/admin/integrations" class="admin-action-button">
        Back to List
      </a>
    </div>

    <form @submit.prevent="submit" class="admin-form">
      <div class="admin-form-section">
        <h2 class="admin-form-title">Integration Details</h2>
        
        <div class="admin-form-grid">
          <div class="admin-form-field">
            <label class="admin-form-label">Source App</label>
            <select
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
          </div>

          <div class="admin-form-field">
            <label class="admin-form-label">Target App</label>
            <select
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
          </div>

          <div class="admin-form-field">
            <label class="admin-form-label">Connection Type</label>
            <select
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
          </div>
        </div>
      </div>

      <button type="submit" class="admin-form-submit">
        {{ isEditing ? 'Update Integration' : 'Create Integration' }}
      </button>
    </form>
  </div>
</template>

<script setup>
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

/* Additional custom styles if needed */
.admin-form-grid {
  /* Override to single column for this form since we have fewer fields */
  grid-template-columns: 1fr;
  max-width: 600px;
  margin: 0 auto;
}

@media (min-width: 768px) {
  .admin-form-grid {
    grid-template-columns: 1fr; /* Keep single column even on larger screens */
  }
}

/* Add some spacing between form sections */
.admin-form-section {
  margin-bottom: 2rem;
}

/* Ensure consistent button alignment */
.admin-form-submit {
  margin-top: 1.5rem;
  align-self: center; /* Center the submit button */
}
</style>
