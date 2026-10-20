// Runs all account-flow e2e scripts. Usage: node e2e/run.mjs [filter]   (see README.md)
import { readdirSync } from 'node:fs'
import { failed } from './lib.mjs'

const filter = process.argv[2] || ''
for (const file of readdirSync(new URL('./flows/', import.meta.url)).filter((f) => f.endsWith('.mjs') && f.includes(filter)).sort()) {
  console.log(`\n== ${file}`)
  const mod = await import(`./flows/${file}`)
  try { await mod.default() } catch (e) { console.log('FAIL  crashed:', e.message.split('\n').slice(0, 8).join(' | ')); process.exitCode = 1 }
}
if (failed()) process.exitCode = 1
console.log(failed() ? `\n${failed()} check(s) failed` : '\nall checks passed')
