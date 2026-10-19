import { afterEach, describe, expect, it, vi } from 'vitest'
import { profileImageUrl } from '@/helpers/profile-image'

describe('profileImageUrl', () => {
  afterEach(() => vi.unstubAllEnvs())

  it('returns null without an image name (anonymous API responses)', () => {
    expect(profileImageUrl(null)).toBeNull()
    expect(profileImageUrl(undefined)).toBeNull()
    expect(profileImageUrl('')).toBeNull()
  })

  it('joins the configured storage base and the image name', () => {
    vi.stubEnv('VITE_IMAGE_FOLDER_URL', 'https://cdn.example.test/img/')
    expect(profileImageUrl('a.jpg')).toBe('https://cdn.example.test/img/a.jpg')
  })

  it('defaults to /api/storage when unset or empty', () => {
    vi.stubEnv('VITE_IMAGE_FOLDER_URL', '')
    expect(profileImageUrl('a.jpg')).toBe('/api/storage/a.jpg')
  })
})
