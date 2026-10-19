import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import { createPinia, setActivePinia } from 'pinia'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

vi.mock('@/http/api', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}))

import CookieUserDialog from './CookieUserDialog.vue'
import { useMasterDataStore } from '@/store/masterData'
import { useCookieAuthStore } from '@/store/cookieAuth'


const vuetify = createVuetify({ components, directives })

function seed() {
  const store = useMasterDataStore()
  store.setBranches([
    { id: 1, name: 'Judo' },
    { id: 2, name: 'Karate' },
  ] as any)
  store.setGroups([
    { id: 10, name: 'Kids', branchId: 1 },
    { id: 11, name: 'Teens', branchId: 1 },
    { id: 20, name: 'Karate A', branchId: 2 },
  ] as any)
  store.simpleUsers = [
    { id: 100, firstName: 'A', familyName: 'One', groupIds: [10] },
    { id: 101, firstName: 'B', familyName: 'Two', groupIds: [11] },
    { id: 102, firstName: 'C', familyName: 'Three', groupIds: [20] },
  ] as any
}

function mountDialog(props: Record<string, unknown> = {}) {
  const wrapper = mount(CookieUserDialog, {
    props: { visible: true, ...props },
    global: { plugins: [vuetify] },
    attachTo: document.body,
  })
  return wrapper
}

// the component is an options-API component without a typed instance
const vm = (w: ReturnType<typeof mountDialog>) => w.vm as any

describe('CookieUserDialog', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    seed()
    document.body.innerHTML = ''
  })

  it('lists only the groups of the selected branch (Vuetify selects emit update:modelValue, not change)', async () => {
    const w = mountDialog()
    expect(vm(w).branchGroups).toEqual([])
    const selects = w.findAllComponents({ name: 'VSelect' })
    selects[0].vm.$emit('update:modelValue', 1)
    await nextTick()
    expect(vm(w).branchGroups.map((g: any) => g.id)).toEqual([10, 11])
    expect(vm(w).filteredUsers.map((u: any) => u.id)).toEqual([100, 101])
    w.unmount()
  })

  it('narrows the users by group and resets the group when the branch changes', async () => {
    const w = mountDialog()
    const selects = w.findAllComponents({ name: 'VSelect' })
    selects[0].vm.$emit('update:modelValue', 1)
    await nextTick()
    selects[1].vm.$emit('update:modelValue', 11)
    await nextTick()
    expect(vm(w).filteredUsers.map((u: any) => u.id)).toEqual([101])
    vm(w).userId = 101
    selects[0].vm.$emit('update:modelValue', 2)
    await nextTick()
    expect(vm(w).groupId).toBeNull()
    expect(vm(w).userId).toBeNull()
    expect(vm(w).filteredUsers.map((u: any) => u.id)).toEqual([102])
    selects[0].vm.$emit('update:modelValue', null)
    await nextTick()
    expect(vm(w).filteredUsers).toHaveLength(3)
    w.unmount()
  })

  it('preselects branch, group and user from the props when reopened', async () => {
    const w = mountDialog({ visible: false, propGroupId: 10, propUserId: 100 })
    await w.setProps({ visible: true })
    expect(vm(w).branchId).toBe(1)
    expect(vm(w).groupId).toBe(10)
    expect(vm(w).userId).toBe(100)
    expect(vm(w).branchGroups.map((g: any) => g.id)).toEqual([10, 11])
    w.unmount()
  })

  it('stores the chosen user and closes', async () => {
    const w = mountDialog({ propUserId: 102, propBranchId: 2 })
    vm(w).done()
    expect(w.emitted('close')).toBeTruthy()
    expect(useCookieAuthStore().cookieUser).not.toBeNull()
    w.unmount()
  })
})
