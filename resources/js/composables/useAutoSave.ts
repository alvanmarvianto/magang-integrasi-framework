import { ref, onUnmounted } from 'vue'

/**
 * Auto-save composable for managing debounced saving
 */
export function useAutoSave() {
  const autoSaveTimeoutId = ref<number | null>(null)
  const lastChangeTime = ref<number>(0)
  const autoSaveTimeout = ref<number | null>(null)

  function scheduleAutoSave(autoSaveCallback: () => void, delay: number = 5000) {
    // Clear existing timeout
    if (autoSaveTimeoutId.value) {
      clearTimeout(autoSaveTimeoutId.value)
    }
    
    // Update last change time
    lastChangeTime.value = Date.now()
    
    // Set auto-save status indicator
    autoSaveTimeout.value = 1 // Indicate auto-save is pending
    
    // Schedule auto-save after specified delay of inactivity
    autoSaveTimeoutId.value = setTimeout(() => {
      const timeSinceLastChange = Date.now() - lastChangeTime.value
      if (timeSinceLastChange >= delay) {
        autoSaveCallback()
      } else {
        // Reschedule if there was recent activity
        scheduleAutoSave(autoSaveCallback, delay)
      }
    }, delay) as unknown as number
  }

  function clearAutoSave() {
    if (autoSaveTimeoutId.value) {
      clearTimeout(autoSaveTimeoutId.value)
      autoSaveTimeoutId.value = null
    }
    autoSaveTimeout.value = null
  }

  // Cleanup on unmount
  onUnmounted(() => {
    clearAutoSave()
  })

  return {
    autoSaveTimeout,
    scheduleAutoSave,
    clearAutoSave,
  }
}
