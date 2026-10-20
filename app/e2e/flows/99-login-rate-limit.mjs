// Flow 3 (rate limiting): too many login requests per minute get a German message. Runs last: it blocks logins for ~1 minute.
import { check, launch, loginFails } from '../lib.mjs'

export default async function run() {
  const { browser, page } = await launch()
  try {
    let msg = ''
    for (let i = 0; i < 12; i++) msg = await loginFails(page, { email: `limit-${i}@example.org`, password: 'Falsch123!x' }, { retryOnRateLimit: false })
    check('rate limit: German message after too many attempts', /Zu viele Anmeldeversuche/.test(msg), msg)
  } finally {
    await browser.close()
  }
}
