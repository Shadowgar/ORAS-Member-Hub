# Architecture

## Bootstrap
`oras-member-hub.php` defines constants and includes:
- `includes/member-hub-modules.php`
- `includes/member-hub-shortcode.php`
- `includes/shortcodes/my-tickets.php`

Hooks are initialized on `plugins_loaded` via `oras_member_hub_bootstrap()`:
- `oras_member_hub_register_shortcode()`
- `oras_member_hub_register_my_tickets_shortcode()`

## `[oras_member_hub]` Rendering
`includes/member-hub-shortcode.php`:
- If user is not logged in, show login prompt with `wp_login_url(get_permalink())`.
- Main modules are defined in an array and can be modified via:
  - `oras_member_hub_main_modules`
- Sidebar modules are defined in an array and can be modified via:
  - `oras_member_hub_sidebar_modules`
- Final output can be modified via:
  - `oras_member_hub_render_output`

Modules are called only if `is_callable()`.

## Modules
`includes/member-hub-modules.php` provides:
- Main modules:
  - `oras_member_hub_module_conditions_summary()`
  - `oras_mh_module_upcoming_events()` (TEC + fallback; includes ongoing)
  - `oras_member_hub_module_my_tickets_reminders()` (placeholder)
  - `oras_member_hub_module_resources()`
  - `oras_member_hub_module_community_updates()`
- Sidebar modules:
  - `oras_member_hub_sidebar_module_membership_status()`
  - `oras_member_hub_sidebar_module_account_links()`
  - `oras_member_hub_sidebar_module_order_history()`
  - `oras_member_hub_sidebar_module_profile_settings()`

Also included:
- `oras_member_hub_account_url()` helper for Woo account endpoints
- `oras_member_hub_wrap_module()` wrapper utility for consistent markup

## Upcoming + Ongoing Events logic
`oras_mh_module_upcoming_events()` includes events whose end date is >= now.
- Preferred path: TEC functions (`tribe_get_events`, `tribe_get_start_date`)
- Fallback path: `WP_Query` on `tribe_events` with `_EventEndDate >= now`
- Output is filterable via:
  - `oras_mh_upcoming_events_limit`
  - `oras_mh_upcoming_events_items`

## `[oras_my_tickets]` Widget
`includes/shortcodes/my-tickets.php`:
- Enqueues:
  - `wp-api-fetch`
  - `assets/my-tickets.js`
- Localizes:
  - `restPathBase` (filterable: `oras_member_hub_my_tickets_rest_path_base`)
  - `nonce` (wp_rest)
- Renders a container with a loading message; JS fills it.

### Dependency expectations
The JS expects ORAS-Tickets to expose REST routes under `/oras-tickets/v1/...`.
Member Hub should remain resilient if ORAS-Tickets is missing:
- show friendly fallback messaging
- avoid console errors where possible

## Security
- Hub page requires `is_user_logged_in()`
- REST calls use WP REST nonce
- Do not expose ticket data to non-owners (enforced by ORAS-Tickets)
