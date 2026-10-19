import { describe, expect, it } from 'vitest'
import { existsSync, readFileSync } from 'node:fs'
import { join } from 'node:path'

// Icons referenced by index.html, the web app manifest and the push service worker must exist in public/.
const root = join(__dirname, '..')
const publicDir = join(root, 'public')

function paths(text: string, pattern: RegExp): string[] {
  return [...text.matchAll(pattern)].map((m) => m[1])
}

describe('referenced icons exist', () => {
  it('index.html', () => {
    const html = readFileSync(join(root, 'index.html'), 'utf8')
    const refs = paths(html, /<link[^>]+href="(\/[^"]+\.(?:png|json|ico|svg))"/g)
    expect(refs.length).toBeGreaterThan(0)
    // /manifest.json is generated at build time from manifest.template.json (see vite-manifest.ts)
    for (const ref of refs.filter((r) => r !== '/manifest.json')) expect(existsSync(join(publicDir, ref)), ref).toBe(true)
    expect(existsSync(join(root, 'manifest.template.json'))).toBe(true)
  })

  it('manifest.json', () => {
    const manifest = JSON.parse(readFileSync(join(root, 'manifest.template.json'), 'utf8'))
    expect(manifest.icons.length).toBeGreaterThan(0)
    for (const icon of manifest.icons) expect(existsSync(join(publicDir, icon.src)), icon.src).toBe(true)
  })

  it('push service worker', () => {
    const sw = readFileSync(join(publicDir, 'firebase-messaging-sw.js'), 'utf8')
    const refs = paths(sw, /['"](\/img\/[^'"]+\.png)['"]/g)
    expect(refs.length).toBeGreaterThan(0)
    for (const ref of refs) expect(existsSync(join(publicDir, ref)), ref).toBe(true)
  })
})
