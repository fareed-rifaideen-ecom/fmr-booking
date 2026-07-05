# FMR Booking by FMR

A secure, reusable, white-label WordPress booking plugin.

## Project Goal
Build a production-grade booking system that supports:
- Client profiles and branding presets.
- Resource management (staff, rooms, equipment).
- Approval workflows and reminder queues.
- Optional WooCommerce integration.

## Architecture
The plugin follows a modular architecture:
- `inc/Core`: Core bootstrap, loader, and internationalization.
- `inc/Admin`: Admin-specific functionality.
- `inc/Frontend`: Public-facing functionality.
- `inc/Database`: Custom table schema and migrations (Phase 2).
- `assets`: CSS, JS, and images.
- `templates`: Replaceable frontend templates.

## Security
- Nonce verification for all actions.
- Capability checks for admin actions.
- Sanitization and escaping of all data.
- Prepared SQL statements for database operations.

## Author
FMR
