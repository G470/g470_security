# G470 Security Plugin - Language Files

This directory contains translation files for the G470 Security plugin.

## Available Languages

- **German (de_DE)**: German translation
- **English (en_US)**: English translation (default)
- **Italian (it_IT)**: Italian translation

## File Types

- **`.pot`**: Portable Object Template - Template file for translators
- **`.po`**: Portable Object - Human-readable translation files
- **`.mo`**: Machine Object - Compiled translation files (used by WordPress)

## Text Domain

The plugin uses the text domain: `g470-gatonet-plugins`

## Updating Translations

When you add new translatable strings to the plugin code:

1. Update the `.pot` file with the new strings
2. Update each `.po` file with the new translations
3. Recompile the `.mo` files using msgfmt:

```bash
msgfmt -o g470-gatonet-plugins-de_DE.mo g470-gatonet-plugins-de_DE.po
msgfmt -o g470-gatonet-plugins-en_US.mo g470-gatonet-plugins-en_US.po
msgfmt -o g470-gatonet-plugins-it_IT.mo g470-gatonet-plugins-it_IT.po
```

## Adding New Languages

To add a new language:

1. Copy the `.pot` template file
2. Rename it to `g470-gatonet-plugins-{locale}.po` (e.g., `g470-gatonet-plugins-fr_FR.po` for French)
3. Update the header information with the language details
4. Translate all msgstr entries
5. Compile to `.mo` format using msgfmt

## Tools

You can use various tools to edit `.po` files:

- **Poedit** (https://poedit.net/) - Cross-platform translation editor
- **Loco Translate** - WordPress plugin for in-browser translation
- Any text editor (for manual editing)

## WordPress Integration

The plugin automatically loads the appropriate translation file based on:
- WordPress locale setting (Settings > General > Site Language)
- User locale setting (Users > Profile > Language)

## Testing Translations

To test a translation:
1. Change your WordPress language in Settings > General > Site Language
2. Clear any caches
3. Visit the plugin's admin pages to verify translations are applied
