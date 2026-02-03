<?php
/**
 * Priority Icons Plugin - Main Class
 *
 * Replaces priority text labels with visual color-coded icons in osTicket's
 * Staff Control Panel using output buffer-based CSS/JS injection.
 *
 * Features:
 * - No core file modifications
 * - Output buffer injection (ob_start callback replaces </head>)
 * - Inline CSS/JS to bypass include/.htaccess "Deny from all"
 * - XSS-safe output escaping
 * - Configurable colors and styles
 *
 * @package    osTicket\Plugins\PriorityIcons
 * @author     Markus Michalski
 * @version    1.0.0
 * @license    GPL-2.0-or-later
 */

declare(strict_types=1);

// Only require base class if not already defined (e.g., by test bootstrap)
if (!class_exists('Plugin')) {
    require_once INCLUDE_DIR . 'class.plugin.php';
}

// Load config class
if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

/**
 * Main plugin class extending osTicket's Plugin base.
 *
 * Asset injection approach:
 * - Uses ob_start() callback to inject inline CSS/JS before </head>
 * - Same proven pattern as osticket-markdown-support plugin
 *
 * Why NOT addExtraHeader():
 *   global $ost is NULL during bootstrap() because PluginManager::bootstrap()
 *   runs inside osTicket::start() BEFORE the return value is assigned.
 *
 * Why NOT Signal::connect('apps.scp'):
 *   That signal only fires in scp/apps/dispatcher.php (Apps tab),
 *   not on normal pages like tickets.php.
 *
 * Why inline (not external files):
 *   include/.htaccess contains "Deny from all" which blocks HTTP access
 *   to include/plugins/*/assets/. We must not modify core files.
 */
class PriorityIconsPlugin extends Plugin
{
    /**
     * Configuration class name for admin settings.
     *
     * @var string
     */
    public $config_class = 'PriorityIconsConfig';

    /**
     * Singleton plugin - only one instance needed.
     *
     * @return bool
     */
    function isSingleton() {
        return true;
    }

    /**
     * Enable hook - auto-create instance for singleton plugin.
     *
     * Called when plugin is enabled. Creates a single instance
     * if this is a singleton plugin with no existing instances.
     *
     * @return array|void Errors array on failure
     */
    function enable() {
        $errors = [];

        // Auto-create instance for singleton plugin
        if ($this->isSingleton() && $this->getNumInstances() === 0) {
            $vars = [
                'name' => $this->getName(),
                'isactive' => 1,
                'notes' => 'Auto-created singleton instance'
            ];

            if (!$this->addInstance($vars, $errors)) {
                return $errors;
            }
        }
    }

    /**
     * Priority-to-visual mapping configuration.
     *
     * Each priority level maps to:
     * - icon: Font Awesome or custom icon identifier
     * - color: Hex color code for the indicator
     * - class: CSS class for styling
     *
     * Supports both English and German priority names.
     *
     * @var array<string, array{icon: string, color: string, class: string}>
     */
    private array $priorityMap = [
        // English names
        'Emergency' => [
            'icon'  => 'exclamation-circle',
            'color' => '#dc3545',
            'class' => 'priority-emergency',
        ],
        'High' => [
            'icon'  => 'arrow-up',
            'color' => '#fd7e14',
            'class' => 'priority-high',
        ],
        'Normal' => [
            'icon'  => 'minus',
            'color' => '#ffc107',
            'class' => 'priority-normal',
        ],
        'Low' => [
            'icon'  => 'arrow-down',
            'color' => '#28a745',
            'class' => 'priority-low',
        ],
        // German names (Aliases)
        'Notfall' => [
            'icon'  => 'exclamation-circle',
            'color' => '#dc3545',
            'class' => 'priority-emergency',
        ],
        'Hoch' => [
            'icon'  => 'arrow-up',
            'color' => '#fd7e14',
            'class' => 'priority-high',
        ],
        'Niedrig' => [
            'icon'  => 'arrow-down',
            'color' => '#28a745',
            'class' => 'priority-low',
        ],
    ];

    /**
     * Bootstrap the plugin by setting up output buffer-based asset injection.
     *
     * Uses ob_start() with a callback that injects inline CSS/JS before </head>.
     * This is the same proven pattern used by osticket-markdown-support.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        // Skip AJAX requests — no full HTML page to inject into
        if ($this->isAjaxRequest()) {
            return;
        }

        ob_start([$this, 'injectAssetsIntoOutput']);
    }

    /**
     * Output buffer callback — injects assets into HTML before </head>.
     *
     * @param string $buffer The complete HTML output
     * @return string Modified HTML with injected assets
     */
    public function injectAssetsIntoOutput(string $buffer): string
    {
        // Prevent double injection
        if (strpos($buffer, 'data-plugin="priority-icons"') !== false) {
            return $buffer;
        }

        $assets = $this->buildAssetsHtml();

        // Inject before </head>
        if (stripos($buffer, '</head>') !== false) {
            return str_ireplace('</head>', $assets . '</head>', $buffer);
        }

        // Fallback: inject before </body>
        if (stripos($buffer, '</body>') !== false) {
            return str_ireplace('</body>', $assets . '</body>', $buffer);
        }

        return $buffer;
    }

    /**
     * Build the complete assets HTML string (CSS + config + JS).
     *
     * @return string HTML with inline <style>, config <script>, and main <script>
     */
    private function buildAssetsHtml(): string
    {
        $html = '';

        // Inline CSS
        $cssFile = __DIR__ . '/assets/priority-icons.css';
        if (file_exists($cssFile)) {
            $css = file_get_contents($cssFile);
            $html .= '<style data-plugin="priority-icons">' . $css . '</style>' . "\n";
        }

        // Inline config (before JS so window.PriorityIconsConfig is available)
        $html .= $this->buildConfigScript();

        // Inline JS
        $jsFile = __DIR__ . '/assets/priority-icons.js';
        if (file_exists($jsFile)) {
            $js = file_get_contents($jsFile);
            $html .= '<script data-plugin="priority-icons">' . $js . '</script>' . "\n";
        }

        return $html;
    }

    /**
     * Build inline JavaScript configuration script.
     *
     * Passes priority mapping from PHP to JavaScript via
     * window.PriorityIconsConfig object.
     *
     * Uses secure JSON encoding flags to prevent XSS:
     * - JSON_HEX_TAG: Converts < and > to \u003C and \u003E
     * - JSON_HEX_APOS: Converts ' to \u0027
     *
     * @return string Script tag with config
     */
    private function buildConfigScript(): string
    {
        $config = [
            'priorities' => $this->priorityMap,
            'debug'      => false,
        ];

        // JSON_HEX_TAG prevents </script> injection attacks
        $jsonConfig = json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_THROW_ON_ERROR);

        return '<script data-plugin="priority-icons">'
            . 'window.PriorityIconsConfig=' . $jsonConfig . ';'
            . '</script>' . "\n";
    }

    /**
     * Check if current request is AJAX.
     *
     * @return bool True if AJAX request
     */
    private function isAjaxRequest(): bool
    {
        $xRequestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        return strtolower($xRequestedWith) === 'xmlhttprequest';
    }
}
