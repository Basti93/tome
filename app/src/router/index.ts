import { createRouter, createWebHashHistory } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { analytics } from '@/services/analytics'
import httpClient from '@/http/api'
import User from '@/models/User'

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', name: 'TrainingsCheckIn', component: () => import('@/pages/TrainingsCheckInPage.vue') },
    { path: '/training/:id', component: () => import('@/pages/TrainingsCheckInPage.vue') },
    { path: '/calendar', name: 'Calendar', component: () => import('@/pages/CalendarPage.vue') },
    { path: '/groupsOverview', name: 'GroupsOverview', component: () => import('@/pages/GroupsOverviewPage.vue') },
    { path: '/absenceForm', name: 'AbsenceForm', component: () => import('@/pages/AbsenceFormPage.vue') },
    {
      path: '/trainingsPrepare', name: 'TrainingsPrepare',
      component: () => import('@/pages/TrainingsPreparePage.vue'),
      meta: { requiresAuth: true, forTrainers: true }
    },
    {
      path: '/trainingsEvaluation', name: 'TrainingsEvaluation',
      component: () => import('@/pages/TrainingsEvaluationPage.vue'),
      meta: { requiresAuth: true, forTrainers: true }
    },
    {
      path: '/quickEvaluation/:id', name: 'TrainingQuickEvaluation',
      component: () => import('@/pages/TrainingQuickEvaluationPage.vue'),
      meta: { requiresAuth: true, forTrainers: true }
    },
    {
      path: '/trainings', name: 'Trainings',
      component: () => import('@/pages/TrainingsTablePage.vue'),
      meta: { requiresAuth: true, forTrainers: true }
    },
    {
      path: '/trainingSeries', name: 'TrainingSeries',
      component: () => import('@/pages/TrainingSeriesPage.vue'),
      meta: { requiresAuth: true, forTrainers: true }
    },
    {
      path: '/users', name: 'Users',
      component: () => import('@/pages/UsersTablePage.vue'),
      meta: { requiresAuth: true, forTrainers: true }
    },
    {
      path: '/groups', name: 'Groups',
      component: () => import('@/pages/GroupsTablePage.vue'),
      meta: { requiresAuth: true, forTrainers: true }
    },
    {
      path: '/locations', name: 'Locations',
      component: () => import('@/pages/LocationsPage.vue'),
      meta: { requiresAuth: true, forTrainers: true }
    },
    {
      path: '/branches', name: 'Branches',
      component: () => import('@/pages/BranchesTablePage.vue'),
      meta: { requiresAuth: true, forTrainers: true }
    },
    { path: '/verify-email', name: 'VerifyEmail', component: () => import('@/pages/VerifyEmailPage.vue') },
    { path: '/reset-password', name: 'ResetPassword', component: () => import('@/pages/ResetPasswordPage.vue') },
    { path: '/info', name: 'Informationen', component: () => import('@/pages/InfoPage.vue') },
    { path: '/login', name: 'Login', component: () => import('@/pages/LoginPage.vue') },
    { path: '/logout', name: 'Logout', component: () => import('@/components/LogoutComponent.vue') },
    { path: '/profile', name: 'Profil', component: () => import('@/pages/ProfilePage.vue') },
    { path: '/statistics', name: 'Statistics', component: () => import('@/pages/StatisticsPage.vue') },
  ]
})

/**
 * Restores the session when the persisted user is missing but the HttpOnly session cookie may still be valid
 * (e.g. the PWA is opened from a push notification after the browser storage was cleared).
 */
async function restoreSession(): Promise<void> {
  const authStore = useAuthStore()
  if (authStore.user) return
  try {
    const { data } = await httpClient.get('/auth/me', { silent401: true })
    if (data && typeof data === 'object' && data.id) {
      authStore.login(User.from(JSON.stringify(data)))
    }
  } catch {
    // no valid session: the guard redirects to the login page
  }
}

router.beforeEach(async (to) => {
  // Track page view
  const pageTitle = (to.name as string) || 'Page'
  analytics.trackPageView(pageTitle)

  if (to.matched.some(record => record.meta.requiresAuth)) {
    await restoreSession()
    const authStore = useAuthStore()
    if (!authStore.user) {
      // the login page reads `redirect` and returns there after the login (e.g. a push deep link)
      return { path: '/login', query: { redirect: to.fullPath } }
    }
    if (to.matched.some(record => record.meta.forTrainers)
      && !(authStore.user.isTrainer || authStore.user.isAdmin)) {
      return { path: '/' }
    }
  }
  return true
})

export default router
