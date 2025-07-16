<template>
  <div 
    class="stream-nest" 
    :class="{ 'admin-mode': adminMode }"
    :style="nestStyle"
  >
    <!-- Stream label -->
    <div class="stream-label">
      {{ nodeData.label }}
    </div>
    
    <!-- Add resize handles for admin mode -->
    <div v-if="adminMode" class="resize-controls">
      <div class="resize-handle corner top-left" @mousedown="(e) => startResize('nw', e)"></div>
      <div class="resize-handle corner top-right" @mousedown="(e) => startResize('ne', e)"></div>
      <div class="resize-handle corner bottom-left" @mousedown="(e) => startResize('sw', e)"></div>
      <div class="resize-handle corner bottom-right" @mousedown="(e) => startResize('se', e)"></div>
      <div class="resize-handle edge top" @mousedown="(e) => startResize('n', e)"></div>
      <div class="resize-handle edge right" @mousedown="(e) => startResize('e', e)"></div>
      <div class="resize-handle edge bottom" @mousedown="(e) => startResize('s', e)"></div>
      <div class="resize-handle edge left" @mousedown="(e) => startResize('w', e)"></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useVueFlow } from '@vue-flow/core'
import type { NodeProps } from '@vue-flow/core'

interface Props extends NodeProps {
  adminMode?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  adminMode: false
})

const emit = defineEmits<{
  resize: [event: { width: number, height: number }]
}>()

// Ensure data exists and has required properties
const nodeData = computed(() => ({
  label: props.data?.label || 'Stream',
  ...props.data
}))

// Get Vue Flow instance for direct node manipulation
const { updateNode } = useVueFlow()

// Resize state
const isResizing = ref(false)
const resizeDirection = ref('')
const startPos = ref({ x: 0, y: 0 })
const startSize = ref({ width: 0, height: 0 })
const currentSize = ref({ width: 0, height: 0 })

// Computed style - use the actual Vue Flow node dimensions
const nestStyle = computed(() => {
  const currentStyle = props.style || {}
  
  // During resize, use the current size for immediate visual feedback
  if (isResizing.value) {
    return {
      width: `${currentSize.value.width}px`,
      height: `${currentSize.value.height}px`,
      border: '2px dashed #3b82f6',
      borderRadius: '12px',
      background: 'rgba(59, 130, 246, 0.05)',
      position: 'relative' as const,
      cursor: props.adminMode ? 'move' : 'default',
      boxSizing: 'border-box' as const,
      // Apply any custom style from Vue Flow
      ...currentStyle
    }
  }
  
  // Normal state - use Vue Flow dimensions
  return {
    width: '100%',
    height: '100%',
    border: '2px dashed #3b82f6',
    borderRadius: '12px',
    background: 'rgba(59, 130, 246, 0.05)',
    position: 'relative' as const,
    cursor: props.adminMode ? 'move' : 'default',
    boxSizing: 'border-box' as const,
    // Apply any custom style from Vue Flow
    ...currentStyle
  }
})

function startResize(direction: string, event: MouseEvent) {
  if (!props.adminMode) return
  
  event.preventDefault()
  event.stopPropagation()
  
  isResizing.value = true
  resizeDirection.value = direction
  startPos.value = { x: event.clientX, y: event.clientY }
  
  // Get current size from dimensions, style, or default
  const currentDimensions = props.dimensions
  const currentStyle = props.style || {}
  
  let currentWidth = 600  // default
  let currentHeight = 400 // default
  
  if (currentDimensions) {
    currentWidth = currentDimensions.width
    currentHeight = currentDimensions.height
  } else if (currentStyle.width || currentStyle.height) {
    currentWidth = parseInt(currentStyle.width?.toString().replace('px', '') || '600')
    currentHeight = parseInt(currentStyle.height?.toString().replace('px', '') || '400')
  }
  
  startSize.value = { width: currentWidth, height: currentHeight }
  currentSize.value = { width: currentWidth, height: currentHeight }
  
  document.addEventListener('mousemove', handleResize)
  document.addEventListener('mouseup', stopResize)
  
  // Prevent text selection
  document.body.style.userSelect = 'none'
}

function handleResize(event: MouseEvent) {
  if (!isResizing.value) return
  
  const deltaX = event.clientX - startPos.value.x
  const deltaY = event.clientY - startPos.value.y
  
  let newWidth = startSize.value.width
  let newHeight = startSize.value.height
  
  // Calculate new dimensions based on direction
  if (resizeDirection.value.includes('e')) {
    newWidth = Math.max(300, startSize.value.width + deltaX)
  }
  if (resizeDirection.value.includes('w')) {
    newWidth = Math.max(300, startSize.value.width - deltaX)
  }
  if (resizeDirection.value.includes('s')) {
    newHeight = Math.max(200, startSize.value.height + deltaY)
  }
  if (resizeDirection.value.includes('n')) {
    newHeight = Math.max(200, startSize.value.height - deltaY)
  }
  
  // Update current size for immediate visual feedback
  currentSize.value = { width: newWidth, height: newHeight }
  
  // Update the Vue Flow node directly for real-time feedback
  if (props.id) {
    updateNode(props.id, {
      style: {
        ...props.style,
        width: `${newWidth}px`,
        height: `${newHeight}px`
      },
      dimensions: {
        width: newWidth,
        height: newHeight
      }
    })
  }
  
  // Also emit resize event for parent component handling
  emit('resize', { width: newWidth, height: newHeight })
}

function stopResize() {
  if (!isResizing.value) return
  
  isResizing.value = false
  resizeDirection.value = ''
  document.removeEventListener('mousemove', handleResize)
  document.removeEventListener('mouseup', stopResize)
  document.body.style.userSelect = ''
}

onUnmounted(() => {
  document.removeEventListener('mousemove', handleResize)
  document.removeEventListener('mouseup', stopResize)
})
</script>

<style scoped>
.stream-nest {
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 20px;
}

.stream-label {
  font-size: 18px;
  font-weight: 600;
  color: #1e40af;
  text-align: center;
  pointer-events: none;
  user-select: none;
}

/* Resize controls */
.resize-controls {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
}

.resize-handle {
  position: absolute;
  background: #3b82f6;
  border: 2px solid white;
  border-radius: 3px;
  pointer-events: all;
  z-index: 1000;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.stream-nest.admin-mode .resize-handle {
  opacity: 0.7;
}

.stream-nest.admin-mode:hover .resize-handle {
  opacity: 1;
}

/* Corner handles */
.resize-handle.corner {
  width: 12px;
  height: 12px;
}

.resize-handle.top-left {
  top: -6px;
  left: -6px;
  cursor: nw-resize;
}

.resize-handle.top-right {
  top: -6px;
  right: -6px;
  cursor: ne-resize;
}

.resize-handle.bottom-left {
  bottom: -6px;
  left: -6px;
  cursor: sw-resize;
}

.resize-handle.bottom-right {
  bottom: -6px;
  right: -6px;
  cursor: se-resize;
}

/* Edge handles */
.resize-handle.edge.top {
  top: -4px;
  left: 50%;
  transform: translateX(-50%);
  width: 20px;
  height: 8px;
  cursor: n-resize;
}

.resize-handle.edge.right {
  right: -4px;
  top: 50%;
  transform: translateY(-50%);
  width: 8px;
  height: 20px;
  cursor: e-resize;
}

.resize-handle.edge.bottom {
  bottom: -4px;
  left: 50%;
  transform: translateX(-50%);
  width: 20px;
  height: 8px;
  cursor: s-resize;
}

.resize-handle.edge.left {
  left: -4px;
  top: 50%;
  transform: translateY(-50%);
  width: 8px;
  height: 20px;
  cursor: w-resize;
}
</style>
