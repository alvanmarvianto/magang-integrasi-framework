<template>
  <div class="custom-node">
    <!-- Source Handles (for outgoing connections) -->
    <Handle
      id="top-source"
      type="source"
      :position="Position.Top"
      class="custom-handle handle-source"
    />
    <Handle
      id="right-source"
      type="source"
      :position="Position.Right"
      class="custom-handle handle-source"
    />
    <Handle
      id="bottom-source"
      type="source"
      :position="Position.Bottom"
      class="custom-handle handle-source"
    />
    <Handle
      id="left-source"
      type="source"
      :position="Position.Left"
      class="custom-handle handle-source"
    />
    
    <!-- Target Handles (for incoming connections) -->
    <Handle
      id="top-target"
      type="target"
      :position="Position.Top"
      class="custom-handle handle-target"
    />
    <Handle
      id="right-target"
      type="target"
      :position="Position.Right"
      class="custom-handle handle-target"
    />
    <Handle
      id="bottom-target"
      type="target"
      :position="Position.Bottom"
      class="custom-handle handle-target"
    />
    <Handle
      id="left-target"
      type="target"
      :position="Position.Left"
      class="custom-handle handle-target"
    />
    
    <!-- Node Content -->
    <div class="node-content">
      {{ data.label }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { toRefs } from 'vue'
import { Handle, Position } from '@vue-flow/core'
import type { NodeProps } from '@vue-flow/core'

interface AppNodeData {
  label: string;
  app_id: number;
  stream_name: string;
  lingkup: string;
  is_home_stream: boolean;
  is_parent_node?: boolean;
}

const props = defineProps<NodeProps<AppNodeData>>()
const { data } = toRefs(props)
</script>

<style scoped>
.custom-node {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.node-content {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  font-size: 12px;
  font-weight: 500;
  color: #1c1c1e;
  padding: 8px 12px;
  box-sizing: border-box;
}

.custom-handle {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: 2px solid #fff;
  opacity: 0;
  transition: all 0.2s ease;
}

.handle-source {
  background: #3b82f6;
}

.handle-target {
  background: #10b981;
}

.custom-node:hover .custom-handle {
  opacity: 1;
}

.custom-handle:hover {
  transform: scale(1.3);
  opacity: 1 !important;
}
</style>
