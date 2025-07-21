declare function route(
    name: string,
    params?: Record<string, any> | Array<any>,
    absolute?: boolean,
    config?: Record<string, any>
): string;

declare module 'ziggy-js' {
    export { route };
    export const Ziggy: Record<string, any>;
    export function ZiggyVue(vue: any): void;
} 