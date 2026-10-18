# TrainingPlanner – Functional Requirements

> Status: **draft** · Version: **1.0.0** · Scope: **backend (REST API)** consumed by a Vue SPA · Last updated: 2026-10-18
>
> Features planned for later are in [roadmap-v2.0.0.md](roadmap-v2.0.0.md); unaccepted proposals are in [ideas.md](ideas.md).

## 1. Overview

TrainingPlanner is a personal life/training planner built around a **weekly timetable** – it looks
like a school timetable, but with 7 days (Monday–Sunday). Users place **events** into the week
(in the past or the future), assign each event to a **category** and optionally a **subcategory**,
and the application tracks them over time. **Statistics** are computed on top of the tracked
events. Each day can have its own note, and every account has multiple global **to-do lists**
(note-like rich-text documents).

The backend is a stateless JSON REST API (Slim 4, PostgreSQL, JWT auth). The frontend is a separate
Vue application and is out of scope of this document except where it shapes the API.

### 1.1 Terminology

| Term | Meaning |
|---|---|
| **Week** | 7 consecutive days, Monday 00:00 – Sunday 23:59:59 in the user's timezone. |
| **Event** | A timetable entry with a start, end, category and optional subcategory and description. |
| **Category** | One of the fixed, system-wide top-level categories (see §4). Not editable by users. |
| **Subcategory** | A user-owned child of a category (e.g. *Running* under *Health and Fitness*), with its own color. |
| **Day note** | A free-text note attached to one calendar date of one user. |
| **To-do list** | A named, user-owned rich-text document (HTML), not bound to any date. The "list" structure exists only inside the HTML. |

### 1.2 Roles

| Role | Description |
|---|---|
| **Guest** | Unauthenticated. Can only register and log in. |
| **User** | Manages their own data only. Can never read or modify another user's data. |
| **Admin** | Everything a user can do, plus user administration (§10). Part of version 1.0.0. |

---

## 2. Authentication & Account

| ID | Requirement |
|---|---|
| FR-AUTH-01 | A guest can register with **name**, **email** and **password** (+ password confirmation). Email must be unique (case-insensitive). Password min. 8 characters. |
| FR-AUTH-02 | Successful registration logs the user in immediately (returns an access token, a refresh token and the user). |
| FR-AUTH-03 | A user can log in with email + password. Wrong credentials return a generic error that does not reveal whether the email exists. |
| FR-AUTH-04 | Authentication uses a short-lived JWT **access token** and a long-lived, rotating **refresh token** stored server-side (hashed). |
| FR-AUTH-05 | A user can exchange a valid refresh token for a new token pair. The used refresh token is invalidated (rotation). |
| FR-AUTH-06 | A user can log out, which revokes the given refresh token. |
| FR-AUTH-07 | A user can get their own profile (`GET /me`). |
| FR-AUTH-08 | A user can update their profile: name, email, **timezone** (IANA, e.g. `Europe/Prague`, default `Europe/Prague`). |
| FR-AUTH-09 | A user can change their password (requires the current password). This revokes all their other refresh tokens. |
| FR-AUTH-10 | A user can delete their own account (requires password). All their data is deleted (cascade). |

---

## 3. Weekly Timetable

| ID | Requirement |
|---|---|
| FR-WEEK-01 | The API returns the timetable for **one week** identified by any date within it (or by ISO week, e.g. `2026-W42`). The week always starts on **Monday**. |
| FR-WEEK-02 | The week response contains: the week range, all of the user's **events** overlapping the week, and the **day notes** for its 7 days. |
| FR-WEEK-03 | Any week can be requested – past, current or future – without limit. |
| FR-WEEK-04 | All dates/times in requests and responses are ISO 8601 **with offset** (`2026-10-20T06:00:00.000Z` or `…+02:00`); a time without an offset is rejected with `422`. In query strings the value must be URL-encoded – an unencoded `+` is decoded as a space (`encodeURIComponent`, or use the `Z` form). Times are stored in UTC and the week boundaries are computed in the user's timezone. |

---

## 4. Categories (fixed)

| ID | Requirement |
|---|---|
| FR-CAT-01 | The system has the following **fixed** categories, inserted by an idempotent seeder (`CategorySeeder`, part of `composer db:seed`) – required in every environment. Users cannot create, rename or delete them. |
| FR-CAT-02 | Each category has a stable machine **slug** (used by the frontend for icons/colors/translations) and an English display name. |
| FR-CAT-03 | Any authenticated user can list all categories. |

| Slug | Name |
|---|---|
| `adventure_travel` | Adventure and Travel |
| `wealth_lifestyle_legacy` | Wealth, Lifestyle and Legacy |
| `creative_projects_artistic_expression` | Creative Projects and Artistic Expression |
| `career_creative_work` | Career and Creative Work |
| `relationship_love_family` | Relationship, Love and Family |
| `health_fitness` | Health and Fitness |
| `spirituality_soul_inner_work` | Spirituality, Soul and Inner Work |
| `connection_community` | Connection and Community |
| `other` | Other |

---

## 5. Subcategories (user-owned)

| ID | Requirement |
|---|---|
| FR-SUB-01 | A user can create a subcategory under any category (e.g. *Health and Fitness → Running, Cycling, Swimming*). |
| FR-SUB-02 | Subcategories belong to exactly one user and one category; other users never see them. |
| FR-SUB-03 | The subcategory name is 1–100 characters and unique per user within the same category (case-insensitive). |
| FR-SUB-03a | Each subcategory has a **color** (hex `#RRGGBB`). It is optional on create – if missing, the API assigns one from a default palette. The frontend uses it to color events in the timetable. |
| FR-SUB-04 | A user can list their subcategories (all, or filtered by category). |
| FR-SUB-05 | A user can rename a subcategory and change its color. Moving it to another category is **not** allowed (it would corrupt statistics). |
| FR-SUB-06 | A user can delete a subcategory **only if no events reference it**. Otherwise the API returns `409 Conflict` with the number of linked events (`subcategory.has_events:<count>`). The database enforces this too (`fk_events_subcategory`, `ON DELETE NO ACTION` – behaves like `RESTRICT`, but still lets a user delete cascade remove events and subcategories together). |
| FR-SUB-07 | Subcategories are managed from the user's profile in the UI; the API provides plain CRUD endpoints. |

---

## 6. Events

| ID | Requirement |
|---|---|
| FR-EVT-01 | A user can create an event with: **title** (1–150 chars), **start** and **end** datetime, **category** (required), **subcategory** (optional) and **description** (optional, up to 5,000 chars). |
| FR-EVT-02 | `end` must be after `start`. An event can be at most **24 hours** long and may cross midnight (it then shows in both days). Multi-day and all-day events are **not** supported. |
| FR-EVT-03 | If a subcategory is given, it must belong to the user **and** to the chosen category. |
| FR-EVT-04 | Events can be created in the **past** (logging what was done) or the **future** (planning). |
| FR-EVT-05 | Each event has a **status**: `planned`, `done` or `skipped` (it was planned but didn't happen). New events in the future default to `planned` and events in the past default to `done`. The user can change the status at any time, in any direction. |
| FR-EVT-06 | Events may overlap; the API does not reject overlaps. |
| FR-EVT-07 | A user can get a single event, update any of its fields (full or partial update – needed for drag & drop in the timetable), and delete it. |
| FR-EVT-08 | A user can list events in an arbitrary date range, optionally filtered by category, subcategory and status (used by the week view and statistics). |
| FR-EVT-09 | A user can access only their own events; the API returns `404` for another user's event (not `403`, so it does not reveal that the event exists). |

---

## 7. Day Notes

| ID | Requirement |
|---|---|
| FR-DAY-01 | A user can attach one text note (up to 5,000 chars) to any calendar date. |
| FR-DAY-02 | A day note is created or replaced by an upsert on the date (`PUT /days/{date}/note`); saving an empty note deletes it. |
| FR-DAY-03 | Day notes are returned as part of the week response (FR-WEEK-02) and can also be fetched by date. |

---

## 8. To-do Lists (global, per account)

| ID | Requirement |
|---|---|
| FR-TODO-01 | A user can have **multiple** to-do lists, each with a **title** (1–100 chars). Lists are not tied to any date. |
| FR-TODO-02 | The body of a list is **one HTML string** (`content`, up to 100 kB). Items, checkboxes and formatting exist only inside the HTML (produced by the frontend's rich-text editor); the backend does not store individual items. |
| FR-TODO-03 | A user can create a list, rename it, replace its content and delete a list. Lists are ordered by last update (most recently edited first). |
| FR-TODO-04 | The backend **sanitizes** `content` on every save against an allowlist of tags/attributes (see NFR-10) – it never stores raw user HTML. |
| FR-TODO-05 | Listing returns titles and metadata (`id`, `title`, `updated_at`) **without** `content`; the content is fetched per list. |

---

## 9. Statistics

> Detailed statistics will be specified later. This section defines the baseline the API must support.

| ID | Requirement |
|---|---|
| FR-STAT-01 | Statistics are computed for a chosen **period**: week, month, year or a custom date range. |
| FR-STAT-02 | For each **category**, the API returns: total time (minutes) and number of events. |
| FR-STAT-03 | The same breakdown is available per **subcategory** (within a category, and events without a subcategory are reported as "unassigned"). |
| FR-STAT-04 | Category and subcategory statistics always return minutes and counts **split by status** (`planned`, `done`, `skipped`), so the client can show "what actually happened" (`done`, the default view), all events, or plan vs. reality from one response – they have no status filter. The time series (FR-STAT-05) can be restricted to one or more statuses (`status=done,skipped`; default: `done` only). |
| FR-STAT-07 | **Plan vs. reality**: for each category/subcategory in the period, the API returns minutes and counts for `done` and `skipped`, plus a completion rate = `done / (done + skipped)` (by count and by time). |
| FR-STAT-05 | A time series is available: totals per category grouped by day / week / month within the period (for charts). |
| FR-STAT-06 | Events crossing a period boundary count only the part inside the period. |

---

## 10. Administration

| ID | Requirement |
|---|---|
| FR-ADM-01 | An admin can list users (paginated, searchable by name/email). |
| FR-ADM-02 | An admin can change a user's role and block/unblock a user. A blocked user cannot log in or refresh tokens, and their existing access token is rejected immediately (`403`). A role change takes effect on the user's next request. An admin cannot demote or block themselves, and the last admin cannot be demoted. |
| FR-ADM-03 | An admin **cannot** read other users' events, notes or to-dos (privacy). |

---

## 11. Non-functional Requirements

| ID | Requirement |
|---|---|
| NFR-01 | JSON REST API; consistent error format `{ error, status, message, errors? }`; validation errors return `422` with per-field messages. |
| NFR-02 | All endpoints except register/login/refresh/logout require a valid access token. On every request the user is loaded from the database: a deleted account gets `401`, a blocked one `403`, and the current role (not the one in the token) is used for authorization. |
| NFR-03 | Every query for user data is scoped by the authenticated user's ID (ownership enforced in the repository/service layer). |
| NFR-04 | Rate limiting on all endpoints; stricter limits on login/register. |
| NFR-05 | CORS restricted to the frontend origin; security headers on all responses, including error responses. |
| NFR-06 | Passwords are hashed with Argon2id; refresh tokens are stored hashed (SHA-256). |
| NFR-07 | The schema is managed by Doctrine Migrations; the fixed categories are inserted by a seeder (`migrations/Seeders`), not by a migration. |
| NFR-08 | The week endpoint responds within 200 ms for a typical week (≤ 100 events), so it needs indexes on `(user_id, start_at)`. |
| NFR-09 | Business logic is covered by automated tests (unit tests for services/validation, integration tests for endpoints). |
| NFR-10 | To-do HTML is sanitized server-side by `App\Infrastructure\HTMLContentSanitizer` (`symfony/html-sanitizer`), aligned with the Quill editor. Allowlist: `p, br, span, blockquote, pre, strong, b, em, i, u, s, h1–h4, ul, ol, li, a, table, thead, tbody, tr, th, td`; attributes `class` (Quill formatting classes), `style` on `span, p, li` (Quill colors/sizes), `data-list` on `li` and `data-checked` on `ul` (checklists), `href, title, target` on `a` (only `http(s)`/`mailto`, `rel="noopener noreferrer"` is forced), `colspan, rowspan` on cells. Everything else is removed, including `on*` attributes, `script` and `iframe`. Max. input 100 kB; the length is checked again after sanitizing. |

---

## 12. Data Model (sketch)

```
users            (id, name, email UNIQUE, password_hash, role, timezone, blocked_at, created_at, updated_at)
refresh_tokens   (id, user_id FK, token_hash, expires_at, revoked_at, created_at)
categories       (id, slug UNIQUE, name, position)                      -- fixed, seeded
subcategories    (id, user_id FK, category_id FK, name, color CHAR(7), created_at, updated_at)
                 UNIQUE (user_id, category_id, lower(name))
events           (id, user_id FK, category_id FK, subcategory_id NULL,
                  FK (subcategory_id, user_id, category_id) → subcategories (id, user_id, category_id)  -- blocks delete,
                  title, description, start_at, end_at,
                  status CHECK (status IN ('planned','done','skipped')), created_at, updated_at)
                 INDEX (user_id, start_at)
day_notes        (id, user_id FK, date, content, updated_at)  UNIQUE (user_id, date)
todo_lists       (id, user_id FK, title, content TEXT, created_at, updated_at)
```

## 13. API Endpoints (sketch)

| Method | Path | FR |
|---|---|---|
| POST | `/auth/register`, `/auth/login`, `/auth/refresh`, `/auth/logout` | AUTH-01…06 |
| GET / PATCH / DELETE | `/me` | AUTH-07, 08, 10 |
| PUT | `/me/password` | AUTH-09 |
| GET | `/weeks/{date}` | WEEK-01…04 |
| GET | `/categories` | CAT-03 |
| GET / POST | `/subcategories` (`?category=`) | SUB-01, 04 |
| PATCH / DELETE | `/subcategories/{id}` | SUB-03a, 05, 06 |
| GET / POST | `/events` (`?from=&to=&category=&subcategory=&status=done,skipped`) | EVT-01, 08 |
| GET / PATCH / DELETE | `/events/{id}` | EVT-07 |
| GET / PUT | `/days/{date}/note` | DAY-01…03 |
| GET / POST | `/todo-lists` | TODO-01, 03, 05 |
| GET / PATCH / DELETE | `/todo-lists/{id}` | TODO-02…04 |
| GET | `/stats/categories?from=&to=` | STAT-01, 02, 04, 06, 07 |
| GET | `/stats/subcategories?from=&to=&category=` | STAT-03, 07 |
| GET | `/stats/timeline?from=&to=&groupBy=day\|week\|month&status=` | STAT-04, 05 |
| GET / PATCH | `/admin/users`, `/admin/users/{id}` | ADM-01, 02 |

## 14. Decisions

| # | Question | Decision |
|---|---|---|
| Q1 | Events longer than 24 h / multi-day events? | **No.** Max 24 h per event (FR-EVT-02). |
| Q2 | Deleting a subcategory that has events? | **Not allowed** – `409 Conflict` (FR-SUB-06). |
| Q3 | Is `planned` / `done` enough for tracking? | **No** – a `skipped` status is added (FR-EVT-05, FR-STAT-07). |
| Q4 | First day of the week? | **Always Monday** (FR-WEEK-01). |
| Q5 | Admin role in the first version? | **Yes**, from the start (§10). |
