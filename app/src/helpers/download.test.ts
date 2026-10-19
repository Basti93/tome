import { describe, expect, it } from 'vitest'
import { filenameFromContentDisposition } from '@/helpers/download'

describe('filenameFromContentDisposition', () => {
  it('reads a quoted filename', () => {
    expect(filenameFromContentDisposition('attachment; filename="abrechnung_2024.xlsx"', 'abrechnung.xlsx')).toBe('abrechnung_2024.xlsx')
  })

  it('reads an unquoted filename', () => {
    expect(filenameFromContentDisposition('attachment; filename=export.xlsx', 'abrechnung.xlsx')).toBe('export.xlsx')
  })

  it('prefers the RFC 5987 filename*', () => {
    expect(
      filenameFromContentDisposition("attachment; filename=\"fallback.xlsx\"; filename*=UTF-8''Abrechnung%20M%C3%A4rz.xlsx", 'abrechnung.xlsx'),
    ).toBe('Abrechnung März.xlsx')
  })

  it('falls back when the header is missing or has no filename', () => {
    expect(filenameFromContentDisposition(null, 'abrechnung.xlsx')).toBe('abrechnung.xlsx')
    expect(filenameFromContentDisposition('attachment', 'abrechnung.xlsx')).toBe('abrechnung.xlsx')
  })
})
