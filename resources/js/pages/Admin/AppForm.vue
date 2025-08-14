<template>
  <div class="admin-container">
    <AdminNavbar :title="(app ? 'Edit' : 'Buat') + ' Aplikasi'" :showBackButton="true" backUrl="/admin/apps" />

    <AdminForm @submit="submit">
      <AdminFormSection title="Informasi Dasar">
        <div class="admin-form-grid">
          <AdminFormField label="Nama Aplikasi" id="app_name">
            <input
              id="app_name"
              v-model="form.app_name"
              type="text"
              class="admin-form-input"
              required
            />
          </AdminFormField>

          <AdminFormField label="Stream" id="stream_id">
            <select
              id="stream_id"
              v-model="form.stream_id"
              class="admin-form-select"
              required
            >
              <option value="">Pilih Stream</option>
              <option v-for="stream in streams" :key="stream.data.stream_id" :value="stream.data.stream_id">
                {{ stream.data.stream_name }}
              </option>
            </select>
          </AdminFormField>

          <AdminFormField label="Deskripsi" id="description" class="col-span-2">
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              class="admin-form-textarea"
            ></textarea>
          </AdminFormField>

          <AdminFormField label="Apakah memiliki modul?" id="is_function" class="col-span-2">
            <div class="flex items-center gap-4">
              <label class="flex items-center gap-2">
                <input type="radio" name="is_function" value="1" :checked="form.is_function === true" @change="form.is_function = true" />
                <span>Ya</span>
              </label>
              <label class="flex items-center gap-2">
                <input type="radio" name="is_function" value="0" :checked="form.is_function === false" @change="form.is_function = false" />
                <span>Tidak</span>
              </label>
            </div>
          </AdminFormField>
        </div>
      </AdminFormSection>

      <AdminFormSection v-if="form.is_function" title="Informasi Fungsi">
        <div class="tech-section">
          <div class="tech-section-header">
            <h3 class="tech-section-title">Daftar Modul dan Integrasi</h3>
            <button type="button" class="tech-section-add" @click="addFunction()">
              <font-awesome-icon icon="fa-solid fa-plus" /> Tambah Modul
            </button>
          </div>

          <div v-if="form.functions?.length === 0" class="tech-section-empty">
            Tidak ada modul yang ditambahkan
          </div>

          <div v-else class="tech-section-items">
            <div v-for="(fn, idx) in form.functions" :key="idx" class="tech-section-item">
              <input
                v-model="fn.function_name"
                type="text"
                placeholder="Nama Modul/Fungsi"
                class="tech-section-input"
                required
              />

              <div class="integration-multi">
                <div class="integration-row" v-for="(intId, j) in (fn.integration_ids || [])" :key="j">
                  <select v-model.number="fn.integration_ids[j]" class="tech-section-select" required>
                    <option value="">Pilih Integrasi</option>
                    <option v-for="opt in filteredIntegrationOptions" :key="opt.integration_id" :value="opt.integration_id">
                      {{ partnerAppName(opt) }}
                    </option>
                  </select>
                  <button type="button" class="tech-section-remove" @click="removeFnIntegration(idx, j)">
                    <font-awesome-icon icon="fa-solid fa-trash" />
                  </button>
                </div>

                <button type="button" class="tech-section-add" @click="addFnIntegration(idx)">
                  <font-awesome-icon icon="fa-solid fa-plus" /> Tambah Integrasi
                </button>
              </div>

              <button type="button" class="tech-section-remove" @click="removeFunction(idx)">
                <font-awesome-icon icon="fa-solid fa-trash" />
              </button>
            </div>
          </div>
        </div>
      </AdminFormSection>

      <AdminFormSection title="Informasi Detail">
        <div class="admin-form-grid">
          <AdminFormField label="Tipe Aplikasi" id="app_type">
            <select
              id="app_type"
              v-model="form.app_type"
              class="admin-form-select"
            >
              <option value="">Pilih Tipe</option>
              <option v-for="type in appTypes" :key="type" :value="type">
                {{ type }}
              </option>
            </select>
          </AdminFormField>

          <AdminFormField label="Stratifikasi" id="stratification">
            <select
              id="stratification"
              v-model="form.stratification"
              class="admin-form-select"
            >
              <option value="">Pilih Stratifikasi</option>
              <option v-for="strat in stratifications" :key="strat" :value="strat">
                {{ strat }}
              </option>
            </select>
          </AdminFormField>
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
          title="Third Party"
          :items="form.third_parties"
          :available-items="thirdParties"
          @add="addItem('third_parties')"
          @remove="removeItem('third_parties', $event)"
        />

        <TechnologySection
          title="Middleware"
          :items="form.middlewares"
          :available-items="middlewares"
          @add="addItem('middlewares')"
          @remove="removeItem('middlewares', $event)"
        />

        <TechnologySection
          title="Frameworks"
          :items="form.frameworks"
          :available-items="frameworks"
          @add="addItem('frameworks')"
          @remove="removeItem('frameworks', $event)"
        />

        <TechnologySection
          title="Platforms"
          :items="form.platforms"
          :available-items="platforms"
          @add="addItem('platforms')"
          @remove="removeItem('platforms', $event)"
        />
      </AdminFormSection>

      <div class="flex justify-end">
        <button type="submit" class="admin-form-submit">
          {{ app ? 'Update' : 'Buat' }} Aplikasi
        </button>
      </div>
    </AdminForm>
  </div>
</template>

<script setup lang="ts">
import AdminNavbar from '@/components/Admin/AdminNavbar.vue';
import { ref, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminForm from '@/components/Admin/AdminForm.vue';
import AdminFormSection from '@/components/Admin/AdminFormSection.vue';
import AdminFormField from '@/components/Admin/AdminFormField.vue';
import TechnologySection from '@/components/Admin/TechnologySection.vue';
import { useNotification } from '@/composables/useNotification';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const { showSuccess, showError } = useNotification();

// Define form type
interface TechItem {
  name: string;
  version?: string;
}

interface RawTechItem {
  name: string | null;
  version: string | null;
}

interface FormData {
  app_name: string;
  description: string | null;
  stream_id: number | null;
  app_type: string | null;
  stratification: string | null;
  is_function: boolean;
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
    description: string | null;
    stream_id: number;
    app_type: string | null;
    stratification: string | null;
  is_function?: boolean;
    stream_name: string;
    technology_components: {
      vendors: RawTechItem[];
      operating_systems: RawTechItem[];
      databases: RawTechItem[];
      languages: RawTechItem[];
      frameworks: RawTechItem[];
      middlewares: RawTechItem[];
      third_parties: RawTechItem[];
      platforms: RawTechItem[];
    };
  };
  streams: {
    data: {
      stream_id: number;
      stream_name: string;
      description: string | null;
    };
  }[];
  appTypes: string[];
  stratifications: string[];
  technologyOptions: {
    vendors: string[];
    operating_systems: string[];
    databases: string[];
    programming_languages: string[];
    frameworks: string[];
    middlewares: string[];
    third_parties: string[];
    platforms: string[];
  };
  vendors: string[];
  operatingSystems: string[];
  databases: string[];
  languages: string[];
  frameworks: string[];
  middlewares: string[];
  thirdParties: string[];
  platforms: string[];
  integrationOptions: Array<{ integration_id: number; label: string; source_app_id: number; target_app_id: number; source_name: string; target_name: string }>
}

const props = defineProps<Props>();

const form = ref<FormData>({
  app_name: '',
  description: null,
  stream_id: null,
  app_type: null,
  stratification: null,
  is_function: false,
  vendors: [],
  operating_systems: [],
  databases: [],
  languages: [],
  frameworks: [],
  middlewares: [],
  third_parties: [],
  platforms: [],
  functions: [],
});

onMounted(() => {
  try {
    if (props.app) {
      // Get raw values from the Proxy object
      const appData = props.app;
      const techComponents = appData.technology_components || {};
      
      form.value = {
        app_name: appData.app_name,
        description: appData.description,
        stream_id: appData.stream_id,
        app_type: appData.app_type,
        stratification: appData.stratification,
  is_function: (appData as any).is_function === true,
        vendors: (techComponents.vendors || []).map((v: RawTechItem) => ({ 
          name: v.name || '', 
          version: v.version || undefined 
        })),
        operating_systems: (techComponents.operating_systems || []).map((os: RawTechItem) => ({ 
          name: os.name || '', 
          version: os.version || undefined 
        })),
        databases: (techComponents.databases || []).map((db: RawTechItem) => ({ 
          name: db.name || '', 
          version: db.version || undefined 
        })),
        languages: (techComponents.languages || []).map((lang: RawTechItem) => ({ 
          name: lang.name || '', 
          version: lang.version || undefined 
        })),
        frameworks: (techComponents.frameworks || []).map((fw: RawTechItem) => ({ 
          name: fw.name || '', 
          version: fw.version || undefined 
        })),
        middlewares: (techComponents.middlewares || []).map((mw: RawTechItem) => ({ 
          name: mw.name || '', 
          version: mw.version || undefined 
        })),
        third_parties: (techComponents.third_parties || []).map((tp: RawTechItem) => ({ 
          name: tp.name || '', 
          version: tp.version || undefined 
        })),
        platforms: (techComponents.platforms || []).map((p: RawTechItem) => ({ 
          name: p.name || '', 
          version: p.version || undefined 
        })),
  // Preload functions if provided by BE in future (not present in DTO yet)
  functions: (appData as any).integration_functions || [],
      };
    }
  } catch (error) {
    console.error('Error in onMounted:', error);
  }
});

function addItem(type: string) {
  form.value[type].push({ name: '', version: undefined });
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

function addFunction() {
  if (!form.value.functions) form.value.functions = [];
  form.value.functions.push({ function_name: '', integration_ids: [undefined] });
}

function removeFunction(index: number) {
  form.value.functions.splice(index, 1);
}

function addFnIntegration(fnIndex: number) {
  const fn = form.value.functions[fnIndex];
  if (!fn.integration_ids) fn.integration_ids = [];
  fn.integration_ids.push(undefined);
}

function removeFnIntegration(fnIndex: number, intIndex: number) {
  const fn = form.value.functions[fnIndex];
  if (!fn?.integration_ids) return;
  fn.integration_ids.splice(intIndex, 1);
}

// Filter integration options to only those involving this app (edit mode). In create mode, show none.
const filteredIntegrationOptions = computed(() => {
  const appId = props.app?.app_id;
  if (!appId) return [] as typeof props.integrationOptions;
  const list = props.integrationOptions.filter(opt => opt.source_app_id === appId || opt.target_app_id === appId);
  return [...list].sort((a, b) => partnerAppName(a).localeCompare(partnerAppName(b)));
});

function partnerAppName(opt: { source_app_id: number; target_app_id: number; source_name: string; target_name: string }) {
  const appId = props.app?.app_id;
  if (!appId) return '';
  return opt.source_app_id === appId ? opt.target_name : opt.source_name;
}
</script>

<style scoped>
@import '../../../css/admin.css';
@import '../../../css/components.css';
.tech-section {
  margin-top: 2rem;
  background-color: white;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  padding: 1.25rem;
  margin-bottom: 1.5rem;
  display: flex;
  flex-direction: column;
  min-height: 0; /* Required for Firefox */
}

.tech-section:last-child {
  margin-bottom: 0;
}

.tech-section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  position: sticky;
  top: 0;
  background: white;
  z-index: var(--z-10);
  padding-bottom: 0.75rem;
  border-bottom: 1px solid var(--border-color);
}

.tech-section-title {
  font-size: 1rem;
  font-weight: 500;
  color: var(--text-color);
}

.tech-section-add {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  padding: 0.375rem 0.75rem;
  color: var(--primary-color);
  border-radius: var(--radius);
  background-color: var(--bg-alt);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  transition: all var(--transition-fast);
  transition: all var(--transition-fast);
  cursor: pointer;
  border: none;
}

.tech-section-add:hover {
  background-color: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
  opacity: 0.9;
}
.tech-section-add i {
  font-size: 0.75rem;
}

.tech-section-empty {
  padding: 1rem;
  text-align: center;
  color: var(--text-muted);
  background-color: var(--bg-alt);
  border-radius: var(--radius);
  font-size: 0.875rem;
  padding: 2rem;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.tech-section-items {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  overflow-y: auto;
  flex: 1;
  padding: 0.5rem;
  margin: -0.5rem;
}

.tech-section-item {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 0.75rem;
  align-items: start;
  padding: 0.75rem;
  background-color: var(--bg-alt);
  border-radius: var(--radius);
  transition: background-color var(--transition-fast);
}

.tech-section-item:hover {
  background-color: var(--bg-hover);
}

.tech-section-select,
.tech-section-input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  font-size: 0.875rem;
  background-color: white;
  transition: all var(--transition-fast);
}

.tech-section-select:focus,
.tech-section-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 2px var(--primary-color-light);
}

.tech-section-select::placeholder,
.tech-section-input::placeholder {
  color: var(--text-muted);
}

.tech-section-remove {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  color: var(--danger-color);
  background-color: var(--bg-alt);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  transition: all var(--transition-fast);
  cursor: pointer;
  border: none;
}

.tech-section-remove:hover {
  background-color: var(--danger-color);
  color: white;
  border-color: var(--danger-color);
}

.tech-section-remove i {
  font-size: 0.875rem;
}
</style> 