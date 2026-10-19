/**
 * Profile images live at `<storage base>/<profileImageName>`. The storage base is configured with
 * VITE_IMAGE_FOLDER_URL. Returns null when there is no image (e.g. anonymous users get
 * `profileImageName: null` from the API), so callers can show the placeholder avatar.
 */
export function profileImageUrl(imageName: string | null | undefined): string | null {
  if (!imageName) return null
  const base = ((import.meta.env.VITE_IMAGE_FOLDER_URL as string | undefined) ?? '').replace(/\/+$/, '')
  return `${base}/${imageName}`
}
