<template>
  <aside 
    v-if="visible" 
    class="edge-details-sidebar"
    :class="{ 'edge-details-sidebar-open': visible }"
    :style="{ top: offsetTop, height: `calc(100vh - ${offsetTop} - 1rem)` }"
  >
    <header>
      <h1>Detail Integrasi</h1>
      <button 
        @click="$emit('close')" 
        class="close-button"
        title="Close"
      >
        <font-awesome-icon icon="fa-solid fa-times" />
      </button>
    </header>
    
    <div class="sidebar-content" v-if="edgeData">
      <!-- Connection Overview -->
      <div class="sidebar-section">
        <h3>Overview Koneksi</h3>
        <div class="connection-flow">
          <div class="app-info">
            <div class="app-name">{{ edgeData.sourceApp?.app_name || edgeData.source_app_name || 'Unknown App' }}</div>
          </div>
          
          <div class="connection-arrow">
            <font-awesome-icon icon="fa-solid fa-arrow-right" v-if="edgeData.direction === 'one_way'" />
            <font-awesome-icon icon="fa-solid fa-exchange-alt" v-else />
          </div>
          
          <div class="app-info">
            <div class="app-name">{{ edgeData.targetApp?.app_name || edgeData.target_app_name || 'Unknown App' }}</div>
          </div>
        </div>
      </div>

      <!-- Connection Type -->
      <div class="sidebar-section">
        <h3>Tipe Koneksi</h3>
        <div class="connection-type-badge" :class="getConnectionTypeClass(edgeData.connection_type)">
          {{ edgeData.connection_type?.toUpperCase() || 'DIRECT' }}
        </div>
      </div>

      <!-- Admin Actions -->
      <div class="sidebar-section" v-if="isAdmin">
        <h3>Back Office</h3>
        <div class="admin-buttons">
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
      <div class="sidebar-section" v-if="edgeData.inbound">
        <h3>Inbound</h3>
        <div class="description-content">
          {{ edgeData.inbound }}
        </div>
      </div>

      <!-- Outbound -->
      <div class="sidebar-section" v-if="edgeData.outbound">
        <h3>Outbound</h3>
        <div class="description-content">
          {{ edgeData.outbound }}
        </div>
      </div>

      <!-- Connection Endpoint -->
      <!-- <div class="sidebar-section" v-if="edgeData.connection_endpoint">
        <h3>Connection Endpoint</h3>
        <div class="endpoint-content">
          <a :href="edgeData.connection_endpoint" target="_blank" class="endpoint-link">
            {{ edgeData.connection_endpoint }}
            <font-awesome-icon icon="fa-solid fa-external-link-alt" />
          </a>
        </div>
      </div> -->
    </div>
    
    <div v-else class="sidebar-content">
      <p>Loading integration details...</p>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useNotification } from '@/composables/useNotification';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

const { showSuccess, showError } = useNotification();

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
  direction: string;
  inbound?: string;
  outbound?: string;
  connection_endpoint?: string;
}

interface Props {
  visible: boolean;
  edgeData?: EdgeData | null;
  isAdmin?: boolean;
  offsetTop?: string;
}

const props = withDefaults(defineProps<Props>(), {
  offsetTop: '1rem'
});

const emit = defineEmits<{
  close: [];
  refresh: [];
}>();

function getConnectionTypeClass(type: string): string {
  const typeMap: { [key: string]: string } = {
    'direct': 'connection-direct',
    'soa': 'connection-soa',
    'sftp': 'connection-sftp'
  };
  return typeMap[type?.toLowerCase()] || 'connection-default';
}

function editIntegration() {
  if (!props.edgeData?.integration_id) return;
  
  router.visit(`/admin/integrations/${props.edgeData.integration_id}/edit`);
}
</script>

<style scoped>
.edge-details-sidebar {
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

.edge-details-sidebar.edge-details-sidebar-open {
  right: 1rem;
}

/* Header styling matching left sidebar */
.edge-details-sidebar header {
  padding: 25px;
  border-bottom: 1px solid rgba(221, 221, 221, 0.2);
  transition: border-color 0.5s ease;
  position: relative;
}

.edge-details-sidebar header h1 {
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

.sidebar-section {
  margin-bottom: 2rem;
}

.sidebar-section h3 {
  margin: 0 0 1rem 0;
  font-size: 1rem;
  font-weight: 600;
  color: var(--text-color);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Connection flow styling */
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

/* Badge styling */
.connection-type-badge {
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
}

.connection-direct {
  background: rgba(255, 255, 255, 0.4);
  color: var(--text-color);
}

.connection-soa {
  background: rgba(34, 197, 94, 0.2);
  border-color: rgba(34, 197, 94, 0.3);
  color: rgb(21, 128, 61);
}

.connection-sftp {
  background: rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.3);
  color: rgb(29, 78, 216);
}

.connection-default {
  background: rgba(255, 255, 255, 0.4);
  color: var(--text-color);
}

.admin-buttons {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

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
}

.edit-button:hover {
  background: rgba(34, 197, 94, 0.3);
  border-color: rgba(34, 197, 94, 0.4);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(34, 197, 94, 0.2);
}

/* Content sections */
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

.endpoint-content {
  padding: 1rem;
  background: rgba(255, 255, 255, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 0.5rem;
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.endpoint-link {
  color: var(--primary-color);
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  word-break: break-all;
  transition: color 0.3s ease;
}

.endpoint-link:hover {
  text-decoration: underline;
  color: var(--primary-color-dark);
}

/* Loading state */
.sidebar-content p {
  color: var(--text-color-muted);
  text-align: center;
  margin: 2rem 0;
}
</style>
