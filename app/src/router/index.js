import Vue from 'vue'
import store from '@/store/index'
import Router from 'vue-router'
import TrainingCheckIn from '@/pages/TrainingCheckIn'
import Signup from "@/pages/Signup";
import Login from "@/pages/Login";
import Logout from "@/pages/Logout";
import Users from "@/pages/Users";
import Trainings from "@/pages/Trainings";
import Profile from "@/pages/Profile";
import Statistics from "@/pages/Statistics";
import ApproveUsers from "@/pages/ApproveUsers";


Vue.use(Router)

const router = new Router({
  mode: 'history',
  routes: [
    {
      path: '/',
      name: 'TrainingCheckIn',
      component: TrainingCheckIn
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
      path: '/users',
      name: 'Users',
      component: Users,
      meta: {
        requiresAuth: true,
        forTrainers: true
      }
    },
    {
      path: '/approveUsers',
      name: 'ApproveUsers',
      component: ApproveUsers,
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
