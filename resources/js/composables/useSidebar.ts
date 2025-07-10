import { ref, onMounted, onUnmounted } from 'vue';

export function useSidebar() {
  const visible = ref(false);
  const isMobile = ref(false);

  function checkScreenSize() {
    isMobile.value = window.innerWidth <= 768;
    if (!isMobile.value) {
      visible.value = false;
      const sidebar = document.getElementById('sidebar');
      sidebar?.classList.remove('visible');
    }
  }

  function toggleSidebar() {
    visible.value = !visible.value;
    const sidebar = document.getElementById('sidebar');
    sidebar?.classList.toggle('visible');
  }

  function closeSidebar() {
    visible.value = false;
    const sidebar = document.getElementById('sidebar');
    sidebar?.classList.remove('visible');
  }

  function handleClickOutside(event: Event) {
    const sidebar = document.getElementById('sidebar');
    const menuToggle = document.getElementById('menu-toggle');

    if (sidebar && menuToggle && !sidebar.contains(event.target as Node) && !menuToggle.contains(event.target as Node)) {
      visible.value = false;
      sidebar.classList.remove('visible');
    }
  }

  function handleEscapeKey(event: KeyboardEvent) {
    if (event.key === 'Escape') {
      visible.value = false;
      const sidebar = document.getElementById('sidebar');
      sidebar?.classList.remove('visible');
    }
  }

  onMounted(() => {
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleEscapeKey);
  });

  onUnmounted(() => {
    window.removeEventListener('resize', checkScreenSize);
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleEscapeKey);
  });

  return {
    visible,
    isMobile,
    toggleSidebar,
    closeSidebar,
  };
}
