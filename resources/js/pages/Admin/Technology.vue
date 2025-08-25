<template>
  <div class="admin-container">
    <AdminNavbar 
      title="Manajemen Teknologi"
      :showBackButton="true"
    />

    <div class="tech-grid">
      <div v-for="category in categories" :key="category.name" class="tech-card">
        <div class="tech-card-header">
          <h2 class="tech-card-title">{{ category.title }}</h2>
          <button @click="addItem(category.name)" class="tech-add-button">
            <font-awesome-icon icon="fa-solid fa-plus" />
            Tambah
          </button>
        </div>

        <div class="tech-table-container">
          <AdminTable
            :columns="tableColumns"
            :items="getTableItems(category.name)"
          >
            <template #column:name="{ item }">
              {{ item.name }}
            </template>
            
            <template #column:actions="{ item }">
              <div class="flex justify-center gap-2">
                <button 
                  @click="editItem(category.name, item.name)"
                  class="action-button edit-button"
                  title="Edit Item"
                >
                  <font-awesome-icon icon="fa-solid fa-pencil" />
                </button>
                <button 
                  @click="deleteItem(category.name, item.name)"
                  class="action-button delete-button"
                  title="Hapus Item"
                >
                  <font-awesome-icon icon="fa-solid fa-trash" />
                </button>
              </div>
            </template>
          </AdminTable>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div v-if="showModal" class="modal-backdrop" @click="closeModal">
      <div class="modal-content" @click.stop>
        <h3 class="modal-title">
          {{ isEditing ? 'Edit' : 'Tambah' }} {{ currentCategory?.title }}
        </h3>
        
        <form @submit.prevent="saveItem" class="modal-form">
          <div class="form-group">
            <label for="name" class="form-label">Nama</label>
            <input
              type="text"
              id="name"
              v-model="formData.name"
              class="form-input"
              :class="{ 'form-input-error': hasFieldError('name') }"
              required
              :placeholder="`Masukkan nama ${currentCategory?.title}`"
            />
            <div v-if="hasFieldError('name')" class="form-error">
              {{ getFieldError('name') }}
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" @click="closeModal" class="button-secondary">
              Batal
            </button>
            <button type="submit" class="button-primary" :disabled="isSubmitting">
              {{ isEditing ? 'Simpan' : 'Tambah' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Usage Modal -->
    <div v-if="showUsageModal" class="modal-backdrop" @click="closeUsageModal">
      <div class="modal-content" @click.stop>
        <h3 class="modal-title">
          Nilai {{ currentCategory?.title }} Sedang Digunakan
        </h3>
        
        <div class="usage-content">
          <p class="usage-message">
            Nilai ini sedang digunakan oleh aplikasi berikut:
          </p>

          <div class="usage-apps">
            <div v-for="app in usageData.apps" :key="app.id" class="usage-app">
              <span class="app-name">{{ app.name }}</span>
              <a :href="app.edit_url" class="button-primary">
                Edit Aplikasi
              </a>
            </div>
          </div>

          <p class="usage-hint">
            Silakan ubah nilai enum di aplikasi yang menggunakannya terlebih dahulu.
          </p>
        </div>

        <div class="modal-actions">
          <button @click="closeUsageModal" class="button-primary">
            Tutup
          </button>
        </div>
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

interface TechnologyData {
  vendors: string[];
  operatingSystems: string[];
  databases: string[];
  languages: string[];
  frameworks: string[];
  middlewares: string[];
  thirdParties: string[];
  platforms: string[];
}

interface Category {
  name: keyof TechnologyData;
  title: string;
}

interface Props {
  enums: TechnologyData;
}

interface FormData {
  name: string;
  oldName?: string;
}

interface AppUsage {
  id: number;
  name: string;
  edit_url: string;
}

interface UsageData {
  is_used: boolean;
  apps: AppUsage[];
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

function getTableItems(categoryName: keyof TechnologyData) {
  return (props.enums[categoryName] || []).map(name => ({
    id: name,
    name: name
  }));
}

const categories = computed<Category[]>(() => [
  { name: 'vendors', title: 'Vendor' },
  { name: 'operatingSystems', title: 'Sistem Operasi' },
  { name: 'databases', title: 'Database' },
  { name: 'languages', title: 'Bahasa Pemrograman' },
  { name: 'frameworks', title: 'Framework' },
  { name: 'middlewares', title: 'Middleware' },
  { name: 'thirdParties', title: 'Third Party' },
  { name: 'platforms', title: 'Platform' },
]);

const showModal = ref(false);
const showUsageModal = ref(false);
const isEditing = ref(false);
const isSubmitting = ref(false);
const currentCategoryName = ref<keyof TechnologyData | null>(null);
const formData = ref<FormData>({ name: '' });
const usageData = ref<UsageData>({ is_used: false, apps: [] });

const page = usePage<PageProps>();
const { showSuccess, showError, showConfirm } = useNotification();
const { errors, setErrors, clearErrors, getFieldError, hasFieldError } = useFormErrors();

const currentCategory = computed(() => 
  currentCategoryName.value ? categories.value.find(c => c.name === currentCategoryName.value) : null
);

async function checkEnumUsage(categoryName: keyof TechnologyData, value: string): Promise<UsageData> {
  const response = await fetch(`/admin/technology/${categoryName}/enum/${encodeURIComponent(value)}/check`);
  return await response.json();
}

function addItem(categoryName: keyof TechnologyData) {
  currentCategoryName.value = categoryName;
  isEditing.value = false;
  formData.value = { name: '' };
  showModal.value = true;
}

async function editItem(categoryName: keyof TechnologyData, value: string) {
  const usage = await checkEnumUsage(categoryName, value);
  
  if (usage.is_used) {
    usageData.value = usage;
    showUsageModal.value = true;
    return;
  }

  currentCategoryName.value = categoryName;
  isEditing.value = true;
  formData.value = { name: value, oldName: value };
  showModal.value = true;
}

async function deleteItem(categoryName: keyof TechnologyData, value: string) {
  try {
    const usage = await checkEnumUsage(categoryName, value);
    
    if (usage.is_used) {
      usageData.value = usage;
      showUsageModal.value = true;
      return;
    }

    const confirmed = await showConfirm('Apakah anda yakin ingin menghapus item ini?');
    if (confirmed) {
      router.delete(
        `/admin/technology/${categoryName}/enum/${encodeURIComponent(value)}`,
        {
          preserveScroll: true,
        }
      );
    }
  } catch (error) {
    showError('Terjadi kesalahan saat menghapus item');
    console.error('Delete error:', error);
  }
}

function saveItem() {
  if (!currentCategoryName.value) return;

  isSubmitting.value = true;
  clearErrors();

  if (isEditing.value) {
    router.put(
      `/admin/technology/${currentCategoryName.value}/enum/${encodeURIComponent(formData.value.oldName!)}`, 
      { name: formData.value.name },
      {
        preserveScroll: true,
        onSuccess: () => {
          closeModal();
        },
        onError: (validationErrors) => {
          setErrors(validationErrors);
        },
        onFinish: () => {
          isSubmitting.value = false;
        }
      }
    );
  } else {
    router.post(
      `/admin/technology/${currentCategoryName.value}/enum`, 
      { name: formData.value.name },
      {
        preserveScroll: true,
        onSuccess: () => {
          closeModal();
        },
        onError: (validationErrors) => {
          setErrors(validationErrors);
        },
        onFinish: () => {
          isSubmitting.value = false;
        }
      }
    );
  }
}

function closeModal() {
  showModal.value = false;
  currentCategoryName.value = null;
  formData.value = { name: '' };
  clearErrors();
  isSubmitting.value = false;
}

function closeUsageModal() {
  showUsageModal.value = false;
  usageData.value = { is_used: false, apps: [] };
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

.tech-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 2rem;
  margin-top: 2rem;
}

.tech-card {
  background-color: white;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  overflow: hidden;
  height: 400px;
  display: flex;
  flex-direction: column;
}

.tech-card-header {
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color);
  background-color: var(--bg-alt);
}

.tech-card-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-color);
}

.tech-add-button {
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

.tech-add-button:hover {
  opacity: 0.9;
}

.tech-table-container {
  flex: 1;
  overflow-y: auto;
  max-height: calc(400px - 80px);
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

.form-input-error {
  border-color: #ef4444;
  box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.1);
}

.form-error {
  color: #ef4444;
  font-size: 0.75rem;
  margin-top: 0.25rem;
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

/* Usage Modal Styles */
.usage-content {
  margin-bottom: 1.5rem;
}

.usage-message {
  color: var(--text-color);
  margin-bottom: 1rem;
}

.usage-apps {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.usage-app {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background-color: var(--bg-alt);
  border-radius: var(--radius);
  border: 1px solid var(--border-color);
}

.app-name {
  font-weight: 500;
  color: var(--text-color);
}

.usage-hint {
  color: var(--text-muted);
  font-size: 0.875rem;
  font-style: italic;
}

/* Grid Styles */
.tech-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 2rem;
  margin-top: 2rem;
}

/* Card Styles */
.tech-card {
  background-color: white;
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.tech-card-header {
  padding: 1rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color);
  background-color: var(--bg-alt);
}

.tech-card-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--text-color);
}

@media (max-width: 768px) {
  .tech-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-top: 1rem;
  }

  .tech-card {
    height: 350px;
  }

  .tech-table-container {
    max-height: calc(350px - 80px);
  }
}
</style> 