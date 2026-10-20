// Flow 3 (rate limiting): too many login requests per minute for ONE e-mail (and IP) get a German message; other e-mails are not affected. Runs last: it blocks logins for ~1 minute.
import { check, launch, loginFails } from '../lib.mjs'

export default async function run() {
  const { browser, page } = await launch()
  try {
    let msg = ''
    for (let i = 0; i < 12; i++) msg = await loginFails(page, { email: 'limit@example.org', password: 'Falsch123!x' }, { retryOnRateLimit: false })
    check('rate limit: German message after too many attempts', /Zu viele Anmeldeversuche/.test(msg), msg)
    const other = await loginFails(page, { email: 'andere@example.org', password: 'Falsch123!x' }, { retryOnRateLimit: false })
    check('another e-mail from the same IP is not blocked (club wifi)', /falsch/.test(other), other)
  } finally {
    await browser.close()
  }
}
