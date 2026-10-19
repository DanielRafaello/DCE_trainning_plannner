import axios from 'axios';
import type { AxiosError, AxiosInstance, AxiosRequestConfig, InternalAxiosRequestConfig } from 'axios';
import { getApiBaseUrl } from './apiConfig';
import { tokenStorage } from './tokenStorage';

interface AuthTokensResponse {
  token: string;
  refresh_token: string;
}

type RetriableConfig = InternalAxiosRequestConfig & { _retried?: boolean };

// /auth/* endpoints are public and their 401 means "bad credentials / bad refresh token",
// not "access token expired" - they must never trigger the refresh flow.
const isAuthEndpoint = (url?: string): boolean => !!url && /^\/?auth\//.test(url);

const axiosInstance: AxiosInstance = axios.create({
  baseURL: getApiBaseUrl(),
  timeout: 15000,
  headers: { 'Content-Type': 'application/json' },
});

// Called when the session can't be recovered (refresh failed, account blocked).
// Wire it up from the auth store / router, e.g. to redirect to the login page.
let onSessionExpired: () => void = () => {};

export const setOnSessionExpired = (handler: () => void): void => {
  onSessionExpired = handler;
};

// Request interceptor – attach JWT access token
axiosInstance.interceptors.request.use((config) => {
  const token = tokenStorage.getAccessToken();
  if (token && !isAuthEndpoint(config.url)) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// The refresh token is rotated and reusing an old one revokes ALL sessions of the user,
// so refreshes must never run in parallel - neither within a tab (shared promise)
// nor across tabs (Web Locks + re-reading the token from localStorage inside the lock).
let refreshPromise: Promise<string> | null = null;

const doRefresh = async (): Promise<string> => {
  const refreshToken = tokenStorage.getRefreshToken();
  if (!refreshToken) {
    throw new Error('No refresh token');
  }

  // Bare axios, not axiosInstance, so the interceptors don't loop
  const { data } = await axios.post<AuthTokensResponse>(
    `${getApiBaseUrl()}/auth/refresh`,
    { refreshToken },
    { timeout: 15000 },
  );
  tokenStorage.setTokens(data.token, data.refresh_token);
  return data.token;
};

const refreshAccessToken = (): Promise<string> => {
  if (!refreshPromise) {
    // doRefresh reads the refresh token only once it holds the lock, so it always
    // uses the latest one even if another tab rotated it in the meantime
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

    if (status === 401 && !config._retried && tokenStorage.getRefreshToken()) {
      config._retried = true;
      try {
        const token = await refreshAccessToken();
        config.headers.Authorization = `Bearer ${token}`;
        return axiosInstance(config);
      } catch (refreshError) {
        tokenStorage.clear();
        onSessionExpired();
        return Promise.reject(refreshError);
      }
    }

    // 401 after a fresh token (deleted account) or 403 for a blocked account
    const message = (error.response?.data as { message?: string } | undefined)?.message;
    if (status === 401 || (status === 403 && message === 'auth.account_blocked')) {
      tokenStorage.clear();
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
