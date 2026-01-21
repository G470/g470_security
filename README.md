# G470 Security

Small utility plugin that protects the REST users endpoint. By default it blocks `/wp/v2/users` for everyone except logged-in users who have `list_users` (or another capability you choose in settings). Settings are stored using WordPress Options API (wp_options).

## Features

- Restricts `/wp/v2/users` via `rest_pre_dispatch`; returns `401` for guests and `403` for logged-in users lacking the capability.
- **Tabbed settings interface:** Organized settings with General Settings and Available Patches tabs.
- **Modular patch system:** Enable/disable security modules with individual configuration options.
- Admin settings page: Settings → G470 Security with an enable toggle and a required capability field.
- Settings stored in a single option `g470_security_options` with safe defaults and sanitization. Capability is selected from a dropdown of available site capabilities.
- Activation seeds defaults; deactivation removes the option.
- **Modular architecture:** Easy to extend with additional security features, admin components, and future GitHub-based updates.
- **GitHub-based updates:** Configure a GitHub repository to receive automatic plugin updates from releases.

## Installation

1) Copy the `g470_security` folder to `wp-content/plugins/`.
2) Activate **G470 Security** in wp-admin.
3) On activation the plugin creates the `g470_security_options` option with defaults.

## Settings

### General Settings Tab
- **GitHub Repository:** Full repository URL for automatic plugin updates (e.g., `https://github.com/yourusername/g470_security`)
- **GitHub Token:** Optional Personal Access Token for private repositories

### Available Patches Tab
- Lists all security modules and patches
- **REST Users Protection:** Core module (always active, locked)
  - Click "Configure Settings" to access module-specific configuration:
    - Enable/disable protection toggle
    - Required capability dropdown
- Additional modules can be registered and toggled here

All settings stored in WordPress database option `g470_security_options`.

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

### Setting Up GitHub Updates

1. **Create a GitHub Repository** (public or private) for your plugin.
2. **Configure in Settings:**
   - Go to Settings → G470 Security → Plugin Update Settings
   - Enter your repository URL: `https://github.com/yourusername/g470_security`
   - For private repos: Add a GitHub Personal Access Token with `repo` scope
3. **Create Releases:**
   - Tag your releases with version numbers (e.g., `v1.0.1`, `v1.1.0`)
   - WordPress will check for updates every 12 hours
   - Updates appear in Dashboard → Updates like any other plugin

**Example: Creating a release**
```bash
# Tag the current commit
git tag v1.0.1

# Push the tag to GitHub
git push origin v1.0.1

# Or create a release via GitHub UI
# Go to Releases → Draft a new release → Create tag v1.0.1
```

## Testing quick steps

1) Logged out request to `/wp-json/wp/v2/users` → expect 401.
2) Logged-in user without capability → expect 403.
3) Logged-in user with capability (e.g., Administrator) → endpoint works.

## Changelog

- **1.0.0** (2026-01-21): Modular architecture release with class-based design, separation of concerns, extensibility framework, GitHub-based updater system, and tabbed settings interface with module management.
