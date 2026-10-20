// Black-box helpers for the account-flow e2e scripts: only the UI and the mail inbox (Mailpit API) are used,
// so the scripts keep working when the backend auth changes (e.g. to Sanctum).
import { chromium } from 'playwright-core'

export const BASE = process.env.E2E_BASE_URL || 'http://localhost:5173'
export const MAIL_API = process.env.E2E_MAIL_API || 'http://localhost:8025/api/v1'
export const ADMIN = { email: process.env.E2E_ADMIN_EMAIL || 'admin@example.org', password: process.env.E2E_ADMIN_PASSWORD || 'Password1!' }
export const TRAINER = { email: process.env.E2E_TRAINER_EMAIL || 'trainer@example.org', password: process.env.E2E_TRAINER_PASSWORD || 'Password1!' }
export const NEW_PASSWORD = 'Neu-Passwort1!'

let failures = 0
export function check(name, ok, detail = '') {
  console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${detail ? '  ' + detail : ''}`)
  if (!ok) failures++
}
export function failed() { return failures }

export async function launch() {
  const executablePath = process.env.E2E_CHROMIUM || undefined
  const browser = await chromium.launch({ executablePath })
  const context = await browser.newContext({ viewport: { width: 1100, height: 900 }, locale: 'de-DE' })
  const page = await context.newPage()
  const consoleErrors = []
  page.on('pageerror', (e) => consoleErrors.push(e.message))
  return { browser, context, page, consoleErrors }
}

/** Text of the visible snackbar (waits up to `timeout`), ignoring a stale text that was already shown before the action. */
export async function snackbarText(page, timeout = 4000, ignore = '') {
  const end = Date.now() + timeout
  while (Date.now() < end) {
    const texts = await page.locator('.v-snackbar--active .v-snackbar__content').allInnerTexts().catch(() => [])
    const fresh = texts.map((t) => t.trim()).filter((t) => t && t !== ignore)
    if (fresh.length) return fresh[fresh.length - 1]
    await page.waitForTimeout(150)
  }
  return ''
}

/** Runs `action`, waits for the API answer matching `urlRe` and returns the (then newest) snackbar text. */
export async function actAndSnack(page, urlRe, action, timeout = 8000) {
  const answer = page.waitForResponse((r) => urlRe.test(r.url()) && r.request().method() !== 'GET', { timeout }).catch(() => null)
  await action()
  await answer
  await page.waitForTimeout(300)
  return snackbarText(page, 2500)
}

/** Snackbar text before an action, to be passed as `ignore` afterwards. */
export async function currentSnackbar(page) {
  const texts = await page.locator('.v-snackbar--active .v-snackbar__content').allInnerTexts().catch(() => [])
  return (texts[texts.length - 1] || '').trim()
}

export async function closeDialog(page) {
  await page.locator('.v-overlay--active .v-toolbar .v-btn').first().click().catch(() => {})
  await page.locator('.v-overlay--active .v-card').waitFor({ state: 'detached', timeout: 5000 }).catch(() => {})
}

export async function login(page, { email, password }, { navigate = true } = {}) {
  if (navigate) {
    await page.goto(`${BASE}/#/login`)
    // dev servers sometimes abort the very first navigation to a lazy page (dependency re-optimisation): retry once
    if (!(await page.locator('input[type=password]').first().waitFor({ timeout: 4000 }).then(() => true).catch(() => false))) {
      await page.goto(`${BASE}/#/login`)
    }
  }
  await page.locator('input[type=text], input[type=email]').first().fill(email)
  await page.locator('input[type=password]').first().fill(password)
  await page.locator('input[type=password]').first().press('Enter')
}

/** Logs in and waits until the app left the login page (use `login` when the login is expected to fail). */
export async function loginOk(page, creds, { navigate = true } = {}) {
  for (let attempt = 0; ; attempt++) {
    const msg = await actAndSnack(page, /auth\/login/, () => login(page, creds, { navigate }))
    if (!RATE_LIMITED.test(msg) || attempt >= 1) break
    await waitForRateLimit()
  }
  await page.waitForFunction(() => !location.hash.startsWith('#/login') && !!localStorage.getItem('user'), null, { timeout: 10000 })
  await page.waitForTimeout(300)
}

// The login route allows 10 requests per minute and IP; the flows log in a lot, so they wait it out once.
const RATE_LIMITED = /^Zu viele Anmeldeversuche\. Bitte warte/
async function waitForRateLimit() {
  console.log('      (login rate limit reached, waiting 62 s)')
  await new Promise((r) => setTimeout(r, 62000))
}

/** Attempts a login that is expected to fail and returns the message shown. */
export async function loginFails(page, creds, { retryOnRateLimit = true } = {}) {
  let msg = await actAndSnack(page, /auth\/login/, () => login(page, creds))
  if (retryOnRateLimit && RATE_LIMITED.test(msg)) {
    await waitForRateLimit()
    msg = await actAndSnack(page, /auth\/login/, () => login(page, creds))
  }
  return msg
}

export async function isLoggedIn(page) {
  return page.evaluate(() => !!localStorage.getItem('user'))
}

export async function logout(page) {
  await page.goto(`${BASE}/#/logout`)
  await page.waitForFunction(() => !localStorage.getItem('user') && !location.hash.startsWith('#/logout'), null, { timeout: 8000 }).catch(() => {})
}

// ---- mail inbox (Mailpit) --------------------------------------------------------------
export async function clearMails() {
  await fetch(`${MAIL_API}/messages`, { method: 'DELETE' })
}

/** Waits for a mail to `to` and returns { subject, text, links }. */
export async function waitForMail(to, { timeout = 10000, subject } = {}) {
  const end = Date.now() + timeout
  while (Date.now() < end) {
    const list = await (await fetch(`${MAIL_API}/search?query=${encodeURIComponent('to:' + to)}`)).json()
    const hit = (list.messages || []).find((m) => !subject || subject.test(m.Subject))
    if (hit) {
      const msg = await (await fetch(`${MAIL_API}/message/${hit.ID}`)).json()
      const text = `${msg.Text || ''}\n${msg.HTML || ''}`
      const links = [...text.matchAll(/https?:\/\/[^\s"'<>)]+/g)].map((m) => m[0].replace(/&amp;/g, '&'))
      return { subject: msg.Subject, text, links }
    }
    await new Promise((r) => setTimeout(r, 300))
  }
  return null
}

export async function mailCount(to) {
  const list = await (await fetch(`${MAIL_API}/search?query=${encodeURIComponent('to:' + to)}`)).json()
  return list.messages?.length || 0
}

export function resetLink(mail) {
  return mail?.links.find((l) => l.includes('reset-password')) || null
}

export function uniqueEmail(prefix) {
  return `${prefix}-${Date.now().toString(36)}@example.org`
}

// ---- flows built from UI steps ---------------------------------------------------------
/** Admin creates a user ("Vorläufigen Benutzer anlegen") and returns the snackbar text. */
export async function createUserViaUi(page, { first, last, email, active = true }) {
  await page.goto(`${BASE}/#/users`)
  await page.locator('.v-overlay--active .v-card').waitFor({ state: 'detached', timeout: 5000 }).catch(() => {})
  await page.getByTitle('Vorläufigen Benutzer anlegen').click({ timeout: 8000 })
  await page.getByLabel('Vorname').fill(first)
  await page.getByLabel('Nachname').fill(last)
  if (email) await page.getByLabel(/E-Mail/).fill(email)
  if (!active) await page.locator('.v-overlay--active .v-checkbox', { hasText: 'Aktiv' }).locator('.v-selection-control__input').click()
  return actAndSnack(page, /\/user/, () => page.getByRole('button', { name: /speichern/i }).click())
}

/** Number of invite actions in the user row containing `text`. */
export async function inviteActions(page, text) {
  await page.goto(`${BASE}/#/users`)
  await page.getByLabel('Suchen', { exact: true }).fill(text)
  const row = page.locator('tbody tr', { hasText: text }).first()
  await row.waitFor({ timeout: 8000 }).catch(async () => {
    await page.getByLabel('Suchen leeren').click().catch(() => {})
    await page.getByLabel('Suchen', { exact: true }).fill(text)
    await row.waitFor({ timeout: 10000 })
  })
  return row.getByTitle('Einladung senden').count()
}

/** Sends the invitation of the user row containing `text` (Benutzerverwaltung) and returns the snackbar text. */
export async function inviteViaUi(page, text) {
  await page.goto(`${BASE}/#/users`)
  const icon = page.locator('tbody tr', { hasText: text }).first().getByTitle('Einladung senden')
  for (let attempt = 0; attempt < 3; attempt++) {
    await page.getByLabel('Suchen', { exact: true }).fill('')
    await page.getByLabel('Suchen', { exact: true }).fill(text)
    if (await icon.waitFor({ state: 'attached', timeout: 6000 }).then(() => true).catch(() => false)) break
  }
  return actAndSnack(page, /invite/, () => icon.dispatchEvent('click'))
}

/** Opens the link of a mail (invite or reset) in a clean page and sets the password. */
export async function setPasswordViaLink(page, link, password) {
  await page.goto(link)
  await page.locator('input[type=password]').nth(1).waitFor({ timeout: 8000 })
  await page.locator('input[type=password]').nth(0).fill(password)
  await page.locator('input[type=password]').nth(1).fill(password)
  return actAndSnack(page, /auth\/reset/, () => page.getByRole('button', { name: /passwort speichern/i }).click())
}

export async function currentHash(page) {
  return page.evaluate(() => location.hash)
}

/** Admin creates a user, invites them and the user sets the password (UI only). Leaves the page logged out. */
export async function provisionUser(page, { tag, password = NEW_PASSWORD, active = true, last = 'Flow' }) {
  const email = `${last.toLowerCase()}-${tag}@example.org`
  const name = `${last}${tag}`
  await clearMailsFor(email)
  await loginOk(page, ADMIN)
  await createUserViaUi(page, { first: 'E2E', last: name, email, active })
  await inviteViaUi(page, name)
  const mail = await waitForMail(email)
  await logout(page)
  await setPasswordViaLink(page, resetLink(mail), password)
  return { email, password, name }
}

async function clearMailsFor() {}
