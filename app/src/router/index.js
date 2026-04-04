import { createRouter, createWebHashHistory } from 'vue-router'
import { useAuthStore } from '@/store/auth'
import TrainingsCheckIn from '@/pages/TrainingsCheckInPage'
import TrainingsPrepare from '@/pages/TrainingsPreparePage'
import TrainingsEvaluation from '@/pages/TrainingsEvaluationPage'
import Signup from "@/pages/SignupPage";
import VerifyEmail from "@/pages/VerifyEmailPage";
import Login from "@/pages/LoginPage";
import Logout from "@/components/LogoutComponent";
import Users from "@/pages/UsersTablePage";
import Trainings from "@/pages/TrainingsTablePage";
import Calendar from "@/pages/CalendarPage";
import TrainingSeries from "@/pages/TrainingSeriesPage";
import Profile from "@/pages/ProfilePage";
import Infos from "@/pages/InfoPage";
import Statistics from "@/pages/StatisticsPage";
import Groups from "@/pages/GroupsTablePage";
import Locations from "@/pages/LocationsPage";
import Branches from "@/pages/BranchesTablePage";
import GroupsOverview from "@/pages/GroupsOverviewPage";
import AbsenceForm from "@/pages/AbsenceFormPage";

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    {
      path: '/',
      name: 'TrainingsCheckIn',
      component: TrainingsCheckIn,
    },
    {
      path: '/training/:id',
      component: TrainingsCheckIn,
    },
    {
      path: '/calendar',
      name: 'Calendar',
      component: Calendar,
    },
    {
      path: '/groupsOverview',
      name: 'GroupsOverview',
      component: GroupsOverview,
    },
    {
      path: '/absenceForm',
      name: 'AbsenceForm',
      component: AbsenceForm,
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
      path: '/verify-email',
      name: 'VerifyEmail',
      component: VerifyEmail
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
    },
  ]
})

router.beforeEach((to, from, next) => {
  if (to.matched.some(record => record.meta.requiresAuth)) {
    const authStore = useAuthStore()
    if (!authStore.user) {
      next({ path: '/login', query: { nextUrl: to.fullPath } })
    } else {
      if (to.matched.some(record => record.meta.forTrainers)) {
        if (authStore.user.isTrainer || authStore.user.isAdmin) {
          next()
        } else {
          next({ path: '/' })
        }
      } else {
        next()
      }
    }
  } else {
    next()
  }
})

export default router;
