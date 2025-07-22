<template>
  <div class="admin-container">
    <div class="admin-header">
      <h1 class="admin-title">Connection Types</h1>
      <button @click="openCreateModal" class="admin-action-button">
        Add Connection Type
      </button>
    </div>

    <div class="admin-table-container">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Type Name</th>
            <th>Usage Count</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="type in connectionTypes" :key="type.connection_type_id">
            <td>{{ type.type_name }}</td>
            <td>{{ type.app_integrations_count || 0 }}</td>
            <td>
              <div class="flex justify-center gap-2">
                <button
                  class="action-button edit-button"
                  @click="openEditModal(type)"
                  title="Edit"
                >
                  <i class="fas fa-edit"></i>
                </button>
                <button
                  class="action-button delete-button"
                  @click="confirmDelete(type)"
                  title="Delete"
                  :disabled="type.app_integrations_count > 0"
                >
                  <i class="fas fa-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Modal for Create/Edit -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal-container">
        <div class="modal-header">
          <h2>{{ isEditing ? 'Edit Connection Type' : 'Create Connection Type' }}</h2>
          <button @click="closeModal" class="modal-close">&amp;times;</button>
        </div>
        
        <form @submit.prevent="submit" class="modal-form">
          <div class="admin-form-field">
            <label class="admin-form-label">Type Name</label>
            <input
              v-model="form.type_name"
              type="text"
              class="admin-form-input"
              required
            >
          </div>
          
          <div class="modal-footer">
            <button type="button" @click="closeModal" class="modal-button cancel">Cancel</button>
            <button type="submit" class="modal-button submit">
              {{ isEditing ? 'Update' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  connectionTypes: {
    type: Array,
    required: true
  }
});

const showModal = ref(false);
const editingType = ref(null);
const isEditing = computed(() => !!editingType.value);

const form = useForm({
  type_name: ''
});

function openCreateModal() {
  form.reset();
  editingType.value = null;
  showModal.value = true;
}

function openEditModal(type) {
  editingType.value = type;
  form.type_name = type.type_name;
  showModal.value = true;
}

function closeModal() {
  showModal.value = false;
  form.reset();
  editingType.value = null;
}

function submit() {
  if (isEditing.value) {
    router.put(route('admin.connection-types.update', editingType.value.connection_type_id), form);
  } else {
    router.post(route('admin.connection-types.store'), form);
  }
  closeModal();
}

function confirmDelete(type) {
  if (type.app_integrations_count > 0) {
    alert('Cannot delete connection type that is in use');
    return;
  }
  
  if (confirm(`Are you sure you want to delete the connection type "${type.type_name}"?`)) {
    router.delete(route('admin.connection-types.destroy', type.connection_type_id));
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-container {
  background-color: white;
  border-radius: var(--radius-lg);
  width: 90%;
  max-width: 500px;
  box-shadow: var(--shadow-lg);
}

.modal-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  font-size: 1.25rem;
  font-weight: 500;
}

.modal-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--text-muted);
}

.modal-form {
  padding: 1.5rem;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.modal-button {
  padding: 0.5rem 1rem;
  border-radius: var(--radius);
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
}

.modal-button.cancel {
  background-color: var(--bg-alt);
  border: 1px solid var(--border-color);
  color: var(--text-color);
}

.modal-button.submit {
  background-color: var(--primary-color);
  border: none;
  color: white;
}

.action-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
