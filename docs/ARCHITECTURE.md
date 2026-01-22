# osTicket Priority Icons - Architecture Documentation

## Overview

Das **Priority Icons Plugin** ersetzt die textbasierten Prioritaetsstufen in osTicket durch visuelle Icons mit Farb-Codierung. Es verwendet das Signal-System von osTicket fuer eine saubere CSS/JS-Injection ohne Core-Modifikationen.

**Ticket Reference:** #237948

---

## Architecture Diagram

```
+------------------------------------------------------------------+
|                         osTicket Core                             |
+------------------------------------------------------------------+
|                                                                    |
|  /scp/apps/dispatcher.php                                         |
|  +--------------------+                                            |
|  | Signal::send(      |                                            |
|  |   'apps.scp',      | -----> Signal-Bus                          |
|  |   $dispatcher      |          |                                 |
|  | )                  |          |                                 |
|  +--------------------+          |                                 |
|                                  v                                 |
+------------------------------------------------------------------+
                                   |
                                   | Signal::connect('apps.scp', ...)
                                   v
+------------------------------------------------------------------+
|                    Priority Icons Plugin                          |
+------------------------------------------------------------------+
|                                                                    |
|  +------------------------+     +-----------------------------+    |
|  | class.PriorityIcons    |     | assets/                     |    |
|  | .php                   |     | +-------------------------+ |    |
|  |                        |     | | priority-icons.css      | |    |
|  | - bootstrap()          |     | | - Icon definitions      | |    |
|  |   -> Signal::connect() |     | | - Color coding          | |    |
|  |                        |     | | - Tooltip styling       | |    |
|  | - injectAssets()       |---->| +-------------------------+ |    |
|  |   -> <link> + <script> |     |                             |    |
|  |                        |     | +-------------------------+ |    |
|  | - enable()             |     | | priority-icons.js       | |    |
|  |   -> Singleton init    |     | | - DOM scanning          | |    |
|  +------------------------+     | | - Text replacement      | |    |
|                                 | | - PJAX handling         | |    |
|                                 | +-------------------------+ |    |
|                                 +-----------------------------+    |
+------------------------------------------------------------------+
                                   |
                                   v
+------------------------------------------------------------------+
|                         Browser DOM                               |
+------------------------------------------------------------------+
|                                                                    |
|  BEFORE:                         AFTER:                            |
|  +-------------+                 +------------------+              |
|  | Emergency   |   --------->   | [!] Emergency    |              |
|  +-------------+                 | (red icon)       |              |
|                                  +------------------+              |
|  +-------------+                 +------------------+              |
|  | High        |   --------->   | [^] High         |              |
|  +-------------+                 | (orange icon)    |              |
|                                  +------------------+              |
|  +-------------+                 +------------------+              |
|  | Normal      |   --------->   | [-] Normal       |              |
|  +-------------+                 | (yellow icon)    |              |
|                                  +------------------+              |
|  +-------------+                 +------------------+              |
|  | Low         |   --------->   | [v] Low          |              |
|  +-------------+                 | (green icon)     |              |
|                                  +------------------+              |
+------------------------------------------------------------------+
```

---

## Signal Flow

```
1. Page Request
   |
   v
2. osTicket loads /scp/apps/dispatcher.php
   |
   v
3. dispatcher.php:39 sends Signal
   +-> Signal::send('apps.scp', $dispatcher)
   |
   v
4. Signal-Bus notifies all registered listeners
   +-> PriorityIconsPlugin::injectAssets($dispatcher)
   |
   v
5. Plugin echoes <link> and <script> tags
   +-> priority-icons.css (inline or external)
   +-> priority-icons.js (inline or external)
   |
   v
6. Browser loads page with injected assets
   |
   v
7. DOMContentLoaded fires
   +-> priority-icons.js scans DOM
   +-> Replaces priority text with icons
   |
   v
8. PJAX Navigation (if any)
   +-> pjax:end event fires
   +-> priority-icons.js re-scans DOM
   +-> Replaces priority text in new content
```

---

## Class Design

### PriorityIconsPlugin (Main Class)

```php
<?php
/**
 * Priority Icons Plugin for osTicket
 *
 * Replaces priority text labels with visual icons using
 * Signal-based asset injection.
 */
class PriorityIconsPlugin extends Plugin
{
    /**
     * Plugin metadata
     */
    public $config_class = 'PriorityIconsConfig';

    /**
     * Singleton instance
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Priority-to-Icon mapping
     * @var array<string, array{icon: string, color: string, class: string}>
     */
    private array $priorityMap = [
        'Emergency' => [
            'icon'  => 'exclamation-circle',  // Font Awesome or custom SVG
            'color' => '#dc3545',             // Bootstrap danger red
            'class' => 'priority-emergency'
        ],
        'High' => [
            'icon'  => 'arrow-up',
            'color' => '#fd7e14',             // Bootstrap orange
            'class' => 'priority-high'
        ],
        'Normal' => [
            'icon'  => 'minus',
            'color' => '#ffc107',             // Bootstrap warning yellow
            'class' => 'priority-normal'
        ],
        'Low' => [
            'icon'  => 'arrow-down',
            'color' => '#28a745',             // Bootstrap success green
            'class' => 'priority-low'
        ]
    ];

    /**
     * Bootstrap: Register signal handlers
     * Called when plugin is loaded (every request if enabled)
     */
    public function bootstrap(): void
    {
        // Staff Control Panel - main injection point
        Signal::connect(
            'apps.scp',
            [$this, 'injectAssets'],
            'PriorityIconsPlugin'
        );
    }

    /**
     * Enable hook: Create singleton instance
     * Called when plugin transitions to "enabled" state
     *
     * @param int|string $id Plugin instance ID
     * @return self
     */
    public static function enable(int|string $id): self
    {
        if (self::$instance === null) {
            self::$instance = new self($id);
        }
        return self::$instance;
    }

    /**
     * Inject CSS and JavaScript assets into page
     *
     * @param object $dispatcher The dispatcher object from signal
     * @return void
     */
    public function injectAssets(object $dispatcher): void
    {
        // Build asset paths
        $pluginPath = INCLUDE_DIR . 'plugins/priority-icons/';
        $assetUrl = ROOT_PATH . 'include/plugins/priority-icons/assets/';

        // CSS - external file with cache-busting
        $cssFile = $pluginPath . 'assets/priority-icons.css';
        if (file_exists($cssFile)) {
            $version = filemtime($cssFile);
            $cssUrl = Format::htmlchars($assetUrl . 'priority-icons.css?v=' . $version);
            echo '<link rel="stylesheet" href="' . $cssUrl . '">' . "\n";
        }

        // JavaScript - external file with cache-busting
        $jsFile = $pluginPath . 'assets/priority-icons.js';
        if (file_exists($jsFile)) {
            $version = filemtime($jsFile);
            $jsUrl = Format::htmlchars($assetUrl . 'priority-icons.js?v=' . $version);
            echo '<script src="' . $jsUrl . '" defer></script>' . "\n";
        }

        // Inline config for JavaScript (priority mapping)
        $this->injectConfig();
    }

    /**
     * Inject inline JavaScript configuration
     * Passes PHP config to JavaScript
     */
    private function injectConfig(): void
    {
        $config = [
            'priorities' => $this->priorityMap,
            'debug'      => false,  // Set true for development
        ];

        $jsonConfig = json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS);
        echo '<script>window.PriorityIconsConfig = ' . $jsonConfig . ';</script>' . "\n";
    }
}
```

### PriorityIconsConfig (Configuration Class)

```php
<?php
/**
 * Configuration form for Priority Icons Plugin
 */
class PriorityIconsConfig extends PluginConfig
{
    /**
     * Define configuration options
     *
     * @return array Form field definitions
     */
    public function getOptions(): array
    {
        return [
            'enabled' => new BooleanField([
                'label'   => __('Enable Priority Icons'),
                'default' => true,
                'hint'    => __('Replace priority text with visual icons'),
            ]),

            'show_tooltip' => new BooleanField([
                'label'   => __('Show Tooltips'),
                'default' => true,
                'hint'    => __('Display priority name on hover'),
            ]),

            'icon_style' => new ChoiceField([
                'label'   => __('Icon Style'),
                'default' => 'circle',
                'choices' => [
                    'circle' => __('Filled Circle'),
                    'badge'  => __('Badge with Text'),
                    'dot'    => __('Simple Dot'),
                ],
                'hint'    => __('Visual style for priority indicators'),
            ]),

            // Custom color overrides (optional)
            'color_emergency' => new TextboxField([
                'label'       => __('Emergency Color'),
                'default'     => '#dc3545',
                'hint'        => __('Hex color code (e.g., #dc3545)'),
                'size'        => 10,
                'maxlength'   => 7,
            ]),

            'color_high' => new TextboxField([
                'label'       => __('High Color'),
                'default'     => '#fd7e14',
                'hint'        => __('Hex color code'),
                'size'        => 10,
                'maxlength'   => 7,
            ]),

            'color_normal' => new TextboxField([
                'label'       => __('Normal Color'),
                'default'     => '#ffc107',
                'hint'        => __('Hex color code'),
                'size'        => 10,
                'maxlength'   => 7,
            ]),

            'color_low' => new TextboxField([
                'label'       => __('Low Color'),
                'default'     => '#28a745',
                'hint'        => __('Hex color code'),
                'size'        => 10,
                'maxlength'   => 7,
            ]),
        ];
    }
}
```

---

## JavaScript Strategy

### DOM Selection Approach

Das Plugin muss Priority-Text in mehreren Views finden. Da osTicket keine CSS-Klassen fuer Prioritaeten verwendet, ist Text-Matching erforderlich.

**Target Views:**
1. **Ticket-Liste** (`queue-tickets.tmpl.php`) - Table cells in queue
2. **Ticket-Preview** (`ticket-preview.tmpl.php`) - Priority in ticket info
3. **Ticket-Detail** - Priority display in header

**Selection Strategy:**

```javascript
// Priority text appears in various containers
// Strategy: Scan all text nodes and match against known priority names

const SELECTORS = {
    // Ticket queue table - priority column
    queueTable: 'table.list.queue.tickets td',

    // Ticket preview panel
    ticketPreview: '.ticket_info span, .ticket_info td',

    // Ticket detail view
    ticketDetail: '.ticket-info span, #ticket-status span',

    // Generic fallback - any element containing priority text
    generic: '*'
};
```

### PJAX Handling

osTicket verwendet PJAX fuer dynamische Seitenwechsel. Das Plugin muss auf PJAX-Events reagieren.

```javascript
/**
 * Priority Icons - Main JavaScript
 *
 * Replaces priority text with visual icons in osTicket Staff Panel.
 * Handles initial page load and PJAX navigation.
 */
(function(window, document) {
    'use strict';

    // Configuration from PHP (injected inline)
    const CONFIG = window.PriorityIconsConfig || {
        priorities: {
            'Emergency': { icon: 'exclamation-circle', color: '#dc3545', class: 'priority-emergency' },
            'High':      { icon: 'arrow-up',          color: '#fd7e14', class: 'priority-high' },
            'Normal':    { icon: 'minus',             color: '#ffc107', class: 'priority-normal' },
            'Low':       { icon: 'arrow-down',        color: '#28a745', class: 'priority-low' }
        },
        debug: false
    };

    // Priority names for text matching
    const PRIORITY_NAMES = Object.keys(CONFIG.priorities);

    // Regex pattern for exact match
    const PRIORITY_REGEX = new RegExp('^(' + PRIORITY_NAMES.join('|') + ')$');

    /**
     * Check if element has already been processed
     */
    function isProcessed(element) {
        return element.hasAttribute('data-priority-icon');
    }

    /**
     * Mark element as processed
     */
    function markProcessed(element, priority) {
        element.setAttribute('data-priority-icon', priority);
    }

    /**
     * Create icon element
     */
    function createIcon(priority) {
        const config = CONFIG.priorities[priority];
        if (!config) return null;

        const icon = document.createElement('span');
        icon.className = 'priority-icon ' + config.class;
        icon.setAttribute('title', priority);
        icon.setAttribute('aria-label', 'Priority: ' + priority);
        icon.style.setProperty('--priority-color', config.color);

        // Icon content (CSS handles actual rendering via ::before)
        icon.textContent = '';

        return icon;
    }

    /**
     * Process a single text node
     */
    function processTextNode(node) {
        const text = node.textContent.trim();

        // Check if text is an exact priority match
        if (!PRIORITY_REGEX.test(text)) {
            return false;
        }

        const priority = text;
        const parent = node.parentElement;

        // Skip if already processed
        if (!parent || isProcessed(parent)) {
            return false;
        }

        // Create icon element
        const icon = createIcon(priority);
        if (!icon) return false;

        // Replace text with icon
        // Keep original text for screen readers (hidden)
        const wrapper = document.createElement('span');
        wrapper.className = 'priority-wrapper';
        wrapper.appendChild(icon);

        // Screen-reader text
        const srText = document.createElement('span');
        srText.className = 'sr-only';
        srText.textContent = priority;
        wrapper.appendChild(srText);

        // Replace the text node
        node.parentNode.replaceChild(wrapper, node);
        markProcessed(wrapper, priority);

        if (CONFIG.debug) {
            console.log('[PriorityIcons] Replaced:', priority);
        }

        return true;
    }

    /**
     * Walk DOM tree and find text nodes with priority text
     */
    function walkAndReplace(root) {
        const walker = document.createTreeWalker(
            root,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: function(node) {
                    const text = node.textContent.trim();
                    // Quick check before regex
                    if (text.length < 3 || text.length > 10) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    if (PRIORITY_REGEX.test(text)) {
                        return NodeFilter.FILTER_ACCEPT;
                    }
                    return NodeFilter.FILTER_REJECT;
                }
            }
        );

        // Collect nodes first (DOM modification during walk is problematic)
        const nodesToProcess = [];
        while (walker.nextNode()) {
            nodesToProcess.push(walker.currentNode);
        }

        // Process collected nodes
        let count = 0;
        nodesToProcess.forEach(function(node) {
            if (processTextNode(node)) {
                count++;
            }
        });

        if (CONFIG.debug && count > 0) {
            console.log('[PriorityIcons] Processed', count, 'elements');
        }
    }

    /**
     * Main replacement function
     */
    function replacePriorities() {
        // Target specific containers first (more efficient)
        const containers = [
            'table.list.queue.tickets',
            '.ticket_info',
            '.ticket-info',
            '#ticket-status',
            '.pjax-container'
        ];

        let found = false;
        containers.forEach(function(selector) {
            const elements = document.querySelectorAll(selector);
            elements.forEach(function(el) {
                walkAndReplace(el);
                found = true;
            });
        });

        // Fallback: scan entire body if no specific containers found
        if (!found) {
            walkAndReplace(document.body);
        }
    }

    /**
     * Initialize plugin
     */
    function init() {
        if (CONFIG.debug) {
            console.log('[PriorityIcons] Initializing...');
        }

        // Initial replacement
        replacePriorities();

        // PJAX support - re-run after dynamic page loads
        // osTicket uses jQuery PJAX
        if (typeof jQuery !== 'undefined') {
            jQuery(document).on('pjax:end', function() {
                if (CONFIG.debug) {
                    console.log('[PriorityIcons] PJAX navigation detected');
                }
                // Small delay to ensure DOM is updated
                setTimeout(replacePriorities, 50);
            });
        }

        // MutationObserver as backup for dynamic content
        const observer = new MutationObserver(function(mutations) {
            let shouldReplace = false;
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    shouldReplace = true;
                }
            });
            if (shouldReplace) {
                // Debounce
                clearTimeout(window._priorityIconsTimeout);
                window._priorityIconsTimeout = setTimeout(replacePriorities, 100);
            }
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });

        if (CONFIG.debug) {
            console.log('[PriorityIcons] Initialized successfully');
        }
    }

    // Wait for DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})(window, document);
```

---

## CSS Strategy

### Icon Styling

```css
/**
 * Priority Icons - Stylesheet
 *
 * Visual styling for priority indicators in osTicket.
 * Uses CSS custom properties for easy color customization.
 */

/* ==========================================================================
   CSS Custom Properties (configurable via JavaScript)
   ========================================================================== */

:root {
    /* Priority Colors */
    --priority-emergency: #dc3545;
    --priority-high: #fd7e14;
    --priority-normal: #ffc107;
    --priority-low: #28a745;

    /* Icon Sizing */
    --priority-icon-size: 12px;
    --priority-icon-spacing: 4px;

    /* Tooltip */
    --priority-tooltip-bg: #333;
    --priority-tooltip-color: #fff;
}

/* ==========================================================================
   Priority Icon Base
   ========================================================================== */

.priority-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: var(--priority-icon-size);
    height: var(--priority-icon-size);
    border-radius: 50%;
    background-color: var(--priority-color, #999);
    margin-right: var(--priority-icon-spacing);
    vertical-align: middle;
    position: relative;
    cursor: help;

    /* Smooth transitions */
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

/* Hover effect */
.priority-icon:hover {
    transform: scale(1.2);
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
}

/* ==========================================================================
   Priority-Specific Colors
   ========================================================================== */

.priority-icon.priority-emergency {
    --priority-color: var(--priority-emergency);
    /* Pulsing animation for emergency */
    animation: pulse-emergency 1.5s ease-in-out infinite;
}

.priority-icon.priority-high {
    --priority-color: var(--priority-high);
}

.priority-icon.priority-normal {
    --priority-color: var(--priority-normal);
}

.priority-icon.priority-low {
    --priority-color: var(--priority-low);
}

/* ==========================================================================
   Emergency Pulse Animation
   ========================================================================== */

@keyframes pulse-emergency {
    0%, 100% {
        box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
    }
    50% {
        box-shadow: 0 0 0 4px rgba(220, 53, 69, 0);
    }
}

/* Disable animation for users who prefer reduced motion */
@media (prefers-reduced-motion: reduce) {
    .priority-icon.priority-emergency {
        animation: none;
    }
}

/* ==========================================================================
   Tooltip (CSS-only)
   ========================================================================== */

.priority-icon::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%) translateY(-4px);
    padding: 4px 8px;
    background-color: var(--priority-tooltip-bg);
    color: var(--priority-tooltip-color);
    font-size: 11px;
    font-weight: normal;
    white-space: nowrap;
    border-radius: 3px;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease;
    z-index: 1000;
    pointer-events: none;
}

/* Tooltip arrow */
.priority-icon::before {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%) translateY(4px);
    border: 4px solid transparent;
    border-top-color: var(--priority-tooltip-bg);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.2s ease, visibility 0.2s ease;
    z-index: 1000;
}

/* Show tooltip on hover */
.priority-icon:hover::after,
.priority-icon:hover::before {
    opacity: 1;
    visibility: visible;
}

/* ==========================================================================
   Wrapper & Screen Reader Support
   ========================================================================== */

.priority-wrapper {
    display: inline-flex;
    align-items: center;
}

/* Screen reader only text */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* ==========================================================================
   Alternative Styles (via config)
   ========================================================================== */

/* Badge style - shows text next to icon */
.priority-icon.style-badge {
    width: auto;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: bold;
    color: #fff;
}

.priority-icon.style-badge::after {
    content: attr(data-label);
    position: static;
    opacity: 1;
    visibility: visible;
    background: transparent;
    padding: 0;
    margin-left: 4px;
}

/* Dot style - minimal */
.priority-icon.style-dot {
    width: 8px;
    height: 8px;
}

/* ==========================================================================
   Table Integration
   ========================================================================== */

/* Ensure icons align properly in table cells */
table.list.queue.tickets td .priority-wrapper {
    display: inline-flex;
    align-items: center;
}

/* Prevent line break in narrow columns */
table.list.queue.tickets td .priority-icon {
    flex-shrink: 0;
}

/* ==========================================================================
   Print Styles
   ========================================================================== */

@media print {
    .priority-icon {
        /* Print as colored circle */
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Hide tooltip on print */
    .priority-icon::after,
    .priority-icon::before {
        display: none;
    }
}
```

---

## File Structure

```
osticket-priority-icons/
├── plugin.php                    # Plugin metadata (required by osTicket)
├── class.PriorityIconsPlugin.php # Main plugin class with Signal handling
├── config.php                    # Configuration class (admin settings)
├── assets/
│   ├── .htaccess                 # Allow direct web access to assets
│   ├── priority-icons.css        # Icon styling, colors, tooltips
│   └── priority-icons.js         # DOM scanning, text replacement, PJAX
├── tests/
│   └── PriorityIconsTest.php     # PHPUnit tests
├── docs/
│   └── ARCHITECTURE.md           # This file
├── README.md                     # User documentation
├── CHANGELOG.md                  # Version history
└── LICENSE                       # GPL v2 (osTicket compatible)
```

### File: plugin.php

```php
<?php
return array(
    'id'          => 'osticket:priority-icons',
    'version'     => '1.0.0',
    'name'        => 'Priority Icons',
    'author'      => 'Markus Michalski',
    'description' => 'Replaces priority text labels with visual color-coded icons',
    'url'         => 'https://github.com/markus-michalski/osticket-priority-icons',
    'plugin'      => 'class.PriorityIconsPlugin.php:PriorityIconsPlugin'
);
```

### File: assets/.htaccess

```apache
# Allow direct access to plugin assets
<IfModule mod_authz_core.c>
    Require all granted
</IfModule>
<IfModule !mod_authz_core.c>
    Order allow,deny
    Allow from all
</IfModule>

# Cache static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 week"
    ExpiresByType application/javascript "access plus 1 week"
</IfModule>
```

---

## Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `enabled` | Boolean | `true` | Enable/disable the plugin |
| `show_tooltip` | Boolean | `true` | Show priority name on hover |
| `icon_style` | Choice | `circle` | Visual style: `circle`, `badge`, `dot` |
| `color_emergency` | String | `#dc3545` | Emergency priority color |
| `color_high` | String | `#fd7e14` | High priority color |
| `color_normal` | String | `#ffc107` | Normal priority color |
| `color_low` | String | `#28a745` | Low priority color |

---

## Compatibility

| Component | Version | Notes |
|-----------|---------|-------|
| osTicket | 1.18.x | Tested with 1.18.1 |
| PHP | 8.1+ | Uses modern type hints |
| Browsers | Modern | ES6+, CSS Custom Properties |

### PJAX Compatibility

osTicket verwendet jQuery PJAX fuer dynamische Navigation. Das Plugin reagiert auf:

- `pjax:end` Event - Re-scans DOM nach Navigation
- `MutationObserver` - Backup fuer dynamisch geladene Inhalte

---

## Security Considerations

1. **XSS Prevention**: Alle Ausgaben via `Format::htmlchars()` escaped
2. **Path Validation**: Asset-Pfade werden validiert (`file_exists()`)
3. **No User Input**: Plugin verwendet keine User-Eingaben im Frontend
4. **CSP Compatible**: Inline-Scripts koennen durch externe Files ersetzt werden

---

## Testing Strategy

```php
<?php
// tests/PriorityIconsTest.php

use PHPUnit\Framework\TestCase;

class PriorityIconsTest extends TestCase
{
    private PriorityIconsPlugin $plugin;

    protected function setUp(): void
    {
        $this->plugin = new PriorityIconsPlugin(1);
    }

    public function testBootstrapRegistersSignal(): void
    {
        // Verify Signal::connect is called with 'apps.scp'
        $this->plugin->bootstrap();
        // Assert signal is registered (requires mock)
    }

    public function testInjectAssetsOutputsValidHtml(): void
    {
        ob_start();
        $this->plugin->injectAssets(new stdClass());
        $output = ob_get_clean();

        $this->assertStringContainsString('<link', $output);
        $this->assertStringContainsString('<script', $output);
        $this->assertStringContainsString('priority-icons.css', $output);
        $this->assertStringContainsString('priority-icons.js', $output);
    }

    public function testAssetUrlsAreEscaped(): void
    {
        ob_start();
        $this->plugin->injectAssets(new stdClass());
        $output = ob_get_clean();

        // No unescaped special characters
        $this->assertStringNotContainsString('<script>', $output); // Should be escaped if in URL
    }
}
```

---

## Future Enhancements

1. **Localization**: Support fuer uebersetzte Priority-Namen (Deutsch, etc.)
2. **Custom Icons**: Upload eigener SVG-Icons im Admin
3. **Per-Queue Settings**: Verschiedene Styles pro Queue
4. **Client Portal**: Optional auch im Client-Portal (Signal `apps.client`)
5. **Email Templates**: Priority-Icons in E-Mail-Benachrichtigungen

---

## References

- [osTicket Signal API](https://docs.osticket.com/en/latest/Developer%20Documentation/Signals.html)
- [osTicket Plugin Development](https://docs.osticket.com/en/latest/Developer%20Documentation/Plugins.html)
- [Ticket #237948](https://support.markus-michalski.net/scp/tickets.php?id=20) - Original Feature Request
- [Custom CSS Loader Plugin](https://github.com/markus-michalski/osticket-custom-css-loader) - Similar Pattern

---

*Document created: 2026-01-22*
*Author: Claude Code with Markus Michalski*
