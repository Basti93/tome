/**
 * Extracts the file name from a Content-Disposition header
 * (`attachment; filename="abrechnung.xlsx"` or RFC 5987 `filename*=UTF-8''abrechnung%20neu.xlsx`).
 */
export function filenameFromContentDisposition(header: string | null | undefined, fallback: string): string {
  if (!header) return fallback

  const encoded = /filename\*\s*=\s*(?:[\w-]+)?'[^']*'([^;]+)/i.exec(header)
  if (encoded) {
    try {
      const name = decodeURIComponent(encoded[1].trim().replace(/^"|"$/g, ''))
      if (name) return name
    } catch {
      // fall through to the plain filename
    }
  }

  const plain = /filename\s*=\s*(?:"([^"]*)"|([^;]+))/i.exec(header)
  const name = (plain?.[1] ?? plain?.[2] ?? '').trim()
  return name || fallback
}

/** Triggers a browser download of the given blob. */
export function saveBlob(blob: Blob, filename: string): void {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.style.display = 'none'
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  // give the browser a moment to start the download before releasing the object URL
  setTimeout(() => URL.revokeObjectURL(url), 1000)
}
