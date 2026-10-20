import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@/http/api', () => ({ default: { get: vi.fn() } }))

import httpClient from '@/http/api'
import { fetchGroupNotes, mapGroupNotes } from './trainingNotes'

const http = httpClient as unknown as { get: ReturnType<typeof vi.fn> }

describe('trainingNotes', () => {
  beforeEach(() => http.get.mockReset())

  it('maps the api rows and tolerates missing data', () => {
    const [n] = mapGroupNotes([{ trainingId: 3, start: '2026-10-01T18:00:00+02:00', groupIds: [1], note: 'Hallo' }])
    expect(n.trainingId).toBe(3)
    expect(n.note).toBe('Hallo')
    expect(n.start.format('DD.MM.')).toBe('01.10.')
    expect(mapGroupNotes(undefined)).toEqual([])
  })

  it('does not call the api without groups', async () => {
    expect(await fetchGroupNotes([])).toEqual([])
    expect(http.get).not.toHaveBeenCalled()
  })

  it('asks for the notes of all groups at once', async () => {
    http.get.mockResolvedValue({ data: { data: [] } })
    await fetchGroupNotes([1, 2])
    expect(http.get).toHaveBeenCalledWith('/trainingprepare/notes?groupIds=1,2')
  })
})
