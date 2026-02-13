# Development Guide

## Running in wp-env
Typical commands (from your env folder):
- Start:
  - `npx @wordpress/env start`
- Stop:
  - `npx @wordpress/env stop`
- Run WP-CLI:
  - `npx @wordpress/env run cli wp plugin list`

## Shortcode Testing
Create a page containing:
- `[oras_member_hub]`

Optional widget test:
- `[oras_my_tickets]`

## Coding Standards
- Keep modules as functions returning strings (HTML).
- Always escape output (`esc_html`, `esc_url`, `wp_kses_post`).
- All module lists should remain filterable.

## Dependency Handling
- TEC:
  - check `function_exists('tribe_get_events')`
- Woo:
  - check `class_exists('WooCommerce')` or `function_exists('wc_get_account_endpoint_url')`
- ORAS-Tickets:
  - detect REST availability (best-effort) and fail gracefully.

## Git
If the repo has no remote configured:
- `git remote add origin <url>`
- push branch:
  - `git push -u origin master`
  - or rename to main: `git branch -M main && git push -u origin main`
