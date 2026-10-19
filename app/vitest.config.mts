import { fileURLToPath } from 'node:url'
import { mergeConfig, defineConfig } from 'vitest/config'
import viteConfig from './vite.config'

export default mergeConfig(
  viteConfig,
  defineConfig({
    test: {
      environment: 'jsdom',
      globals: false,
      // ignore developer .env files: tests assume the defaults
      env: { VITE_API_URL: '', VITE_APP_API_URL: '', VITE_IMAGE_FOLDER_URL: '' },
      include: ['src/**/*.{test,spec}.{js,ts}'],
      root: fileURLToPath(new URL('./', import.meta.url)),
      server: { deps: { inline: ['vuetify'] } },
      setupFiles: ['./src/test/setup.ts'],
      clearMocks: true,
      restoreMocks: true,
    },
  })
)
