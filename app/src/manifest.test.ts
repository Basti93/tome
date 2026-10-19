import { afterEach, describe, expect, it } from 'vitest'
import { mkdtempSync, readFileSync, rmSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { build } from 'vite'
import { buildManifest } from '../vite-manifest'

describe('web app manifest', () => {
  it('defaults to T.O.M.E. and takes the names from the env', () => {
    expect(buildManifest({}).short_name).toBe('T.O.M.E.')
    expect(buildManifest({ VITE_APP_SHORT_NAME: 'Test-App' })).toMatchObject({ short_name: 'Test-App', name: 'Training Organization Made Easy' })
    expect(buildManifest({ VITE_APP_SHORT_NAME: 'Test-App', VITE_APP_NAME: 'Test Name' }).name).toBe('Test Name')
  })

  describe('build output', () => {
    const original = process.env.VITE_APP_SHORT_NAME
    let outDir = ''
    afterEach(() => {
      if (original === undefined) delete process.env.VITE_APP_SHORT_NAME
      else process.env.VITE_APP_SHORT_NAME = original
      if (outDir) rmSync(outDir, { recursive: true, force: true })
    })

    it('emits manifest.json and the iOS title with VITE_APP_SHORT_NAME', async () => {
      process.env.VITE_APP_SHORT_NAME = 'Test-App'
      outDir = mkdtempSync(join(tmpdir(), 'tome-build-'))
      await build({ root: join(__dirname, '..'), logLevel: 'silent', build: { outDir, emptyOutDir: true } })

      const manifest = JSON.parse(readFileSync(join(outDir, 'manifest.json'), 'utf8'))
      expect(manifest.short_name).toBe('Test-App')
      expect(manifest.icons.length).toBeGreaterThan(0)
      expect(readFileSync(join(outDir, 'index.html'), 'utf8')).toContain('<meta name="apple-mobile-web-app-title" content="Test-App">')
    }, 60_000)
  })
})
