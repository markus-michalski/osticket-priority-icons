<?php
/**
 * Priority Icons Plugin - Main Class
 *
 * Replaces priority text labels with visual color-coded icons in osTicket's
 * Staff Control Panel using inline CSS/JS injection via addExtraHeader().
 *
 * Features:
 * - No core file modifications
 * - Inline asset injection via $ost->addExtraHeader()
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
 * Follows osTicket plugin conventions:
 * - Asset injection via $ost->addExtraHeader() in bootstrap()
 * - Inline CSS/JS to bypass include/.htaccess "Deny from all"
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
     * Bootstrap the plugin by injecting inline CSS/JS into the page header.
     *
     * Called on every page load when plugin is enabled.
     * Uses $ost->addExtraHeader() to register assets in the <head>
     * of every Staff Control Panel page.
     *
     * Assets are injected INLINE because:
     * 1. Signal::connect('apps.scp') does NOT work — that signal only fires
     *    in scp/apps/dispatcher.php (Apps tab), not on tickets.php etc.
     * 2. External <link>/<script> to include/plugins/ would be blocked by
     *    include/.htaccess "Deny from all" — and we must not modify core files.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        global $ost;

        if (!$ost instanceof \osTicket) {
            return;
        }

        // Inline CSS
        $cssFile = __DIR__ . '/assets/priority-icons.css';
        if (file_exists($cssFile)) {
            $css = file_get_contents($cssFile);
            $ost->addExtraHeader('<style data-plugin="priority-icons">' . $css . '</style>');
        }

        // Inline config (before JS so window.PriorityIconsConfig is available)
        $this->injectConfig($ost);

        // Inline JS with initialization guard to prevent double-init on PJAX
        $jsFile = __DIR__ . '/assets/priority-icons.js';
        if (file_exists($jsFile)) {
            $js = file_get_contents($jsFile);
            $guard = 'if(window.PriorityIconsLoaded){}else{window.PriorityIconsLoaded=true;';
            $ost->addExtraHeader(
                '<script data-plugin="priority-icons">' . $guard . $js . '}</script>'
            );
        }
    }

    /**
     * Inject inline JavaScript configuration via addExtraHeader.
     *
     * Passes priority mapping from PHP to JavaScript via
     * window.PriorityIconsConfig object.
     *
     * Uses secure JSON encoding flags to prevent XSS:
     * - JSON_HEX_TAG: Converts < and > to \u003C and \u003E
     * - JSON_HEX_APOS: Converts ' to \u0027
     *
     * @param \osTicket $ost The osTicket instance
     * @return void
     */
    private function injectConfig(\osTicket $ost): void
    {
        $config = [
            'priorities' => $this->priorityMap,
            'debug'      => false,
        ];

        // JSON_HEX_TAG prevents </script> injection attacks
        $jsonConfig = json_encode($config, JSON_HEX_TAG | JSON_HEX_APOS | JSON_THROW_ON_ERROR);

        $ost->addExtraHeader(
            '<script data-plugin="priority-icons">'
            . 'window.PriorityIconsConfig=' . $jsonConfig . ';'
            . '</script>'
        );
    }
}
