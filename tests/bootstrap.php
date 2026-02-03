<?php
/**
 * PHPUnit Test Bootstrap
 *
 * Mocks osTicket core classes and defines constants required for testing
 * the Priority Icons Plugin in isolation.
 */

declare(strict_types=1);

// =============================================================================
// osTicket Constants
// =============================================================================

if (!defined('INCLUDE_DIR')) {
    define('INCLUDE_DIR', sys_get_temp_dir() . '/osticket-test/');
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', '/osticket/');
}

// =============================================================================
// Mock: Plugin Base Class
// =============================================================================

if (!class_exists('Plugin')) {
    abstract class Plugin
    {
        public $config_class = null;

        protected int|string $id;

        /** @var PluginConfig|null */
        protected $config = null;

        /** @var array Simulated active instances */
        private array $activeInstances = [];

        /** @var int */
        private int $numInstances = 0;

        /** @var string */
        private string $name = 'Test Plugin';

        public function __construct(int|string $id)
        {
            $this->id = $id;
        }

        abstract public function bootstrap(): void;

        public function getId(): int|string
        {
            return $this->id;
        }

        public function getName(): string
        {
            return $this->name;
        }

        public function setName(string $name): void
        {
            $this->name = $name;
        }

        public function isSingleton()
        {
            return false;
        }

        public function getNumInstances(): int
        {
            return $this->numInstances;
        }

        public function setNumInstances(int $count): void
        {
            $this->numInstances = $count;
        }

        public function addInstance(array $vars, array &$errors = []): bool
        {
            $this->numInstances++;
            return true;
        }

        public function getConfig(): ?PluginConfig
        {
            return $this->config;
        }

        /**
         * Set config for testing (bypasses real DB lookup)
         */
        public function setTestConfig(?PluginConfig $config): void
        {
            $this->config = $config;
        }

        /**
         * @return PluginInstance[]
         */
        public function getActiveInstances(): array
        {
            return $this->activeInstances;
        }

        /**
         * Set active instances for testing
         * @param PluginInstance[] $instances
         */
        public function setTestActiveInstances(array $instances): void
        {
            $this->activeInstances = $instances;
        }

        public function getInstallPath(): string
        {
            return 'plugins/priority-icons/';
        }
    }
}

// =============================================================================
// Mock: PluginInstance
// =============================================================================

if (!class_exists('PluginInstance')) {
    class PluginInstance
    {
        private ?PluginConfig $config;
        private int $pluginId;
        private int $instanceId;

        public function __construct(int $pluginId, int $instanceId, ?PluginConfig $config = null)
        {
            $this->pluginId = $pluginId;
            $this->instanceId = $instanceId;
            $this->config = $config;
        }

        public function getConfig(array $defaults = []): ?PluginConfig
        {
            return $this->config;
        }

        public function getPluginId(): int
        {
            return $this->pluginId;
        }

        public function getId(): int
        {
            return $this->instanceId;
        }

        public function getNamespace(): string
        {
            return sprintf('plugin.%d.instance.%d', $this->pluginId, $this->instanceId);
        }
    }
}

// =============================================================================
// Mock: PluginConfig Base Class
// =============================================================================

if (!class_exists('PluginConfig')) {
    abstract class PluginConfig
    {
        /** @var array<string, mixed> Stored config values */
        protected array $values = [];

        abstract public function getOptions(): array;

        public function get(string $key, mixed $default = null): mixed
        {
            if (array_key_exists($key, $this->values)) {
                return $this->values[$key];
            }

            // Fall back to field defaults
            $options = $this->getOptions();
            if (isset($options[$key])) {
                return $options[$key]->getDefault();
            }

            return $default;
        }

        /**
         * Set a config value for testing
         */
        public function set(string $key, mixed $value): void
        {
            $this->values[$key] = $value;
        }
    }
}

// =============================================================================
// Mock: Form Field Classes
// =============================================================================

if (!class_exists('FormField')) {
    abstract class FormField
    {
        protected array $config;

        public function __construct(array $config = [])
        {
            $this->config = $config;
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

        public function getConfig(): array
        {
            return $this->config;
        }
    }
}

if (!class_exists('BooleanField')) {
    class BooleanField extends FormField
    {
        public function getDefault(): bool
        {
            return (bool) ($this->config['default'] ?? false);
        }
    }
}

if (!class_exists('ChoiceField')) {
    class ChoiceField extends FormField
    {
        public function getChoices(): array
        {
            return $this->config['choices'] ?? [];
        }
    }
}

if (!class_exists('TextboxField')) {
    class TextboxField extends FormField
    {
        public function getMaxLength(): ?int
        {
            return $this->config['maxlength'] ?? null;
        }

        public function getSize(): ?int
        {
            return $this->config['size'] ?? null;
        }
    }
}

// =============================================================================
// Mock: Signal Class (kept for compatibility, no longer used by plugin)
// =============================================================================

if (!class_exists('Signal')) {
    class Signal
    {
        public static function connect(string $signal, callable $handler, $object = null, $check = null): void
        {
        }

        public static function send(string $signal, mixed $data = null): void
        {
        }
    }
}

// =============================================================================
// Mock: Format Class
// =============================================================================

if (!class_exists('Format')) {
    class Format
    {
        public static function htmlchars(?string $string): string
        {
            if ($string === null) {
                return '';
            }
            return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
}

// =============================================================================
// Mock: Translation Function
// =============================================================================

if (!function_exists('__')) {
    function __(string $text): string
    {
        return $text;
    }
}

// =============================================================================
// Autoloader
// =============================================================================

spl_autoload_register(function (string $class): void {
    $baseDir = dirname(__DIR__) . '/';
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
