// Backend returns both tokens in the JSON body and reads them only from the
// Authorization header / request body (no cookies), so they have to live in JS.
// - access token (short-lived JWT): memory only, never persisted
// - refresh token (30 days, rotated on every use): localStorage, so a page reload
//   can get a new access token via POST /auth/refresh

const REFRESH_TOKEN_KEY = 'tp_refresh_token';

let accessToken: string | null = null;

export const tokenStorage = {
  getAccessToken(): string | null {
    return accessToken;
  },

  getRefreshToken(): string | null {
    return localStorage.getItem(REFRESH_TOKEN_KEY);
  },

  setTokens(token: string, refreshToken: string): void {
    accessToken = token;
    localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken);
  },

  clear(): void {
    accessToken = null;
    localStorage.removeItem(REFRESH_TOKEN_KEY);
  },
};
