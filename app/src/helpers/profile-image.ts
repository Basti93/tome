/**
 * Profile images live at `<storage base>/<profileImageName>`. The storage base is configured with
 * VITE_IMAGE_FOLDER_URL
 * and defaults to `/api/storage` (nginx / Vite dev proxy serve storage/app/public there). Returns null when there is no image (e.g. anonymous users get
 * `profileImageName: null` from the API), so callers can show the placeholder avatar.
 */
const DEFAULT_IMAGE_BASE = '/api/storage'

export function profileImageUrl(imageName: string | null | undefined): string | null {
  if (!imageName) return null
  const configured = (import.meta.env.VITE_IMAGE_FOLDER_URL as string | undefined) || DEFAULT_IMAGE_BASE
  const base = configured.replace(/\/+$/, '')
  return `${base}/${imageName}`
}
