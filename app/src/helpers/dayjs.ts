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
export default dayjs
