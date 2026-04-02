import { createRouter, createWebHistory, RouteRecordRaw } from 'vue-router'
import store from '@/store/index'
import TrainingsCheckIn from '@/pages/TrainingsCheckInPage.vue'
import TrainingsPrepare from '@/pages/TrainingsPreparePage.vue'
import TrainingsEvaluation from '@/pages/TrainingsEvaluationPage.vue'
import Signup from '@/pages/SignupPage.vue'
import Login from '@/pages/LoginPage.vue'
import Logout from '@/components/LogoutComponent.vue'
import Users from '@/pages/UsersTablePage.vue'
import Trainings from '@/pages/TrainingsTablePage.vue'
import Calendar from '@/pages/CalendarPage.vue'
import TrainingSeries from '@/pages/TrainingSeriesPage.vue'
import Profile from '@/pages/ProfilePage.vue'
import Infos from '@/pages/InfoPage.vue'
import Statistics from '@/pages/StatisticsPage.vue'
import Groups from '@/pages/GroupsTablePage.vue'
import Locations from '@/pages/LocationsPage.vue'
import Branches from '@/pages/BranchesTablePage.vue'
import GroupsOverview from '@/pages/GroupsOverviewPage.vue'
import AbsenceForm from '@/pages/AbsenceFormPage.vue'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'TrainingsCheckIn',
    component: TrainingsCheckIn
  },
  {
    path: '/training/:id',
    component: TrainingsCheckIn
  },
  {
    path: '/calendar',
    name: 'Calendar',
    component: Calendar
  },
  {
    path: '/groupsOverview',
    name: 'GroupsOverview',
    component: GroupsOverview
  },
  {
    path: '/absenceForm',
    name: 'AbsenceForm',
    component: AbsenceForm
  },
  {
    path: '/trainingsPrepare',
    name: 'TrainingsPrepare',
    component: TrainingsPrepare,
    meta: {
      requiresAuth: true,
      forTrainers: true
    }
  },
  {
    path: '/trainingsEvaluation',
    name: 'TrainingsEvaluation',
    component: TrainingsEvaluation,
    meta: {
      requiresAuth: true,
      forTrainers: true
    }
  },
  {
    path: '/trainings',
    name: 'Trainings',
    component: Trainings,
    meta: {
      requiresAuth: true,
      forTrainers: true
    }
  },
  {
    path: '/trainingSeries',
    name: 'TrainingSeries',
    component: TrainingSeries,
    meta: {
      requiresAuth: true,
      forTrainers: true
    }
  },
  {
    path: '/users',
    name: 'Users',
    component: Users,
    meta: {
      requiresAuth: true,
      forTrainers: true
    }
  },
  {
    path: '/groups',
    name: 'Groups',
    component: Groups,
    meta: {
      requiresAuth: true,
      forTrainers: true
    }
  },
  {
    path: '/locations',
    name: 'Locations',
    component: Locations,
    meta: {
      requiresAuth: true,
      forTrainers: true
    }
  },
  {
    path: '/branches',
    name: 'Branches',
    component: Branches,
    meta: {
      requiresAuth: true,
      forTrainers: true
    }
  },
  {
    path: '/signup',
    name: 'Signup',
    component: Signup
  },
  {
    path: '/info',
    name: 'Informationen',
    component: Infos
  },
  {
    path: '/login',
    name: 'Login',
    component: Login
  },
  {
    path: '/logout',
    name: 'Logout',
    component: Logout
  },
  {
    path: '/profile',
    name: 'Profil',
    component: Profile
  },
  {
    path: '/statistics',
    name: 'Statistics',
    component: Statistics
  }
]

const router = createRouter({
  history: createWebHistory(process.env.BASE_URL),
  routes
})

router.beforeEach((to, from, next) => {
  if (to.matched.some(record => record.meta?.requiresAuth)) {
    if (!store.state.auth.user) {
      next({
        path: '/login',
        query: { nextUrl: to.fullPath }
      })
    } else {
      if (to.matched.some(record => record.meta?.forTrainers)) {
        if (store.state.auth.user.isTrainer || store.state.auth.user.isAdmin) {
          next()
        } else {
          next({ name: 'TrainingsCheckIn' })
        }
      } else {
        next()
      }
    }
  } else {
    next()
  }
})

export default router
