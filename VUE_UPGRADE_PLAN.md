# Vue.js 2 to Vue 3 Upgrade Plan for T.O.M.E.

**Date:** April 1, 2026  
**Current Version:** Vue 2.6.14  
**Target Version:** Vue 3.5.x (latest stable)  
**Project Size:** 44 Vue components, ~19K lines of code

---

## Executive Summary

This is a **major version upgrade** that requires careful planning and execution. The project will need updates across:
- **Framework:** Vue 2.6.14 → Vue 3.5+
- **Build Tool:** Vue CLI → Vite (recommended) or keep Vue CLI with Vue 3
- **State Management:** Vuex 3 → Vuex 4 or migrate to Pinia
- **Router:** Vue Router 3 → Vue Router 4
- **UI Library:** Vuetify 2 → Vuetify 3
- **TypeScript:** 3.6.5 → 5.x
- **Component API:** Options API → Options API (compatible) or gradually migrate to Composition API

**Estimated Duration:** 4-6 weeks (depending on team size and testing thoroughness)  
**Complexity Level:** HIGH

---

## Phase 1: Planning & Preparation (Week 1)

### 1.1 Create Feature Branch & Backup
```bash
git checkout -b vue3-upgrade
git branch --list  # Verify backup exists
```

### 1.2 Dependency Audit
- [ ] Run `npm outdated` to see current state
- [ ] Identify all direct and peer dependencies
- [ ] Check compatibility matrix for:
  - Vue 3 + Vuetify 3 + Vue Router 4 + Vuex 4/Pinia
  - Firebase 9.x compatibility with Vue 3
  - ApexCharts compatibility
  - ESLint + TypeScript compatibility

### 1.3 Compatibility Check Required Packages
```
Current → Target Versions:
- vue: 2.6.14 → 3.5.x
- vuetify: 2.6.6 → 3.x (breaking changes)
- vue-router: 3.5.4 → 4.x (breaking changes)
- vuex: 3.6.2 → 4.x (or migrate to Pinia)
- @babel/eslint-parser: 7.18.2 → 7.x (likely compatible)
- vue-template-compiler: 2.6.14 → remove (not needed in Vue 3)
- typescript: 3.6.5 → 5.x
- eslint-plugin-vue: 8.7.1 → 9.x (Vue 3 support)
- apexcharts: 3.35.3 → 3.45.x+ (verify compatibility)
- firebase: 9.12.1 → 9.x or 10.x (check Vue 3 support)
```

### 1.4 Documentation Review
- [ ] Review Vue 3 Migration Guide: https://v3-migration.vuejs.org/
- [ ] Review Vuetify 3 Migration: https://vuetifyjs.com/en/
- [ ] Review Vue Router 4: https://router.vuejs.org/
- [ ] Review Vue 3 Composition API (if planning to migrate)

---

## Phase 2: Upgrade Dependencies (Week 2)

### 2.1 Update Core Framework Dependencies

```bash
# Option A: Using Vue CLI (if keeping CLI)
cd app/
npm install -g @vue/cli@latest
npm uninstall vue vue-template-compiler
npm install vue@^3.5.0

# Option B: Better - Migrate to Vite (recommended for new Vue 3 projects)
# See section 2.2 below
```

### 2.2 Choose Build Tool: Vue CLI vs Vite

#### Option A: Keep Vue CLI (Simpler Migration)
**Pros:** Minimal structural changes, gradual migration possible  
**Cons:** Slower build times, not optimal for Vue 3

```bash
npm install --save-dev @vue/cli-service@^5.0.0
npm install --save-dev @vue/cli-plugin-typescript@^5.0.0
npm install --save-dev @vue/cli-plugin-eslint@^5.0.0
npm install --save-dev @vue/cli-plugin-pwa@^5.0.0
```

#### Option B: Migrate to Vite (Recommended)
**Pros:** Much faster builds, modern tooling, Vue 3 native  
**Cons:** Requires structural changes, more initial work

**Steps:**
1. Create new Vite project structure
2. Install Vite and Vue 3 dependencies
3. Migrate configuration
4. Update import paths
5. Update build scripts

**Install Vite setup:**
```bash
npm create vite@latest . -- --template vue-ts
# or use Vue CLI migration tool
```

### 2.3 Update Package.json Dependencies

```json
{
  "dependencies": {
    "vue": "^3.5.0",
    "vue-router": "^4.3.0",
    "vuex": "^4.1.0",
    "vuetify": "^3.5.0",
    "typescript": "^5.3.0",
    "@vueuse/core": "^10.7.0",
    "axios": "^1.6.0",
    "firebase": "^10.0.0",
    "apexcharts": "^3.45.0",
    "vue-apexcharts": "^1.6.2",
    "moment": "^2.29.4",
    "jwt-decode": "^4.0.0",
    "core-js": "^3.35.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.0",
    "@vue/compiler-sfc": "^3.5.0",
    "typescript": "^5.3.0",
    "@typescript-eslint/eslint-plugin": "^6.0.0",
    "@typescript-eslint/parser": "^6.0.0",
    "eslint": "^8.54.0",
    "eslint-plugin-vue": "^9.17.0",
    "sass": "^1.69.0",
    "vite": "^5.0.0"
  }
}
```

### 2.4 Update TypeScript Configuration

**New tsconfig.json for Vue 3:**
```json
{
  "compilerOptions": {
    "target": "ES2020",
    "useDefineForClassFields": true,
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "skip": [],
    "module": "ESNext",
    "skipLibCheck": true,
    "esModuleInterop": true,
    "allowSyntheticDefaultImports": true,
    "resolveJsonModule": true,
    "strict": true,
    "noEmit": true,
    "moduleResolution": "bundler",
    "allowImportingTsExtensions": true,
    "types": ["vite/client"],
    "baseUrl": ".",
    "paths": {
      "@/*": ["./src/*"]
    }
  },
  "include": ["src/**/*.ts", "src/**/*.d.ts", "src/**/*.tsx", "src/**/*.vue"],
  "references": [{ "path": "./tsconfig.node.json" }]
}
```

### 2.5 ESLint Configuration Update

**New .eslintrc.cjs:**
```javascript
/* eslint-env node */
require('@rushstack/eslint-patch/modern-module-resolution')

module.exports = {
  root: true,
  env: {
    browser: true,
    es2021: true,
    node: true
  },
  extends: [
    'plugin:vue/vue3-essential',
    'eslint:recommended',
    '@vue/eslint-config-typescript'
  ],
  parserOptions: {
    parser: '@typescript-eslint/parser'
  }
}
```

---

## Phase 3: Framework-Specific Updates (Weeks 2-3)

### 3.1 Vue 3 Core Changes

#### Remove Vue 2 Specific Code
- [ ] Remove `vue-template-compiler` references
- [ ] Remove `registerServiceWorker` 2.0 patterns (if used)
- [ ] Update global Vue configuration

#### Update main.ts Entry Point

**Old (Vue 2):**
```typescript
import Vue from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'

Vue.config.productionTip = false

new Vue({
  router,
  store,
  render: h => h(App)
}).$mount('#app')
```

**New (Vue 3):**
```typescript
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import store from './store'
import vuetify from './plugins/vuetify'

const app = createApp(App)

app.use(router)
app.use(store)
app.use(vuetify)
app.mount('#app')
```

### 3.2 Vue Router 4 Migration

**Key Changes:**
- `mode: 'history'` → `history: createWebHistory()`
- Router lifecycle hooks updated
- `router-link` and `router-view` remain similar

**New router/index.ts structure:**
```typescript
import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  // routes here
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

export default router
```

**Update All Route Files:**
- [ ] Replace `mode` configuration
- [ ] Update lazy loading syntax: `() => import('...')`
- [ ] Review navigation guards
- [ ] Update `beforeEach`, `afterEach` hooks

### 3.3 Vuex 4 Migration

**Option A: Stick with Vuex 4**
```typescript
// store/index.ts
import { createStore } from 'vuex'

export default createStore({
  state: {
    // state
  },
  mutations: {
    // mutations
  },
  actions: {
    // actions
  },
  modules: {
    // modules
  }
})
```

**Option B: Migrate to Pinia (Recommended)**
Pinia is the official state management library for Vue 3.

```bash
npm install pinia
```

**Example Pinia store:**
```typescript
import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', () => {
  const state = ref({
    user: null,
    token: null
  })

  const login = async (credentials) => {
    // login logic
  }

  return { state, login }
})
```

### 3.4 Vuetify 3 Migration

**Major Changes:**
- Components renamed/updated
- Props syntax changes
- Theme system updated
- Layout system changed

**Update vuetify plugin:**
```typescript
// src/plugins/vuetify.ts
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'

export default createVuetify({
  components,
  directives,
  theme: {
    themes: {
      light: {
        colors: {
          primary: '#1976D2',
          // colors
        }
      }
    }
  }
})
```

**Update Vuetify imports in components:**
- [ ] Update layout components (v-app, v-navigation-drawer, etc.)
- [ ] Update form components (v-text-field syntax may change)
- [ ] Update grid system (v-row, v-col remain similar)
- [ ] Test all Material icons usage

### 3.5 Update Component Files (44 components)

**Generic Transformation Pattern:**

**Old (Vue 2):**
```vue
<template>
  <div>
    <v-text-field v-model="input" />
  </div>
</template>

<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator'

@Component
export default class MyComponent extends Vue {
  @Prop() title!: string
  input = ''

  mounted() {
    console.log('mounted')
  }
}
</script>
```

**New (Vue 3 - Options API):**
```vue
<template>
  <div>
    <v-text-field v-model="input" />
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue'

export default defineComponent({
  name: 'MyComponent',
  props: {
    title: String
  },
  data() {
    return {
      input: ''
    }
  },
  mounted() {
    console.log('mounted')
  }
})
</script>
```

**New (Vue 3 - Composition API - Future Migration):**
```vue
<template>
  <div>
    <v-text-field v-model="input" />
  </div>
</template>

<script lang="ts" setup>
import { ref } from 'vue'

defineProps<{ title?: string }>()

const input = ref('')
</script>
```

**Required Updates per Component (44 files):**
- [ ] Remove `vue-property-decorator` imports
- [ ] Update to `defineComponent()` wrapper
- [ ] Convert `@Prop` → `props` object/array
- [ ] Convert lifecycle hooks to Vue 3 syntax
- [ ] Update template event binding if needed
- [ ] Test component functionality

---

## Phase 4: Critical Components Deep Dive (Week 3-4)

### 4.1 Form Components
**Files to Review:**
- EditTrainingDialog.vue
- EditUserDialog.vue
- GroupsSelectDialog.vue
- CookieUserDialog.vue
- UploadProfileImage.vue

**Actions:**
- [ ] Test all form validations
- [ ] Update v-text-field, v-select, v-checkbox props
- [ ] Test file uploads
- [ ] Verify form submission flow

### 4.2 Pages (17 pages)
**Files to Review:**
- AbsenceFormPage.vue
- BranchesTablePage.vue
- TrainingsTablePage.vue
- CalendarPage.vue
- StatisticsPage.vue
- ProfilePage.vue
- LoginPage.vue
- SignupPage.vue
- And 9 others

**Actions:**
- [ ] Update all page routing
- [ ] Test page navigation
- [ ] Verify data loading
- [ ] Check axios/API calls

### 4.3 Store Files (auth.ts, masterData.ts, etc.)
- [ ] Update store structure (Vuex 4 or migrate to Pinia)
- [ ] Test state mutations
- [ ] Test async actions
- [ ] Verify module imports

### 4.4 Router Configuration
- [ ] Update route definitions
- [ ] Test all page navigation
- [ ] Test lazy loading
- [ ] Test route guards

### 4.5 Axios Configuration
- [ ] Update axios setup
- [ ] Test API interceptors
- [ ] Verify authentication flow
- [ ] Test error handling

---

## Phase 5: Plugin & Integration Updates (Week 4)

### 5.1 Firebase Integration
- [ ] Update Firebase imports for Vue 3
- [ ] Test FCM notifications
- [ ] Verify authentication
- [ ] Test database operations

### 5.2 Service Worker & PWA
- [ ] Update PWA plugin configuration
- [ ] Update firebase-messaging-sw.js
- [ ] Test offline functionality
- [ ] Test install prompt

### 5.3 Third-Party Libraries
- [ ] ApexCharts compatibility check
- [ ] Material Design Icons integration
- [ ] Moment.js usage (consider date-fns or Day.js)
- [ ] jwt-decode update

---

## Phase 6: Testing (Week 4-5)

### 6.1 Manual Testing Checklist

#### Authentication
- [ ] User login
- [ ] JWT token refresh
- [ ] Cookie-based simple user identification
- [ ] Password reset
- [ ] User signup
- [ ] Logout

#### Training Operations
- [ ] View training calendar
- [ ] View training list
- [ ] Create training
- [ ] Edit training
- [ ] Delete training
- [ ] Check-in/out
- [ ] Evaluate training

#### Data Management
- [ ] View groups
- [ ] Create group
- [ ] Edit group
- [ ] View branches
- [ ] View locations
- [ ] Manage users
- [ ] View statistics

#### UI/UX
- [ ] Mobile responsiveness
- [ ] Dialog boxes work correctly
- [ ] Form validation displays properly
- [ ] Charts render correctly
- [ ] Navigation works
- [ ] Icons display correctly
- [ ] Colors and theme apply

#### PWA Features
- [ ] Install app (if on supported platform)
- [ ] Offline functionality
- [ ] Push notifications
- [ ] Service worker updates

### 6.2 Automated Testing
- [ ] Unit tests (if any exist)
- [ ] Component tests
- [ ] Integration tests
- [ ] E2E tests

### 6.3 Performance Testing
- [ ] Build size comparison
- [ ] Load time comparison
- [ ] Runtime performance
- [ ] Memory usage

---

## Phase 7: Deployment & Cleanup (Week 5-6)

### 7.1 Final Checks
- [ ] Code review
- [ ] ESLint passes
- [ ] TypeScript compilation passes
- [ ] No console errors
- [ ] All features tested

### 7.2 Build & Test Production Build
```bash
npm run build
# Verify bundle size
# Test production build locally
```

### 7.3 Staging Deployment
- [ ] Deploy to staging environment
- [ ] Full QA testing
- [ ] Performance monitoring
- [ ] Browser compatibility testing

### 7.4 Production Deployment
- [ ] Deploy to production
- [ ] Monitor for errors
- [ ] User feedback
- [ ] Rollback plan ready

### 7.5 Cleanup & Documentation
- [ ] Delete old Vue CLI config if migrated to Vite
- [ ] Update project documentation
- [ ] Document breaking changes
- [ ] Update README.md
- [ ] Merge to main branch

---

## Detailed Breaking Changes Reference

### Vue 2 → Vue 3

| Feature | Vue 2 | Vue 3 | Migration |
|---------|-------|-------|-----------|
| Import | `import Vue from 'vue'` | `import { createApp } from 'vue'` | Use `createApp()` |
| Root component | `new Vue().$mount('#app')` | `createApp().mount('#app')` | See code example |
| Global properties | `Vue.prototype.$foo` | `app.config.globalProperties.$foo` | Use config object |
| Custom directives | `Vue.directive('focus')` | `app.directive('focus')` | Use app instance |
| Lifecycle hooks | `beforeCreate`, `created` | `beforeCreate`, `created`, or `setup` | Check new names |
| v-model | Single binding | Multiple bindings supported | Update templates |
| `this.$slots` | Array | Object with slots | Update slot access |
| Async components | `Vue.component()` | `defineAsyncComponent()` | New import/syntax |

### Vuetify 2 → Vuetify 3

| Feature | Vuetify 2 | Vuetify 3 | Notes |
|---------|-----------|-----------|-------|
| v-app | Required wrapper | Still required | Unchanged |
| Props syntax | Kebab-case | Camel-case (usually) | Check migration guide |
| v-toolbar | Component | Renamed sections | Use `v-app-bar` |
| v-navigation-drawer | Similar | Enhanced | Test carefully |
| Themes | Limited | Enhanced | New theme system |
| Icons | Icon prop | Icon slot | May need changes |

### Vue Router 3 → Vue Router 4

| Feature | Vue Router 3 | Vue Router 4 | Migration |
|---------|--------------|--------------|-----------|
| Mode | `mode: 'history'` | `history: createWebHistory()` | Import `createWebHistory` |
| Hash routes | `mode: 'hash'` | `history: createWebHashHistory()` | Import function |
| Router hooks | `beforeEach()`, `afterEach()` | `beforeEach()`, `afterEach()` | Same but on router instance |
| Matched routes | `$route.matched` | `$route.matched` | Unchanged |
| Meta | `meta: {}` | `meta: {}` | Unchanged |

### Vuex 3 → Vuex 4

| Feature | Vuex 3 | Vuex 4 | Migration |
|---------|--------|--------|-----------|
| Create store | `new Vuex.Store()` | `createStore()` | Import `createStore` |
| Module registration | `modules: {}` | `modules: {}` | Unchanged |
| Getters | Function | Function | Unchanged |
| Mutations | Method | Method | Unchanged |
| Actions | Method | Method | Unchanged |
| Subscriptions | `store.subscribe()` | `store.subscribe()` | Unchanged |

---

## Risk Assessment & Mitigation

### High Risk Areas

#### 1. **TypeScript Compatibility**
- **Risk:** TypeScript 3.6.5 is ancient, major changes in 5.x
- **Mitigation:** Update to latest TypeScript 5.x, run type checking, add more strict types
- **Timeline:** Parallel with Vue upgrade

#### 2. **Vuetify Component Changes (44 components using Vuetify)**
- **Risk:** Many components use Vuetify, changes in 3.x could break UI
- **Mitigation:** Test each component carefully, screenshot comparisons, gradual rollout
- **Timeline:** Phases 4-5

#### 3. **State Management Complexity**
- **Risk:** Vuex store changes could affect data flow
- **Mitigation:** Comprehensive store testing, consider Pinia migration, monitor store state
- **Timeline:** Phase 3

#### 4. **Firebase & Third-Party Libraries**
- **Risk:** Compatibility issues with Firebase, ApexCharts, etc.
- **Mitigation:** Check compatibility matrix before upgrade, have fallback versions ready
- **Timeline:** Phase 5

#### 5. **PWA & Service Worker**
- **Risk:** Service worker changes, PWA manifest compatibility
- **Mitigation:** Test PWA functionality thoroughly, browser testing
- **Timeline:** Phase 5-6

### Medium Risk Areas

- Component lifecycle hooks changes
- Template syntax changes (minor)
- Router guard behavior changes
- ESLint configuration updates

### Low Risk Areas

- Core data models (unchanged)
- API communication (axios still works)
- UI layout (similar in Vuetify 3)
- Theme colors (transportable)

---

## Recommended Approach

### Strategy: Gradual Vue 2 → Vue 3 Migration with Vue CLI

1. **Safer than jumping to Vite immediately**
2. **Allows incremental testing**
3. **Full Vue 2 compatibility layer available**
4. **Easier rollback if issues arise**

### Timeline: 5-6 weeks with 1-2 developers

```
Week 1: Planning, Dependency Analysis, Setup
Week 2: Dependency Updates, Build Tool Config
Week 3: Framework Updates (Router, Store, Core)
Week 4: Component Updates (partial), Testing (parallel)
Week 5: Component Updates (complete), Deep Testing
Week 6: Final Testing, Deployment
```

---

## Success Criteria

- [ ] All 44 components working in Vue 3
- [ ] All 17 pages rendering correctly
- [ ] State management working (Vuex 4 or Pinia)
- [ ] API calls working correctly
- [ ] Authentication flow intact
- [ ] PWA features functional
- [ ] No TypeScript errors
- [ ] ESLint passes all checks
- [ ] Mobile responsive design intact
- [ ] All user flows tested manually
- [ ] Performance >= current version
- [ ] Zero console errors
- [ ] Browser compatibility confirmed

---

## Post-Upgrade Improvements

Once Vue 3 is working, consider:

1. **Composition API Refactoring** - Gradual migration to `<script setup>`
2. **Pinia State Management** - Better than Vuex for Vue 3
3. **Vite Migration** - For faster builds
4. **TypeScript Strict Mode** - Improve type safety
5. **Component Testing** - Add unit tests with Vitest
6. **E2E Testing** - Add Cypress or Playwright tests
7. **Performance Optimization** - Use Vue 3's new features

---

## Resources & References

### Official Documentation
- Vue 3 Migration Guide: https://v3-migration.vuejs.org/
- Vue 3 API Docs: https://vuejs.org/
- Vuetify 3: https://vuetifyjs.com/en/
- Vue Router 4: https://router.vuejs.org/
- Vuex 4: https://vuex.vuejs.org/

### Tools & Helpers
- Vue CLI: https://cli.vuejs.org/
- Vite: https://vitejs.dev/
- TypeScript: https://www.typescriptlang.org/

### Migration Guides
- Vuetify Migration: https://vuetifyjs.com/en/getting-started/upgrade-guide/
- Vue Router Migration: https://router.vuejs.org/guide/migration/

---

## Questions to Answer Before Starting

1. **Team Capacity:** Can we allocate 1-2 developers full-time for 5-6 weeks?
2. **Testing:** Do we have automated tests, or is this manual only?
3. **Scope:** Should we also modernize other parts (build tool, state management)?
4. **Timeline:** When does production need to stay stable?
5. **Rollback Plan:** How will we handle production issues post-launch?
6. **User Impact:** Should migration be transparent to users?

---

## Approval & Sign-Off

**Document prepared:** April 1, 2026  
**Status:** Ready for Review  
**Next Step:** Review with team, adjust timeline, begin Phase 1
