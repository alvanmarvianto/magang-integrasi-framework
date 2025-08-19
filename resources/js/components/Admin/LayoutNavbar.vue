<template>
  <div class="admin-header">
    <div>
      <h1 class="title">{{ title }}</h1>
      <p v-if="subtitle" class="subtitle">{{ subtitle }}</p>
    </div>
    <div class="header-controls">
      <!-- Status indicator -->
      <div class="status-indicator" :class="{
        'saved': !layoutChanged,
        'unsaved': layoutChanged && !saving,
        'auto-saving': saving
      }">
        <div v-if="saving" class="spinner"></div>
        <div v-else-if="layoutChanged" class="indicator-dot"></div>
        <div v-else class="indicator-dot saved-dot"></div>
        {{ saving ? 'Menyimpan...' : layoutChanged ? 'Ada perubahan' : 'Tersimpan' }}
      </div>

      <!-- Layout selector (streams and apps) -->
      <select 
        v-if="showLayoutSelector && (allowedStreams || functionApps)"
        v-model="selectedLayout" 
        @change="onLayoutChange"
        class="layout-selector"
      >
        <!-- Streams section -->
        <optgroup v-if="allowedStreams && allowedStreams.length > 0" label="Stream:">
          <option 
            v-for="stream in allowedStreams" 
            :key="`stream-${stream}`" 
            :value="`stream:${stream}`"
          >
            {{ stream }}
          </option>
        </optgroup>
        
        <!-- Apps section -->
        <optgroup v-if="functionApps && functionApps.length > 0" label="App:">
          <option 
            v-for="app in functionApps" 
            :key="`app-${app.app_id}`" 
            :value="`app:${app.app_id}`"
          >
            {{ app.app_name }}
          </option>
        </optgroup>
      </select>

      <!-- Action buttons -->
      <button 
        @click="$emit('save')" 
        :disabled="!layoutChanged || saving"
        class="save-btn"
      >
        {{ saving ? 'Menyimpan...' : 'Simpan Layout' }}
      </button>
      
      <button 
        v-if="showRefreshButton"
        @click="$emit('refresh')" 
        :disabled="refreshing" 
        class="refresh-btn"
        :class="{ 'has-unsaved-changes': layoutChanged }"
        :title="layoutChanged ? 'Peringatan: Ada perubahan yang belum disimpan!' : 'Refresh layout dan hapus data yang tidak valid'"
      >
        {{ refreshing ? 'Refreshing...' : 'Refresh Layout' }}
      </button>

      <button 
        @click="$emit('reset')"
        class="reset-btn"
      >
        Reset
      </button>

      <a 
          v-if="showBackButton" 
          :href="backUrl || '/admin'" 
          class="admin-back-button"
        >
          <font-awesome-icon icon="fa-solid fa-arrow-left" />
          Kembali
        </a>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

interface Props {
  title: string
  subtitle?: string
  layoutChanged: boolean
  saving: boolean
  refreshing?: boolean
  showLayoutSelector?: boolean
  showRefreshButton?: boolean
  showBackButton?: boolean
  backButtonText?: string
  allowedStreams?: string[]
  functionApps?: { app_id: number; app_name: string }[]
  currentStream?: string
  currentAppId?: number | string
  backUrl?: string;
}

const props = withDefaults(defineProps<Props>(), {
  refreshing: false,
  showLayoutSelector: false,
  showRefreshButton: false,
  showBackButton: false,
  currentAppId: ''
})

const emit = defineEmits<{
  save: []
  refresh: []
  reset: []
  back: []
  streamChange: [stream: string]
  appChange: [appId: number | string]
}>()

// Local state for layout selector
const selectedLayout = ref('')

// Initialize selectedLayout based on current props
function initializeSelectedLayout() {
  if (props.currentStream) {
    selectedLayout.value = `stream:${props.currentStream}`
  } else if (props.currentAppId && props.currentAppId !== '') {
    selectedLayout.value = `app:${props.currentAppId}`
  }
}

// Watch for prop changes
watch(() => props.currentStream, (newStream) => {
  if (newStream) {
    selectedLayout.value = `stream:${newStream}`
  }
})

watch(() => props.currentAppId, (newAppId) => {
  if (newAppId && newAppId !== '') {
    selectedLayout.value = `app:${newAppId}`
  }
})

// Handle layout change
function onLayoutChange() {
  const [type, value] = selectedLayout.value.split(':')
  
  if (type === 'stream') {
    emit('streamChange', value)
  } else if (type === 'app') {
    emit('appChange', value)
  }
}

// Initialize on mount
initializeSelectedLayout()
</script>

<style scoped>
/* Uses shared admin layout styles from admin-layout.css */
.admin-header {
  background: white;
  border-bottom: 1px solid #e2e8f0;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
}

.subtitle {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0.25rem 0 0 0;
}

.header-controls {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.layout-selector {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  background: white;
  font-size: 0.875rem;
}

.save-btn, .refresh-btn, .reset-btn, .back-btn {
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.save-btn {
  background: #3b82f6;
  color: white;
}

.save-btn:hover:not(:disabled) {
  background: #2563eb;
}

.save-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.refresh-btn {
  background: #10b981;
  color: white;
  position: relative;
}

.refresh-btn:hover:not(:disabled) {
  background: #059669;
}

.refresh-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.refresh-btn.has-unsaved-changes {
  background: #f59e0b;
}

.refresh-btn.has-unsaved-changes:hover:not(:disabled) {
  background: #d97706;
}

.reset-btn {
  background: #6b7280;
  color: white;
}

.reset-btn:hover {
  background: #4b5563;
}

.back-btn {
  background: #374151;
  color: white;
}

.back-btn:hover {
  background: #1f2937;
}

/* Status indicators */
.status-indicator {
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  font-weight: 500;
  padding: 0.375rem 0.75rem;
  border-radius: 0.375rem;
  margin-right: 0.75rem;
}

.status-indicator.auto-saving {
  color: #d97706;
  background: #fef3c7;
  border: 1px solid #fde68a;
}

.status-indicator.unsaved {
  color: #ea580c;
  background: #fed7aa;
  border: 1px solid #fdba74;
}

.status-indicator.saved {
  color: #059669;
  background: #d1fae5;
  border: 1px solid #a7f3d0;
}

.spinner {
  animation: spin 1s linear infinite;
  width: 1rem;
  height: 1rem;
  margin-right: 0.5rem;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.indicator-dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 50%;
  margin-right: 0.5rem;
  background: #ea580c;
}

.indicator-dot.saved-dot {
  background: #059669;
}

  .admin-back-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background-color: var(--bg-alt);
    color: var(--text-color);
    border: 1px solid var(--border-color);
    border-radius: var(--radius);
    font-size: 0.875rem;
    text-decoration: none;
    transition: all var(--transition-fast);
  }
  
  .admin-back-button:hover {
    background-color: var(--bg-hover);
  }
</style>
