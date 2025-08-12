<template>
  <div class="admin-container">
    <AdminNavbar :title="(stream ? 'Edit' : 'Buat') + ' Stream'" :showBackButton="true" backUrl="/admin/streams" />

    <AdminForm @submit="submit">
      <AdminFormSection title="Informasi Stream">
        <div class="admin-form-grid">
          <AdminFormField label="Nama Stream" id="stream_name">
            <input
              id="stream_name"
              v-model="form.stream_name"
              type="text"
              class="admin-form-input"
              placeholder="Contoh: Service Provider, Master Index, etc."
              required
            />
            <p class="admin-form-help">
              Nama stream dapat menggunakan karakter apa saja
            </p>
          </AdminFormField>

          <AdminFormField label="Status" id="is_allowed_for_diagram">
            <div class="flex items-center">
              <button
                type="button"
                @click="form.is_allowed_for_diagram = !form.is_allowed_for_diagram"
                :class="[
                  'toggle-switch',
                  form.is_allowed_for_diagram ? 'toggle-switch-on' : 'toggle-switch-off'
                ]"
                :title="form.is_allowed_for_diagram ? 'Diizinkan untuk diagram' : 'Tidak diizinkan untuk diagram'"
              >
                <div class="toggle-switch-handle"></div>
              </button>
              <label class="ml-3 text-sm text-gray-700">
                {{ form.is_allowed_for_diagram ? 'Diizinkan untuk diagram' : 'Tidak diizinkan untuk diagram' }}
              </label>
            </div>
          </AdminFormField>

          <AdminFormField label="Deskripsi" id="description" class="col-span-2">
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              class="admin-form-textarea"
              placeholder="Deskripsi singkat tentang stream ini"
            ></textarea>
          </AdminFormField>

          <AdminFormField label="Warna" id="color">
            <div class="color-input-container">
              <input
                id="color"
                v-model="form.color"
                type="color"
                class="color-input"
              />
              <input
                v-model="form.color"
                type="text"
                class="admin-form-input color-text-input"
                placeholder="#FF6B35"
                pattern="^#[0-9A-Fa-f]{6}$"
              />
            </div>
            <p class="admin-form-help">
              Warna akan digunakan dalam diagram untuk membedakan stream
            </p>
          </AdminFormField>
        </div>
      </AdminFormSection>

      <div class="flex justify-end">
        <button type="submit" class="admin-form-submit">
          {{ stream ? 'Update' : 'Buat' }} Stream
        </button>
      </div>
    </AdminForm>
  </div>
</template>

<script setup lang="ts">
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminForm from '@/components/Admin/AdminForm.vue';
import AdminFormSection from '@/components/Admin/AdminFormSection.vue';
import AdminFormField from '@/components/Admin/AdminFormField.vue';
import { useNotification } from '@/composables/useNotification';

const { showSuccess, showError } = useNotification();

interface FormData {
  stream_name: string;
  description: string | null;
  is_allowed_for_diagram: boolean;
  color: string | null;
}

interface Props {
  stream?: {
    stream_id: number;
    stream_name: string;
    description: string | null;
    is_allowed_for_diagram: boolean;
    color: string | null;
  };
}

const props = defineProps<Props>();

const form = ref<FormData>({
  stream_name: '',
  description: null,
  is_allowed_for_diagram: false,
  color: '#000000',
});

onMounted(() => {
  if (props.stream) {
    form.value = {
      stream_name: props.stream.stream_name,
      description: props.stream.description,
      is_allowed_for_diagram: props.stream.is_allowed_for_diagram,
      color: props.stream.color || '#000000',
    };
  }
});

function submit() {
  if (props.stream) {
    router.put(`/admin/streams/${props.stream.stream_id}`, form.value, {
      onSuccess: () => {
        showSuccess('Stream berhasil diperbarui');
      },
      onError: (errors) => {
        showError('Gagal memperbarui stream: ' + Object.values(errors).join(', '));
      },
    });
  } else {
    router.post('/admin/streams', form.value, {
      onSuccess: () => {
        showSuccess('Stream berhasil dibuat');
      },
      onError: (errors) => {
        showError('Gagal membuat stream: ' + Object.values(errors).join(', '));
      },
    });
  }
}
</script>

<style scoped>
@import '../../../css/admin.css';

.toggle-switch {
  position: relative;
  display: inline-flex;
  height: 1.5rem;
  width: 2.75rem;
  align-items: center;
  border-radius: 9999px;
  transition: background-color 0.2s;
  cursor: pointer;
  border: none;
}

.toggle-switch:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.5);
}

.toggle-switch-off {
  background-color: #e5e7eb;
}

.toggle-switch-off:hover {
  background-color: #d1d5db;
}

.toggle-switch-on {
  background-color: #4f46e5;
}

.toggle-switch-on:hover {
  background-color: #4338ca;
}

.toggle-switch-handle {
  display: inline-block;
  height: 1rem;
  width: 1rem;
  border-radius: 50%;
  background-color: white;
  transition: transform 0.2s;
}

.toggle-switch-off .toggle-switch-handle {
  transform: translateX(0.25rem);
}

.toggle-switch-on .toggle-switch-handle {
  transform: translateX(1.5rem);
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

@media (max-width: 768px) {
  .color-input-container {
    flex-direction: column;
    align-items: flex-start;
  }

  .color-input {
    width: 100%;
  }
}
</style>
