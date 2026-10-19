import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { defineComponent, nextTick, ref } from 'vue'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import WeekdaysComponent from './WeekdaysComponent.vue'

const vuetify = createVuetify({ components, directives })

describe('WeekdaysComponent', () => {
  it('lets the user check weekdays and reports them through change', async () => {
    const Parent = defineComponent({
      components: { WeekdaysComponent },
      setup() {
        const weekdays = ref<number[]>([])
        return { weekdays, set: (v: number[]) => (weekdays.value = v) }
      },
      template: '<WeekdaysComponent :weekdays="weekdays" v-on:change="set" />',
    })
    const w = mount(Parent, { global: { plugins: [vuetify] }, attachTo: document.body })
    await nextTick()
    const boxes = w.findAll('input[type=checkbox]')
    expect(boxes).toHaveLength(7)
    await boxes[0].setValue(true)
    await boxes[2].setValue(true)
    await nextTick()
    expect((w.vm as any).weekdays).toEqual([1, 3])
    expect((boxes[0].element as HTMLInputElement).checked).toBe(true)
    w.unmount()
  })
})
