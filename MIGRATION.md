# T.O.M.E. Migration Plan

Migration from Vue 2 + Vuetify 2 + Laravel 8 to Vue 3 + Tailwind CSS + shadcn-vue + Laravel 11.

## Target Stack

| Layer | From | To |
|-------|------|----|
| Frontend framework | Vue 2.6 | Vue 3.4 |
| Build tool | Vue CLI / Webpack | Vite 5 |
| UI components | Vuetify 2 | Tailwind CSS + shadcn-vue |
| State management | Vuex 3 | Pinia 2 |
| Router | Vue Router 3 | Vue Router 4 |
| HTTP client | vue-axios + axios 0.27 | axios 1.x (plain instance) |
| TypeScript | 3.6 (partial) | 5.x (strict) |
| Backend framework | Laravel 8 | Laravel 11 |
| Auth (backend) | tymon/jwt-auth | php-open-source-saver/jwt-auth 2 |
| Permissions | spatie/laravel-permission 3 | spatie/laravel-permission 6 |
| Image processing | intervention/image 2 | intervention/image 3 |

**Removed:** Zoom integration (incompatible with PHP 8.5), Vuex, vue-axios, vue-template-compiler, babel.config.js, vue.config.js, node-sass, Dingo API

---

## Branch Strategy

- `vue3-migration` — long-lived feature branch; all migration PRs target this
- `master` — stable production branch; `vue3-migration` merges here when complete
- Each phase/unit gets its own short-lived branch off `vue3-migration`

---

## Phase 0 — Backend (Laravel 8 → 11)

**Status: PR open** → [#3 feat(server): upgrade Laravel 8 → 11](https://github.com/Basti93/tome/pull/3)

### Done
- Laravel 8 → 11 (new `bootstrap/app.php`, `bootstrap/providers.php`, updated `artisan`)
- Replaced `tymon/jwt-auth` with `php-open-source-saver/jwt-auth ^2.0`
- Removed `fruitcake/laravel-cors` — Laravel 11 has built-in CORS
- Upgraded `spatie/laravel-permission` 3 → 6
- Upgraded `intervention/image` 2 → 3 + `intervention/image-laravel`
- Migrated Dingo API routes to standard Laravel routing
- Removed Zoom integration
- Suppressed PHP 8.5 `PDO::MYSQL_ATTR_SSL_CA` deprecation notices

### Follow-up (separate PR)
- Compare models and authorization policies from `tome-rebuild` repo
- The rebuild has cleaner Eloquent relationships and proper Laravel Policies
  (`TrainingPolicy`, `UserPolicy`, etc.) worth adopting over Spatie middleware strings
- Review `NotificationSetting` model from the rebuild for push notification preferences

---

## Phase 1 — Frontend Foundation

**One PR. Must land before any other frontend work.**

Everything else in Phases 2–5 runs inside this foundation. No page or component
migrations in this PR — just infrastructure.

### Files to create / replace

| File | Change |
|------|--------|
| `app/package.json` | Full replacement — Vue 3, Vite, Tailwind, shadcn-vue, Pinia, Router 4, Axios 1.x |
| `app/vite.config.ts` | New — replaces `vue.config.js` |
| `app/tailwind.config.ts` | New |
| `app/tsconfig.json` | Updated for TS 5 + Vite (`moduleResolution: bundler`, no `baseUrl`) |
| `app/src/main.ts` | `createApp()` + `createPinia()` + `createRouter()` |
| `app/src/App.vue` | Shell: router-view + Toaster (shadcn Sonner) |
| `app/src/router/index.ts` | Vue Router 4 (`createRouter`, `createWebHashHistory`) |
| `app/src/axios/index.ts` | Plain axios instance, JWT interceptors, no `Vue.use` |
| `app/src/store/auth.ts` | Pinia store (replaces Vuex module) |
| `app/src/store/cookieAuth.ts` | Pinia store |
| `app/src/store/masterData.ts` | Pinia store — all mutations become actions |
| `app/src/store/snackbar.ts` | Pinia store with `show(text, color, timeout)` action |
| `app/src/plugins/` | Remove `vuetify.js` — no longer needed |
| `app/src/shims-vue.d.ts` | Updated for Vue 3 |
| `app/src/firebase-config.js` | Update to Firebase 10 modular API, `process.env` → `import.meta.env` |

### Files to delete
- `app/vue.config.js`
- `app/babel.config.js`
- `app/src/store/index.ts` (Pinia has no root store)

### Key contracts established by this phase

All subsequent PRs depend on these conventions:

```ts
// Stores — import and call inside setup() or methods
import { useAuthStore } from '@/store/auth'
import { useMasterDataStore } from '@/store/masterData'
import { useCookieAuthStore } from '@/store/cookieAuth'
import { useSnackbarStore } from '@/store/snackbar'

// HTTP — plain axios, not this.$http
import axios from '@/axios'

// Env vars
import.meta.env.VITE_API_URL   // was process.env.VUE_APP_API_URL

// Snackbar — no more $emit('showSnackbar')
useSnackbarStore().show('message', 'error')

// Dialogs — modelValue prop + update:modelValue emit (v-model)

// Moment — import per file, not this.moment
import moment from 'moment'
```

### shadcn-vue components to install in this phase
`Button`, `Dialog`, `AlertDialog`, `Toast` (Sonner), `Sheet`, `Badge`, `Input`, `Label`, `Form`

---

## Phase 2 — Shared Components

**Parallel with Phase 3. Both depend on Phase 1.**

Primitive components used across many pages. No page dependencies.

| Component | Vuetify → shadcn-vue |
|-----------|----------------------|
| `TomeNavigation.vue` | `Sheet` (mobile drawer) + `NavigationMenu` |
| `SnackbarStore.vue` | Sonner `toast()` calls — component may become a no-op wrapper |
| `ProfileImage.vue` | Plain `<img>` + Tailwind |
| `ListItemProfileImage.vue` | Avatar with Tailwind |
| `GroupChip.vue` | shadcn `Badge` + `Popover` |
| `LogoutComponent.vue` | shadcn `Button` |
| `WeekdaysComponent.vue` | shadcn `Toggle` group |
| `TrainingContent.vue` | Plain Tailwind — no complex components needed |

**Universal changes in every `.vue` file:**

| Before | After |
|--------|-------|
| `Vue.extend({...})` | `export default { ... }` |
| `this.$http.X` | `axios.X` |
| `mapGetters` / `mapState` / `mapMutations` | Pinia store calls |
| `this.$store.commit(...)` | Pinia action |
| `this.$emit('showSnackbar', text, color)` | `useSnackbarStore().show(text, color)` |
| `this.moment(...)` | `moment(...)` (imported) |
| `process.env.VUE_APP_X` | `import.meta.env.VITE_X` |
| `this.$set(obj, key, val)` | `obj[key] = val` |

---

## Phase 3 — Form Infrastructure Components

**Parallel with Phase 2. Both depend on Phase 1.**

| Component | Notes |
|-----------|-------|
| `GroupSelect.vue` | shadcn `Select` |
| `GroupsSelect.vue` | shadcn `Combobox` (multi-select) |
| `GroupsSelectDialog.vue` | shadcn `Dialog` + `Combobox` |
| `StatisticsFilter1.vue` | shadcn `Select` + date inputs |
| `UploadProfileImage.vue` | Native `<input type="file">` + Tailwind |
| `ConfirmDialog.vue` | shadcn `AlertDialog` |
| `ChangePasswordDialog.vue` | shadcn `Dialog` + `Form` |
| `CookieUserDialog.vue` | shadcn `Dialog` |
| `EditUserDialog.vue` | shadcn `Dialog` + `Form` — remove `$refs.birthdatePicker.activePicker` hack |
| `EditTrainingBase.vue` | shadcn `Form` + date/time picker (`vue-datepicker` or shadcn date picker) |
| `TrainingAccountingExportDialog.vue` | shadcn `Dialog` |

**Dialog pattern in Vue 3 + shadcn:**
```vue
<!-- Props -->
const props = defineProps<{ modelValue: boolean }>()
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>()

<!-- Usage -->
<MyDialog v-model="showDialog" />
```

---

## Phase 4 — Pages (Parallel)

**All Phase 4 PRs are independent of each other. All depend on Phases 1–3.**

### 4a — Auth & Profile Pages
- `LoginPage.vue`
- `SignupPage.vue`
- `ProfilePage.vue` — tabs with shadcn `Tabs`
- Remove `$refs.birthdatePicker.activePicker` Vuetify 2 hack (appears in Signup + Profile)
- Remove `$refs.form.$children['0'].focus()` DOM traversal — use explicit `ref`

### 4b — Training Check-in Flow
- `TrainingsCheckInPage.vue`
- `TrainingSelector.vue` — replace `v-timeline` with Tailwind timeline pattern
- `TrainingCheckIn.vue`

### 4c — Training Prepare & Evaluate
- `TrainingsPreparePage.vue`
- `TrainingsEvaluationPage.vue`
- `TrainerTimeline.vue` — update `vue-apexcharts` → `vue3-apexcharts`

### 4d — Training CRUD
- `TrainingsTablePage.vue` — shadcn `DataTable` (TanStack Table)
- `TrainingSeriesPage.vue` — shadcn `Tabs`

### 4e — Admin Tables
- `UsersTablePage.vue` — shadcn `DataTable`
- `GroupsTablePage.vue` — shadcn `DataTable`
- `BranchesTablePage.vue` — shadcn `DataTable` + color picker
- `LocationsPage.vue` — shadcn `DataTable`

### 4f — Calendar
- `CalendarPage.vue`
- `TrainingCalendar.vue` — replace `v-calendar` (removed in Vuetify 3) with `vue-cal` or FullCalendar Vue 3

### 4g — Statistics & Charts
- `StatisticsPage.vue`
- `TrainingParticipationBarChart.vue` — `vue3-apexcharts`
- `GroupsOverviewPage.vue` — shadcn `DataTable` or card grid

### 4h — Remaining Public Pages
- `AbsenceFormPage.vue`
- `InfoPage.vue` — shadcn `Accordion` (replaces `v-expansion-panels`)

---

## Phase 5 — TypeScript & Models

**Parallel with Phase 4.**

- All `src/models/*.ts` — fix `import * as moment from 'moment'` → `import moment from 'moment'`
- `src/helpers/color-helper.js` → `.ts`
- `src/helpers/cookie-helper.ts` — type cleanup
- `src/helpers/date-helpers.js` → `.ts`

---

## Phase 6 — Integration & Polish

**After all Phase 4 PRs merged into `vue3-migration`.**

- PWA manifest, service worker update
- Firebase 10 push notifications — test end-to-end
- Dark mode — Tailwind `dark:` classes throughout
- Final `npm run build` with zero TypeScript errors
- Verify all API endpoints work against the Laravel 11 backend
- Update `CLAUDE.md` with new commands and architecture

---

## Merge Order

```
Phase 0 (backend)     → merge PR #3 into vue3-migration
Phase 1 (foundation)  → merge into vue3-migration (blocks everything else)
Phase 2 + Phase 3     → merge in any order (parallel PRs)
Phase 4a–4h           → merge in any order (all independent)
Phase 5               → merge anytime after Phase 1
Phase 6               → final integration, merge into master
```

---

## Key Decisions Made

| Topic | Decision | Reason |
|-------|----------|--------|
| UI library | Tailwind + shadcn-vue | No breaking upgrades, own the components, modern DX |
| State | Pinia | Official Vue 3 recommendation, simpler than Vuex |
| Build | Vite | Vue CLI in maintenance mode, Vite is significantly faster |
| Calendar | vue-cal | v-calendar removed from Vuetify 3, needs replacement regardless |
| Charts | vue3-apexcharts | Existing chart code is reusable |
| Zoom | Removed | macsidigital/laravel-zoom incompatible with PHP 8.5 |
| Backend auth | php-open-source-saver/jwt-auth | Maintained fork for Laravel 9+ |
