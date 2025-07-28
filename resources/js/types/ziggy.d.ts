import { route as routeFunction } from 'ziggy-js';

export interface Route {
  uri: string;
  methods: string[];
  parameters?: string[];
  bindings?: Record<string, string>;
  wheres?: Record<string, string>;
}

export interface Router {
  url: string;
  port: number | null;
  defaults: Record<string, any>;
  routes: Record<string, Route>;
}

declare global {
  interface Window {
    route: typeof routeFunction;
    Ziggy: Router;
  }
  
  const route: typeof routeFunction;
}

// Add route names for type safety
declare module 'ziggy-js' {
  interface RouteList {
    'index': never;
    'appIntegration': { app_id: string | number };
    'integrations.stream': { stream: string };
    'technology.index': never;
    'technology.app_type': { app_type: string };
    'technology.stratification': { stratification: string };
    'technology.vendor': { vendor_name: string };
    'technology.os': { os_name: string };
    'technology.database': { database_name: string };
    'technology.language': { language_name: string };
    'technology.third_party': { third_party_name: string };
    'technology.middleware': { middleware_name: string };
    'technology.framework': { framework_name: string };
    'technology.platform': { platform_name: string };
    'technology.app': { app_id: string | number };
    'admin.index': never;
    'admin.integrations.index': never;
    'admin.integrations.create': never;
    'admin.integrations.edit': { id: string | number };
    'admin.apps.index': never;
    'admin.apps.create': never;
    'admin.technology.index': never;
    'admin.diagrams.show': { streamName: string };
  }
}

export {};
