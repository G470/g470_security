# G470 Security

Small utility plugin that protects the REST users endpoint. By default it blocks `/wp/v2/users` for everyone except logged-in users who have `list_users` (or another capability you choose in settings). Settings are stored using WordPress Options API (wp_options).

## Features

- Restricts `/wp/v2/users` via `rest_pre_dispatch`; returns `401` for guests and `403` for logged-in users lacking the capability.
- Admin settings page: Settings → G470 Security with an enable toggle and a required capability field.
- Settings stored in a single option `g470_security_options` with safe defaults and sanitization. Capability is selected from a dropdown of available site capabilities.
- Activation seeds defaults; deactivation removes the option.
- **Modular architecture:** Easy to extend with additional security features, admin components, and future GitHub-based updates.

## Installation

1) Copy the `g470_security` folder to `wp-content/plugins/`.
2) Activate **G470 Security** in wp-admin.
3) On activation the plugin creates the `g470_security_options` option with defaults.

## Settings

- Enable Protection: on/off switch (default: on).
- Required Capability: capability string used for access (default: `list_users`). Selected from dropdown of available site capabilities.
- Storage: WordPress database option `g470_security_options`.

## Behavior

- When enabled, only logged-in users with the required capability can hit `/wp/v2/users`; others receive an error (`401` if not logged in, `403` if insufficient capability).
- If disabled, the endpoint behaves as WordPress core defines.
- Deactivation deletes the `g470_security_options` option; activation recreates it with defaults.

## Architecture

The plugin now follows a modular class-based architecture. See [docs/g470_security/ARCHITECTURE.md](../docs/g470_security/ARCHITECTURE.md) for detailed documentation on:

- Directory structure
- Class responsibilities
- Extension points for adding new features
- Testing strategies
- Multisite considerations

**Key directories:**

- `includes/` – Core plugin logic (orchestrator, activator, deactivator)
- `admin/` – Admin UI, settings, and view templates
- `security/` – Security features (REST API filtering, capability management)
- `updater/` – Placeholder for future GitHub-based update system

## Customization

- Change defaults via the admin page (Settings → G470 Security).
- Programmatically access settings: `$options = wp_parse_args( get_option( 'g470_security_options' ), array( 'g470_security_enabled' => true, 'g470_security_capability' => 'list_users' ) );`
- Add new security features by creating classes in `security/` and registering them in `G470_Security_Plugin`.
- Extend logic: add your own `rest_pre_dispatch` filter with a lower priority than `G470_Security_REST_Security::filter_users_endpoint()` (it runs at priority 10).
- Localization: text domain `g470-gatonet-plugins`; add `.po`/`.mo` files to `languages/` directory.

## Testing quick steps

1) Logged out request to `/wp-json/wp/v2/users` → expect 401.
2) Logged-in user without capability → expect 403.
3) Logged-in user with capability (e.g., Administrator) → endpoint works.

## Changelog

- **1.0.0** (2026-01-21): Modular architecture release with class-based design, separation of concerns, and extensibility framework.
