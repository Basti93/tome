// Flows 4, 5: "Passwort vergessen", unknown e-mail, used link; changing the password in the profile.
import { BASE, actAndSnack, check, clearMails, launch, loginFails, loginOk, logout, mailCount, provisionUser, resetLink, setPasswordViaLink, snackbarText, waitForMail } from '../lib.mjs'

const REQUEST_NEW = 'Passwort-Neu-1!x'

async function requestReset(page, email) {
  await page.goto(`${BASE}/#/login`)
  await page.getByText('Passwort vergessen?').click()
  await page.getByRole('button', { name: /link senden/i }).waitFor()
  await page.locator('input[type=text], input[type=email]').first().fill(email)
  await page.keyboard.press('Tab')
  const send = () => actAndSnack(page, /auth\/recovery/, () => page.getByRole('button', { name: /link senden/i }).click())
  let msg = await send()
  if (/Zu viele Anfragen/.test(msg)) { // recovery is limited to 3 per minute
    console.log('      (recovery rate limit reached, waiting 62 s)')
    await new Promise((r) => setTimeout(r, 62000))
    await page.getByRole('button', { name: /neuen link anfordern/i }).click().catch(() => {})
    msg = await send()
  }
  return msg
}

export default async function run() {
  const { browser, page } = await launch()
  try {
    const tag = Date.now().toString(36)
    const user = await provisionUser(page, { tag, last: 'Vergessen' })

    const known = await requestReset(page, user.email)
    const mail = await waitForMail(user.email, { subject: /zurücksetzen/i })
    check('"Passwort vergessen" sends a reset mail', !!mail && !!resetLink(mail), known)

    const unknownEmail = `niemand-${tag}@example.org`
    const unknown = await requestReset(page, unknownEmail)
    check('unknown address gets the same answer (no account enumeration)', unknown === known, unknown)
    check('... and no mail is sent', (await mailCount(unknownEmail)) === 0)

    await page.goto(resetLink(mail))
    await page.locator('input[type=password]').nth(1).waitFor()
    await page.locator('input[type=password]').nth(0).fill('schwach')
    await page.locator('input[type=password]').nth(1).fill('schwach')
    check('a weak password cannot be saved (button disabled, policy hint shown)', await page.getByRole('button', { name: /passwort speichern/i }).isDisabled())

    const done = await setPasswordViaLink(page, resetLink(mail), REQUEST_NEW)
    check('new password is set', /erfolgreich/i.test(done), done)
    const old = await loginFails(page, { email: user.email, password: user.password })
    check('the old password no longer works', /falsch/.test(old), old)
    await loginOk(page, { email: user.email, password: REQUEST_NEW })
    check('the new password works', true)

    // flow 5: change the password in the profile
    await page.goto(`${BASE}/#/profile`)
    await page.getByRole('button', { name: /passwort ändern/i }).click()
    const inputs = page.locator('.v-overlay--active input[type=password]')
    await inputs.nth(2).waitFor()
    const save = () => actAndSnack(page, /changepassword/, () => page.locator('.v-overlay--active').getByRole('button', { name: /speichern/i }).first().click())
    await inputs.nth(0).fill('Falsch123!x')
    await inputs.nth(1).fill('ProfilNeu1!x')
    await inputs.nth(2).fill('ProfilNeu1!x')
    const wrongCurrent = await save()
    check('password change with a wrong current password is refused (German)', /aktuelle Passwort ist falsch/.test(wrongCurrent), wrongCurrent)
    await inputs.nth(0).fill(REQUEST_NEW)
    const changed = await save()
    check('password change in the profile succeeds', /geändert/.test(changed), changed)
    await logout(page)
    const oldAgain = await loginFails(page, { email: user.email, password: REQUEST_NEW })
    check('after the change the previous password is refused', /falsch/.test(oldAgain), oldAgain)
    await loginOk(page, { email: user.email, password: 'ProfilNeu1!x' })
    check('and the changed password works', true)
  } finally {
    await browser.close()
  }
}
