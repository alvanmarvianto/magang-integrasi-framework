<template>
  <div class="stream-parent-node">
    {{ data.label }}
  </div>
</template>

<script setup lang="ts">
import { toRefs } from 'vue'
import type { NodeProps } from '@vue-flow/core'

interface StreamNodeData {
  label: string;
  app_id: number;
  stream_name: string;
  lingkup: string;
  is_home_stream: boolean;
  is_parent_node?: boolean;
}

const props = defineProps<NodeProps<StreamNodeData>>()
const { data } = toRefs(props)
</script>

<style scoped>
.stream-parent-node {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 12px;
  font-size: 16px;
  font-weight: bold;
  color: #1e40af;
  text-align: center;
  background: transparent;
  pointer-events: none;
}
</style>
