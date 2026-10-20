# Account-flow e2e

Black-box scripts (Playwright, real browser, cookies) for the account workflows: invite and first login, login errors,
forgot/change password, session expiry and `?redirect=`. They only use the UI and the mail inbox (Mailpit API), so they
stay valid when the backend auth changes.

    npx playwright-core install chromium        # once
    npm run dev                                 # or any running build, plus a backend on a THROWAWAY database
    npm run e2e                                 # all flows; `npm run e2e -- 03` runs one

Environment: `E2E_BASE_URL` (default `http://localhost:5173`), `E2E_MAIL_API` (Mailpit, default `http://localhost:8025/api/v1`),
`E2E_ADMIN_EMAIL`/`E2E_ADMIN_PASSWORD` (an admin of the seeded database), `E2E_CHROMIUM` (browser binary). The flows create users
(`example.org` addresses) and clear the mailbox: never run them against real data. Login is limited to 10 requests per minute, the
flows wait it out when needed (a full run takes a few minutes).
