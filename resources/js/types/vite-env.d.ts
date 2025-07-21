/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_APP_NAME: string
  // more env variables...
}

interface ImportMeta {
  readonly env: ImportMetaEnv
  readonly glob: <T>(pattern: string) => Record<string, T>
} 