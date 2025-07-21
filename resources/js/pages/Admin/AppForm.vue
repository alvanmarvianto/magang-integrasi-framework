<template>
  <div class="admin-container">
    <div class="admin-header">
      <h1 class="admin-title">{{ app ? 'Edit' : 'Buat' }} Aplikasi</h1>
      <a href="/admin/apps" class="admin-action-button">
        Kembali
      </a>
    </div>

    <form @submit.prevent="submit" class="admin-form">
      <!-- Basic Information -->
      <div class="admin-form-section">
        <h2 class="admin-form-title">Informasi Dasar</h2>
        
        <div class="admin-form-grid">
          <div class="admin-form-field">
            <label for="app_name" class="admin-form-label">Nama Aplikasi</label>
            <input
              id="app_name"
              v-model="form.app_name"
              type="text"
              class="admin-form-input"
              required
            >
          </div>

          <div class="admin-form-field">
            <label for="stream_id" class="admin-form-label">Stream</label>
            <select
              id="stream_id"
              v-model="form.stream_id"
              class="admin-form-select"
              required
            >
              <option value="">Pilih Stream</option>
              <option v-for="stream in streams" :key="stream.stream_id" :value="stream.stream_id">
                {{ stream.stream_name }}
              </option>
            </select>
          </div>

          <div class="col-span-2">
            <label for="description" class="admin-form-label">Deskripsi</label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              class="admin-form-textarea"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Technology Components -->
      <div class="admin-form-section">
        <h2 class="admin-form-title">Informasi Detail</h2>

        <div class="admin-form-grid">
          <div class="admin-form-field">
            <label for="app_type" class="admin-form-label">Tipe Aplikasi</label>
            <select
              id="app_type"
              v-model="form.app_type"
              class="admin-form-select"
              required
            >
              <option value="">Pilih Tipe</option>
              <option v-for="type in appTypes" :key="type" :value="type">
                {{ type }}
              </option>
            </select>
          </div>

          <div class="admin-form-field">
            <label for="stratification" class="admin-form-label">Stratifikasi</label>
            <select
              id="stratification"
              v-model="form.stratification"
              class="admin-form-select"
              required
            >
              <option value="">Pilih Stratifikasi</option>
              <option v-for="strat in stratifications" :key="strat" :value="strat">
                {{ strat }}
              </option>
            </select>
          </div>
        </div>

        <TechnologySection
          title="Vendor"
          :items="form.vendors"
          :available-items="vendors"
          @add="addItem('vendors')"
          @remove="removeItem('vendors', $event)"
        />

        <TechnologySection
          title="Operating Systems"
          :items="form.operating_systems"
          :available-items="operatingSystems"
          @add="addItem('operating_systems')"
          @remove="removeItem('operating_systems', $event)"
        />

        <TechnologySection
          title="Databases"
          :items="form.databases"
          :available-items="databases"
          @add="addItem('databases')"
          @remove="removeItem('databases', $event)"
        />

        <TechnologySection
          title="Programming Languages"
          :items="form.languages"
          :available-items="languages"
          @add="addItem('languages')"
          @remove="removeItem('languages', $event)"
        />

        <TechnologySection
          title="Frameworks"
          :items="form.frameworks"
          :available-items="frameworks"
          @add="addItem('frameworks')"
          @remove="removeItem('frameworks', $event)"
        />

        <TechnologySection
          title="Middleware"
          :items="form.middlewares"
          :available-items="middlewares"
          @add="addItem('middlewares')"
          @remove="removeItem('middlewares', $event)"
        />

        <TechnologySection
          title="Third Party"
          :items="form.third_parties"
          :available-items="thirdParties"
          @add="addItem('third_parties')"
          @remove="removeItem('third_parties', $event)"
        />

        <TechnologySection
          title="Platforms"
          :items="form.platforms"
          :available-items="platforms"
          @add="addItem('platforms')"
          @remove="removeItem('platforms', $event)"
        />
      </div>

      <div class="flex justify-end">
        <button type="submit" class="admin-form-submit">
          {{ app ? 'Update' : 'Buat' }} Aplikasi
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import TechnologySection from '@/components/Admin/TechnologySection.vue';
import { useNotification } from '@/composables/useNotification';

const { showSuccess, showError } = useNotification();

// Define form type
interface TechItem {
  name: string;
  version?: string;
}

interface FormData {
  app_name: string;
  description: string;
  stream_id: string;
  app_type: string;
  stratification: string;
  vendors: TechItem[];
  operating_systems: TechItem[];
  databases: TechItem[];
  languages: TechItem[];
  frameworks: TechItem[];
  middlewares: TechItem[];
  third_parties: TechItem[];
  platforms: TechItem[];
  [key: string]: any; // Index signature for dynamic access
}

interface Props {
  app?: {
    app_id: number;
    app_name: string;
    description: string;
    stream_id: number;
    app_type: string;
    stratification: string;
    vendors: TechItem[];
    operating_systems: TechItem[];
    databases: TechItem[];
    programming_languages: TechItem[];
    frameworks: TechItem[];
    middlewares: TechItem[];
    third_parties: TechItem[];
    platforms: TechItem[];
  };
  streams: any[];
  appTypes: string[];
  stratifications: string[];
  vendors: string[];
  operatingSystems: string[];
  databases: string[];
  languages: string[];
  frameworks: string[];
  middlewares: string[];
  thirdParties: string[];
  platforms: string[];
}

const props = defineProps<Props>();

const form = ref<FormData>({
  app_name: '',
  description: '',
  stream_id: '',
  app_type: '',
  stratification: '',
  vendors: [],
  operating_systems: [],
  databases: [],
  languages: [],
  frameworks: [],
  middlewares: [],
  third_parties: [],
  platforms: [],
});

onMounted(() => {
  if (props.app) {
    form.value = {
      app_name: props.app.app_name || '',
      description: props.app.description || '',
      stream_id: String(props.app.stream_id || ''),
      app_type: props.app.app_type || '',
      stratification: props.app.stratification || '',
      vendors: props.app.vendors || [],
      operating_systems: props.app.operating_systems || [],
      databases: props.app.databases || [],
      languages: props.app.programming_languages || [],
      frameworks: props.app.frameworks || [],
      middlewares: props.app.middlewares || [],
      third_parties: props.app.third_parties || [],
      platforms: props.app.platforms || [],
    };
  }
});

function addItem(type: string) {
  form.value[type].push({ name: '', version: '' });
}

function removeItem(type: string, index: number) {
  form.value[type].splice(index, 1);
}

function submit() {
  if (props.app) {
    router.put(`/admin/apps/${props.app.app_id}`, form.value, {
      onSuccess: () => {
        showSuccess('Aplikasi berhasil diperbarui');
      },
      onError: (errors) => {
        showError('Gagal memperbarui aplikasi: ' + Object.values(errors).join(', '));
      },
    });
  } else {
    router.post('/admin/apps', form.value, {
      onSuccess: () => {
        showSuccess('Aplikasi berhasil dibuat');
      },
      onError: (errors) => {
        showError('Gagal membuat aplikasi: ' + Object.values(errors).join(', '));
      },
    });
  }
}
</script>

<style scoped>
@import '../../../css/admin.css';
</style> 