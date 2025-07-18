import { ref } from 'vue'

/**
 * Status message composable for displaying temporary notifications
 */
export function useStatusMessage() {
  const statusMessage = ref('')
  const statusType = ref<'success' | 'error' | 'info'>('info')

  function showStatus(message: string, type: 'success' | 'error' | 'info' = 'info', duration: number = 3000) {
    statusMessage.value = message
    statusType.value = type
    
    setTimeout(() => {
      statusMessage.value = ''
    }, duration)
  }

  function clearStatus() {
    statusMessage.value = ''
  }

  return {
    statusMessage,
    statusType,
    showStatus,
    clearStatus,
  }
}
