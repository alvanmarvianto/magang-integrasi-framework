<template>
  <div 
    class="app-node" 
    :class="{ 
      'admin-mode': adminMode,
      'home-stream': data.is_home_stream,
      'external-stream': !data.is_home_stream
    }"
  >
    <!-- Source Handles (for outgoing connections) -->
    <Handle
      v-if="!adminMode"
      id="top-source"
      type="source"
      :position="Position.Top"
      class="custom-handle handle-source"
    />
    <Handle
      v-if="!adminMode"
      id="right-source"
      type="source"
      :position="Position.Right"
      class="custom-handle handle-source"
    />
    <Handle
      v-if="!adminMode"
      id="bottom-source"
      type="source"
      :position="Position.Bottom"
      class="custom-handle handle-source"
    />
    <Handle
      v-if="!adminMode"
      id="left-source"
      type="source"
      :position="Position.Left"
      class="custom-handle handle-source"
    />
    
    <!-- Target Handles (for incoming connections) -->
    <Handle
      v-if="!adminMode"
      id="top-target"
      type="target"
      :position="Position.Top"
      class="custom-handle handle-target"
    />
    <Handle
      v-if="!adminMode"
      id="right-target"
      type="target"
      :position="Position.Right"
      class="custom-handle handle-target"
    />
    <Handle
      v-if="!adminMode"
      id="bottom-target"
      type="target"
      :position="Position.Bottom"
      class="custom-handle handle-target"
    />
    <Handle
      v-if="!adminMode"
      id="left-target"
      type="target"
      :position="Position.Left"
      class="custom-handle handle-target"
    />
    
    <!-- Node Content -->
    <div class="node-content">
      <div class="app-label">{{ data.label }}</div>
      <div v-if="adminMode" class="admin-info">
        <div class="stream-info">{{ data.lingkup }}</div>
        <div class="app-id">ID: {{ data.app_id }}</div>
      </div>
    </div>

    <!-- Admin mode drag indicator -->
    <div v-if="adminMode" class="drag-indicator">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
        <path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/>
      </svg>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Handle, Position } from '@vue-flow/core'
import type { NodeProps } from '@vue-flow/core'

interface Props extends NodeProps {
  adminMode?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  adminMode: false
})

const nodeStyle = computed(() => {
  const baseStyle = {
    borderRadius: '8px',
    padding: '12px',
    minWidth: '120px',
    minHeight: '60px',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    position: 'relative',
    border: '2px solid',
    background: 'white',
    boxShadow: '0 2px 8px rgba(0, 0, 0, 0.1)',
    transition: 'all 0.2s ease',
    cursor: props.adminMode ? 'grab' : 'default'
  }

  if (props.data.is_home_stream) {
    return {
      ...baseStyle,
      borderColor: '#10b981',
      background: '#f0fdf4'
    }
  } else {
    return {
      ...baseStyle,
      borderColor: '#6b7280',
      background: '#f9fafb'
    }
  }
})
</script>

<style scoped>
.app-node {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.app-node.admin-mode {
  outline: 2px solid #3b82f6;
  outline-offset: 2px;
}

.app-node.admin-mode:hover {
  outline-color: #1d4ed8;
}

.node-content {
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  gap: 4px;
}

.app-label {
  font-size: 14px;
  font-weight: 600;
  color: #1c1c1e;
  line-height: 1.2;
}

.admin-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  margin-top: 4px;
}

.stream-info {
  font-size: 11px;
  color: #6b7280;
  font-weight: 500;
  text-transform: uppercase;
}

.app-id {
  font-size: 10px;
  color: #9ca3af;
}

.drag-indicator {
  position: absolute;
  top: 4px;
  right: 4px;
  color: #6b7280;
  opacity: 0.6;
}

.app-node.admin-mode:hover .drag-indicator {
  opacity: 1;
  color: #3b82f6;
}

/* Custom handle styles */
.custom-handle {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: 2px solid white;
  background: #6b7280;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.app-node:hover .custom-handle {
  opacity: 1;
}

.handle-source {
  background: #10b981;
}

.handle-target {
  background: #3b82f6;
}

.custom-handle:hover {
  transform: scale(1.2);
}

/* Different styles for home vs external stream apps */
.app-node.home-stream .app-label {
  color: #065f46;
}

.app-node.external-stream .app-label {
  color: #374151;
}

.app-node.admin-mode.home-stream {
  outline-color: #10b981;
}

.app-node.admin-mode.external-stream {
  outline-color: #6b7280;
}
</style>
