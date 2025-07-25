<template>
  <div 
    class="app-node" 
    :class="{ 
      'admin-mode': adminMode,
      'home-stream': nodeData.is_home_stream,
      'external-stream': !nodeData.is_home_stream
    }"
    :style="nodeStyle"
  >
    <!-- Source Handles (for outgoing connections) - always present but hidden in user mode -->
    <Handle
      id="top-source"
      type="source"
      :position="Position.Top"
      class="custom-handle"
      :class="{ 'user-mode-hidden': !adminMode }"
    />
    <Handle
      id="right-source"
      type="source"
      :position="Position.Right"
      class="custom-handle"
      :class="{ 'user-mode-hidden': !adminMode }"
    />
    <Handle
      id="bottom-source"
      type="source"
      :position="Position.Bottom"
      class="custom-handle"
      :class="{ 'user-mode-hidden': !adminMode }"
    />
    <Handle
      id="left-source"
      type="source"
      :position="Position.Left"
      class="custom-handle"
      :class="{ 'user-mode-hidden': !adminMode }"
    />
    
    <!-- Target Handles (for incoming connections) - always present but hidden in user mode -->
    <Handle
      id="top-target"
      type="target"
      :position="Position.Top"
      class="custom-handle"
      :class="{ 'user-mode-hidden': !adminMode }"
    />
    <Handle
      id="right-target"
      type="target"
      :position="Position.Right"
      class="custom-handle"
      :class="{ 'user-mode-hidden': !adminMode }"
    />
    <Handle
      id="bottom-target"
      type="target"
      :position="Position.Bottom"
      class="custom-handle"
      :class="{ 'user-mode-hidden': !adminMode }"
    />
    <Handle
      id="left-target"
      type="target"
      :position="Position.Left"
      class="custom-handle"
      :class="{ 'user-mode-hidden': !adminMode }"
    />
    
    <!-- Node Content -->
    <div class="node-content">
      <div class="app-label">{{ nodeData.label }}</div>
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

// Ensure data exists and has required properties
const nodeData = computed(() => ({
  label: props.data?.label || 'Unknown',
  lingkup: props.data?.lingkup || '',
  is_home_stream: props.data?.is_home_stream || false,
  ...props.data
}))

const nodeColors = computed(() => {
  const colorMap: { [key: string]: { background: string; border: string } } = {
    'sp': { background: '#f5f5f5', border: '#000000' },
    'mi': { background: '#fff5f5', border: '#ff0000' },
    'ssk': { background: '#fffef0', border: '#fbff00' },
    'ssk-mon': { background: '#fffef0', border: '#fbff00' },
    'moneter': { background: '#fffef0', border: '#fbff00' },
    'market': { background: '#fdf4ff', border: '#dd00ff' },
    'internal bi': { background: '#f0fff4', border: '#00ff48' },
    'external bi': { background: '#eff6ff', border: '#0a74da' },
    'middleware': { background: '#f0fffe', border: '#00ddff' },
  }
  
  return colorMap[nodeData.value.lingkup] || { background: '#ffffff', border: '#6b7280' }
})

const nodeStyle = computed(() => {
  const colors = nodeColors.value
  return {
    backgroundColor: colors.background,
    borderColor: colors.border,
  }
})
</script>

<style scoped>
.app-node {
  position: relative;
  width: 120px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  border-radius: 8px;
  border: 2px solid #e5e7eb;
  box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.app-node.admin-mode {
  cursor: grab;
  border-color: #3b82f6;
}

.app-node.admin-mode:hover {
  box-shadow: 0 4px 8px -1px rgba(0, 0, 0, 0.15), 0 2px 4px -1px rgba(0, 0, 0, 0.1);
  transform: translateY(-1px);
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
  padding: 2px;
}

/* Slightly adjusted content spacing for user mode */
.app-node:not(.admin-mode) .node-content {
  padding: 3px;
  gap: 2px;
}

.app-label {
  font-size: 11px;
  font-weight: 600;
  color: #1c1c1e;
  line-height: 1.2;
  text-align: center;
  white-space: pre-line;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 100%;
  padding: 4px;
}

/* Slightly larger text for user mode (non-admin) */
.app-node:not(.admin-mode) .app-label {
  font-size: 12px;
  font-weight: 650;
  line-height: 1.25;
  padding: 6px 4px;
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

/* Custom handle styles */
.custom-handle {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: 2px solid white;
  background: #3b82f6;
  opacity: 0;
  transition: opacity 0.2s ease;
}

/* Hide handles completely in user mode */
.custom-handle.user-mode-hidden {
  opacity: 0 !important;
  pointer-events: none;
  visibility: hidden;
}

.app-node:hover .custom-handle {
  opacity: 1;
}

/* Don't show handles on hover in user mode */
.app-node:hover .custom-handle.user-mode-hidden {
  opacity: 0 !important;
}

.custom-handle:hover {
  background: #10b981;
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
