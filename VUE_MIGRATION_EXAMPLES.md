# Vue 3 Migration - Code Examples & Patterns

This document shows practical before/after examples for migrating T.O.M.E. code from Vue 2 to Vue 3.

---

## 1. Entry Point (main.ts)

### Before (Vue 2)
```typescript
import Vue from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import vuetify from './plugins/vuetify'
import axios from 'axios'

Vue.use(axios)
Vue.config.productionTip = false

new Vue({
  router,
  store,
  vuetify,
  render: h => h(App)
}).$mount('#app')
```

### After (Vue 3)
```typescript
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import vuetify from './plugins/vuetify'
import axios from 'axios'

const app = createApp(App)

app.use(router)
app.use(store)
app.use(vuetify)

// Global property (replaces Vue.use)
app.config.globalProperties.$axios = axios

app.mount('#app')
```

---

## 2. Router Configuration

### Before (Vue Router 3)
```typescript
// router/index.ts
import Vue from 'vue'
import Router from 'vue-router'
import Home from '../views/Home.vue'

Vue.use(Router)

const router = new Router({
  mode: 'history',
  base: process.env.BASE_URL,
  routes: [
    {
      path: '/',
      name: 'Home',
      component: Home
    },
    {
      path: '/trainings',
      name: 'TrainingsTable',
      component: () => import('../pages/TrainingsTablePage.vue')
    },
    {
      path: '/trainings/:id',
      name: 'TrainingDetail',
      component: () => import('../pages/TrainingDetailPage.vue'),
      beforeEnter: (to, from, next) => {
        // guard logic
        next()
      }
    }
  ]
})

router.beforeEach((to, from, next) => {
  // Global guard
  next()
})

export default router
```

### After (Vue Router 4)
```typescript
// router/index.ts
import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import Home from '../views/Home.vue'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'Home',
    component: Home
  },
  {
    path: '/trainings',
    name: 'TrainingsTable',
    component: () => import('../pages/TrainingsTablePage.vue')
  },
  {
    path: '/trainings/:id',
    name: 'TrainingDetail',
    component: () => import('../pages/TrainingDetailPage.vue'),
    beforeEnter: (to, from, next) => {
      // guard logic
      next()
    }
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

router.beforeEach((to, from, next) => {
  // Global guard
  next()
})

export default router
```

---

## 3. Vuex Store

### Before (Vuex 3)
```typescript
// store/index.ts
import Vue from 'vue'
import Vuex from 'vuex'
import auth from './modules/auth'
import masterData from './modules/masterData'

Vue.use(Vuex)

export default new Vuex.Store({
  state: {
    version: '1.0.0'
  },
  modules: {
    auth,
    masterData
  }
})
```

### After (Vuex 4)
```typescript
// store/index.ts
import { createStore } from 'vuex'
import auth from './modules/auth'
import masterData from './modules/masterData'

export default createStore({
  state: {
    version: '1.0.0'
  },
  modules: {
    auth,
    masterData
  }
})
```

### Vuex Store Module

#### Before (Vuex 3)
```typescript
// store/modules/auth.ts
const state = {
  user: null,
  token: null,
  isAuthenticated: false
}

const getters = {
  currentUser: (state) => state.user,
  isLoggedIn: (state) => state.isAuthenticated
}

const mutations = {
  SET_USER(state, user) {
    state.user = user
  },
  SET_TOKEN(state, token) {
    state.token = token
    state.isAuthenticated = !!token
  },
  LOGOUT(state) {
    state.user = null
    state.token = null
    state.isAuthenticated = false
  }
}

const actions = {
  async login({ commit }, credentials) {
    try {
      const response = await axios.post('/auth/login', credentials)
      const { token, user } = response.data
      commit('SET_TOKEN', token)
      commit('SET_USER', user)
      return user
    } catch (error) {
      throw error
    }
  },
  logout({ commit }) {
    commit('LOGOUT')
  }
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
}
```

#### After (Vuex 4 - Same Code, Different Setup)
```typescript
// store/modules/auth.ts
// Code is identical! Vuex 4 maintains backward compatibility
const state = {
  user: null,
  token: null,
  isAuthenticated: false
}

const getters = {
  currentUser: (state) => state.user,
  isLoggedIn: (state) => state.isAuthenticated
}

const mutations = {
  SET_USER(state, user) {
    state.user = user
  },
  SET_TOKEN(state, token) {
    state.token = token
    state.isAuthenticated = !!token
  },
  LOGOUT(state) {
    state.user = null
    state.token = null
    state.isAuthenticated = false
  }
}

const actions = {
  async login({ commit }, credentials) {
    try {
      const response = await axios.post('/auth/login', credentials)
      const { token, user } = response.data
      commit('SET_TOKEN', token)
      commit('SET_USER', user)
      return user
    } catch (error) {
      throw error
    }
  },
  logout({ commit }) {
    commit('LOGOUT')
  }
}

export default {
  namespaced: true,
  state,
  getters,
  mutations,
  actions
}
```

---

## 4. Component - Class-Based (most common in current project)

### Before (Vue 2 with vue-property-decorator)
```vue
<template>
  <v-dialog v-model="dialog" max-width="500px">
    <v-card>
      <v-card-title>{{ title }}</v-card-title>
      <v-card-text>
        <v-text-field
          v-model="form.name"
          label="Name"
          :error="!!errors.name"
          :error-messages="errors.name"
        />
        <v-text-field
          v-model="form.email"
          label="Email"
          type="email"
        />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="dialog = false">Cancel</v-btn>
        <v-btn color="primary" @click="save">Save</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
import { Component, Vue, Prop, Emit } from 'vue-property-decorator'
import { User } from '@/models/User'

@Component
export default class EditUserDialog extends Vue {
  @Prop({ required: true }) user!: User
  @Prop({ type: Boolean, default: false }) visible!: boolean
  @Emit() save!: (user: User) => void

  dialog = false
  form = { name: '', email: '' }
  errors = { name: [], email: [] }

  created() {
    this.loadUser()
  }

  watch() {
    return {
      visible: (newVal) => {
        this.dialog = newVal
      },
      dialog: (newVal) => {
        this.$emit('update:visible', newVal)
      }
    }
  }

  mounted() {
    console.log('Dialog mounted')
  }

  loadUser() {
    this.form = { ...this.user }
  }

  async save() {
    try {
      await this.$axios.put(`/user/${this.user.id}`, this.form)
      this.$emit('save', this.form)
      this.dialog = false
    } catch (error) {
      this.errors = error.response?.data?.errors || {}
    }
  }
}
</script>

<style scoped lang="scss">
.v-card-text {
  padding: 16px;
}
</style>
```

### After (Vue 3 with defineComponent)
```vue
<template>
  <v-dialog v-model="dialog" max-width="500px">
    <v-card>
      <v-card-title>{{ title }}</v-card-title>
      <v-card-text>
        <v-text-field
          v-model="form.name"
          label="Name"
          :error="!!errors.name"
          :error-messages="errors.name"
        />
        <v-text-field
          v-model="form.email"
          label="Email"
          type="email"
        />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="dialog = false">Cancel</v-btn>
        <v-btn color="primary" @click="save">Save</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import type { PropType } from 'vue'
import type { User } from '@/models/User'

export default defineComponent({
  name: 'EditUserDialog',
  props: {
    user: {
      type: Object as PropType<User>,
      required: true
    },
    visible: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:visible', 'save'],
  data() {
    return {
      dialog: false,
      form: { name: '', email: '' },
      errors: { name: [] as string[], email: [] as string[] }
    }
  },
  watch: {
    visible(newVal) {
      this.dialog = newVal
    },
    dialog(newVal) {
      this.$emit('update:visible', newVal)
    }
  },
  created() {
    this.loadUser()
  },
  mounted() {
    console.log('Dialog mounted')
  },
  methods: {
    loadUser() {
      this.form = { ...this.user }
    },
    async save() {
      try {
        await this.$axios.put(`/user/${this.user.id}`, this.form)
        this.$emit('save', this.form)
        this.dialog = false
      } catch (error: any) {
        this.errors = error.response?.data?.errors || {}
      }
    }
  }
})
</script>

<style scoped lang="scss">
.v-card-text {
  padding: 16px;
}
</style>
```

---

## 5. Component - Composition API (Optional, Modern Approach)

### Vue 3 with Composition API & `<script setup>`

This is the most modern approach (optional, can be adopted gradually):

```vue
<template>
  <v-dialog v-model="dialog" max-width="500px">
    <v-card>
      <v-card-title>{{ title }}</v-card-title>
      <v-card-text>
        <v-text-field
          v-model="form.name"
          label="Name"
          :error="!!errors.name"
          :error-messages="errors.name"
        />
        <v-text-field
          v-model="form.email"
          label="Email"
          type="email"
        />
      </v-card-text>
      <v-card-actions>
        <v-spacer />
        <v-btn text @click="dialog = false">Cancel</v-btn>
        <v-btn color="primary" @click="save">Save</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useAxios } from '@vueuse/integrations/useAxios'
import type { User } from '@/models/User'

interface Props {
  user: User
  visible?: boolean
}

interface Emits {
  (e: 'update:visible', value: boolean): void
  (e: 'save', user: User): void
}

const props = withDefaults(defineProps<Props>(), {
  visible: false
})

const emit = defineEmits<Emits>()

const dialog = ref(props.visible)
const form = ref({ name: '', email: '' })
const errors = ref<Record<string, string[]>>({ name: [], email: [] })

// Load user on mount
const loadUser = () => {
  form.value = { ...props.user }
}

// Watch for visible prop changes
watch(() => props.visible, (newVal) => {
  dialog.value = newVal
})

watch(() => dialog.value, (newVal) => {
  emit('update:visible', newVal)
})

const save = async () => {
  try {
    const { data } = await axios.put(`/user/${props.user.id}`, form.value)
    emit('save', form.value)
    dialog.value = false
  } catch (error: any) {
    errors.value = error.response?.data?.errors || {}
  }
}

// Initialize on mount
loadUser()
</script>

<style scoped lang="scss">
.v-card-text {
  padding: 16px;
}
</style>
```

---

## 6. Page Component

### Before (Vue 2)
```vue
<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <h1>Trainings</h1>
      </v-col>
    </v-row>
    <v-data-table
      :headers="headers"
      :items="trainings"
      @click:row="selectTraining"
    />
    <EditTrainingDialog
      :visible="showDialog"
      :training="selectedTraining"
      @save="refreshTrainings"
      @update:visible="showDialog = $event"
    />
  </v-container>
</template>

<script lang="ts">
import { Component, Vue } from 'vue-property-decorator'
import { mapState, mapActions } from 'vuex'
import EditTrainingDialog from '@/components/EditTrainingDialog.vue'
import { Training } from '@/models/Training'

@Component({
  components: { EditTrainingDialog },
  computed: mapState('masterData', ['trainings']),
  methods: mapActions('masterData', ['fetchTrainings'])
})
export default class TrainingsTablePage extends Vue {
  trainings!: Training[]
  fetchTrainings!: () => Promise<void>
  showDialog = false
  selectedTraining: Training | null = null

  headers = [
    { text: 'Date', value: 'date' },
    { text: 'Trainer', value: 'trainer' },
    { text: 'Location', value: 'location' }
  ]

  async mounted() {
    await this.fetchTrainings()
  }

  selectTraining(training: Training) {
    this.selectedTraining = training
    this.showDialog = true
  }

  refreshTrainings() {
    this.fetchTrainings()
    this.showDialog = false
  }
}
</script>
```

### After (Vue 3)
```vue
<template>
  <v-container fluid>
    <v-row>
      <v-col cols="12">
        <h1>Trainings</h1>
      </v-col>
    </v-row>
    <v-data-table
      :headers="headers"
      :items="trainings"
      @click:row="selectTraining"
    />
    <EditTrainingDialog
      :visible="showDialog"
      :training="selectedTraining"
      @save="refreshTrainings"
      @update:visible="showDialog = $event"
    />
  </v-container>
</template>

<script lang="ts">
import { defineComponent } from 'vue'
import { mapState, mapActions } from 'vuex'
import EditTrainingDialog from '@/components/EditTrainingDialog.vue'
import type { Training } from '@/models/Training'

export default defineComponent({
  name: 'TrainingsTablePage',
  components: { EditTrainingDialog },
  computed: {
    ...mapState('masterData', ['trainings'])
  },
  methods: {
    ...mapActions('masterData', ['fetchTrainings'])
  },
  data() {
    return {
      showDialog: false,
      selectedTraining: null as Training | null,
      headers: [
        { text: 'Date', value: 'date' },
        { text: 'Trainer', value: 'trainer' },
        { text: 'Location', value: 'location' }
      ]
    }
  },
  async mounted() {
    await this.fetchTrainings()
  },
  methods: {
    selectTraining(training: Training) {
      this.selectedTraining = training
      this.showDialog = true
    },
    refreshTrainings() {
      this.fetchTrainings()
      this.showDialog = false
    }
  }
})
</script>
```

---

## 7. Vuetify Plugin Configuration

### Before (Vue 2)
```typescript
// plugins/vuetify.ts
import Vue from 'vue'
import Vuetify from 'vuetify'
import 'vuetify/dist/vuetify.min.css'

Vue.use(Vuetify)

export default new Vuetify({
  theme: {
    themes: {
      light: {
        primary: '#1976D2',
        secondary: '#424242',
        accent: '#82B1FF'
      }
    }
  },
  icons: {
    iconfont: 'mdi'
  }
})
```

### After (Vue 3)
```typescript
// plugins/vuetify.ts
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import 'vuetify/styles'

export default createVuetify({
  components,
  directives,
  theme: {
    themes: {
      light: {
        colors: {
          primary: '#1976D2',
          secondary: '#424242',
          accent: '#82B1FF'
        }
      }
    }
  }
})
```

---

## 8. Computed Properties & Watchers

### Before (Vue 2)
```typescript
@Component
export default class MyComponent extends Vue {
  @Prop() user!: User
  firstName = ''
  lastName = ''

  get fullName(): string {
    return `${this.firstName} ${this.lastName}`
  }

  @Watch('user', { deep: true })
  onUserChange(newVal: User) {
    this.firstName = newVal.firstName
    this.lastName = newVal.lastName
  }

  @Watch('firstName')
  debounceFirstName = debounce(function() {
    console.log('First name changed')
  }, 300)
}
```

### After (Vue 3)
```typescript
export default defineComponent({
  props: { user: Object as PropType<User> },
  data() {
    return {
      firstName: '',
      lastName: ''
    }
  },
  computed: {
    fullName(): string {
      return `${this.firstName} ${this.lastName}`
    }
  },
  watch: {
    user: {
      handler(newVal: User) {
        this.firstName = newVal.firstName
        this.lastName = newVal.lastName
      },
      deep: true
    },
    firstName: debounce(function() {
      console.log('First name changed')
    }, 300)
  }
})
```

---

## 9. Axios HTTP Client

### Before (Vue 2)
```typescript
import axios from 'axios'
import Vue from 'vue'
import VueAxios from 'vue-axios'

Vue.use(VueAxios, axios)

// In component:
this.$axios.get('/api/trainings')
```

### After (Vue 3)
```typescript
import axios from 'axios'

// In main.ts:
app.config.globalProperties.$axios = axios

// Or better - use composable:
// In composable/useAxios.ts
export function useAxios() {
  return {
    get: axios.get,
    post: axios.post,
    put: axios.put,
    delete: axios.delete
  }
}

// In component:
const { get } = useAxios()
get('/api/trainings')
```

---

## 10. Common Patterns

### Lifecycle Hooks Comparison

| Vue 2 | Vue 3 |
|-------|-------|
| `beforeCreate()` | Not typically needed |
| `created()` | `setup()` |
| `beforeMount()` | `onBeforeMount()` |
| `mounted()` | `onMounted()` |
| `beforeUpdate()` | `onBeforeUpdate()` |
| `updated()` | `onUpdated()` |
| `beforeDestroy()` | `onBeforeUnmount()` |
| `destroyed()` | `onUnmounted()` |

### Using Lifecycle Hooks in Options API (Vue 3)

```typescript
export default defineComponent({
  setup() {
    // If you need setup in Options API
    // (usually not necessary)
  },
  created() {
    // Same as Vue 2
    console.log('created')
  },
  mounted() {
    // Same as Vue 2
    console.log('mounted')
  }
})
```

### Emitting Events

#### Before (Vue 2)
```typescript
this.$emit('save', data)
```

#### After (Vue 3 - Options API)
```typescript
// In data:
emits: ['save', 'cancel']

// In method:
this.$emit('save', data)
```

#### After (Vue 3 - Composition API)
```typescript
const emit = defineEmits<{
  save: [data: User]
  cancel: []
}>()

emit('save', data)
```

---

## 11. Testing Checklist Per Component

When migrating each component, test:

- [ ] Component renders without errors
- [ ] Props are received correctly
- [ ] Events are emitted correctly
- [ ] v-model works (if used)
- [ ] Form inputs work
- [ ] Buttons trigger actions
- [ ] API calls work
- [ ] Error handling works
- [ ] Responsive design works
- [ ] Icons display correctly
- [ ] Colors apply correctly

---

## 12. Common Gotchas & Fixes

### Issue: `this.$axios` is undefined
**Solution:** Register axios in main.ts:
```typescript
app.config.globalProperties.$axios = axios
```

### Issue: `this.$store` is undefined
**Solution:** Make sure store is registered:
```typescript
app.use(store)
```

### Issue: `v-slot` syntax errors
**Solution:** Vue 3 uses same syntax, but be explicit:
```vue
<template v-slot:activator="{ on, attrs }">
<!-- becomes -->
<template #activator="{ on, attrs }">
```

### Issue: TypeScript errors with `this`
**Solution:** Type your data:
```typescript
data(): { firstName: string } {
  return { firstName: '' }
}
```

### Issue: Custom directives not working
**Solution:** Register them in main.ts:
```typescript
app.directive('focus', {
  mounted: (el) => el.focus()
})
```

---

## Next Steps

1. **Create a feature branch:** `git checkout -b vue3-upgrade`
2. **Start with main.ts:** Easiest entry point
3. **Then update router/store:** Foundation for everything
4. **Then update components in batches:** 5-10 components per day
5. **Test frequently:** Don't wait until the end
6. **Keep git commits small:** Easy to debug later

---

**Last Updated:** April 1, 2026
