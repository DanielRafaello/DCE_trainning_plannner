# TrainingPlanner – Feature Ideas (not yet accepted)

> Proposals that are **not** part of any version yet. Accepted ideas were moved to
> [functional-requirements.md](functional-requirements.md) (v1.0.0) or [roadmap-v2.0.0.md](roadmap-v2.0.0.md).
> Effort: **S** = hours, **M** = a day or two, **L** = several days.

## Planning & tracking

| # | Idea | Why | Effort |
|---|---|---|---|
| I-01 | **Recurring events** – repeat daily / on chosen weekdays / weekly until a date or N times; edit "this one" vs. "all future". | Training plans are repetitive; copying a week (v2) only covers part of this. | L |
| I-04 | **Goals per category/subcategory** – e.g. "Running: 3×/week" or "Creative projects: 5 h/month", with progress in the stats. | Turns statistics into motivation. | M |
| I-07 | **Streaks** – consecutive days/weeks with at least one `done` event in a category/subcategory. | Simple and motivating gamification. | S |
| I-08 | **Archiving subcategories** – hide an unused subcategory from the picker but keep it in statistics. | Since subcategories with events can't be deleted (FR-SUB-06), this is the only way to tidy up the list. | S |

## Convenience

| # | Idea | Why | Effort |
|---|---|---|---|
| I-12 | **To-do → event** – create an event from text selected in a to-do list. | Connects the two halves of the app. | M |
| I-14 | **Full-text search** across events, day notes and to-dos. | Finding "when did I last…" | M |
| I-15 | **Pinned / colored to-do lists**. | Since lists act like notes, a little organisation helps. | S |
| I-16 | **Tags** on events (cross-category, e.g. `#outdoor`, `#with-friends`). | Statistics that don't fit the category tree. | M |
| I-20 | **Soft delete + undo** for events. | Prevents accidental data loss from drag & drop or delete clicks. | S |

## Integrations

| # | Idea | Why | Effort |
|---|---|---|---|
| I-17 | **iCal feed (read-only)** – a private URL to subscribe to in Google/Apple Calendar. | Planned events show up on the phone without a mobile app. | M |
| I-19 | **Reminders** – email/push before a planned event; a daily digest. | Needs a scheduler/worker – more infrastructure. | L |

> **I-08 (archiving)** is worth deciding before v1.0.0 ships. Without it, a user who stops doing an
> activity keeps its subcategory in the picker forever.
