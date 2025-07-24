import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

// Debounce utility
function debounce<T extends (...args: any[]) => any>(func: T, delay: number): T {
  let timeoutId: ReturnType<typeof setTimeout>;
  return ((...args: any[]) => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => func.apply(null, args), delay);
  }) as T;
}

interface UseAdminTableOptions {
  defaultSortBy?: string;
  searchDelay?: number;
}

export function useAdminTable(options: UseAdminTableOptions = {}) {
  const { defaultSortBy = 'name', searchDelay = 300 } = options;

  // Initialize from URL parameters
  const urlParams = new URLSearchParams(window.location.search);
  const searchQuery = ref(urlParams.get('search') || '');
  const sortBy = ref(urlParams.get('sort_by') || defaultSortBy);
  const sortDesc = ref(urlParams.get('sort_desc') === '1');

  function updateData(resetPage = false) {
    const params = new URLSearchParams();
    
    if (searchQuery.value) {
      params.set('search', searchQuery.value);
    }
    
    if (sortBy.value !== defaultSortBy) {
      params.set('sort_by', sortBy.value);
    }
    
    if (sortDesc.value) {
      params.set('sort_desc', '1');
    }

    // If resetPage is false, preserve current page
    if (!resetPage) {
      const currentParams = new URLSearchParams(window.location.search);
      const currentPage = currentParams.get('page');
      if (currentPage) {
        params.set('page', currentPage);
      }
    }

    router.get(
      window.location.pathname + (params.toString() ? '?' + params.toString() : ''),
      {},
      { preserveState: true, preserveScroll: true }
    );
  }

  function handleSearch() {
    updateData(true); // Reset to page 1 when searching
  }

  function navigateToPage(url: string) {
    router.get(url, {}, { preserveState: true, preserveScroll: true });
  }

  // Create debounced search function
  const debouncedSearch = debounce(handleSearch, searchDelay);

  // Watch for sort changes and trigger server request
  watch([sortBy, sortDesc], () => {
    updateData(false); // Don't reset page when sorting
  }, { deep: true });

  return {
    searchQuery,
    sortBy,
    sortDesc,
    handleSearch,
    debouncedSearch,
    navigateToPage,
    updateData
  };
}
