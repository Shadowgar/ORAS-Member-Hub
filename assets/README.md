# ORAS Member Hub

ORAS Member Hub is a WordPress plugin for the Oil Region Astronomical Society (ORAS) that provides a member-facing dashboard (“command center”) experience.

The hub is designed to be:
- modular
- dependency-aware (TEC/Woo/ORAS-Tickets may or may not be active)
- performance-conscious
- easy to extend via filters

## Key Features (Current)
- `[oras_member_hub]` shortcode renders a two-column dashboard layout:
  - Main modules: observing conditions, upcoming/ongoing events, ticket reminders, resources, community updates
  - Sidebar modules: membership status placeholder, account links, orders, profile/settings
- `Upcoming Events` module supports:
  - The Events Calendar (TEC) via `tribe_get_events()`
  - Fallback query on `tribe_events` with `_EventStartDate` / `_EventEndDate`
  - Ongoing events are included (`_EventEndDate >= now`) and labeled “(Ongoing)”
- `[oras_my_tickets]` shortcode renders a JS-powered “My Tickets” widget
  - Uses `wp-api-fetch`
  - Calls ORAS-Tickets REST API (base path default: `/oras-tickets/v1`)
  - Base path is filterable: `oras_member_hub_my_tickets_rest_path_base`

## Shortcodes
- Member Hub page:
  - `[oras_member_hub]`
- Tickets widget:
  - `[oras_my_tickets]`

## Repo Layout
