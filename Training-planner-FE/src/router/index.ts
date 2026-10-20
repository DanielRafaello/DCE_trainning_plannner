import { createRouter, createWebHistory } from 'vue-router';
import AuthLayout from '@/layouts/AuthLayout.vue';
import DefaultLayout from '@/layouts/DefaultLayout.vue';

declare module 'vue-router' {
  interface RouteMeta {
    // pages under DefaultLayout need a logged-in user, pages under AuthLayout are for guests only
    requiresAuth?: boolean;
    guestOnly?: boolean;
    requiresAdmin?: boolean;
  }
}

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/auth',
      component: AuthLayout,
      meta: { guestOnly: true },
      redirect: { name: 'login' },
      children: [
        {
          path: 'login',
          name: 'login',
          component: () => import('@/views/auth/LoginView.vue')
        },
        {
          path: 'register',
          name: 'register',
          component: () => import('@/views/auth/RegisterView.vue')
        }
      ]
    },
    {
      path: '/',
      component: DefaultLayout,
      meta: { requiresAuth: true },
      children: [
        {
          // weekly timetable + events, day notes, to-do lists and statistics
          path: '',
          name: 'calendar',
          component: () => import('@/views/CalendarView.vue')
        },
        {
          // own account: name, timezone, password, account deletion
          path: 'profile',
          name: 'profile',
          component: () => import('@/views/ProfileView.vue')
        },
        {
          path: 'admin',
          name: 'admin',
          meta: { requiresAdmin: true },
          component: () => import('@/views/AdminView.vue')
        }
      ]
    },
    {
      path: '/:pathMatch(.*)*',
      redirect: { name: 'calendar' }
    }
  ]
});

export default router;
