import { computed } from 'vue';
import { useRoutes } from './useRoutes';

export interface NavigationLink {
  icon: string;
  text: string;
  onClick: () => void;
  disabled?: boolean;
  visible?: boolean;
}

export interface NavigationOptions {
  appId?: number;
  streamName?: string;
  showHome?: boolean;
  showIntegration?: boolean;
  showTechnology?: boolean;
  showContract?: boolean;
  showStream?: boolean;
  showModule?: boolean;
  customLinks?: NavigationLink[];
}

/**
 * Composable for standardized navigation links across pages
 */
export function useNavigation(options: NavigationOptions = {}) {
  const { visitRoute } = useRoutes();

  const {
    appId,
    streamName,
    showHome = true,
    showTechnology = false,
    showContract = false,
    showStream = false,
    showModule = false,
    customLinks = []
  } = options;

  /**
   * Home navigation link
   */
  const homeLink = computed<NavigationLink>(() => ({
    icon: 'fa-solid fa-home',
    text: 'Halaman Utama',
    onClick: () => visitRoute('index'),
    visible: showHome
  }));

  /**
   * Technology navigation link
   */
  const technologyLink = computed<NavigationLink>(() => ({
    icon: 'fa-solid fa-microchip',
    text: 'Halaman Teknologi',
    onClick: () => {
      if (appId) {
        visitRoute('technology.app', { app_id: appId });
      } else {
        visitRoute('technology.index');
      }
    },
    visible: showTechnology
  }));

  /**
   * Contract navigation link
   */
  const contractLink = computed<NavigationLink>(() => ({
    icon: 'fa-solid fa-file-contract',
    text: 'Halaman Kontrak',
    onClick: () => {
      if (appId) {
        visitRoute('contract.app', { app_id: appId });
      } else {
        visitRoute('contract.index');
      }
    },
    visible: showContract
  }));

  /**
   * Stream navigation link
   */
  const streamLink = computed<NavigationLink>(() => ({
    icon: 'fa-solid fa-bezier-curve',
    text: 'Halaman Stream',
    onClick: () => {
      if (streamName) {
        visitRoute('integrations.stream', { stream: streamName });
      } else {
        visitRoute('index');
      }
    },
    visible: showStream,
    disabled: !streamName
  }));

  /**
   * Function/Module navigation link
   */
  const functionLink = computed<NavigationLink>(() => ({
    icon: 'fa-solid fa-sitemap',
    text: 'Halaman Modul',
    onClick: () => {
      if (appId) {
        visitRoute('integration.module', { app_id: appId });
      } else {
        visitRoute('index');
      }
    },
    visible: showModule,
    disabled: !appId
  }));

  /**
   * All available navigation links
   */
  const allLinks = computed<NavigationLink[]>(() => {
    const baseLinks = [
      homeLink.value,
      technologyLink.value,
      contractLink.value,
      streamLink.value,
      functionLink.value
    ];

    // Filter links by visibility and add custom links
    const visibleLinks = baseLinks.filter(link => link.visible !== false);
    
    return [...visibleLinks, ...customLinks];
  });

  /**
   * Navigation links filtered by enabled state
   */
  const enabledLinks = computed<NavigationLink[]>(() => 
    allLinks.value.filter(link => !link.disabled)
  );

  /**
   * Create a standard app-context navigation set (common for all app pages)
   */
  const createAppNavigation = (appId: number, isModule: boolean = true): NavigationLink[] => {
    const baseNavigation = [
      {
        icon: 'fa-solid fa-home',
        text: 'Halaman Utama',
        onClick: () => visitRoute('index')
      }
    ];

    // Only add "Halaman Aplikasi" if the app is a module
    if (isModule) {
      baseNavigation.push({
        icon: 'fa-solid fa-sitemap',
        text: 'Halaman Aplikasi',
        onClick: () => visitRoute('integration.module', { app_id: appId })
      });
    }

    baseNavigation.push(
      {
        icon: 'fa-solid fa-microchip',
        text: 'Halaman Teknologi',
        onClick: () => visitRoute('technology.app', { app_id: appId })
      },
      {
        icon: 'fa-solid fa-file-contract',
        text: 'Halaman Kontrak',
        onClick: () => visitRoute('contract.app', { app_id: appId })
      }
    );

    return baseNavigation;
  };


  /**
   * Create a standard stream navigation set
   */
  const createStreamNavigation = (): NavigationLink[] => {
    return [
      {
        icon: 'fa-solid fa-home',
        text: 'Halaman Utama',
        onClick: () => visitRoute('index')
      }
    ];
  };

  /**
   * Create technology-specific navigation
   */
  const createTechnologyNavigation = (appId: number, isModule: boolean = true): NavigationLink[] => {
    const baseNavigation = createAppNavigation(appId, isModule);
    return [
      ...baseNavigation,
      {
        icon: 'fa-solid fa-microchip',
        text: 'Semua Teknologi',
        onClick: () => visitRoute('technology.index')
      }
    ];
  };

  /**
   * Create contract-specific navigation
   */
  const createContractNavigation = (app?: any, contract?: any): NavigationLink[] => {
    // Determine the app to use for navigation
    let targetApp = app;
    if (!targetApp && contract?.apps?.length === 1) {
      targetApp = contract.apps[0];
    }

    if (targetApp) {
      const baseNavigation = createAppNavigation(targetApp.app_id, targetApp.is_module ?? true);
      return [
        ...baseNavigation,
        {
          icon: 'fa-solid fa-file-contract',
          text: 'Semua Kontrak',
          onClick: () => visitRoute('contract.index')
        }
      ];
    } else {
      // Fallback for multi-app contracts or no app context
      return [
        {
          icon: 'fa-solid fa-home',
          text: 'Halaman Utama',
          onClick: () => visitRoute('index')
        },
        {
          icon: 'fa-solid fa-microchip',
          text: 'Halaman Teknologi',
          onClick: () => visitRoute('technology.index')
        },
        {
          icon: 'fa-solid fa-file-contract',
          text: 'Semua Kontrak',
          onClick: () => visitRoute('contract.index')
        }
      ];
    }
  };

  /**
   * Create function/module navigation
   */
  const createFunctionNavigation = (appId: number, streamName?: string, isModule: boolean = true): NavigationLink[] => {
    const baseNavigation = createAppNavigation(appId, isModule);
    return [
      ...baseNavigation,
      ...(streamName ? [{
        icon: 'fa-solid fa-bezier-curve',
        text: 'Halaman Stream',
        onClick: () => visitRoute('integrations.stream', { stream: streamName })
      }] : [])
    ];
  };

  return {
    // Individual links
    homeLink,
    technologyLink,
    contractLink,
    streamLink,
    functionLink,
    
    // Combined links
    allLinks,
    enabledLinks,
    
    // Factory functions for common patterns
    createAppNavigation,
    createStreamNavigation,
    createTechnologyNavigation,
    createFunctionNavigation,
    createContractNavigation
  };
}
