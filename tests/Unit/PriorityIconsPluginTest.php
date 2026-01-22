<?php
/**
 * PHPUnit Tests for PriorityIconsPlugin
 *
 * TDD Red Phase: These tests define expected behavior BEFORE implementation.
 * All tests should FAIL initially until the plugin class is implemented.
 *
 * Test Coverage:
 * - bootstrap() - Signal registration
 * - injectAssets() - CSS/JS output generation
 * - enable() - Singleton pattern
 * - XSS prevention for asset URLs
 * - Priority mapping configuration
 *
 * @package    osTicket\Plugins\PriorityIcons
 * @subpackage Tests
 */

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test suite for PriorityIconsPlugin main class.
 *
 * Expected class structure (to be implemented):
 *
 * class PriorityIconsPlugin extends Plugin
 * {
 *     public $config_class = 'PriorityIconsConfig';
 *     private static ?self $instance = null;
 *     private array $priorityMap = [...];
 *
 *     public function bootstrap(): void;
 *     public static function enable(int|string $id): self;
 *     public function injectAssets(object $dispatcher): void;
 *     private function injectConfig(): void;
 * }
 */
#[CoversClass(\PriorityIconsPlugin::class)]
#[Group('unit')]
class PriorityIconsPluginTest extends TestCase
{
    /**
     * Plugin instance under test.
     */
    private \PriorityIconsPlugin $plugin;

    /**
     * Temporary directory for test assets.
     */
    private string $testAssetsDir;

    /**
     * Set up test fixtures.
     *
     * Creates a fresh plugin instance and resets the Signal mock
     * before each test for isolation.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset Signal mock to ensure test isolation
        \Signal::reset();

        // Create test assets directory with dummy files
        $this->testAssetsDir = createTestAssets();

        // Instantiate plugin - this will FAIL until class exists
        $this->plugin = new \PriorityIconsPlugin(1);
    }

    /**
     * Tear down test fixtures.
     */
    protected function tearDown(): void
    {
        // Clean up test assets
        if (isset($this->testAssetsDir)) {
            removeTestAssets($this->testAssetsDir);
        }

        parent::tearDown();
    }

    // =========================================================================
    // Tests: Plugin Instantiation
    // =========================================================================

    /**
     * Test that plugin can be instantiated with an ID.
     *
     * Expected: Plugin accepts integer or string ID in constructor.
     */
    #[Test]
    public function pluginCanBeInstantiatedWithIntegerId(): void
    {
        $plugin = new \PriorityIconsPlugin(42);

        $this->assertInstanceOf(\PriorityIconsPlugin::class, $plugin);
        $this->assertInstanceOf(\Plugin::class, $plugin);
    }

    /**
     * Test that plugin can be instantiated with a string ID.
     *
     * Expected: Plugin accepts string ID (osTicket sometimes uses string IDs).
     */
    #[Test]
    public function pluginCanBeInstantiatedWithStringId(): void
    {
        $plugin = new \PriorityIconsPlugin('plugin-instance-1');

        $this->assertInstanceOf(\PriorityIconsPlugin::class, $plugin);
    }

    /**
     * Test that config_class property is set correctly.
     *
     * Expected: Plugin declares 'PriorityIconsConfig' as its configuration class.
     */
    #[Test]
    public function pluginDeclaresConfigClass(): void
    {
        $this->assertEquals('PriorityIconsConfig', $this->plugin->config_class);
    }

    // =========================================================================
    // Tests: bootstrap() - Signal Registration
    // =========================================================================

    /**
     * Test that bootstrap() registers a signal handler.
     *
     * Expected: After calling bootstrap(), a handler should be registered
     * for the 'apps.scp' signal (Staff Control Panel).
     */
    #[Test]
    public function bootstrapRegistersSignalHandler(): void
    {
        $this->plugin->bootstrap();

        $this->assertTrue(
            \Signal::hasHandlers('apps.scp'),
            'bootstrap() should register a handler for "apps.scp" signal'
        );
    }

    /**
     * Test that bootstrap() registers injectAssets as the signal handler.
     *
     * Expected: The registered handler should be the injectAssets() method.
     */
    #[Test]
    public function bootstrapRegistersInjectAssetsMethod(): void
    {
        $this->plugin->bootstrap();

        $lastReg = \Signal::getLastRegistration();

        $this->assertNotNull($lastReg, 'A signal handler should be registered');
        $this->assertEquals('apps.scp', $lastReg['signal']);
        $this->assertIsCallable($lastReg['handler']);

        // Verify it's the injectAssets method of our plugin
        $this->assertIsArray($lastReg['handler']);
        $this->assertSame($this->plugin, $lastReg['handler'][0]);
        $this->assertEquals('injectAssets', $lastReg['handler'][1]);
    }

    /**
     * Test that bootstrap() uses a unique handler ID.
     *
     * Expected: Handler ID should identify this plugin (e.g., 'PriorityIconsPlugin').
     */
    #[Test]
    public function bootstrapUsesUniqueHandlerId(): void
    {
        $this->plugin->bootstrap();

        $lastReg = \Signal::getLastRegistration();

        $this->assertNotEmpty($lastReg['id'], 'Handler should have a unique ID');
        $this->assertStringContainsString(
            'PriorityIcons',
            $lastReg['id'],
            'Handler ID should identify the plugin'
        );
    }

    /**
     * Test that bootstrap() does not throw exceptions.
     *
     * Expected: bootstrap() should be safe to call and not throw.
     */
    #[Test]
    public function bootstrapDoesNotThrowException(): void
    {
        // Should not throw
        $this->plugin->bootstrap();

        $this->assertTrue(true, 'bootstrap() completed without exception');
    }

    // =========================================================================
    // Tests: enable() - Singleton Pattern
    // =========================================================================

    /**
     * Test that enable() returns a plugin instance.
     *
     * Expected: Static enable() method returns an instance of the plugin.
     */
    #[Test]
    public function enableReturnsPluginInstance(): void
    {
        $instance = \PriorityIconsPlugin::enable(1);

        $this->assertInstanceOf(\PriorityIconsPlugin::class, $instance);
    }

    /**
     * Test that enable() implements singleton pattern.
     *
     * Expected: Multiple calls to enable() return the same instance.
     */
    #[Test]
    public function enableReturnsSameInstanceOnMultipleCalls(): void
    {
        $instance1 = \PriorityIconsPlugin::enable(1);
        $instance2 = \PriorityIconsPlugin::enable(2);
        $instance3 = \PriorityIconsPlugin::enable('different-id');

        $this->assertSame(
            $instance1,
            $instance2,
            'enable() should return the same singleton instance'
        );
        $this->assertSame($instance1, $instance3);
    }

    // =========================================================================
    // Tests: injectAssets() - HTML Output
    // =========================================================================

    /**
     * Test that injectAssets() outputs a CSS link tag.
     *
     * Expected: Output should contain a <link> tag for the CSS file.
     */
    #[Test]
    public function injectAssetsOutputsCssLinkTag(): void
    {
        ob_start();
        $this->plugin->injectAssets(new \stdClass());
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '<link',
            $output,
            'Output should contain a <link> tag'
        );
        $this->assertStringContainsString(
            'rel="stylesheet"',
            $output,
            'Link tag should be a stylesheet'
        );
        $this->assertStringContainsString(
            'priority-icons.css',
            $output,
            'Link should reference the CSS file'
        );
    }

    /**
     * Test that injectAssets() outputs a script tag.
     *
     * Expected: Output should contain a <script> tag for the JS file.
     */
    #[Test]
    public function injectAssetsOutputsScriptTag(): void
    {
        ob_start();
        $this->plugin->injectAssets(new \stdClass());
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '<script',
            $output,
            'Output should contain a <script> tag'
        );
        $this->assertStringContainsString(
            'priority-icons.js',
            $output,
            'Script should reference the JS file'
        );
    }

    /**
     * Test that injectAssets() adds defer attribute to script.
     *
     * Expected: Script tag should have defer attribute for non-blocking load.
     */
    #[Test]
    public function injectAssetsUsesScriptDeferAttribute(): void
    {
        ob_start();
        $this->plugin->injectAssets(new \stdClass());
        $output = ob_get_clean();

        $this->assertMatchesRegularExpression(
            '/<script[^>]+defer/',
            $output,
            'Script tag should have defer attribute'
        );
    }

    /**
     * Test that injectAssets() includes cache-busting version parameter.
     *
     * Expected: Asset URLs should include ?v= version query parameter.
     */
    #[Test]
    public function injectAssetsIncludesCacheBustingVersion(): void
    {
        ob_start();
        $this->plugin->injectAssets(new \stdClass());
        $output = ob_get_clean();

        $this->assertMatchesRegularExpression(
            '/priority-icons\.css\?v=\d+/',
            $output,
            'CSS URL should have version parameter'
        );
        $this->assertMatchesRegularExpression(
            '/priority-icons\.js\?v=\d+/',
            $output,
            'JS URL should have version parameter'
        );
    }

    /**
     * Test that injectAssets() outputs inline configuration.
     *
     * Expected: Output should contain inline script with PriorityIconsConfig.
     */
    #[Test]
    public function injectAssetsOutputsInlineConfig(): void
    {
        ob_start();
        $this->plugin->injectAssets(new \stdClass());
        $output = ob_get_clean();

        $this->assertStringContainsString(
            'window.PriorityIconsConfig',
            $output,
            'Output should set window.PriorityIconsConfig'
        );
    }

    /**
     * Test that inline config contains priorities mapping.
     *
     * Expected: PriorityIconsConfig should include 'priorities' object.
     */
    #[Test]
    public function injectAssetsConfigContainsPriorities(): void
    {
        ob_start();
        $this->plugin->injectAssets(new \stdClass());
        $output = ob_get_clean();

        $this->assertStringContainsString(
            '"priorities"',
            $output,
            'Config should contain priorities mapping'
        );
    }

    // =========================================================================
    // Tests: XSS Prevention
    // =========================================================================

    /**
     * Test that asset URLs are properly escaped.
     *
     * Expected: URLs should be sanitized via Format::htmlchars() to prevent XSS.
     */
    #[Test]
    public function injectAssetsEscapesUrlsForXssPrevention(): void
    {
        ob_start();
        $this->plugin->injectAssets(new \stdClass());
        $output = ob_get_clean();

        // Output should not contain unescaped angle brackets in attributes
        // (except for the tags themselves)
        $this->assertStringNotContainsString(
            'href="<',
            $output,
            'URLs should not contain unescaped < characters'
        );
        $this->assertStringNotContainsString(
            'src="<',
            $output,
            'URLs should not contain unescaped < characters'
        );
    }

    /**
     * Test that inline JSON config uses safe encoding.
     *
     * Expected: JSON should use JSON_HEX_TAG | JSON_HEX_APOS for XSS safety.
     */
    #[Test]
    public function injectAssetsUsesSecureJsonEncoding(): void
    {
        ob_start();
        $this->plugin->injectAssets(new \stdClass());
        $output = ob_get_clean();

        // JSON_HEX_TAG converts < to \u003C and > to \u003E
        // This prevents </script> injection
        $this->assertStringNotContainsString(
            '</script>',
            preg_replace('/<\/script>$/m', '', $output),
            'JSON config should not contain literal </script>'
        );
    }

    // =========================================================================
    // Tests: Priority Mapping
    // =========================================================================

    /**
     * Test that config includes all standard priorities.
     *
     * Expected: Config should include Emergency, High, Normal, Low.
     */
    #[Test]
    #[DataProvider('standardPriorityProvider')]
    public function injectAssetsConfigIncludesStandardPriority(string $priority): void
    {
        ob_start();
        $this->plugin->injectAssets(new \stdClass());
        $output = ob_get_clean();

        $this->assertStringContainsString(
            "\"$priority\"",
            $output,
            "Config should include '$priority' priority"
        );
    }

    /**
     * Data provider for standard priority names.
     *
     * @return array<string, array{string}>
     */
    public static function standardPriorityProvider(): array
    {
        return [
            'Emergency priority' => ['Emergency'],
            'High priority'      => ['High'],
            'Normal priority'    => ['Normal'],
            'Low priority'       => ['Low'],
        ];
    }

    /**
     * Test that each priority has color configuration.
     *
     * Expected: Each priority should have a 'color' property with hex value.
     */
    #[Test]
    public function injectAssetsConfigIncludesColorsForPriorities(): void
    {
        ob_start();
        $this->plugin->injectAssets(new \stdClass());
        $output = ob_get_clean();

        // Extract JSON config
        preg_match('/window\.PriorityIconsConfig\s*=\s*({.+?});/', $output, $matches);

        $this->assertNotEmpty($matches, 'Should find config in output');

        $config = json_decode($matches[1], true);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('priorities', $config);

        foreach (['Emergency', 'High', 'Normal', 'Low'] as $priority) {
            $this->assertArrayHasKey($priority, $config['priorities']);
            $this->assertArrayHasKey('color', $config['priorities'][$priority]);
            $this->assertMatchesRegularExpression(
                '/^#[0-9a-fA-F]{6}$/',
                $config['priorities'][$priority]['color'],
                "$priority should have valid hex color"
            );
        }
    }

    /**
     * Test that each priority has CSS class configuration.
     *
     * Expected: Each priority should have a 'class' property.
     */
    #[Test]
    public function injectAssetsConfigIncludesCssClassesForPriorities(): void
    {
        ob_start();
        $this->plugin->injectAssets(new \stdClass());
        $output = ob_get_clean();

        preg_match('/window\.PriorityIconsConfig\s*=\s*({.+?});/', $output, $matches);
        $config = json_decode($matches[1] ?? '{}', true);

        foreach (['Emergency', 'High', 'Normal', 'Low'] as $priority) {
            $this->assertArrayHasKey(
                'class',
                $config['priorities'][$priority] ?? [],
                "$priority should have CSS class"
            );
            $this->assertStringContainsString(
                'priority-',
                $config['priorities'][$priority]['class'],
                "$priority CSS class should follow naming convention"
            );
        }
    }

    // =========================================================================
    // Tests: Error Handling
    // =========================================================================

    /**
     * Test that injectAssets() handles missing CSS file gracefully.
     *
     * Expected: If CSS file doesn't exist, don't output link tag, don't throw.
     */
    #[Test]
    public function injectAssetsHandlesMissingCssFileGracefully(): void
    {
        // Use a non-existent assets directory by redefining INCLUDE_DIR
        // This is tricky with constants, so we test the general behavior
        // that the method doesn't throw even when files might be missing

        ob_start();
        try {
            $this->plugin->injectAssets(new \stdClass());
            $output = ob_get_clean();
            // If we get here, no exception was thrown
            $this->assertTrue(true, 'injectAssets() should not throw on missing files');
        } catch (\Exception $e) {
            ob_get_clean();
            $this->fail('injectAssets() should not throw exception: ' . $e->getMessage());
        }
    }

    /**
     * Test that injectAssets() accepts any object as dispatcher.
     *
     * Expected: Method should accept stdClass or any dispatcher object.
     */
    #[Test]
    public function injectAssetsAcceptsAnyDispatcherObject(): void
    {
        $dispatchers = [
            new \stdClass(),
            new class {
                public string $name = 'test';
            },
        ];

        foreach ($dispatchers as $dispatcher) {
            ob_start();
            $this->plugin->injectAssets($dispatcher);
            $output = ob_get_clean();

            $this->assertIsString($output, 'Should produce string output');
        }
    }

    // =========================================================================
    // Tests: Output Format
    // =========================================================================

    /**
     * Test that output contains proper line breaks.
     *
     * Expected: Each tag should be on its own line for readability.
     */
    #[Test]
    public function injectAssetsOutputHasProperLineBreaks(): void
    {
        ob_start();
        $this->plugin->injectAssets(new \stdClass());
        $output = ob_get_clean();

        // Tags should end with newlines
        $this->assertMatchesRegularExpression(
            '/<link[^>]+>\s*\n/',
            $output,
            'Link tag should be followed by newline'
        );
    }

    /**
     * Test that output is valid HTML (well-formed tags).
     *
     * Expected: All tags should be properly closed or self-closing.
     */
    #[Test]
    public function injectAssetsOutputIsValidHtml(): void
    {
        ob_start();
        $this->plugin->injectAssets(new \stdClass());
        $output = ob_get_clean();

        // Link tags should be self-closing (HTML5 allows unclosed, but > is required)
        $this->assertMatchesRegularExpression(
            '/<link[^>]+>/',
            $output,
            'Link tag should be properly formed'
        );

        // Script tags must be closed
        $scriptOpenCount = substr_count($output, '<script');
        $scriptCloseCount = substr_count($output, '</script>');

        $this->assertEquals(
            $scriptOpenCount,
            $scriptCloseCount,
            'All script tags should be properly closed'
        );
    }
}
