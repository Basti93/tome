import dayjs from 'dayjs'
import 'dayjs/locale/de'
import customParseFormat from 'dayjs/plugin/customParseFormat'
import localizedFormat from 'dayjs/plugin/localizedFormat'
import isSameOrAfter from 'dayjs/plugin/isSameOrAfter'
import isSameOrBefore from 'dayjs/plugin/isSameOrBefore'
import relativeTime from 'dayjs/plugin/relativeTime'
import advancedFormat from 'dayjs/plugin/advancedFormat'

dayjs.extend(customParseFormat)
dayjs.extend(localizedFormat)
dayjs.extend(isSameOrAfter)
dayjs.extend(isSameOrBefore)
dayjs.extend(relativeTime)
dayjs.extend(advancedFormat)
dayjs.locale('de')

export type { Dayjs } from 'dayjs'

const API_DATE = /^(\d{4})-(\d{2})-(\d{2})(?:[T ](\d{2}):(\d{2}))?/

/**
 * Parses date(-time) values coming from the API ("2024-03-05", "2024-03-05 18:30:00",
 * "2024-03-05T18:30:00+01:00", "2010-05-01T00:00:00.000000Z") as *local* time and ignores seconds
 * and UTC offsets, exactly like the former moment(value, 'YYYY-MM-DDTHH:mm'). Values that are
 * already Dayjs/Date objects are cloned; anything unparsable yields an invalid Dayjs.
 */
export function parseApiDate(value: unknown): dayjs.Dayjs {
  if (value === null || value === undefined || value === '') return dayjs(null)
  if (typeof value !== 'string') return dayjs(value as any)
  const m = API_DATE.exec(value.trim())
  if (!m) return dayjs(null)
  const [, y, mo, d, h, mi] = m
  const date = new Date(Number(y), Number(mo) - 1, Number(d), Number(h ?? 0), Number(mi ?? 0))
  return dayjs(date)
}

export default dayjs
