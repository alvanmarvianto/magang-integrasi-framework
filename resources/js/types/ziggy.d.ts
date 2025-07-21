interface RouteFunction {
    (name: string, params?: Record<string, any> | Array<any>, absolute?: boolean, config?: Record<string, any>): string;
}

declare module 'ziggy-js' {
    export const route: RouteFunction;
    export const Ziggy: Record<string, any>;
    export function ZiggyVue(vue: any): void;
} 