import dayjs, { type Dayjs } from '@/helpers/dayjs'
import { birthdayHint } from '@/helpers/birthday'

export interface UpcomingTraining {
  id: number
  start: string
  end: string
  locationId?: number
  groupIds: number[]
  trainerIds: number[]
  participants: { userId: number; attend: number | boolean; cancelreason?: string | null }[]
}

/**
 * The training "Heute" shows: the next one the trainer is assigned to (the list is sorted by start), else the next one of the
 * groups of their branches, else null.
 */
export function pickNextTraining(
  own: UpcomingTraining[],
  ofBranchGroups: UpcomingTraining[],
): { training: UpcomingTraining; own: boolean } | null {
  if (own.length) return { training: own[0], own: true }
  if (ofBranchGroups.length) return { training: ofBranchGroups[0], own: false }
  return null
}

export interface ParticipationSummary {
  yes: number
  cancellations: { userId: number; reason: string | null }[]
}

/** "X zugesagt, Y abgesagt" and who cancelled with which reason. */
export function participationSummary(participants: UpcomingTraining['participants']): ParticipationSummary {
  let yes = 0
  const cancellations: ParticipationSummary['cancellations'] = []
  for (const p of participants ?? []) {
    if (Number(p.attend) === 1) yes++
    else if (p.attend !== null && p.attend !== undefined) cancellations.push({ userId: p.userId, reason: p.cancelreason?.trim() || null })
  }
  return { yes, cancellations }
}

/** Athletes of the given groups who have a birthday today (full names for the trainer). */
export function todaysBirthdays(
  users: { id: number; firstName: string; familyName: string; groupIds: number[]; birthdate?: string | null }[],
  groupIds: number[],
  today: Dayjs = dayjs(),
): string[] {
  return users
    .filter((u) => u.groupIds.some((g) => groupIds.includes(g)))
    .filter((u) => birthdayHint(u.birthdate, today, { days: 0, today })?.when === 'today')
    .map((u) => `${u.firstName} ${u.familyName}`.trim())
    .sort((a, b) => a.localeCompare(b, 'de'))
}
