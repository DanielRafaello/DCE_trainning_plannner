# TrainingPlanner – Roadmap v2.0.0

> Status: **accepted, not scheduled** · Implemented **after** version 1.0.0
> ([functional-requirements.md](functional-requirements.md)) is finished.
> Requirements here are intentionally coarse; each feature gets a detailed spec before implementation.

## 1. Copy a Day / a Week

| ID | Requirement |
|---|---|
| FR2-COPY-01 | A user can copy all events of one **day** to another day. Times stay the same, only the date shifts. |
| FR2-COPY-02 | A user can copy all events of one **week** to another week (same weekday and time). |
| FR2-COPY-03 | Copied events get the status `planned` if they end up in the future, otherwise `done` (same defaults as FR-EVT-05). Descriptions are copied; day notes are not. |
| FR2-COPY-04 | Copying never deletes or overwrites events already in the target day/week; it only adds. The response returns the created events. |

Endpoints (sketch): `POST /days/{date}/copy { "to": "2026-10-20" }`, `POST /weeks/{date}/copy { "to": "2026-10-26" }`.

## 2. Event Templates

| ID | Requirement |
|---|---|
| FR2-TPL-01 | A user can save an event template: title, duration, category, optional subcategory and description. |
| FR2-TPL-02 | A user can create an event from a template by giving only the start time. |
| FR2-TPL-03 | A user can create a template from an existing event. |
| FR2-TPL-04 | Templates are user-owned, with CRUD. Deleting a subcategory that a template references is blocked, the same as with events (FR-SUB-06). |

Data model (sketch): `event_templates (id, user_id, title, duration_minutes, category_id, subcategory_id NULL, description, created_at, updated_at)`.

## 3. Life Balance (Wheel of Life)

| ID | Requirement |
|---|---|
| FR2-LIFE-01 | For a chosen period, the API returns the share of time (minutes and %) spent in each of the 9 fixed categories, including the ones with zero time. |
| FR2-LIFE-02 | Two periods can be compared, e.g. this month vs. last month. |

Endpoint (sketch): `GET /stats/life-balance?from=&to=&compareFrom=&compareTo=`. It can be built on the same aggregation as FR-STAT-02.

## 4. Training-specific Features (Garmin-like)

> Details are **to be decided later**. The goal is to stay close to what Garmin Connect offers for activities, so
> that data can be compared with Garmin (and maybe imported from it).

Scope candidates:

- **Activity metrics on events**: distance, duration vs. moving time, pace/speed, elevation gain, average/max heart rate, calories, training effect/intensity, which metrics are available is configured per subcategory (e.g. Running → distance, pace, HR).
- **Subjective feedback**: perceived effort (RPE 1–10), how the user felt (like Garmin's post-activity rating).
- **Weekly summaries / training load** per subcategory (e.g. km per week, time in intensity zones).
- **Personal records** per subcategory (longest run, fastest 5 km, …).
- **Weekly review** note (what went well / what to improve).
- *(Possible)* import of activities from Garmin (FIT/GPX/TCX file upload or the Garmin API).

Open points: which metrics are in the first iteration, units (metric/imperial), whether metrics are stored as fixed columns or a flexible `event_metrics (event_id, metric, value)` table.

## 5. Data Export

| ID | Requirement |
|---|---|
| FR2-EXP-01 | A user can download all their data (profile, subcategories, events, day notes, to-do lists, templates) as **JSON**. |
| FR2-EXP-02 | Events can also be exported as **CSV** for a chosen date range (for spreadsheets). |
| FR2-EXP-03 | The export contains only the requesting user's data and never contains password hashes or tokens. |

Endpoints (sketch): `GET /me/export` (JSON), `GET /events/export.csv?from=&to=`.
