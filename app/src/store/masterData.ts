import Branch from "../models/Branch";
import Group from "../models/Group";
import User from "../models/User";
import TrainingSeries from "../models/TrainingSeries";


const state = {
  branches: [],
  groups: [],
  locations: [],
  trainers: [],
  simpleTrainers: [],
  simpleUsers: [],
  contents: [],
  trainingSeries: [],
}

const mutations = {
  setBranches (state, branches: Array<Branch>) {
    state.branches = branches;
  },
  setGroups (state, groups) {
    state.groups = groups
  },
  setLocations (state, locations) {
    state.locations = locations
  },
  setTrainers (state, trainers) {
    state.trainers = trainers
  },
  setSimpleTrainers (state, trainers) {
    state.simpleTrainers = trainers
  },
  setSimpleUsers (state, users) {
    state.simpleUsers = users
  },
  setTrainingSeries (state, trainingSeries) {
    state.trainingSeries = trainingSeries
  },
  setContents (state, contents) {
    state.contents = contents
  },
}

const getters = {
  getGroupById: (state) => (id): Group => {
    return state.groups.find(g => g.id === id)
  },
  getBranchById: (state) => (id):Branch => {
    return state.branches.find(g => g.id === id)
  },
  getTrainerById: (state) => (id) => {
    return state.trainers.find(t => t.id === id)
  },
  getLocationById: (state) => (id) => {
    return state.locations.find(l => l.id === id)
  },
  getContentById: (state) => (id) => {
    return state.contents.find(c => c.id === id)
  },
  getContentsByBranchId: (state) => (id) => {
    return state.contents.filter(c => c.branchId === id)
  },
  getContentIdsByBranchId: (state, getters) => (branchId) => {
    return getters.getContentsByBranchId(branchId).map(c => c.id);
  },
  getContentIdsByGroupIds: (state, getters) => (groupIds) => {
    const groups = getters.getGroupsByIds(groupIds);
    return state.contents.filter(c => groups.map(g => g.branchId).indexOf(c.branchId) >= 0).map(c => c.id);
  },
  getContentsByIds: (state) => (ids) => {
    return state.contents.filter(c => ids.includes(c.id))
  },
  getGroupColorById: (state, getters) => (id) => {
    const group = getters.getGroupById(id);
    if (group) {
      return group.colorHex
    }
    return null;
  },
  getLocationNameById: (state) => (id) => {
    const location = state.locations.find(l => l.id === id)
    if (location) {
      return location.name
    }
  },
  getBranchByGroupId: (state, getters) => (id): Branch => {
    const group = getters.getGroupById(id);
    if (group) {
      return state.branches.find(b => b.id == group.branchId)
    }
    return null;
  },
  getBranchByGroupIds: (state, getters) => (ids): Branch => {
    const branchIds = getters.getGroupsByIds(ids).map(g => g.branchId);
    if (ids && ids.length > 0  && branchIds.every(v => v === branchIds[0] )) {
      const group = getters.getGroupById(ids[0]);
      if (group) {
        return state.branches.find(b => b.id == group.branchId)
      }
    }
    return null;
  },
  getBranchShortNameByGroupIds: (state, getters) => (ids): Branch => {
    const branch = getters.getBranchByGroupIds(ids);
    if (branch) {
      return branch.shortName;
    }
    return null;
  },
  getGroupsByBranchId: (state, getters) => (id): Array<Group> => {
    return state.groups.filter(g => g.branchId === id)
  },
  getGroupIdsByBranchId: (state, getters) => (id) => {
    return state.groups.filter(g => g.branchId === id).map(g => g.id)
  },
  getGroupsByIds: (state, getters)  => (ids): Array<Group> => {
    return state.groups.filter(g => ids.includes(g.id))
  },
  getSimpleTrainerById: (state, getters) => (id) => {
      return state.simpleTrainers.find(g => id === g.id)
  },
  getSimpleTrainersByIds: (state, getters) => (ids) => {
      return state.simpleTrainers.filter(g => ids.includes(g.id))
  },
  getSimpleTrainersByGroupId: (state, getters) => (groupId) => {
      return state.simpleTrainers.filter(st => st.groupIds.includes(groupId))
  },
  getSimpleTrainersByBranchId: (state, getters) => (branchId) => {
      return state.simpleTrainers.filter(st => st.trainerBranchIds && st.trainerBranchIds.includes(branchId))
  },
  getGroupsByBranchIds: (state, getters) => (ids) => {
    return state.groups.filter(g => ids.includes(g.branchId))
  },
  getSimpleUsersByIds: (state) => (ids): Array<User> => {
    return state.simpleUsers.filter(g => ids.includes(g.id))
  },
  getTrainingSeriesByGroupId: (state) => (groupId): Array<TrainingSeries> => {
    return state.trainingSeries.filter(ts => ts.groupIds.includes(groupId))
  },
  getAllSimpleUsersWithGroup: (state) => (): Array<User> => {
    return state.simpleUsers.filter(su => su.groupIds.length > 0)
  },
}


export default {
  namespaced: true,
  state,
  mutations,
  getters,
}
