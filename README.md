# ORAS Member Hub

Member-facing dashboard hub plugin for the Oil Region Astronomical Society (ORAS).

## Overview

ORAS Member Hub provides a shortcode-driven members area with:

- a modular main dashboard
- a modular account sidebar
- guest gating for protected content
- integrations for events, tickets, and membership/account links
- filterable extension points for customization

## Requirements

- WordPress 6.0+
- PHP 7.4+
- Optional integrations:
  - The Events Calendar (TEC)
  - ORAS-Tickets plugin / REST routes
  - WooCommerce account endpoints
  - Paid Memberships Pro (PMPro)

## Installation

1. Place this plugin folder in your WordPress plugins directory.
2. Activate ORAS Member Hub in WordPress Admin.
3. Add the main shortcode to a page:
   - `[oras_member_hub]`
4. Optional standalone tickets widget shortcode:
   - `[oras_my_tickets]`

## Shortcodes

### Main hub

- `[oras_member_hub]`

Renders the full member dashboard layout.

### Tickets widget

- `[oras_my_tickets]`

Renders the tickets widget container used by the frontend script.

## Module Architecture

Core bootstrap:

- `oras-member-hub.php`

Primary implementation files:

- `includes/member-hub-shortcode.php`
- `includes/member-hub-modules.php`
- `includes/shortcodes/my-tickets.php`
- `includes/services/class-oras-mh-conditions-service.php`

Main modules include:

- Conditions summary
- Upcoming events
- My tickets / reminders
- Resources
- Community updates

Sidebar modules include:

- Membership status
- Account links
- Order history
- Profile settings

## Hooks and Filters

Common extension points include:

- `oras_member_hub_main_modules`
- `oras_member_hub_sidebar_modules`
- `oras_member_hub_render_output`
- `oras_mh_upcoming_events_limit`
- `oras_mh_upcoming_events_items`
- `oras_member_hub_my_tickets_rest_path_base`
- `oras_member_hub_my_tickets_print_path`

## Data and Integration Behavior

- Events use TEC APIs when available, with fallback querying.
- Tickets rely on ORAS-Tickets REST routes and degrade gracefully when unavailable.
- Membership and account links adapt based on PMPro and Woo availability.
- Conditions module uses Open-Meteo and transient caching.

## Development

### Local wp-env

- Start: `npx @wordpress/env start`
- Stop: `npx @wordpress/env stop`
- WP-CLI: `npx @wordpress/env run cli wp plugin list`

### Quality checks

Install tooling:

- `composer install`

Run full local quality gate:

- `composer quality`

Run CI-equivalent subset:

- `composer quality:ci`

Quality pipeline includes:

- PHP syntax lint
- PHPStan static analysis
- PHPCS (WordPress coding standards)

## CI

GitHub Actions workflow:

- `.github/workflows/static-analysis.yml`

Runs on pull requests and pushes to main/master.

## Security Notes

- Hub access is gated for logged-in users.
- REST calls use WP REST nonces where applicable.
- Data ownership checks are expected from upstream systems (for example ORAS-Tickets).

## Project Status

Current roadmap focus:

- complete and refine data integration for tickets/resources/conditions
- improve personalization and member context
- iterate on UX/performance
- evaluate future observatory-focused power features

See full roadmap in:

- `docs/ROADMAP.md`
