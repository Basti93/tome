
const state = {
  branches: [],
  groups: [],
  locations: [],
  trainers: [],
  simpleTrainers: [],
  contents: [],
}

const mutations = {
  setBranches (state, branches) {
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
  setContents (state, contents) {
    state.contents = contents
  },
}

const getters = {
  getGroupById: (state) => (id) => {
    return state.groups.find(g => g.id === id)
  },
  getBranchById: (state) => (id) => {
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
  getContentsByIds: (state) => (ids) => {
    return state.contents.filter(c => ids.includes(c.id))
  },
  getGroupColorById: (state, getters) => (id) => {
    let group = getters.getGroupById(id);
    if (group) {
      return group.colorHex
    }
    return null;
  },
  getLocationNameById: (state) => (id) => {
    let location = state.locations.find(l => l.id === id)
    if (location) {
      return location.name
    }
  },
  getBranchByGroupId: (state, getters) => (id) => {
    let group = getters.getGroupById(id);
    if (group) {
      return state.branches.find(b => b.id == group.branchId)
    }
    return null;
  },
  getGroupsByBranchId: (state, getters) => (id) => {
    return state.groups.filter(g => g.branchId === id)
  },
  getGroupIdsByBranchId: (state, getters) => (id) => {
    return state.groups.filter(g => g.branchId === id).map(g => g.id)
  },
  getGroupsByIds: (state, getters) => (ids) => {
    return state.groups.filter(g => ids.includes(g.id))
  },
  getSimpleTrainerById: (state, getters) => (id) => {
      return state.simpleTrainers.find(g => id === g.id)
  },
  getSimpleTrainersByIds: (state, getters) => (ids) => {
      return state.simpleTrainers.filter(g => ids.includes(g.id))
  },
  getGroupsByBranchIds: (state, getters) => (ids) => {
    return state.groups.filter(g => ids.includes(g.branchId))
  },
}


export default {
  namespaced: true,
  state,
  mutations,
  getters,
}
