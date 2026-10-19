import { readFileSync } from 'node:fs'
import { join } from 'node:path'
import type { Plugin } from 'vite'

const templatePath = join(import.meta.dirname, 'manifest.template.json')

export const DEFAULT_SHORT_NAME = 'T.O.M.E.'
export const DEFAULT_NAME = 'Training Organization Made Easy'

/** The web app manifest with the (build-time configurable) app names. */
export function buildManifest(env: Record<string, string | undefined>): Record<string, unknown> {
  const template = JSON.parse(readFileSync(templatePath, 'utf8'))
  return {
    ...template,
    short_name: env.VITE_APP_SHORT_NAME || DEFAULT_SHORT_NAME,
    name: env.VITE_APP_NAME || DEFAULT_NAME,
  }
}

/**
 * Serves/emits /manifest.json from manifest.template.json with VITE_APP_SHORT_NAME and VITE_APP_NAME
 * and sets the iOS home screen title to the short name.
 */
export function tomeManifest(): Plugin {
  let env: Record<string, string> = {}
  return {
    name: 'tome-manifest',
    configResolved(config) {
      env = config.env as Record<string, string>
    },
    configureServer(server) {
      server.middlewares.use('/manifest.json', (_req, res) => {
        res.setHeader('Content-Type', 'application/manifest+json')
        res.end(JSON.stringify(buildManifest(env), null, 2))
      })
    },
    generateBundle() {
      this.emitFile({ type: 'asset', fileName: 'manifest.json', source: JSON.stringify(buildManifest(env), null, 2) })
    },
    transformIndexHtml() {
      return [
        {
          tag: 'meta',
          attrs: { name: 'apple-mobile-web-app-title', content: (buildManifest(env).short_name as string) },
          injectTo: 'head',
        },
      ]
    },
  }
}
