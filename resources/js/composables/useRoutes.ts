import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

/**
 * Composable for handling routes with Ziggy + Inertia
 */
export function useRoutes() {
  /**
   * Navigate to a named route using Inertia
   * @param name - Route name
   * @param params - Route parameters
   * @param options - Inertia visit options
   */
  const visitRoute = (name: string, params?: any, options?: any) => {
    const url = route(name, params);
    router.visit(url, options);
  };

  /**
   * Get URL for a named route
   * @param name - Route name
   * @param params - Route parameters
   * @param absolute - Whether to return absolute URL
   */
  const getRoute = (name: string, params?: any, absolute?: boolean) => {
    return route(name, params, absolute);
  };

  /**
   * Check if current route matches the given name
   * @param name - Route name to check
   */
  const isCurrentRoute = (name: string) => {
    try {
      const currentUrl = window.location.pathname;
      const routeUrl = route(name);
      return currentUrl === routeUrl;
    } catch {
      return false;
    }
  };

  return {
    visitRoute,
    getRoute,
    isCurrentRoute,
    route, // Export raw route function for direct use
  };
}
