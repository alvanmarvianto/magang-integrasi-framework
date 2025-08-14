import { ref, onMounted, onUnmounted, watch } from 'vue';

export function useSidebar() {
  const SIDEBAR_CACHE_KEY = 'sidebar-state';
  
  // Initialize with cached state or default to false
  const getCachedSidebarState = (): boolean => {
    try {
      const cached = localStorage.getItem(SIDEBAR_CACHE_KEY);
      return cached ? JSON.parse(cached) : false;
    } catch (error) {
      console.warn('Failed to load sidebar state from cache:', error);
      return false;
    }
  };

  const visible = ref(getCachedSidebarState());
  const isMobile = ref(false);

  // Cache sidebar state whenever it changes
  const cacheSidebarState = (state: boolean) => {
    try {
      localStorage.setItem(SIDEBAR_CACHE_KEY, JSON.stringify(state));
    } catch (error) {
      console.warn('Failed to cache sidebar state:', error);
    }
  };

  function checkScreenSize() {
    const wasMobile = isMobile.value;
    isMobile.value = window.innerWidth <= 768;
    
    // Auto-close sidebar when switching to mobile view
    if (!wasMobile && isMobile.value && visible.value) {
      closeSidebar();
    }
  }

  function toggleSidebar() {
    visible.value = !visible.value;
    cacheSidebarState(visible.value);
    const sidebar = document.getElementById('sidebar');
    sidebar?.classList.toggle('visible');
  }

  function closeSidebar() {
    visible.value = false;
    cacheSidebarState(false);
    const sidebar = document.getElementById('sidebar');
    sidebar?.classList.remove('visible');
  }

  function handleEscapeKey(event: KeyboardEvent) {
    if (event.key === 'Escape') {
      visible.value = false;
      cacheSidebarState(false);
      const sidebar = document.getElementById('sidebar');
      sidebar?.classList.remove('visible');
    }
  }

  // Apply cached state to DOM on mount
  function applyCachedState() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) {
      if (visible.value) {
        sidebar.classList.add('visible');
      } else {
        sidebar.classList.remove('visible');
      }
    }
  }

  onMounted(() => {
    checkScreenSize();
    applyCachedState(); // Apply cached sidebar state to DOM
    window.addEventListener('resize', checkScreenSize);
    document.addEventListener('keydown', handleEscapeKey);
  });

  // Watch for visibility changes and auto-close on mobile
  watch([visible, isMobile], ([isVisible, mobile]) => {
    if (mobile && isVisible) {
      // On mobile, close sidebar when navigating (this can be enhanced based on route changes)
      const sidebar = document.getElementById('sidebar');
      if (sidebar) {
        if (isVisible) {
          sidebar.classList.add('visible');
        } else {
          sidebar.classList.remove('visible');
        }
      }
    }
  });

  onUnmounted(() => {
    window.removeEventListener('resize', checkScreenSize);
    document.removeEventListener('keydown', handleEscapeKey);
  });

  // Clear sidebar cache (useful for debugging or reset functionality)
  function clearSidebarCache() {
    try {
      localStorage.removeItem(SIDEBAR_CACHE_KEY);
    } catch (error) {
      console.warn('Failed to clear sidebar cache:', error);
    }
  }

  return {
    visible,
    isMobile,
    toggleSidebar,
    closeSidebar,
    clearSidebarCache,
  };
}
