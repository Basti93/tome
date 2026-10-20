// Flows 1, 2, 8: admin creates a user, invites them, the user sets a password and logs in; duplicates; no e-mail.
import { ADMIN, closeDialog, inviteActions, NEW_PASSWORD, BASE, check, clearMails, createUserViaUi, inviteViaUi, isLoggedIn, launch, loginFails, loginOk, logout, resetLink, setPasswordViaLink, snackbarText, waitForMail } from '../lib.mjs'

export default async function run() {
  const { browser, page } = await launch()
  const tag = Date.now().toString(36)
  const email = `einladung-${tag}@example.org`
  try {
    await clearMails()
    await loginOk(page, ADMIN)

    check('admin creates a user with an e-mail address', /gespeichert/i.test(await createUserViaUi(page, { first: 'E2E', last: `Einladung${tag}`, email })))
    const dup = await createUserViaUi(page, { first: 'E2E', last: `Doppelt${tag}`, email })
    check('a second user with the same e-mail is refused with a German message', /bereits verwendet/.test(dup), dup)
    await closeDialog(page)

    check('user without e-mail can be created', /gespeichert/i.test(await createUserViaUi(page, { first: 'E2E', last: `OhneMail${tag}` })))
    check('no invite action for a user without e-mail', (await inviteActions(page, `OhneMail${tag}`)) === 0)

    const sent = await inviteViaUi(page, `Einladung${tag}`)
    check('invite is sent', /Einladung.*gesendet/.test(sent), sent)
    const mail = await waitForMail(email)
    check('invite mail arrives in German with a hash-route link', !!mail && /Passwort festlegen/.test(mail.subject) && !!resetLink(mail), mail?.subject)
    await logout(page)

    const unverifiedLogin = await loginFails(page, { email, password: 'Falsch123!x' })
    check('login before the invite was accepted fails with a German message', /falsch/i.test(unverifiedLogin), unverifiedLogin)

    const done = await setPasswordViaLink(page, resetLink(mail), NEW_PASSWORD)
    check('set-password page confirms', /erfolgreich/i.test(done), done)
    await loginOk(page, { email, password: NEW_PASSWORD })
    check('invited user can log in with the new password', await isLoggedIn(page))

    await logout(page)
    const reused = await setPasswordViaLink(page, resetLink(mail), NEW_PASSWORD)
    check('the invite link cannot be used twice (German error)', /ungültig oder abgelaufen/.test(reused), reused)
  } finally {
    await browser.close()
  }
  return { email }
}
