import { createRouter, createWebHashHistory } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import { analytics } from '@/services/analytics'

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

router.beforeEach((to) => {
  // Track page view
  const pageTitle = (to.name as string) || 'Page'
  analytics.trackPageView(pageTitle)

  if (to.matched.some(record => record.meta.requiresAuth)) {
    const authStore = useAuthStore()
    if (!authStore.user) {
      return { path: '/login', query: { nextUrl: to.fullPath } }
    }
    if (to.matched.some(record => record.meta.forTrainers)
      && !(authStore.user.isTrainer || authStore.user.isAdmin)) {
      return { path: '/' }
    }
  }
  return true
})

export default router
