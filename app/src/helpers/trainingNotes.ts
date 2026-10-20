import httpClient from '@/http/api'
import { parseApiDate, type Dayjs } from '@/helpers/dayjs'

export const NOTE_MAX_LENGTH = 1000

export interface GroupNote {
  trainingId: number
  start: Dayjs
  groupIds: number[]
  note: string
}

/** Notes trainers left after the last (up to 10) past trainings of the given groups, newest first. */
export async function fetchGroupNotes(groupIds: number[]): Promise<GroupNote[]> {
  if (!groupIds.length) return []
  const { data } = await httpClient.get(`/trainingprepare/notes?groupIds=${groupIds.join(',')}`)
  return mapGroupNotes(data?.data)
}

export function mapGroupNotes(rows: any[] | undefined): GroupNote[] {
  return (rows ?? []).map((r) => ({
    trainingId: r.trainingId,
    start: parseApiDate(r.start),
    groupIds: r.groupIds ?? [],
    note: String(r.note ?? ''),
  }))
}
