# Roadmap

## Phase 1 — Scaffold (Done)

- Shortcode-based hub output
- Two-column layout (main + sidebar)
- Modular function-based rendering
- Filterable module lists
- Basic guest gating

## Phase 2 — Data Integration (Active)

### 2.1 Events (Done / verify)

- Upcoming + ongoing events via TEC
- Fallback query for `tribe_events`
- Mark ongoing events

### 2.2 Tickets (Next)

Goal: show user’s tickets for upcoming/ongoing events, with view/print.

- Use `[oras_my_tickets]` widget or call shared rendering function.
- Define and document ORAS-Tickets REST API contract.
- Add graceful fallback messaging if ORAS-Tickets not installed.

### 2.3 Resources

- Decide source of resources:
  - CPT
  - category/tag
  - download plugin integration
- Display: title, type, link, access indicator.

### 2.4 Observing Conditions

- Integrate Open-Meteo (no API key).
- Cache with transients (5–10 minutes).
- Output minimal “snapshot tiles”:
  - cloud cover
  - wind
  - temperature
  - moon phase
  - sunset/sunrise

## Phase 3 — Personalization

- Cross-highlight events the user has tickets for
- Reminders/callouts for next attended event
- Membership level / renewal awareness (if PMPro/Woo memberships are used)

## Phase 4 — UX + Performance

- Add CSS (cards, spacing, responsive)
- Sticky sidebar behavior (CSS)
- Add light JS enhancements only where needed
- Audit queries and caching

## Phase 5 — Observatory Power Features (Future)

- Dark sky / seeing forecasts (if feasible)
- Equipment checklists / observing plans
- Member activity feed (optional)
- Discord integration surfacing content
