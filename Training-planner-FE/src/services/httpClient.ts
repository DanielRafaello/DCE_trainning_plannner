import axios from 'axios';
import type { AxiosError, AxiosInstance, AxiosRequestConfig, InternalAxiosRequestConfig } from 'axios';
import { getApiBaseUrl } from './apiConfig';

// Auth is cookie-based: the backend sets HttpOnly access_token / refresh_token cookies on
// login / register / refresh and the browser sends them automatically. JS never sees the tokens.

type RetriableConfig = InternalAxiosRequestConfig & { _retried?: boolean };

// /auth/* endpoints are public and their 401 means "bad credentials / bad refresh token",
// not "access token expired" - they must never trigger the refresh flow.
const isAuthEndpoint = (url?: string): boolean => !!url && /^\/?auth\//.test(url);

const axiosInstance: AxiosInstance = axios.create({
  baseURL: getApiBaseUrl(),
  timeout: 15000,
  headers: { 'Content-Type': 'application/json' },
  // Needed only if the API runs on a different origin than the app (through the Vite proxy it's the same)
  withCredentials: true,
});

// Called when the session can't be recovered (refresh failed, account blocked).
// Wire it up from the auth store / router, e.g. to redirect to the login page.
let onSessionExpired: () => void = () => {};

export const setOnSessionExpired = (handler: () => void): void => {
  onSessionExpired = handler;
};

// The refresh token is rotated and reusing an old one revokes ALL sessions of the user,
// so refreshes must never run in parallel - neither within a tab (shared promise)
// nor across tabs (Web Locks; the browser sends the latest cookie once the lock is held).
let refreshPromise: Promise<void> | null = null;

const doRefresh = async (): Promise<void> => {
  // Bare axios, not axiosInstance, so the interceptors don't loop
  await axios.post(`${getApiBaseUrl()}/auth/refresh`, undefined, {
    timeout: 15000,
    withCredentials: true,
  });
};

export const refreshSession = (): Promise<void> => {
  if (!refreshPromise) {
    refreshPromise = (navigator.locks
      ? navigator.locks.request('tp-token-refresh', doRefresh)
      : doRefresh()
    ).finally(() => {
      refreshPromise = null;
    });
  }
  return refreshPromise;
};

// Response interceptor – refresh expired access token once, then retry the request
axiosInstance.interceptors.response.use(
  (response) => response,
  async (error: AxiosError) => {
    const config = error.config as RetriableConfig | undefined;
    const status = error.response?.status;

    if (!config || isAuthEndpoint(config.url)) {
      return Promise.reject(error);
    }

    if (status === 401 && !config._retried) {
      config._retried = true;
      try {
        await refreshSession();
        return axiosInstance(config);
      } catch (refreshError) {
        onSessionExpired();
        return Promise.reject(refreshError);
      }
    }

    // 401 after a fresh token (deleted account) or 403 for a blocked account
    const message = (error.response?.data as { message?: string } | undefined)?.message;
    if (status === 401 || (status === 403 && message === 'auth.account_blocked')) {
      onSessionExpired();
    }

    return Promise.reject(error);
  },
);

// Orval mutator
export const httpClient = <T>(
  config: AxiosRequestConfig,
  options?: AxiosRequestConfig,
): Promise<T> => {
  return axiosInstance({
    ...config,
    ...options,
  }).then((response) => response.data);
};

export type ErrorType<Error> = AxiosError<Error>;
export type BodyType<BodyData> = BodyData;

export default axiosInstance;
