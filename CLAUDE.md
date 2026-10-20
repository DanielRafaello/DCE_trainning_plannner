# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

TrainingPlanner is a personal weekly-timetable planner: events placed in a Mon–Sun week, assigned to fixed system categories and user-owned subcategories, plus day notes, rich-text to-do lists, statistics and admin user management.

- `backend/` – PHP 8.5 JSON REST API (Slim 4, PHP-DI, Doctrine DBAL, PostgreSQL 18, JWT).
- `Training-planner-FE/` – Vue 3 + TypeScript + Vite SPA (Pinia, vue-router, axios, Quill). Czech UI. Plain CSS with design tokens, no Tailwind.
- Specs: `backend/docs/functional-requirements.md` (v1.0.0 scope; requirement IDs such as `FR-EVT-01` are referenced from code docblocks), `backend/docs/roadmap-v2.0.0.md` (accepted future work), `backend/docs/ideas.md` (unaccepted proposals), and `backend/docs/openapi.yaml` (API contract).

## Running the stack

Everything runs through the root `docker-compose.yml` (the empty `backend/docker-compose.yml` is unused):

```sh
docker compose up --build
```

- Backend (Apache): http://localhost:8080
- Frontend (Vite dev server): http://localhost:5173. `/api/*` is proxied to the backend with the `/api` prefix stripped (`vite.config.ts`, `BACKEND_URL`).
- Postgres: localhost:5432 (db/user/password `myapp`). Adminer: http://localhost:8081

`vendor/`, `var/cache` and `node_modules` are anonymous volumes inside the containers, so run composer/npm commands **inside the containers**, not on the Windows host:

```sh
docker compose exec backend composer <script>
docker compose exec training-planner-fe npm run <script>
```

## Backend commands (composer scripts)

- `composer migrate`, `composer migrate:generate`: Doctrine migrations in `migrations/Migrations/`
- `composer db:seed`: `bin/seed.php` → `migrations/Seeders/DatabaseSeeder` (categories + a guest user with sample data)
- `composer db:fresh`: roll back all migrations, migrate, then seed
- `composer db:truncate`
- `composer format`: php-cs-fixer (`.php-cs-fixer.dist.php`)
- `composer test` / `test:unit` / `test:int`: Pest. The `tests/` directory does not exist yet. Run a single test with `./vendor/bin/pest path/to/FooTest.php --filter "name"`.

**DI container compilation is always on.** After you add or change bindings in `app/dependencies.php`, delete `var/cache/CompiledContainer.php` inside the container, or the old wiring stays in use.

## Backend architecture (Action–Domain–Responder)

Request flow: `public/index.php` → `app/bootstrap.php` (dotenv, PHP-DI, then `app/middleware.php` and `app/routes.php`) → Action.

- **Action** (`src/Action/<Area>/*Action.php`): one invokable class per endpoint. Its docblock documents the method and path, auth, body, response and error codes, so keep that docblock in sync. It builds a FormRequest, throws `ValidationException($form->errors())` if validation fails, calls a service with `(int) $request->getAttribute('user_id')`, and returns through a Responder.
- **Requests** (`src/Requests/`): extend `App\Validation\FormRequest`, which is Laravel-style: a `rules()` string syntax handled by the custom `Validator`, input trimmed automatically, and `safe()` returning only the fields named in the rules.
- **Services** (`src/Services/`): business rules. Each one is bound to an interface in `src/Services/Contracts/`.
- **Repositories** (`src/Repositories/`): raw SQL via DBAL `Connection`, bound to interfaces in `src/Repositories/Contracts/`. Every query is scoped by `user_id`. Rows map to immutable `src/Domain/*` objects through `::fromRow()`. `ReadModel/Statistics` holds the statistics projections.
- **Responder** (`src/Responder/`): extends `JsonResponder`.
- New interface → implementation pairs, and classes that need primitive constructor args (env values), must be registered in `app/dependencies.php`. Anything else is autowired.

Conventions:
- **Errors**: throw `AppException` subclasses (`src/Exceptions/generalExceptions/*`, `ValidationException`). `HttpErrorHandler` renders them as `{error, status, message, errors?}`. Messages and validation errors are **translation keys** for the frontend (e.g. `not.found.event`, `event.end_before_start`, `auth.account_blocked`), not human-readable text.
- **Domain constants mirror DB constraints**, e.g. `Event::TITLE_MAX_LENGTH` matches `VARCHAR(150)`. Change both together.
- **Migrations** are hand-written SQL in `addSql(<<<'SQL' ... SQL)`, one table per migration. Times are stored as `timestamptz`, and the API exchanges ISO 8601 with offset.
- **Middleware order matters** (Slim runs the last added first). In `app/middleware.php`, CORS is outermost so that error responses also get CORS headers, then security headers, error middleware, routing, origin check (CSRF) and rate limit (DB-backed, 50 req/60 s). On route groups, `JwtAuthMiddleware` is added *after* `AdminOnlyMiddleware` so that it runs first.

### Auth model

- Tokens travel only in **HttpOnly cookies** (`src/Infrastructure/AuthCookies.php`): `access_token` (JWT, `Path=/`, Lax) and `refresh_token` (opaque, `Path=/auth`, Strict). They never appear in response bodies.
- Refresh tokens are rotated on every `/auth/refresh`. Reusing an old one revokes all of the user's sessions.
- `JwtAuthMiddleware` reloads the user from the DB on every request. A deleted user gets 401, and a blocked user gets 403 `auth.account_blocked`. It sets the `user_id` and `role` request attributes.
- CSRF protection: `OriginCheckMiddleware` rejects state-changing requests whose `Origin` differs from `FRONTEND_URL`.

## Frontend

Scripts (in `Training-planner-FE/`): `npm run dev`, `npm run build` (vue-tsc type-check + vite build), `npm run type-check`, `npm run test:unit` (Vitest, jsdom; for a single file use `npx vitest run path/to/file.spec.ts`, or add `-t "name"`), `npm run lint` (oxlint then eslint, both with `--fix`), `npm run format` (prettier on `src/`), `npm run generate:api`.

- **API client is generated by Orval** from `openapi/openapi.yaml` into `src/services/api/` (split by tag, axios client). Don't edit those files by hand. `openapi/openapi.yaml` is a copy of `backend/docs/openapi.yaml`: when the API changes, update the backend spec, copy it over, then run `npm run generate:api`.
- All generated calls go through the mutator `src/services/httpClient.ts`. It uses `withCredentials` cookie auth and, on a 401, refreshes the session once and then retries the request. Refreshes are serialized within a tab (shared promise) and across tabs (`navigator.locks`), because parallel refreshes would trigger the reuse detection and revoke all of the user's sessions. `/auth/*` responses never trigger a refresh. `setOnSessionExpired()` is the hook for redirecting to login.
- `VITE_API_BASE_URL=/api` (`.env.development`) and goes through the Vite proxy. The proxy also rewrites the refresh cookie path `/auth` → `/api/auth`.
- Path alias `@` → `src/`.

Frontend structure and conventions:
- **Routing** (`src/router/index.ts`): `/auth/*` under `AuthLayout` (guests only), everything else under `DefaultLayout` (calendar `/`, `/todo/:id?`, `/statistics`, `/profile`, `/admin`). A `beforeEach` guard reads `meta.requiresAuth / guestOnly / requiresAdmin` and calls `useAuthStore().loadUser()` (one `GET /me` per page load, because JS can't see the HttpOnly cookies).
- **Stores**: `auth` (current user) and `catalog` (fixed categories + the user's subcategories, shared by the calendar, profile and statistics). `auth.setUser()` resets `catalog` when the user changes.
- **Times**: `src/lib/dates.ts` computes everything in the **user's profile timezone** (`User.timezone`) via `Intl`, not the browser's. "Date keys" are `YYYY-MM-DD`. Send instants as `toISOString()`. Calendar layout lives in `lib/eventLayout.ts` and drag & drop math in `lib/reschedule.ts`. Both are covered by Vitest specs in `src/lib/__tests__/`.
- **Forms**: `composables/useForm.ts` handles client validation (mirrors the backend rules in `lib/validation.ts`), maps 422 field errors to inputs and shows other errors as one message. All backend error keys and English validator sentences are translated to Czech in `lib/apiErrors.ts`. Add new keys there.
- **Styling**: tokens in `src/assets/variables.css` (light + dark), shared classes (`.card`, `.btn-*`, `.input`, `.field`, `.segmented`, `.event` glass card) in `components.css`. Component-specific CSS is scoped.
- **Dialogs**: `components/ui/BaseModal.vue` (native `<dialog>`) is mounted with `v-if` and emits `close`. Use `ConfirmButton` instead of `window.confirm`.
- **To-do content** is Quill 2 HTML read from `quill.root.innerHTML` (not `getSemanticHTML`, which has a known XSS advisory). The backend sanitizes it against a Quill-aligned allowlist.
- **Charts** (`components/stats/`): the muted category colors fail categorical-palette checks, so charts don't rely on them. Categories are identified by text labels, and statuses by lightness and hatching of a single hue.
- **Containers**: after changing `package.json`, also run `docker compose exec training-planner-fe npm install`, because the container has its own `node_modules` volume.
