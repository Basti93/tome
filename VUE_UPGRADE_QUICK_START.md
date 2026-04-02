# Vue Upgrade - Quick Start Checklist

**Current:** Vue 2.6.14 → **Target:** Vue 3.5+  
**Duration:** 5-6 weeks | **Complexity:** HIGH

---

## 🚀 Quick Start (Do This First)

### 1. Create Branch & Backup
```bash
cd /Users/sbinder/Projects/tome
git checkout -b vue3-upgrade
git branch -a  # Verify
```

### 2. Check Dependencies (Right Now)
```bash
cd app/
npm outdated
npm list vue vuetify vue-router vuex
```

### 3. Review Compatibility
- Vue 2.6.14 → Vue 3.5+
- Vuetify 2.6.6 → Vuetify 3.5+
- Vue Router 3.5.4 → Vue Router 4.3+
- Vuex 3.6.2 → Vuex 4.1+ (or migrate to **Pinia** - recommended)
- TypeScript 3.6.5 → TypeScript 5.3+ (MAJOR jump, watch for breaking changes)

---

## 📋 Phase Overview

| Phase | Duration | Tasks | Status |
|-------|----------|-------|--------|
| **1. Planning** | 3 days | Dependency audit, create plan ✓ | Ready |
| **2. Dependencies** | 4 days | Update package.json, configs | TODO |
| **3. Framework** | 4 days | Vue 3, Router 4, Vuex 4, Vuetify 3 | TODO |
| **4. Components** | 5 days | Update 44 Vue files | TODO |
| **5. Testing** | 5 days | Manual & automated testing | TODO |
| **6. Deploy** | 3 days | Staging → Production | TODO |

---

## 📦 Key Dependency Updates

```json
{
  "dependencies": {
    "vue": "^3.5.0",
    "vue-router": "^4.3.0",
    "vuex": "^4.1.0",
    "vuetify": "^3.5.0",
    "typescript": "^5.3.0",
    "firebase": "^10.0.0",
    "axios": "^1.6.0"
  }
}
```

---

## 🔄 Critical Code Changes

### Entry Point (main.ts)

**Before:**
```typescript
import Vue from 'vue'
new Vue({ router, store, render: h => h(App) }).$mount('#app')
```

**After:**
```typescript
import { createApp } from 'vue'
const app = createApp(App)
app.use(router).use(store).use(vuetify).mount('#app')
```

### Component Structure

**Before (Vue 2):**
```vue
<script lang="ts">
import { Component, Vue, Prop } from 'vue-property-decorator'
@Component
export default class MyComponent extends Vue {
  @Prop() title!: string
}
</script>
```

**After (Vue 3):**
```vue
<script lang="ts">
import { defineComponent } from 'vue'
export default defineComponent({
  props: { title: String }
})
</script>
```

### Router Config

**Before:**
```typescript
new Router({ mode: 'history', routes: [] })
```

**After:**
```typescript
import { createRouter, createWebHistory } from 'vue-router'
createRouter({ history: createWebHistory(), routes: [] })
```

### Store Config

**Before:**
```typescript
new Vuex.Store({ state: {}, mutations: {} })
```

**After:**
```typescript
import { createStore } from 'vuex'
createStore({ state: {}, mutations: {} })
```

---

## ⚠️ High Risk Items

| Risk | Impact | Mitigation |
|------|--------|-----------|
| **Vuetify 2→3** | Component props/API changes | Test all 44 components, comparison screenshots |
| **TypeScript 3→5** | Major version jump | Update code incrementally, strict mode gradually |
| **Service Worker** | PWA functionality | Test offline mode, install prompt |
| **Firebase** | Authentication flow | Verify Firebase API compatibility |
| **State Management** | Data flow changes | Comprehensive Vuex 4 testing |

---

## 🎯 Testing Strategy

### Automated
- [ ] Unit tests (if exist)
- [ ] ESLint checks
- [ ] TypeScript compilation

### Manual (Priority Order)
1. **Authentication** - Login, JWT, logout
2. **Training Operations** - Create, edit, delete, check-in
3. **Data Management** - CRUD for all entities
4. **UI/UX** - Forms, dialogs, charts, responsive design
5. **PWA Features** - Install, offline, notifications

---

## 📊 Component Updates (44 Total)

**Categories:**
- **Pages:** 17 (calendar, tables, forms, etc.)
- **Components:** 30+ (dialogs, inputs, charts, etc.)
- **Store:** 5 modules (auth, masterData, etc.)
- **Router:** 1 config file

**Per-component checklist:**
- [ ] Remove `vue-property-decorator` imports
- [ ] Update to `defineComponent()` wrapper
- [ ] Convert `@Prop` decorators to props object
- [ ] Update lifecycle hooks syntax
- [ ] Test in browser
- [ ] Verify template rendering

---

## 📈 Success Metrics

**Must Have:**
- ✅ All 44 components render
- ✅ All 17 pages work
- ✅ Authentication working
- ✅ API calls successful
- ✅ No console errors
- ✅ No TypeScript errors

**Should Have:**
- ✅ PWA features working
- ✅ Charts rendering
- ✅ Mobile responsive
- ✅ Performance >= current

**Nice to Have:**
- ✅ Unit tests added
- ✅ Pinia migration
- ✅ Vite migration
- ✅ Composition API usage

---

## 🛑 Decision Points

### 1. Build Tool
- **Option A:** Keep Vue CLI (safer, incremental)
- **Option B:** Migrate to Vite (faster, modern, recommended)

**Decision:** \_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_

### 2. State Management
- **Option A:** Stay with Vuex 4 (easier migration)
- **Option B:** Migrate to Pinia (recommended for Vue 3)

**Decision:** \_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_

### 3. Component API
- **Option A:** Options API (compatible with Vue 2, safer)
- **Option B:** Composition API + `<script setup>` (modern, gradual migration)

**Decision:** \_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_

---

## 📅 Suggested Timeline

```
Week 1 (Mon-Fri): Dependency analysis & setup
├─ Day 1: Review plan, make decisions
├─ Day 2-3: Update package.json, install dependencies
├─ Day 4-5: Update configs (TypeScript, ESLint, Vue config)

Week 2 (Mon-Fri): Framework core updates
├─ Day 1-2: Update entry point, router, store
├─ Day 3-4: Update Vuetify plugins
├─ Day 5: Fix compilation errors

Week 3 (Mon-Fri): Component updates (batch 1)
├─ Day 1-5: Update 22 components
├─ Parallel: Test each batch

Week 4 (Mon-Fri): Component updates (batch 2) + Testing
├─ Day 1-3: Update remaining 22 components
├─ Day 4-5: Manual testing sprint

Week 5 (Mon-Fri): Deep Testing & Bug Fixes
├─ Day 1-2: Critical path testing
├─ Day 3: Bug fixes & refinements
├─ Day 4: Staging deployment
├─ Day 5: Production readiness review

Week 6 (Mon-Wed): Production Deployment
├─ Day 1: Final QA
├─ Day 2: Production deploy
├─ Day 3: Monitor & hotfixes
```

---

## 🔗 Essential Resources

- [Vue 3 Migration Guide](https://v3-migration.vuejs.org/)
- [Vuetify 3 Upgrade Guide](https://vuetifyjs.com/en/getting-started/upgrade-guide/)
- [Vue Router 4 Documentation](https://router.vuejs.org/)
- [TypeScript 5.x Changes](https://www.typescriptlang.org/)

---

## 📝 Pre-Start Checklist

- [ ] Team agreed on timeline (5-6 weeks)
- [ ] Build tool decision made (Vue CLI vs Vite)
- [ ] State management decision made (Vuex 4 vs Pinia)
- [ ] Backup/branch created
- [ ] Testing strategy defined
- [ ] Rollback plan documented
- [ ] Stakeholders informed

---

## 🚨 Rollback Plan

If critical issues arise:

1. **Local Issues:** `git stash` or `git reset --hard`
2. **Deployment Issues:** Revert commits, restore previous version
3. **Data Issues:** Database is unchanged (API compatible)

**Keep:** Current `main` branch untouched until ready

---

**Status:** 🔴 Not Started  
**Last Updated:** April 1, 2026  
**Prepared by:** Vue Upgrade Planning
