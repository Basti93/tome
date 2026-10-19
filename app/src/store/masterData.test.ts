import { beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import Branch from '@/models/Branch'
import Group from '@/models/Group'
import SimpleUser from '@/models/SimpleUser'
import TrainingSeries from '@/models/TrainingSeries'

// The store receives the http client as an argument; `@/http/api` is mocked so that importing it
// never reaches fetch (or the auth store).
vi.mock('@/http/api', () => ({
  default: { get: vi.fn(), post: vi.fn(), put: vi.fn(), patch: vi.fn(), delete: vi.fn() },
}))

import httpClient from '@/http/api'
import { useMasterDataStore } from '@/store/masterData'

const branchJudo = new Branch(1, 'Judo', 'JUD', '#111111')
const branchKarate = new Branch(2, 'Karate', 'KAR', '#222222')
const groupKids = new Group(10, 'Kids', 1, branchJudo, [100, 101])
const groupTeens = new Group(11, 'Teens', 1, branchJudo, [102])
const groupKarate = new Group(20, 'Karate A', 2, branchKarate, [])

function seededStore() {
  const store = useMasterDataStore()
  store.setBranches([branchJudo, branchKarate])
  store.setGroups([groupKids, groupTeens, groupKarate])
  store.setLocations([{ id: 1, name: 'Halle 1' }, { id: 2, name: 'Halle 2' }])
  store.setContents([
    { id: 1, name: 'Wurf', branchId: 1 },
    { id: 2, name: 'Kata', branchId: 2 },
    { id: 3, name: 'Boden', branchId: 1 },
  ])
  store.setSimpleTrainers([
    { id: 50, firstName: 'T1', familyName: 'A', groupIds: [10], trainerBranchIds: [1] },
    { id: 51, firstName: 'T2', familyName: 'B', groupIds: [20], trainerBranchIds: [2] },
    { id: 52, firstName: 'T3', familyName: 'C', groupIds: [10, 20], trainerBranchIds: undefined },
  ])
  store.setSimpleUsers([new SimpleUser(100, 'A', 'A', [10]), new SimpleUser(101, 'B', 'B', []), new SimpleUser(102, 'C', 'C', [11])])
  store.setTrainingSeries([TrainingSeries.from({ id: 1, groupIds: [10], weekdays: [1], automaticAttend: 0 }), TrainingSeries.from({ id: 2, groupIds: [20], weekdays: [2] })])
  return store
}

describe('masterData store getters', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('starts empty', () => {
    const store = useMasterDataStore()
    expect(store.branches).toEqual([])
    expect(store.groups).toEqual([])
    expect(store.contents).toEqual([])
  })

  it('finds entities by id', () => {
    const store = seededStore()

    expect(store.getGroupById(10)).toEqual(groupKids)
    expect(store.getGroupById(999)).toBeUndefined()
    expect(store.getBranchById(2)).toEqual(branchKarate)
    expect(store.getLocationById(2)).toEqual({ id: 2, name: 'Halle 2' })
    expect(store.getLocationNameById(1)).toBe('Halle 1')
    expect(store.getLocationNameById(99)).toBeUndefined()
    expect(store.getContentById(2)).toMatchObject({ name: 'Kata' })
    expect(store.getSimpleTrainerById(51)).toMatchObject({ firstName: 'T2' })
  })

  it('groups colors', () => {
    const store = seededStore()
    ;(store.groups[0] as any).colorHex = '#abcdef'

    expect(store.getGroupColorById(10)).toBe('#abcdef')
    expect(store.getGroupColorById(999)).toBeNull()
  })

  it('contents by branch / ids / groups', () => {
    const store = seededStore()

    expect(store.getContentsByBranchId(1).map((c: any) => c.id)).toEqual([1, 3])
    expect(store.getContentIdsByBranchId(2)).toEqual([2])
    expect(store.getContentIdsByGroupIds([10, 11])).toEqual([1, 3])
    expect(store.getContentIdsByGroupIds([10, 20])).toEqual([1, 2, 3])
    expect(store.getContentsByIds([2, 3]).map((c: any) => c.name)).toEqual(['Kata', 'Boden'])
  })

  it('resolves the branch of groups', () => {
    const store = seededStore()

    expect(store.getBranchByGroupId(20)).toEqual(branchKarate)
    expect(store.getBranchByGroupId(999)).toBeNull()
    expect(store.getBranchByGroupIds([10, 11])).toEqual(branchJudo)
    expect(store.getBranchByGroupIds([10, 20])).toBeNull() // mixed branches
    expect(store.getBranchByGroupIds([])).toBeNull()
    expect(store.getBranchShortNameByGroupIds([10, 11])).toBe('JUD')
    expect(store.getBranchShortNameByGroupIds([10, 20])).toBeNull()
  })

  it('filters groups', () => {
    const store = seededStore()

    expect(store.getGroupsByBranchId(1)).toEqual([groupKids, groupTeens])
    expect(store.getGroupIdsByBranchId(1)).toEqual([10, 11])
    expect(store.getGroupsByIds([11, 20])).toEqual([groupTeens, groupKarate])
    expect(store.getGroupsByBranchIds([2])).toEqual([groupKarate])
    expect(store.getGroupsByBranchIds(undefined as any)).toEqual([])
  })

  it('filters trainers', () => {
    const store = seededStore()

    expect(store.getSimpleTrainersByIds([50, 52]).map((t: any) => t.id)).toEqual([50, 52])
    expect(store.getSimpleTrainersByIds(undefined as any)).toEqual([])
    expect(store.getSimpleTrainersByGroupId(10).map((t: any) => t.id)).toEqual([50, 52])
    expect(store.getSimpleTrainersByBranchId(2).map((t: any) => t.id)).toEqual([51])
  })

  it('filters simple users and training series', () => {
    const store = seededStore()

    expect(store.getSimpleUsersByIds([100, 102]).map(u => u.id)).toEqual([100, 102])
    expect(store.getSimpleUsersByIds(null as any)).toEqual([])
    expect(store.getAllSimpleUsersWithGroup().map(u => u.id)).toEqual([100, 102])
    expect(store.getTrainingSeriesByGroupId(20).map(ts => Number(ts.id))).toEqual([2])
  })

  it('getSimpleUsersByGroupId filters by SimpleUser.groupIds and sorts without crashing on missing names', () => {
    const store = seededStore()
    store.setSimpleUsers([
      new SimpleUser(1, 'Zoe', 'M.', [10]),
      new SimpleUser(2, 'Max', 'B.', [10, 11]),
      new SimpleUser(3, 'Ann', null as any, [10]),
      new SimpleUser(4, 'Bob', 'C.', [11]),
    ])
    expect(store.getSimpleUsersByGroupId(10).map(u => u.id)).toEqual([3, 2, 1])
    expect(store.getSimpleUsersByGroupId(99)).toEqual([])
  })
})

describe('masterData store loadAll', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('loads and maps all master data through the http client', async () => {
    const responses: Record<string, unknown> = {
      '/branch': { data: [{ id: 1, name: 'Judo', shortName: 'JUD', colorHex: '#111111' }] },
      '/group': { data: [{ id: 10, name: 'Kids', branchId: 1, userIds: [100] }] },
      '/location': { data: [{ id: 1, name: 'Halle 1' }] },
      '/content': [{ id: 1, name: 'Wurf', branchId: 1 }], // plain array, not wrapped in "data"
      '/simpleuser/trainers': { data: [{ id: 50, firstName: 'T', familyName: 'A', groupIds: [10] }] },
      '/simpleuser': { data: [{ id: 100, firstName: 'Max', familyName: 'Muster', groupIds: [10] }] },
      '/trainingSeries': { data: [{ id: 5, startTime: '18:00', endTime: '19:30', weekdays: [1, 3], groupIds: [10], trainerIds: [], contentIds: [], automaticAttend: 1 }] },
    }
    vi.mocked(httpClient.get).mockImplementation(async (url: string) => ({ data: responses[url], status: 200, statusText: 'OK' }))
    const store = useMasterDataStore()

    await store.loadAll(httpClient)

    expect(httpClient.get).toHaveBeenCalledTimes(7)
    expect(store.branches[0]).toBeInstanceOf(Branch)
    expect(store.branches[0].getShortNameAndName()).toBe('Judo (JUD)')
    expect(store.groups[0]).toBeInstanceOf(Group)
    expect(store.groups[0].branch).toEqual(store.branches[0])
    expect(store.groups[0].userIds).toEqual([100])
    expect(store.locations).toEqual([{ id: 1, name: 'Halle 1' }])
    expect(store.contents).toEqual(responses['/content'])
    expect(store.simpleTrainers).toHaveLength(1)
    expect(store.simpleUsers[0]).toBeInstanceOf(SimpleUser)
    expect(store.simpleUsers[0].getFullName()).toBe('Max Muster')
    expect(store.trainingSeries[0]).toBeInstanceOf(TrainingSeries)
    expect(store.trainingSeries[0].automaticAttend).toBe(true)
    expect(store.trainingSeries[0].weekdays).toEqual([1, 3])
  })

  it('propagates http errors and leaves the state untouched', async () => {
    vi.mocked(httpClient.get).mockRejectedValue(new Error('HTTP 500'))
    const store = useMasterDataStore()

    await expect(store.loadAll(httpClient)).rejects.toThrow('HTTP 500')
    expect(store.branches).toEqual([])
  })
})
