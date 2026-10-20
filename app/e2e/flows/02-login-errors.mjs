// Flow 3: wrong password, inactive user, lockout: each with a German message.
import { check, launch, loginFails, loginOk, isLoggedIn, provisionUser, logout } from '../lib.mjs'

export default async function run() {
  const { browser, page } = await launch()
  try {
    const tag = Date.now().toString(36)
    const user = await provisionUser(page, { tag, last: 'Login' })
    const wrong = await loginFails(page, { email: user.email, password: 'Falsch123!x' })
    check('wrong password: German message', wrong === 'E-Mail oder Passwort ist falsch.', wrong)
    const unknown = await loginFails(page, { email: `niemand-${tag}@example.org`, password: 'Falsch123!x' })
    check('unknown e-mail looks exactly like a wrong password', unknown === wrong, unknown)

    let last = ''
    for (let i = 0; i < 4; i++) last = await loginFails(page, { email: user.email, password: 'Falsch123!x' })
    check('5th failed attempt locks the account (German, names the duration)', /gesperrt/.test(last) && /15 Minuten/.test(last), last)
    const lockedCorrect = await loginFails(page, { email: user.email, password: user.password })
    check('the correct password is refused while locked', /gesperrt/.test(lockedCorrect), lockedCorrect)

    const inactive = await provisionUser(page, { tag: tag + 'i', last: 'Inaktiv', active: false })
    const msg = await loginFails(page, { email: inactive.email, password: inactive.password })
    check('inactive user: clear German message', /deaktiviert/.test(msg), msg)
    check('inactive user is not logged in', !(await isLoggedIn(page)))
  } finally {
    await browser.close()
  }
}
