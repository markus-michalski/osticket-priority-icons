# osTicket Priority Icons Plugin

Replaces priority text labels with visual color-coded icons in osTicket's Staff Control Panel.

## Features

- Visual priority indicators (Emergency, High, Normal, Low)
- Color-coded icons with Bootstrap-inspired palette
- CSS-only tooltips showing priority name
- Pulsing animation for Emergency tickets
- WCAG-compliant accessibility support
- PJAX-compatible (works with dynamic navigation)
- Zero core file modifications

## Screenshots

### Before
| Emergency | High | Normal | Low |
|-----------|------|--------|-----|
| Emergency | High | Normal | Low |

### After
| Emergency | High | Normal | Low |
|-----------|------|--------|-----|
| Red Circle (pulsing) | Orange Circle | Yellow Circle | Green Circle |

## Requirements

- osTicket 1.18.x
- PHP 8.1 or higher
- Modern browser (ES6+ support)

## Installation

### Manual Installation

1. Download the plugin files
2. Copy to `include/plugins/priority-icons/`
3. Navigate to Admin Panel > Manage > Plugins
4. Find "Priority Icons" and click "Install"
5. Enable the plugin

### Directory Structure

```
include/plugins/priority-icons/
├── plugin.php                    # Plugin metadata
├── class.PriorityIconsPlugin.php # Main plugin class
├── config.php                    # Configuration form
├── assets/
│   ├── .htaccess                 # Security rules
│   ├── priority-icons.css        # Stylesheet
│   └── priority-icons.js         # JavaScript
└── README.md
```

## Configuration

After installation, configure the plugin in Admin Panel > Manage > Plugins > Priority Icons:

| Option | Default | Description |
|--------|---------|-------------|
| Enable Priority Icons | Yes | Master toggle for the plugin |
| Show Tooltips | Yes | Display priority name on hover |
| Icon Style | Circle | Visual style (Circle, Badge, Dot) |
| Emergency Color | #dc3545 | Red color for emergency |
| High Color | #fd7e14 | Orange color for high |
| Normal Color | #ffc107 | Yellow color for normal |
| Low Color | #28a745 | Green color for low |

## How It Works

1. Plugin registers a signal handler for `apps.scp` (Staff Control Panel)
2. On each page load, CSS and JavaScript assets are injected
3. JavaScript scans the DOM for priority text using TreeWalker API
4. Matching text is replaced with styled icon elements
5. MutationObserver re-scans after PJAX navigation

## Technical Details

### Signal-Based Injection

```php
Signal::connect('apps.scp', [$this, 'injectAssets'], 'PriorityIconsPlugin');
```

### XSS Prevention

- All URLs escaped via `Format::htmlchars()`
- JSON config uses `JSON_HEX_TAG | JSON_HEX_APOS`
- PHP execution blocked in assets directory

### Performance

- TreeWalker with early-exit filtering
- Debounced MutationObserver (100ms)
- Cache-busting via file modification time
- Deferred script loading

### Accessibility

- Screen reader text (`.sr-only` class)
- `aria-label` attributes on icons
- `prefers-reduced-motion` support
- Native browser tooltips

## Customization

### Custom Colors via CSS

Override colors in your osTicket theme:

```css
:root {
    --priority-emergency: #ff0000;
    --priority-high: #ff6600;
    --priority-normal: #ffcc00;
    --priority-low: #00cc00;
}
```

### Icon Styles

Three built-in styles via admin config:

- **Circle** (default): Solid colored circle
- **Badge**: Rounded badge with optional text
- **Dot**: Minimal 8px indicator

## Troubleshooting

### Icons Not Appearing

1. Verify plugin is enabled in Admin Panel
2. Check browser console for JavaScript errors
3. Ensure assets are accessible (check `.htaccess`)
4. Clear browser cache

### Icons Appear Twice

If icons duplicate after navigation:
- The plugin checks for `data-priority-icon` attribute
- If still duplicating, clear osTicket cache

### Custom Priority Names

If you've renamed priorities (e.g., "Urgent" instead of "Emergency"):

1. Edit `class.PriorityIconsPlugin.php`
2. Update the `$priorityMap` array
3. Clear browser cache

## Development

### Running Tests

```bash
composer install
./vendor/bin/phpunit
```

### Project Structure

```
osticket-priority-icons/
├── assets/               # Static assets
├── docs/                 # Documentation
├── tests/                # PHPUnit tests
├── class.PriorityIconsPlugin.php
├── config.php
├── plugin.php
├── composer.json
└── phpunit.xml
```

## License

GPL-2.0-or-later (compatible with osTicket)

## Author

Markus Michalski

## Changelog

### 1.0.0 (2026-01-22)

- Initial release
- Signal-based CSS/JS injection
- Configurable colors and styles
- PJAX support
- Accessibility features

## Credits

- osTicket Team for the excellent plugin architecture
- Bootstrap for color inspiration
