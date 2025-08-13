<template>
  <div 
    class="stream-nest" 
    :class="{ 'admin-mode': adminMode, 'user-mode': !adminMode }"
    :style="nestStyle"
  >
    <!-- User mode: separate border and center areas -->
    <template v-if="!adminMode">
      <!-- Draggable border strips -->
      <div class="drag-border-top" @mousedown="startManualDrag"></div>
      <div class="drag-border-right" @mousedown="startManualDrag"></div>
      <div class="drag-border-bottom" @mousedown="startManualDrag"></div>
      <div class="drag-border-left" @mousedown="startManualDrag"></div>
      <!-- Non-draggable center area -->
      <div 
        class="center-content"
        data-nodrag="true"
      >
        <div class="stream-label">
          {{ nodeData.label }}
        </div>
      </div>
    </template>
    
    <!-- Admin mode: everything is draggable -->
    <template v-else>
      <div class="stream-label">
        {{ nodeData.label }}
      </div>
    </template>
    
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
import { ref, computed, onUnmounted } from 'vue'
import { useVueFlow } from '@vue-flow/core'
import type { NodeProps } from '@vue-flow/core'

interface Props extends NodeProps {
  adminMode?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  adminMode: false
})

const emit = defineEmits<{
  resize: [event: { width: number, height: number, position?: { x: number, y: number } }]
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
const startPosition = ref({ x: 0, y: 0 })
const currentSize = ref({ width: 0, height: 0 })

// Manual drag state for border dragging
const isDragging = ref(false)
const dragStartPos = ref({ x: 0, y: 0 })
const dragStartNodePos = ref({ x: 0, y: 0 })

// Computed style - use the actual Vue Flow node dimensions
const nestStyle = computed(() => {
  const currentStyle = props.style || {}
  
  // Base style
  const baseStyle = {
    border: '2px dashed #3b82f6',
    borderRadius: '12px',
    background: 'rgba(59, 130, 246, 0.05)',
    position: 'relative' as const,
    boxSizing: 'border-box' as const,
    // Ensure the component fills the Vue Flow node container completely
    width: '100%',
    height: '100%',
    minWidth: '300px',
    minHeight: '200px',
    // In admin mode, make the border solid and slightly more visible
    ...(props.adminMode && {
      borderStyle: 'solid',
      background: 'rgba(59, 130, 246, 0.08)',
    })
  }
  
  // During resize, use the current size for immediate visual feedback
  if (isResizing.value) {
    return {
      ...baseStyle,
      width: `${currentSize.value.width}px`,
      height: `${currentSize.value.height}px`,
      border: '2px dashed #3b82f6',
      cursor: 'move',
      // Apply any custom style from Vue Flow
      ...currentStyle
    }
  }
  
  // Normal state - let Vue Flow handle the sizing through its style prop
  const finalStyle = {
    ...baseStyle,
    cursor: props.adminMode ? 'move' : 'drag',
    // Apply Vue Flow styles (from saved layout) if they exist
    ...currentStyle,
  }
  
  // Debug logging
  if (props.adminMode) {
    console.log(`StreamNest ${props.id} (admin) - Final style:`, finalStyle);
    console.log(`StreamNest ${props.id} (admin) - Props dimensions:`, props.dimensions);
    console.log(`StreamNest ${props.id} (admin) - Props style:`, currentStyle);
  }
  
  return finalStyle;
})

function startResize(direction: string, event: MouseEvent) {
  if (!props.adminMode) return
  
  event.preventDefault()
  event.stopPropagation()
  
  isResizing.value = true
  resizeDirection.value = direction
  startPos.value = { x: event.clientX, y: event.clientY }
  
  // Store initial position
  startPosition.value = { x: props.position?.x || 0, y: props.position?.y || 0 }
  
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
  let newPositionX = startPosition.value.x
  let newPositionY = startPosition.value.y
  
  // Calculate new dimensions and positions based on direction
  if (resizeDirection.value.includes('e')) {
    // Right side: increase width, no position change
    newWidth = Math.max(300, startSize.value.width + deltaX)
  }
  if (resizeDirection.value.includes('w')) {
    // Left side: increase width and adjust position
    const widthChange = startSize.value.width - deltaX
    newWidth = Math.max(300, widthChange)
    // Only adjust position if we're not at minimum width
    if (widthChange >= 300) {
      newPositionX = startPosition.value.x + deltaX
    } else {
      // If at minimum width, calculate position based on minimum width
      newPositionX = startPosition.value.x + (startSize.value.width - 300)
    }
  }
  if (resizeDirection.value.includes('s')) {
    // Bottom side: increase height, no position change
    newHeight = Math.max(200, startSize.value.height + deltaY)
  }
  if (resizeDirection.value.includes('n')) {
    // Top side: increase height and adjust position
    const heightChange = startSize.value.height - deltaY
    newHeight = Math.max(200, heightChange)
    // Only adjust position if we're not at minimum height
    if (heightChange >= 200) {
      newPositionY = startPosition.value.y + deltaY
    } else {
      // If at minimum height, calculate position based on minimum height
      newPositionY = startPosition.value.y + (startSize.value.height - 200)
    }
  }
  
  // Update current size for immediate visual feedback
  currentSize.value = { width: newWidth, height: newHeight }
  
  // Update the Vue Flow node directly for real-time feedback
  if (props.id) {
    updateNode(props.id, {
      position: { x: newPositionX, y: newPositionY },
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
  const resizeEvent: { width: number, height: number, position?: { x: number, y: number } } = {
    width: newWidth,
    height: newHeight
  }
  
  // Include position if it changed (top or left resize)
  if (newPositionX !== startPosition.value.x || newPositionY !== startPosition.value.y) {
    resizeEvent.position = { x: newPositionX, y: newPositionY }
  }
  
  emit('resize', resizeEvent)
}

function stopResize() {
  if (!isResizing.value) return
  
  isResizing.value = false
  resizeDirection.value = ''
  document.removeEventListener('mousemove', handleResize)
  document.removeEventListener('mouseup', stopResize)
  document.body.style.userSelect = ''
}

// Manual drag functions for border dragging in user mode
function startManualDrag(event: MouseEvent) {
  if (props.adminMode) return // Only for user mode
  
  event.preventDefault()
  event.stopPropagation()
  
  isDragging.value = true
  dragStartPos.value = { x: event.clientX, y: event.clientY }
  dragStartNodePos.value = { x: props.position?.x || 0, y: props.position?.y || 0 }
  
  document.addEventListener('mousemove', handleManualDrag)
  document.addEventListener('mouseup', stopManualDrag)
  document.body.style.userSelect = 'none'
}

function handleManualDrag(event: MouseEvent) {
  if (!isDragging.value) return
  
  const deltaX = event.clientX - dragStartPos.value.x
  const deltaY = event.clientY - dragStartPos.value.y
  
  const newPosition = {
    x: dragStartNodePos.value.x + deltaX,
    y: dragStartNodePos.value.y + deltaY
  }
  
  // Update the node position using VueFlow
  if (props.id) {
    updateNode(props.id, {
      position: newPosition
    })
  }
}

function stopManualDrag() {
  if (!isDragging.value) return
  
  isDragging.value = false
  document.removeEventListener('mousemove', handleManualDrag)
  document.removeEventListener('mouseup', stopManualDrag)
  document.body.style.userSelect = ''
}

onUnmounted(() => {
  document.removeEventListener('mousemove', handleResize)
  document.removeEventListener('mouseup', stopResize)
  document.removeEventListener('mousemove', handleManualDrag)
  document.removeEventListener('mouseup', stopManualDrag)
})
</script>

<style scoped>
.stream-nest {
  position: relative;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 20px;
  /* Ensure it fills the Vue Flow node container */
  width: 100%;
  height: 100%;
  box-sizing: border-box;
}

.stream-nest.user-mode {
  cursor: grab;
}

.stream-nest.user-mode:active {
  cursor: grabbing;
}

.stream-nest.admin-mode {
  /* In admin mode, make the entire nest draggable with Vue Flow */
  z-index: 1;
  /* Allow all pointer events for Vue Flow dragging */
  pointer-events: all;
  cursor: move;
  /* Add a subtle indication this is in admin mode */
  border-style: solid;
  background: rgba(59, 130, 246, 0.08);
}

.stream-nest.admin-mode .stream-label {
  /* Label is now just for display, the whole nest handles dragging */
  pointer-events: none;
  z-index: 2;
  /* Subtle styling since whole nest is draggable */
  background: rgba(59, 130, 246, 0.05);
  padding: 2px 6px;
  border-radius: 4px;
}

/* User mode drag areas - specific border strips only */
.drag-border-top {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 40px;
  cursor: move;
  z-index: 1;
}

.drag-border-right {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  width: 40px;
  cursor: move;
  z-index: 1;
}

.drag-border-bottom {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 40px;
  cursor: move;
  z-index: 1;
}

.drag-border-left {
  position: absolute;
  top: 0;
  left: 0;
  bottom: 0;
  width: 40px;
  cursor: move;
  z-index: 1;
}

/* Ensure drag borders don't interfere in admin mode */
.stream-nest.admin-mode .drag-border-top,
.stream-nest.admin-mode .drag-border-right,
.stream-nest.admin-mode .drag-border-bottom,
.stream-nest.admin-mode .drag-border-left {
  display: none;
}

.center-content {
  position: absolute;
  top: 8px;
  left: 8px;
  right: 8px;
  bottom: 8px;
  background: rgba(59, 130, 246, 0.05);
  border-radius: 8px;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding-top: 12px;
  /* Make completely transparent to all pointer events */
  pointer-events: none;
  z-index: 0;
  /* Prevent any selection or drag operations */
  user-select: none;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
}

.stream-label {
  font-size: 18px;
  font-weight: 600;
  color: #1e40af;
  text-align: center;
  user-select: none;
  pointer-events: none;
}

/* Resize controls */
.resize-controls {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
  z-index: 20;
}

.resize-handle {
  position: absolute;
  background: #3b82f6;
  border: 2px solid white;
  border-radius: 3px;
  pointer-events: all;
  z-index: 21;
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
