// Flows 6, 7: protected page -> login -> back (?redirect=), expired session, logout, cookie user vs. login.
import { ADMIN, BASE, check, currentHash, isLoggedIn, launch, loginOk, logout } from '../lib.mjs'

export default async function run() {
  const { browser, context, page } = await launch()
  try {
    // anonymous visitor opens a protected page (e.g. a push deep link)
    await page.goto(`${BASE}/#/trainings`)
    await page.waitForFunction(() => location.hash.startsWith('#/login'), null, { timeout: 8000 })
    const hash = await currentHash(page)
    check('protected page sends anonymous visitors to the login with ?redirect=', /redirect=(%2F|\/)trainings/.test(hash), hash)
    await loginOk(page, ADMIN, { navigate: false })
    check('after the login the user lands on the requested page', (await currentHash(page)).startsWith('#/trainings'), await currentHash(page))

    // session expired: the cookie is gone, the stored user is still there
    await context.clearCookies()
    await page.goto(`${BASE}/#/users`)
    await page.reload()
    await page.waitForFunction(() => location.hash.startsWith('#/login'), null, { timeout: 10000 }).catch(() => {})
    const expired = await currentHash(page)
    check('expired session: sent to the login with ?redirect= back to the page', /^#\/login/.test(expired) && /redirect=(%2F|\/)users/.test(expired), expired)
    check('... and the stale user is cleared', !(await isLoggedIn(page)))

    // cookie user ("Wer bist du?") then login on the same device
    await context.addCookies([{ name: 'cookieUser', value: encodeURIComponent(JSON.stringify({ id: 4, firstName: 'Kind', familyName: 'K.' })), url: BASE }])
    await page.goto(`${BASE}/#/`)
    await loginOk(page, ADMIN)
    const cookies = await context.cookies()
    check('logging in replaces the cookie user (cookie erased)', !cookies.some((c) => c.name === 'cookieUser'))
    await logout(page)
    check('logout clears the session', !(await isLoggedIn(page)) && !(await context.cookies()).some((c) => c.name === 'jwt_token'))
  } finally {
    await browser.close()
  }
}
