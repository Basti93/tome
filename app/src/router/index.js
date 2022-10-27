import Vue from 'vue'
import store from '@/store/index'
import Router from 'vue-router'
import TrainingsCheckIn from '@/pages/TrainingsCheckInPage'
import TrainingsPrepare from '@/pages/TrainingsPreparePage'
import TrainingsEvaluation from '@/pages/TrainingsEvaluationPage'
import Signup from "@/pages/SignupPage";
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


Vue.use(Router)

const router = new Router({
  mode: 'hash',
  base: '/',
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
    if (!store.state.auth.user) {
      next({
        path: '/login',
        params: {nextUrl: to.fullPath}
      })
    } else {
      if (to.matched.some(record => record.meta.forTrainers)) {
        if (store.state.auth.user.isTrainer || store.state.auth.user.isAdmin) {
          next()
        } else {
          next({name: '/'})
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
