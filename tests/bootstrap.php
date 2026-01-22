<?php
/**
 * PHPUnit Test Bootstrap
 *
 * Mocks osTicket core classes and defines constants required for testing
 * the Priority Icons Plugin in isolation.
 *
 * TDD Red Phase: These mocks enable tests to run without osTicket installation.
 */

declare(strict_types=1);

// =============================================================================
// osTicket Constants (normally defined in bootstrap.php)
// =============================================================================

if (!defined('INCLUDE_DIR')) {
    // Point to parent directory which contains 'plugins/priority-icons/'
    // We create a symlink/structure during test setup
    define('INCLUDE_DIR', sys_get_temp_dir() . '/osticket-test/');
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', '/osticket/');
}

// Create test directory structure with plugin assets
$testPluginDir = INCLUDE_DIR . 'plugins/priority-icons/assets/';
if (!is_dir($testPluginDir)) {
    mkdir($testPluginDir, 0777, true);

    // Copy actual CSS file
    $srcCss = __DIR__ . '/../assets/priority-icons.css';
    $dstCss = $testPluginDir . 'priority-icons.css';
    if (file_exists($srcCss)) {
        copy($srcCss, $dstCss);
    } else {
        file_put_contents($dstCss, '/* Test CSS */ .priority-icon { display: inline-block; }');
    }

    // Copy actual JS file
    $srcJs = __DIR__ . '/../assets/priority-icons.js';
    $dstJs = $testPluginDir . 'priority-icons.js';
    if (file_exists($srcJs)) {
        copy($srcJs, $dstJs);
    } else {
        file_put_contents($dstJs, '// Test JS');
    }
}

// =============================================================================
// Mock: Signal Class
// Simulates osTicket's Signal-based event system
// =============================================================================

if (!class_exists('Signal')) {
    /**
     * Mock Signal class for testing Signal::connect() calls.
     *
     * In production osTicket, Signal is the central event bus for plugins.
     * This mock captures registered handlers for assertion.
     */
    class Signal
    {
        /**
         * Registered signal handlers.
         * Structure: ['signal_name' => [callable, ...], ...]
         *
         * @var array<string, array<callable>>
         */
        private static array $handlers = [];

        /**
         * Last registered handler info for assertions.
         *
         * @var array{signal: string, handler: callable, id: string}|null
         */
        private static ?array $lastRegistration = null;

        /**
         * Register a signal handler.
         *
         * @param string   $signal  Signal name (e.g., 'apps.scp')
         * @param callable $handler Callback function/method
         * @param string   $id      Unique identifier for the handler
         */
        public static function connect(string $signal, callable $handler, string $id = ''): void
        {
            if (!isset(self::$handlers[$signal])) {
                self::$handlers[$signal] = [];
            }
            self::$handlers[$signal][] = $handler;

            self::$lastRegistration = [
                'signal'  => $signal,
                'handler' => $handler,
                'id'      => $id,
            ];
        }

        /**
         * Send a signal to all registered handlers.
         *
         * @param string $signal Signal name
         * @param mixed  $data   Data to pass to handlers
         */
        public static function send(string $signal, mixed $data = null): void
        {
            if (!isset(self::$handlers[$signal])) {
                return;
            }

            foreach (self::$handlers[$signal] as $handler) {
                call_user_func($handler, $data);
            }
        }

        /**
         * Get all registered handlers for a signal.
         *
         * @param string $signal Signal name
         * @return array<callable>
         */
        public static function getHandlers(string $signal): array
        {
            return self::$handlers[$signal] ?? [];
        }

        /**
         * Get the last registration for assertions.
         *
         * @return array{signal: string, handler: callable, id: string}|null
         */
        public static function getLastRegistration(): ?array
        {
            return self::$lastRegistration;
        }

        /**
         * Check if a signal has any handlers registered.
         *
         * @param string $signal Signal name
         * @return bool
         */
        public static function hasHandlers(string $signal): bool
        {
            return !empty(self::$handlers[$signal]);
        }

        /**
         * Reset all handlers (for test isolation).
         */
        public static function reset(): void
        {
            self::$handlers = [];
            self::$lastRegistration = null;
        }
    }
}

// =============================================================================
// Mock: Plugin Base Class
// osTicket's abstract Plugin class that all plugins extend
// =============================================================================

if (!class_exists('Plugin')) {
    /**
     * Mock Plugin base class.
     *
     * In production osTicket, this provides:
     * - Configuration management
     * - Enable/disable hooks
     * - Plugin metadata
     */
    abstract class Plugin
    {
        /**
         * Configuration class name.
         *
         * @var string|null
         */
        public ?string $config_class = null;

        /**
         * Plugin instance ID.
         *
         * @var int|string
         */
        protected int|string $id;

        /**
         * Constructor.
         *
         * @param int|string $id Plugin instance ID
         */
        public function __construct(int|string $id)
        {
            $this->id = $id;
        }

        /**
         * Bootstrap the plugin (register signals, etc.).
         * Must be implemented by child classes.
         */
        abstract public function bootstrap(): void;

        /**
         * Get the plugin instance ID.
         *
         * @return int|string
         */
        public function getId(): int|string
        {
            return $this->id;
        }
    }
}

// =============================================================================
// Mock: PluginConfig Base Class
// Configuration form handler for plugins
// =============================================================================

if (!class_exists('PluginConfig')) {
    /**
     * Mock PluginConfig base class.
     *
     * In production osTicket, this handles:
     * - Admin configuration forms
     * - Settings persistence
     * - Form field rendering
     */
    abstract class PluginConfig
    {
        /**
         * Get configuration form options.
         * Must be implemented by child classes.
         *
         * @return array Form field definitions
         */
        abstract public function getOptions(): array;

        /**
         * Get a configuration value.
         *
         * @param string $key     Configuration key
         * @param mixed  $default Default value if not set
         * @return mixed
         */
        public function get(string $key, mixed $default = null): mixed
        {
            $options = $this->getOptions();
            if (isset($options[$key]) && method_exists($options[$key], 'getValue')) {
                return $options[$key]->getValue() ?? $default;
            }
            return $default;
        }
    }
}

// =============================================================================
// Mock: Form Field Classes
// osTicket form field types used in plugin configuration
// =============================================================================

if (!class_exists('FormField')) {
    /**
     * Base form field class.
     */
    abstract class FormField
    {
        protected array $config;
        protected mixed $value = null;

        public function __construct(array $config = [])
        {
            $this->config = $config;
            $this->value = $config['default'] ?? null;
        }

        public function getLabel(): string
        {
            return $this->config['label'] ?? '';
        }

        public function getDefault(): mixed
        {
            return $this->config['default'] ?? null;
        }

        public function getHint(): string
        {
            return $this->config['hint'] ?? '';
        }

        public function getValue(): mixed
        {
            return $this->value;
        }

        public function setValue(mixed $value): void
        {
            $this->value = $value;
        }

        public function getConfig(): array
        {
            return $this->config;
        }
    }
}

if (!class_exists('BooleanField')) {
    /**
     * Boolean/checkbox form field.
     */
    class BooleanField extends FormField
    {
        public function getDefault(): bool
        {
            return (bool) ($this->config['default'] ?? false);
        }
    }
}

if (!class_exists('ChoiceField')) {
    /**
     * Select/dropdown form field.
     */
    class ChoiceField extends FormField
    {
        public function getChoices(): array
        {
            return $this->config['choices'] ?? [];
        }
    }
}

if (!class_exists('TextboxField')) {
    /**
     * Text input form field.
     */
    class TextboxField extends FormField
    {
        public function getSize(): int
        {
            return (int) ($this->config['size'] ?? 20);
        }

        public function getMaxLength(): int
        {
            return (int) ($this->config['maxlength'] ?? 255);
        }
    }
}

// =============================================================================
// Mock: Format Class
// osTicket's output sanitization utility
// =============================================================================

if (!class_exists('Format')) {
    /**
     * Mock Format class for XSS prevention.
     *
     * In production osTicket, Format provides various sanitization methods.
     * This mock implements the essential htmlchars() method.
     */
    class Format
    {
        /**
         * Escape HTML special characters.
         *
         * @param string|null $string Input string
         * @return string Escaped string
         */
        public static function htmlchars(?string $string): string
        {
            if ($string === null) {
                return '';
            }
            return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        /**
         * Sanitize output (alias for htmlchars).
         *
         * @param string|null $string Input string
         * @return string Sanitized string
         */
        public static function sanitize(?string $string): string
        {
            return self::htmlchars($string);
        }
    }
}

// =============================================================================
// Mock: Translation Function
// osTicket's gettext-style translation wrapper
// =============================================================================

if (!function_exists('__')) {
    /**
     * Mock translation function.
     *
     * In production osTicket, this wraps gettext for i18n.
     * This mock simply returns the input string unchanged.
     *
     * @param string $text Text to translate
     * @return string "Translated" text (unchanged in mock)
     */
    function __(string $text): string
    {
        return $text;
    }
}

// =============================================================================
// Autoloader for Plugin Classes
// =============================================================================

spl_autoload_register(function (string $class): void {
    $baseDir = dirname(__DIR__) . '/';

    // Map class names to file paths
    $classMap = [
        'PriorityIconsPlugin' => 'class.PriorityIconsPlugin.php',
        'PriorityIconsConfig' => 'config.php',
    ];

    if (isset($classMap[$class])) {
        $file = $baseDir . $classMap[$class];
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// =============================================================================
// Test Helper Functions
// =============================================================================

/**
 * Create a temporary directory with test assets.
 *
 * @return string Path to temporary plugin directory
 */
function createTestAssets(): string
{
    $tmpDir = sys_get_temp_dir() . '/osticket-priority-icons-test-' . uniqid();
    $assetsDir = $tmpDir . '/assets';

    mkdir($assetsDir, 0777, true);

    // Create dummy CSS file
    file_put_contents(
        $assetsDir . '/priority-icons.css',
        '/* Test CSS */ .priority-icon { display: inline-block; }'
    );

    // Create dummy JS file
    file_put_contents(
        $assetsDir . '/priority-icons.js',
        '// Test JS\nconsole.log("Priority Icons loaded");'
    );

    return $tmpDir;
}

/**
 * Clean up temporary test assets.
 *
 * @param string $dir Directory to remove
 */
function removeTestAssets(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }

    rmdir($dir);
}
