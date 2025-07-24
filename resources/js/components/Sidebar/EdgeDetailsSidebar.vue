<template>
  <div 
    v-if="visible" 
    class="edge-details-sidebar"
    :class="{ 'edge-details-sidebar-open': visible }"
  >
    <div class="edge-details-header">
      <h3>Integration Details</h3>
      <button 
        @click="$emit('close')" 
        class="edge-details-close-btn"
        title="Close"
      >
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="edge-details-content" v-if="edgeData">
      <!-- Connection Overview -->
      <div class="edge-details-section">
        <h4>Connection</h4>
        <div class="connection-flow">
          <div class="app-info source-app">
            <div class="app-name">{{ edgeData.sourceApp?.app_name || 'Unknown App' }}</div>
            <div class="app-type">Source</div>
          </div>
          
          <div class="connection-arrow">
            <i class="fas fa-arrow-right" v-if="edgeData.direction === 'one_way'"></i>
            <i class="fas fa-exchange-alt" v-else></i>
          </div>
          
          <div class="app-info target-app">
            <div class="app-name">{{ edgeData.targetApp?.app_name || 'Unknown App' }}</div>
            <div class="app-type">Target</div>
          </div>
        </div>
      </div>

      <!-- Connection Type -->
      <div class="edge-details-section">
        <h4>Connection Type</h4>
        <div class="connection-type-badge" :class="getConnectionTypeClass(edgeData.connection_type)">
          {{ edgeData.connection_type?.toUpperCase() || 'DIRECT' }}
        </div>
      </div>

      <!-- Direction Details -->
      <div class="edge-details-section">
        <h4>Direction</h4>
        <div class="direction-info">
          <div class="direction-badge" :class="getDirectionClass(edgeData.direction)">
            <i class="fas fa-arrow-right" v-if="edgeData.direction === 'one_way'"></i>
            <i class="fas fa-exchange-alt" v-else></i>
            {{ edgeData.direction === 'one_way' ? 'Unidirectional' : 'Bidirectional' }}
          </div>
          
          <div v-if="edgeData.direction === 'one_way' && edgeData.starting_point" class="starting-point">
            <strong>Starting Point:</strong> 
            {{ edgeData.starting_point === 'source' ? edgeData.sourceApp?.app_name : edgeData.targetApp?.app_name }}
          </div>
        </div>
      </div>

      <!-- Description -->
      <div class="edge-details-section" v-if="edgeData.description">
        <h4>Description</h4>
        <div class="description-content">
          {{ edgeData.description }}
        </div>
      </div>

      <!-- Connection Endpoint -->
      <div class="edge-details-section" v-if="edgeData.connection_endpoint">
        <h4>Connection Endpoint</h4>
        <div class="endpoint-content">
          <a :href="edgeData.connection_endpoint" target="_blank" class="endpoint-link">
            {{ edgeData.connection_endpoint }}
            <i class="fas fa-external-link-alt"></i>
          </a>
        </div>
      </div>

      <!-- Integration ID -->
      <div class="edge-details-section">
        <h4>Integration ID</h4>
        <div class="integration-id">
          {{ edgeData.integration_id }}
        </div>
      </div>
    </div>
    
    <div v-else class="edge-details-loading">
      <p>Loading integration details...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
interface AppInfo {
  app_id: number;
  app_name: string;
}

interface EdgeData {
  integration_id: number;
  sourceApp: AppInfo;
  targetApp: AppInfo;
  connection_type: string;
  direction: string;
  starting_point?: string;
  description?: string;
  connection_endpoint?: string;
}

interface Props {
  visible: boolean;
  edgeData?: EdgeData | null;
}

defineProps<Props>();

defineEmits<{
  close: [];
}>();

function getConnectionTypeClass(type: string): string {
  const typeMap: { [key: string]: string } = {
    'direct': 'connection-direct',
    'soa': 'connection-soa',
    'sftp': 'connection-sftp'
  };
  return typeMap[type?.toLowerCase()] || 'connection-default';
}

function getDirectionClass(direction: string): string {
  return direction === 'one_way' ? 'direction-unidirectional' : 'direction-bidirectional';
}
</script>

<style scoped>
.edge-details-sidebar {
  position: fixed;
  top: 0;
  right: -400px;
  width: 400px;
  height: 100vh;
  background: white;
  border-left: 1px solid #e5e7eb;
  box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  transition: right 0.3s ease-in-out;
  overflow-y: auto;
}

.edge-details-sidebar-open {
  right: 0;
}

.edge-details-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  background: #f9fafb;
}

.edge-details-header h3 {
  margin: 0;
  color: #1f2937;
  font-size: 1.125rem;
  font-weight: 600;
}

.edge-details-close-btn {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 0.25rem;
  transition: background-color 0.2s;
}

.edge-details-close-btn:hover {
  background: #e5e7eb;
  color: #374151;
}

.edge-details-content {
  padding: 1.5rem;
}

.edge-details-section {
  margin-bottom: 1.5rem;
}

.edge-details-section h4 {
  margin: 0 0 0.75rem 0;
  color: #374151;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.connection-flow {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}

.app-info {
  flex: 1;
  text-align: center;
}

.app-name {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.app-type {
  font-size: 0.75rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.connection-arrow {
  color: #3b82f6;
  font-size: 1.25rem;
}

.connection-type-badge {
  display: inline-block;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.connection-direct {
  background: #f3f4f6;
  color: #1f2937;
}

.connection-soa {
  background: #dcfce7;
  color: #166534;
}

.connection-sftp {
  background: #dbeafe;
  color: #1e40af;
}

.connection-default {
  background: #f3f4f6;
  color: #6b7280;
}

.direction-info {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.direction-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.direction-unidirectional {
  background: #fef3c7;
  color: #92400e;
}

.direction-bidirectional {
  background: #e0e7ff;
  color: #3730a3;
}

.starting-point {
  font-size: 0.875rem;
  color: #6b7280;
}

.description-content {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
  color: #374151;
  line-height: 1.5;
}

.endpoint-content {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}

.endpoint-link {
  color: #3b82f6;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  word-break: break-all;
}

.endpoint-link:hover {
  text-decoration: underline;
}

.integration-id {
  font-family: monospace;
  font-size: 0.875rem;
  color: #6b7280;
  background: #f3f4f6;
  padding: 0.5rem;
  border-radius: 0.25rem;
}

.edge-details-loading {
  padding: 1.5rem;
  text-align: center;
  color: #6b7280;
}
</style>
