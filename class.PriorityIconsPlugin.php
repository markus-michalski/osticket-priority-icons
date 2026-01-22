<?php
/**
 * Priority Icons Plugin - Main Class
 *
 * Replaces priority text labels with visual color-coded icons in osTicket's
 * Staff Control Panel using Signal-based CSS/JS injection.
 *
 * Features:
 * - No core file modifications
 * - Signal-based asset injection via 'apps.scp'
 * - XSS-safe output escaping
 * - Cache-busting version parameters
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
 * Follows osTicket plugin conventions:
 * - Signal registration in bootstrap()
 * - Asset injection via injectAssets()
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
     * Bootstrap the plugin by registering signal handlers.
     *
     * Called on every page load when plugin is enabled.
     * Registers handler for 'apps.scp' signal to inject CSS/JS
     * into the Staff Control Panel.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        Signal::connect(
            'apps.scp',
            [$this, 'injectAssets'],
            'PriorityIconsPlugin'
        );
    }

    /**
     * Inject CSS and JavaScript assets into page output.
     *
     * Called by Signal::send('apps.scp') in dispatcher.php.
     * Outputs:
     * - External CSS link with cache-busting version
     * - External JS script (deferred) with cache-busting version
     * - Inline JS config with priority mapping
     *
     * All URLs are escaped via Format::htmlchars() for XSS prevention.
     *
     * @param object $dispatcher The dispatcher object from signal (unused but required)
     * @return void
     */
    public function injectAssets(object $dispatcher): void
    {
        $pluginDir = $this->getPluginDirectory();
        $assetUrl = $this->getAssetUrl();

        // CSS - external file with cache-busting
        $cssFile = $pluginDir . 'assets/priority-icons.css';
        if (file_exists($cssFile)) {
            $version = (string) filemtime($cssFile);
            $cssUrl = Format::htmlchars($assetUrl . 'assets/priority-icons.css?v=' . $version);
            echo '<link rel="stylesheet" href="' . $cssUrl . '">' . "\n";
        }

        // JavaScript - external file with cache-busting (deferred for non-blocking)
        $jsFile = $pluginDir . 'assets/priority-icons.js';
        if (file_exists($jsFile)) {
            $version = (string) filemtime($jsFile);
            $jsUrl = Format::htmlchars($assetUrl . 'assets/priority-icons.js?v=' . $version);
            echo '<script src="' . $jsUrl . '" defer></script>' . "\n";
        }

        // Inline configuration for JavaScript
        $this->injectConfig();
    }

    /**
     * Inject inline JavaScript configuration.
     *
     * Passes priority mapping from PHP to JavaScript via
     * window.PriorityIconsConfig object.
     *
     * Uses secure JSON encoding flags to prevent XSS:
     * - JSON_HEX_TAG: Converts < and > to \u003C and \u003E
     * - JSON_HEX_APOS: Converts ' to \u0027
     *
     * @return void
     */
    private function injectConfig(): void
    {
        $config = [
            'priorities' => $this->priorityMap,
            'debug'      => false,
        ];

        // JSON_HEX_TAG prevents </script> injection attacks
        $jsonConfig = json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_THROW_ON_ERROR);

        echo '<script>window.PriorityIconsConfig = ' . $jsonConfig . ';</script>' . "\n";
    }

    /**
     * Get the plugin directory path.
     *
     * @return string Absolute path to plugin directory
     */
    private function getPluginDirectory(): string
    {
        return __DIR__ . '/';
    }

    /**
     * Get the base URL for plugin assets.
     *
     * @return string URL path to plugin directory
     */
    private function getAssetUrl(): string
    {
        $pluginDir = basename(__DIR__);
        return ROOT_PATH . 'include/plugins/' . $pluginDir . '/';
    }
}
