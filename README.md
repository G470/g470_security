# G470 Security

Small utility plugin that protects the REST users endpoint. By default it blocks `/wp/v2/users` for everyone except logged-in users who have `list_users` (or another capability you choose in settings). Settings are stored using WordPress Options API (wp_options).

## Features
- Restricts `/wp/v2/users` via `rest_pre_dispatch`; returns `401` for guests and `403` for logged-in users lacking the capability.
- Admin settings page: Settings -> REST Users Protect with an enable toggle and a required capability field.
 - Settings stored in a single option `rup_options` with safe defaults and sanitization. Capability is selected from a dropdown of available site capabilities.
- Activation seeds defaults; deactivation removes the option.

## Installation
1) Copy the `g470_security` folder to `wp-content/plugins/`.
2) Activate **G470 Security** in wp-admin.
3) On activation the plugin creates the `rup_options` option with defaults.

## Settings
- Enable Restriction: on/off switch (default: on).
- Required Capability: capability string used for access (default: `list_users`). Input is sanitized to letters, numbers, underscores, and dashes.
- Storage: WordPress database option `rup_options`.

## Behavior
- When enabled, only logged-in users with the required capability can hit `/wp/v2/users`; others receive an error (`401` if not logged in, `403` if insufficient capability).
- If disabled, the endpoint behaves as WordPress core defines.
- Deactivation deletes the `rup_options` option; activation recreates it with defaults.

## Customization
- Change defaults via the admin page or by editing `wp-content/uploads/g470-security/settings.json` (booleans and strings only).
- Extend logic: add your own `rest_pre_dispatch` filter with a lower priority than `rup_rest_pre_dispatch` (it runs at priority 10).
- Localization: text domain `g470-gatonet-plugins`; add a `languages/` directory and load translations as needed.

## Testing quick steps
1) Logged out request to `/wp-json/wp/v2/users` -> expect 401.
2) Logged-in user without capability -> expect 403.
3) Logged-in user with capability (e.g., Administrator) -> endpoint works.

## Changelog
- 1.0.0: Initial release.
