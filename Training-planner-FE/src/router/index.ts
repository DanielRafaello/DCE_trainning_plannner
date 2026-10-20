import { createRouter, createWebHistory } from 'vue-router';
import AuthLayout from '@/layouts/AuthLayout.vue';
import DefaultLayout from '@/layouts/DefaultLayout.vue';
import { setOnSessionExpired } from '@/services/httpClient';
import { useAuthStore } from '@/stores/auth';

declare module 'vue-router' {
  interface RouteMeta {
    // pages under DefaultLayout need a logged-in user, pages under AuthLayout are for guests only
    requiresAuth?: boolean;
    guestOnly?: boolean;
    requiresAdmin?: boolean;
    /** Browser tab title */
    title?: string;
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
          meta: { title: 'Přihlášení' },
          component: () => import('@/views/auth/LoginView.vue')
        },
        {
          path: 'register',
          name: 'register',
          meta: { title: 'Registrace' },
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
          // weekly timetable + events, day notes and a week summary
          path: '',
          name: 'calendar',
          meta: { title: 'Kalendář' },
          component: () => import('@/views/CalendarView.vue')
        },
        {
          path: 'todo/:id(\\d+)?',
          name: 'todo',
          meta: { title: 'To-do listy' },
          component: () => import('@/views/TodoListsView.vue')
        },
        {
          path: 'statistics',
          name: 'statistics',
          meta: { title: 'Statistiky' },
          component: () => import('@/views/StatisticsView.vue')
        },
        {
          // own account: name, timezone, password, subcategories, account deletion
          path: 'profile',
          name: 'profile',
          meta: { title: 'Profil' },
          component: () => import('@/views/ProfileView.vue')
        },
        {
          path: 'admin',
          name: 'admin',
          meta: { requiresAdmin: true, title: 'Administrace' },
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

// to.meta already contains the meta of the parent (layout) routes
router.afterEach((to) => {
  document.title = to.meta.title ? `${to.meta.title} · TrainingPlanner` : 'TrainingPlanner';
});

router.beforeEach(async (to) => {
  const auth = useAuthStore();
  await auth.loadUser();

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: to.fullPath !== '/' ? { redirect: to.fullPath } : {} };
  }
  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'calendar' };
  }
  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return { name: 'calendar' };
  }
});

// refresh failed / account blocked or deleted while the app is open
setOnSessionExpired(() => {
  useAuthStore().setUser(null);
  if (router.currentRoute.value.meta.requiresAuth) {
    void router.replace({ name: 'login', query: { expired: '1' } });
  }
});

export default router;
