<template>
  <aside 
    v-if="visible" 
    class="details-sidebar"
    :class="{ 'details-sidebar-open': visible }"
    :style="{ top: offsetTop, height: `calc(100vh - ${offsetTop} - 1rem)` }"
  >
    <header>
      <h1>{{ sidebarTitle }}</h1>
      <button 
        @click="$emit('close')" 
        class="close-button"
        title="Close"
      >
        <font-awesome-icon icon="fa-solid fa-times" />
      </button>
    </header>
    
    <!-- Edge Details -->
    <div class="sidebar-content" v-if="detailType === 'edge' && edgeData">
      <!-- Connection Overview -->
      <div class="detail-section">
        <h3>Overview Koneksi</h3>
        <div class="connection-flow">
          <div class="app-info">
            <div class="app-name">{{ getSourceAppName(edgeData) }}</div>
          </div>
          
          <div class="connection-arrow" aria-hidden="true">
            <font-awesome-icon icon="fa-solid fa-minus" class="connection-icon" />
          </div>
          
          <div class="app-info">
            <div class="app-name">{{ getTargetAppName(edgeData) }}</div>
          </div>
        </div>
      </div>

      <!-- Connection Type -->
      <div class="detail-section">
        <h3>Tipe Koneksi</h3>
        <div class="badges-content">
          <div 
            class="detail-badge" 
            :class="getConnectionBadgeClass(edgeData.connection_type)"
            :style="getConnectionBadgeStyle(edgeData)"
          >
            {{ getConnectionTypeLabel(edgeData.connection_type) }}
          </div>
        </div>
      </div>

      <!-- Admin Actions for Edge -->
      <div class="detail-section" v-if="isAdmin">
        <h3>Admin</h3>
        <div class="buttons-content">
          <button 
            @click="editIntegration" 
            class="edit-button"
            title="Edit Integration"
          >
            <font-awesome-icon icon="fa-solid fa-edit" />
            Edit Koneksi
          </button>
        </div>
      </div>

      <!-- Inbound -->
      <div class="detail-section" v-if="edgeData.inbound">
        <h3>Inbound</h3>
        <div class="description-content">
          {{ edgeData.inbound }}
        </div>
      </div>

      <!-- Outbound -->
      <div class="detail-section" v-if="edgeData.outbound">
        <h3>Outbound</h3>
        <div class="description-content">
          {{ edgeData.outbound }}
        </div>
      </div>
    </div>

    <!-- Node Details -->
    <div class="sidebar-content" v-else-if="detailType === 'node' && nodeData">
      <!-- App Overview -->
      <div class="detail-section">
        <h3>Detail Aplikasi</h3>
        <div class="default-content">
          <div class="detail-item">
            <strong>Nama Aplikasi:</strong>
            <span>{{ nodeData.app_name }}</span>
          </div>
          <div class="detail-item">
            <strong>Stream:</strong>
            <span>{{ nodeData.stream_name?.toUpperCase() || 'Unknown' }}</span>
          </div>
          <div class="detail-item">
            <strong>App ID:</strong>
            <span>{{ nodeData.app_id }}</span>
          </div>
        </div>
      </div>

      <!-- App Description -->
      <div class="detail-section" v-if="nodeData.description">
        <h3>Deskripsi</h3>
        <div class="description-content">
          {{ nodeData.description }}
        </div>
      </div>

      <!-- Admin Actions for Node -->
      <div class="detail-section" v-if="isAdmin">
        <h3>Admin</h3>
        <div class="buttons-content">
          <button 
            @click="editApp" 
            class="edit-button"
            title="Edit Application"
          >
            <font-awesome-icon icon="fa-solid fa-edit" />
            Edit Aplikasi
          </button>
        </div>
      </div>
    </div>
    
    <!-- Loading state -->
    <div v-else class="sidebar-content">
      <p>Loading details...</p>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoutes } from '@/composables/useRoutes'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

interface AppInfo {
  app_id: number;
  app_name: string;
}

interface EdgeData {
  integration_id: number;
  sourceApp?: AppInfo;
  targetApp?: AppInfo;
  source_app_name?: string;
  target_app_name?: string;
  connection_type: string;
  color?: string;
  direction: string;
  inbound?: string;
  outbound?: string;
  connection_endpoint?: string;
}

interface NodeData {
  app_id: number;
  app_name: string;
  stream_name?: string;
  description?: string;
  app_type?: string;
  is_home_stream: boolean;
  is_external: boolean;
}

interface Props {
  visible: boolean;
  detailType?: 'edge' | 'node';
  edgeData?: EdgeData | null;
  nodeData?: NodeData | null;
  isAdmin?: boolean;
  offsetTop?: string;
}

const props = withDefaults(defineProps<Props>(), {
  offsetTop: '1rem',
  detailType: 'edge'
});

const { visitRoute } = useRoutes();

const sidebarTitle = computed(() => {
  if (props.detailType === 'edge') {
    return 'Detail Integrasi';
  } else if (props.detailType === 'node') {
    return 'Detail Aplikasi';
  }
  return 'Detail';
});

function getSourceAppName(edgeData: EdgeData): string {
  console.log('getSourceAppName called with:', edgeData);
  const result = edgeData.sourceApp?.app_name || edgeData.source_app_name || 'Unknown App';
  console.log('Source app name result:', result);
  return result;
}

function getTargetAppName(edgeData: EdgeData): string {
  console.log('getTargetAppName called with:', edgeData);
  const result = edgeData.targetApp?.app_name || edgeData.target_app_name || 'Unknown App';
  console.log('Target app name result:', result);
  return result;
}

function getConnectionTypeLabel(connectionType: string): string {
  console.log('getConnectionTypeLabel called with:', connectionType);
  const result = (connectionType || 'direct').toUpperCase();
  console.log('Connection type result:', result);
  return result;
}

function getConnectionBadgeClass(type: string): string {
  // Return a generic class since we'll use dynamic styles
  return 'badge-dynamic';
}

function getConnectionBadgeStyle(edgeData: EdgeData): any {
  if (edgeData.color) {
    // Convert hex color to rgba for background with opacity
    const hexColor = edgeData.color;
    const r = parseInt(hexColor.slice(1, 3), 16);
    const g = parseInt(hexColor.slice(3, 5), 16);
    const b = parseInt(hexColor.slice(5, 7), 16);
    
    return {
      backgroundColor: `rgba(${r}, ${g}, ${b}, 0.2)`,
      borderColor: `rgba(${r}, ${g}, ${b}, 0.3)`,
      color: '#000000'
    };
  }
  
  // Fallback style
  return {
    backgroundColor: 'rgba(255, 255, 255, 0.4)',
    borderColor: 'rgba(255, 255, 255, 0.2)',
    color: '#000000'
  };
}

function editIntegration() {
  if (!props.edgeData?.integration_id) return;
  
  visitRoute('admin.integrations.edit', { id: props.edgeData.integration_id });
}

function editApp() {
  if (!props.nodeData?.app_id) return;
  
  visitRoute('admin.apps.edit', { app: props.nodeData.app_id });
}
</script>

<style scoped>
.details-sidebar {
  position: fixed;
  top: 1rem;
  right: -340px;
  width: 320px;
  height: calc(100vh - 2rem);
  background-color: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(15px) saturate(180%);
  -webkit-backdrop-filter: blur(15px) saturate(180%);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 1rem;
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
  z-index: 9999;
  transition: right 0.3s ease-in-out, background-color 0.5s ease, border-color 0.5s ease;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.details-sidebar.details-sidebar-open {
  right: 1rem;
}

/* Header styling matching left sidebar */
.details-sidebar header {
  padding: 25px;
  border-bottom: 1px solid rgba(221, 221, 221, 0.2);
  transition: border-color 0.5s ease;
  position: relative;
}

.details-sidebar header h1 {
  margin: 0;
  font-size: 1.8em;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-color);
}

.close-button {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 0.25rem;
  transition: all 0.3s ease;
}

.close-button:hover {
  transform: scale(1.1);
}

/* Content styling matching left sidebar */
.sidebar-content {
  padding: 25px;
  flex-grow: 1;
  overflow-y: auto;
}

/* Detail sections */
.detail-section {
  margin-bottom: 2rem;
}

.detail-section h3 {
  margin: 0 0 1rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-color);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Content styles */
.default-content {
  padding: 1rem;
  background: rgba(255, 255, 255, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 0.5rem;
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.description-content {
  padding: 1rem;
  background: rgba(255, 255, 255, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 0.5rem;
  color: var(--text-color);
  line-height: 1.5;
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.badges-content {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.buttons-content {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

/* Detail items */
.detail-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.detail-item:last-child {
  margin-bottom: 0;
  border-bottom: none;
}

.detail-item strong {
  color: var(--text-color);
  font-weight: 600;
  font-size: 0.875rem;
}

.detail-item span {
  color: var(--text-color);
  font-size: 0.875rem;
  text-align: right;
}

/* Connection flow */
.connection-flow {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 0.5rem;
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.app-info {
  flex: 1;
  text-align: center;
}

.app-name {
  font-weight: 600;
  color: var(--text-color);
  font-size: 0.875rem;
}

.connection-arrow {
  width: 24px;
  text-align: center;
}

.connection-icon {
  color: var(--text-color);
  font-size: 0.875rem;
}

/* Badges */
.detail-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background: rgba(255, 255, 255, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: var(--text-color);
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  text-align: center;
}

.badge-dynamic {
  background: rgba(255, 255, 255, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: var(--text-color);
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  text-align: center;
}

.badge-direct {
  background: rgba(255, 255, 255, 0.4);
  color: var(--text-color);
}

.badge-soa {
  background: rgba(34, 197, 94, 0.2);
  border-color: rgba(34, 197, 94, 0.3);
  color: rgb(21, 128, 61);
}

.badge-sftp {
  background: rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.3);
  color: rgb(29, 78, 216);
}

.badge-soa-sftp {
  background: rgba(107, 114, 128, 0.2);
  border-color: rgba(107, 114, 128, 0.3);
  color: rgb(75, 85, 99);
}

.badge-default {
  background: rgba(255, 255, 255, 0.4);
  color: var(--text-color);
}

/* Edit buttons */
.edit-button {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: rgba(34, 197, 94, 0.2);
  border: 1px solid rgba(34, 197, 94, 0.3);
  color: rgb(21, 128, 61);
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
  width: 100%;
  justify-content: center;
}

.edit-button:hover {
  background: rgba(34, 197, 94, 0.3);
  border-color: rgba(34, 197, 94, 0.4);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(34, 197, 94, 0.2);
}

/* Loading state */
.sidebar-content p {
  color: var(--text-color-muted);
  text-align: center;
  margin: 2rem 0;
}
</style>
