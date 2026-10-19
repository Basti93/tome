import { describe, expect, it } from 'vitest'
import { readdirSync, readFileSync, statSync } from 'node:fs'
import { join } from 'node:path'

// Vue 2 / Vuetify 2 leftovers fail silently in Vue 3 / Vuetify 4 (unknown attributes and
// events are ignored). This guards the template patterns that bit us before.
function vueFiles(dir: string): string[] {
  return readdirSync(dir).flatMap((name) => {
    const p = join(dir, name)
    return statSync(p).isDirectory() ? vueFiles(p) : p.endsWith('.vue') ? [p] : []
  })
}

const files = vueFiles(join(__dirname))
const openingTag = /<(v-[a-z-]+)((?:"[^"]*"|'[^']*'|[^>"'])*)>/g

function findings(): string[] {
  const out: string[] = []
  for (const file of files) {
    const src = readFileSync(file, 'utf8')
    const template = src.slice(0, src.indexOf('<script') === -1 ? undefined : src.indexOf('<script'))
    for (const m of template.matchAll(openingTag)) {
      const [, tag, attrs] = m
      const where = `${file.replace(__dirname, 'src')} <${tag}>`
      // Vuetify inputs/selects/pickers never emit change/input (they emit update:modelValue)
      if (/^v-(select|autocomplete|combobox|text-field|textarea|checkbox|switch|file-input|date-picker|time-picker|radio-group|slider)$/.test(tag) &&
          /(\s(@|v-on:)(change|input)[=.\s])/.test(attrs)) out.push(`${where}: @change/@input (use @update:model-value)`)
      if (/\sv-on="on"|\.sync\b/.test(attrs)) out.push(`${where}: Vue 2 v-on="on" / .sync`)
      if (tag === 'v-btn' && /\s(text|outlined|depressed|fab|small|x-small|large)(\s|$)/.test(attrs.replace(/"[^"]*"|'[^']*'/g, ''))) out.push(`${where}: Vuetify 2 boolean prop (use variant/size/icon)`)
      if (tag === 'v-icon' && /\s(left|right|small|x-small|large)(\s|$)/.test(attrs.replace(/"[^"]*"|'[^']*'/g, ''))) out.push(`${where}: Vuetify 2 boolean prop (use start/end/size)`)
    }
    if (/v-slot:activator="\{ on \}"|slot-scope=|\sslot="|<v-tab-item|<v-tabs-items|<v-list-item-content>|<v-expansion-panel-header|\$vuetify\.breakpoint|\$listeners|\$scopedSlots/.test(template.replace(/<!--[\s\S]*?-->/g, ''))) {
      out.push(`${file.replace(__dirname, 'src')}: removed Vue 2 / Vuetify 2 API`)
    }
  }
  return out
}

describe('legacy Vue 2 / Vuetify 2 template patterns', () => {
  it('are not used', () => {
    expect(findings()).toEqual([])
  })
})
