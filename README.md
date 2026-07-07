# FMR Booking by FMR

A secure, reusable, white-label WordPress booking plugin designed for multi-client scalability and production-grade reliability.

## Core Features
- **Multi-Client Architecture:** Support for multiple client profiles and white-label branding presets.
- **Advanced Resource Management:** Manage typed resources including staff, rooms, and equipment with capacity rules.
- **Robust Booking Engine:** Real-time availability checking, slot generation, and conflict prevention with resource synchronization.
- **Automated Reminders:** Queue-based notification system with scheduled reminders (24h, 2h, etc.) and audit logs.
- **Approval Workflows:** Secure administrative oversight for booking confirmations, reschedules, and cancellations.
- **WooCommerce Integration:** Optional adapter for deposit payments and order-linked booking confirmations.
- **Security First:** Strict adherence to WordPress security standards, including nonces, capability checks, and SQL hardening.

## Architecture Overview
The plugin uses a modular, service-oriented architecture:
- `inc/Core`: Bootstrap, autoloader, and hook orchestration.
- `inc/Application`: Business logic layer (Repositories and Services).
- `inc/Database`: Schema definitions and migration runner.
- `inc/Admin`: Dashboard interfaces and management screens.
- `inc/Frontend`: Shortcodes, templates, and public-facing assets.
- `inc/Integrations`: External adapters (REST API, WooCommerce).
- `inc/Cron`: Scheduled background tasks.

## Installation
1. Upload the `fmr-booking` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure your Client Profiles and Branding Presets in the FMR Booking menu.
4. Use the shortcode `[fmr_booking_form client_slug="your-client-slug"]` to display the booking form.

## Security Standards
- **Nonces:** Required for all AJAX and REST operations.
- **Capabilities:** Admin actions restricted to `manage_options`.
- **Data Safety:** All inputs sanitized; all outputs escaped; all SQL uses `$wpdb->prepare()`.
- **Clean Uninstall:** Full cleanup of options, cron jobs, and custom tables upon deletion.

## Author
FMR - Production-grade solutions for WordPress.
