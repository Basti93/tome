import { defineStore } from 'pinia'
import Branch from '@/models/Branch'
import Group from '@/models/Group'
import User from '@/models/User'
import TrainingSeries from '@/models/TrainingSeries'
import SimpleUser from '@/models/SimpleUser'
import type { AxiosInstance } from 'axios'

export const useMasterDataStore = defineStore('masterData', {
  state: () => ({
    branches: [] as Branch[],
    groups: [] as Group[],
    locations: [] as unknown[],
    trainers: [] as unknown[],
    simpleTrainers: [] as unknown[],
    simpleUsers: [] as SimpleUser[],
    contents: [] as unknown[],
    trainingSeries: [] as TrainingSeries[],
  }),
  getters: {
    getGroupById: (state) => (id: number): Group | undefined => {
      return state.groups.find(g => g.id === id)
    },
    getBranchById: (state) => (id: number): Branch | undefined => {
      return state.branches.find(b => b.id === id)
    },
    getTrainerById: (state) => (id: number) => {
      return state.trainers.find((t: any) => t.id === id)
    },
    getLocationById: (state) => (id: number) => {
      return state.locations.find((l: any) => l.id === id)
    },
    getContentById: (state) => (id: number) => {
      return state.contents.find((c: any) => c.id === id)
    },
    getContentsByBranchId: (state) => (id: number) => {
      return state.contents.filter((c: any) => c.branchId === id)
    },
    getContentIdsByBranchId: (state) => (branchId: number) => {
      return state.contents.filter((c: any) => c.branchId === branchId).map((c: any) => c.id)
    },
    getContentIdsByGroupIds: (state) => (groupIds: number[]) => {
      const groups = state.groups.filter(g => groupIds.includes(g.id as number))
      return state.contents.filter((c: any) => groups.map(g => g.branchId).indexOf(c.branchId) >= 0).map((c: any) => c.id)
    },
    getContentsByIds: (state) => (ids: number[]) => {
      return state.contents.filter((c: any) => ids.includes(c.id))
    },
    getGroupColorById: (state) => (id: number) => {
      const group = state.groups.find(g => g.id === id)
      if (group) {
        return (group as any).colorHex
      }
      return null
    },
    getLocationNameById: (state) => (id: number) => {
      const location = state.locations.find((l: any) => l.id === id)
      if (location) {
        return (location as any).name
      }
    },
    getBranchByGroupId: (state) => (id: number): Branch | null => {
      const group = state.groups.find(g => g.id === id)
      if (group) {
        return state.branches.find(b => b.id == group.branchId) || null
      }
      return null
    },
    getBranchByGroupIds: (state) => (ids: number[]): Branch | null => {
      const groups = state.groups.filter(g => ids.includes(g.id as number))
      const branchIds = groups.map(g => g.branchId)
      if (ids && ids.length > 0 && branchIds.every(v => v === branchIds[0])) {
        const group = state.groups.find(g => g.id === ids[0])
        if (group) {
          return state.branches.find(b => b.id == group.branchId) || null
        }
      }
      return null
    },
    getBranchShortNameByGroupIds: (state) => (ids: number[]): string | null => {
      const groups = state.groups.filter(g => ids.includes(g.id as number))
      const branchIds = groups.map(g => g.branchId)
      if (ids && ids.length > 0 && branchIds.every(v => v === branchIds[0])) {
        const group = state.groups.find(g => g.id === ids[0])
        if (group) {
          const branch = state.branches.find(b => b.id == group.branchId)
          if (branch) return branch.shortName as string
        }
      }
      return null
    },
    getGroupsByBranchId: (state) => (id: number): Group[] => {
      return state.groups.filter(g => g.branchId === id)
    },
    getGroupIdsByBranchId: (state) => (id: number) => {
      return state.groups.filter(g => g.branchId === id).map(g => g.id)
    },
    getGroupsByIds: (state) => (ids: number[]): Group[] => {
      return state.groups.filter(g => ids.includes(g.id as number))
    },
    getSimpleTrainerById: (state) => (id: number) => {
      return state.simpleTrainers.find((g: any) => id === g.id)
    },
    getSimpleTrainersByIds: (state) => (ids: number[]) => {
      if (!ids || !Array.isArray(ids)) {
        return []
      }
      return state.simpleTrainers.filter((g: any) => ids.includes(g.id))
    },
    getSimpleTrainersByGroupId: (state) => (groupId: number) => {
      return state.simpleTrainers.filter((st: any) => st.groupIds.includes(groupId))
    },
    getSimpleTrainersByBranchId: (state) => (branchId: number) => {
      return state.simpleTrainers.filter((st: any) => st.trainerBranchIds && st.trainerBranchIds.includes(branchId))
    },
    getGroupsByBranchIds: (state) => (ids: number[]) => {
      if (!ids || !Array.isArray(ids)) {
        return []
      }
      return state.groups.filter(g => ids.includes(g.branchId as number))
    },
    getSimpleUsersByIds: (state) => (ids: number[]): SimpleUser[] => {
      if (!ids || !Array.isArray(ids)) {
        return []
      }
      return state.simpleUsers.filter(g => ids.includes(g.id))
    },
    getTrainingSeriesByGroupId: (state) => (groupId: number): TrainingSeries[] => {
      return state.trainingSeries.filter(ts => ts.groupIds.includes(groupId as Number))
    },
    getAllSimpleUsersWithGroup: (state) => (): SimpleUser[] => {
      return state.simpleUsers.filter(su => su.groupIds.length > 0)
    },
  },
  actions: {
    setBranches(branches: Branch[]) {
      this.branches = branches
    },
    setGroups(groups: Group[]) {
      this.groups = groups
    },
    setLocations(locations: unknown[]) {
      this.locations = locations
    },
    setTrainers(trainers: unknown[]) {
      this.trainers = trainers
    },
    setSimpleTrainers(trainers: unknown[]) {
      this.simpleTrainers = trainers
    },
    setSimpleUsers(users: SimpleUser[]) {
      this.simpleUsers = users
    },
    setTrainingSeries(trainingSeries: TrainingSeries[]) {
      this.trainingSeries = trainingSeries
    },
    setContents(contents: unknown[]) {
      this.contents = contents
    },
    async loadAll(axiosInstance: AxiosInstance) {
      const [resBranches, resGroups, locations, contents, trainers, resUsers, resTrainingSeries] = await Promise.all([
        axiosInstance.get('/branch'),
        axiosInstance.get('/group'),
        axiosInstance.get('/location'),
        axiosInstance.get('/content'),
        axiosInstance.get('/simpleuser/trainers'),
        axiosInstance.get('/simpleuser'),
        axiosInstance.get('/trainingSeries'),
      ])

      const branches = resBranches.data.data.map((b: any) => new Branch(b.id, b.name, b.shortName, b.colorHex))
      this.setBranches(branches)
      this.setGroups(resGroups.data.data.map((g: any) => new Group(g.id, g.name, g.branchId, branches.filter((b: Branch) => b.id == g.branchId)[0], g.userIds)))
      this.setLocations(locations.data.data)
      this.setContents(contents.data)
      this.setSimpleTrainers(trainers.data.data)
      this.setSimpleUsers(resUsers.data.data.map((u: any) => new SimpleUser(u.id, u.firstName, u.familyName, u.groupIds)))
      this.setTrainingSeries(resTrainingSeries.data.data.map((ts: any) => TrainingSeries.from(ts)))
    }
  }
})
