import { computed, ref } from 'vue';
import { defineStore } from 'pinia';
import { getAuth, getMe } from '@/services/api';
import {
  Role,
  type LoginRequest,
  type RegisterRequest,
  type User
} from '@/services/api/trainingPlannerAPI.schemas';
import { useCatalogStore } from '@/stores/catalog';

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null);

  const isAuthenticated = computed(() => user.value !== null);
  const isAdmin = computed(() => user.value?.role === Role.admin);

  // The tokens are HttpOnly cookies, so JS can't tell whether a session exists –
  // the first GET /me (from the router guard) decides. It runs only once per page load.
  let loadPromise: Promise<void> | null = null;

  function loadUser(): Promise<void> {
    loadPromise ??= getMe()
      .getMe()
      .then((response) => {
        user.value = response.user;
      })
      .catch(() => {
        user.value = null;
      });
    return loadPromise;
  }

  function setUser(newUser: User | null): void {
    // another account (or none) – drop the previous user's cached data
    if (newUser?.id !== user.value?.id) {
      useCatalogStore().reset();
    }
    user.value = newUser;
    loadPromise = Promise.resolve();
  }

  async function login(credentials: LoginRequest): Promise<void> {
    const response = await getAuth().login(credentials);
    setUser(response.user);
  }

  async function register(data: RegisterRequest): Promise<void> {
    const response = await getAuth().register(data);
    setUser(response.user);
  }

  async function logout(): Promise<void> {
    try {
      await getAuth().logout();
    } finally {
      // the cookies may already be gone server-side, the local session ends either way
      setUser(null);
    }
  }

  return { user, isAuthenticated, isAdmin, loadUser, setUser, login, register, logout };
});
