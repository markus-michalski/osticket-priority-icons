<?php
/**
 * PHPUnit Tests for PriorityIconsPlugin
 *
 * Tests the output buffer-based asset injection, priority mapping,
 * config reading from plugin instances, and XSS prevention.
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

#[CoversClass(\PriorityIconsPlugin::class)]
#[Group('unit')]
class PriorityIconsPluginTest extends TestCase
{
    private \PriorityIconsPlugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->plugin = new \PriorityIconsPlugin(1);
    }

    // =========================================================================
    // Tests: Plugin Instantiation
    // =========================================================================

    #[Test]
    public function pluginCanBeInstantiated(): void
    {
        $this->assertInstanceOf(\PriorityIconsPlugin::class, $this->plugin);
        $this->assertInstanceOf(\Plugin::class, $this->plugin);
    }

    #[Test]
    public function pluginDeclaresConfigClass(): void
    {
        $this->assertEquals('PriorityIconsConfig', $this->plugin->config_class);
    }

    #[Test]
    public function pluginIsSingleton(): void
    {
        $this->assertTrue($this->plugin->isSingleton());
    }

    // =========================================================================
    // Tests: enable() - Singleton Auto-Instance
    // =========================================================================

    #[Test]
    public function enableCreatesInstanceForSingleton(): void
    {
        $this->plugin->setNumInstances(0);

        $result = $this->plugin->enable();

        $this->assertNull($result, 'enable() should return null on success');
        $this->assertEquals(1, $this->plugin->getNumInstances());
    }

    #[Test]
    public function enableSkipsIfInstanceExists(): void
    {
        $this->plugin->setNumInstances(1);

        $this->plugin->enable();

        $this->assertEquals(1, $this->plugin->getNumInstances());
    }

    // =========================================================================
    // Tests: bootstrap() - Output Buffer Setup
    // =========================================================================

    #[Test]
    public function bootstrapDoesNotThrow(): void
    {
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);

        $this->plugin->bootstrap();

        // Clean up ob_start() that bootstrap() initiated
        ob_end_clean();

        $this->assertTrue(true);
    }

    #[Test]
    public function bootstrapSkipsAjaxRequests(): void
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $levelBefore = ob_get_level();

        $this->plugin->bootstrap();

        $this->assertEquals($levelBefore, ob_get_level(), 'Should not start output buffer for AJAX');

        unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    }

    #[Test]
    public function bootstrapSkipsWhenDisabledViaConfig(): void
    {
        unset($_SERVER['HTTP_X_REQUESTED_WITH']);

        $config = new \PriorityIconsConfig();
        $config->set('enabled', false);

        $instance = new \PluginInstance(1, 1, $config);
        $this->plugin->setTestActiveInstances([$instance]);

        $levelBefore = ob_get_level();

        $this->plugin->bootstrap();

        $this->assertEquals($levelBefore, ob_get_level(), 'Should not start output buffer when disabled');
    }

    // =========================================================================
    // Tests: injectAssetsIntoOutput() - HTML Injection
    // =========================================================================

    #[Test]
    public function injectAssetsInjectsBeforeHeadClose(): void
    {
        $html = '<html><head><title>Test</title></head><body></body></html>';

        $result = $this->plugin->injectAssetsIntoOutput($html);

        $this->assertStringContainsString('data-plugin="priority-icons"', $result);
        $this->assertStringContainsString('</head>', $result);
    }

    #[Test]
    public function injectAssetsFallsBackToBodyClose(): void
    {
        $html = '<html><body><p>No head tag</p></body></html>';

        $result = $this->plugin->injectAssetsIntoOutput($html);

        $this->assertStringContainsString('data-plugin="priority-icons"', $result);
    }

    #[Test]
    public function injectAssetsPreventsDoubleInjection(): void
    {
        $html = '<html><head><style data-plugin="priority-icons"></style></head><body></body></html>';

        $result = $this->plugin->injectAssetsIntoOutput($html);

        $this->assertEquals($html, $result, 'Should not inject twice');
    }

    #[Test]
    public function injectAssetsReturnsUnmodifiedBufferWithoutHeadOrBody(): void
    {
        $html = '<div>Just a fragment</div>';

        $result = $this->plugin->injectAssetsIntoOutput($html);

        $this->assertEquals($html, $result);
    }

    // =========================================================================
    // Tests: Inline Assets Content
    // =========================================================================

    #[Test]
    public function injectedAssetsContainInlineCss(): void
    {
        $html = '<html><head></head><body></body></html>';
        $result = $this->plugin->injectAssetsIntoOutput($html);

        $this->assertStringContainsString('<style data-plugin="priority-icons">', $result);
        $this->assertStringContainsString('.priority-icon', $result);
    }

    #[Test]
    public function injectedAssetsContainInlineJs(): void
    {
        $html = '<html><head></head><body></body></html>';
        $result = $this->plugin->injectAssetsIntoOutput($html);

        $this->assertStringContainsString('<script data-plugin="priority-icons">', $result);
    }

    #[Test]
    public function injectedAssetsContainConfigScript(): void
    {
        $html = '<html><head></head><body></body></html>';
        $result = $this->plugin->injectAssetsIntoOutput($html);

        $this->assertStringContainsString('window.PriorityIconsConfig=', $result);
    }

    // =========================================================================
    // Tests: Priority Mapping in Config
    // =========================================================================

    #[Test]
    #[DataProvider('standardPriorityProvider')]
    public function configContainsPriority(string $priority): void
    {
        $html = '<html><head></head><body></body></html>';
        $result = $this->plugin->injectAssetsIntoOutput($html);

        $config = $this->extractConfig($result);

        $this->assertArrayHasKey($priority, $config['priorities']);
        $this->assertArrayHasKey('color', $config['priorities'][$priority]);
        $this->assertArrayHasKey('class', $config['priorities'][$priority]);
        $this->assertArrayHasKey('icon', $config['priorities'][$priority]);
    }

    public static function standardPriorityProvider(): array
    {
        return [
            'Emergency' => ['Emergency'],
            'High'      => ['High'],
            'Normal'    => ['Normal'],
            'Low'       => ['Low'],
            'Notfall'   => ['Notfall'],
            'Hoch'      => ['Hoch'],
            'Niedrig'   => ['Niedrig'],
        ];
    }

    #[Test]
    public function priorityColorsAreValidHex(): void
    {
        $html = '<html><head></head><body></body></html>';
        $result = $this->plugin->injectAssetsIntoOutput($html);

        $config = $this->extractConfig($result);

        foreach ($config['priorities'] as $name => $priority) {
            $this->assertMatchesRegularExpression(
                '/^#[0-9a-fA-F]{6}$/',
                $priority['color'],
                "$name should have a valid 6-digit hex color"
            );
        }
    }

    // =========================================================================
    // Tests: Instance Config Colors
    // =========================================================================

    #[Test]
    public function configColorsOverrideDefaults(): void
    {
        $config = new \PriorityIconsConfig();
        $config->set('enabled', true);
        $config->set('color_emergency', '#ff0000');
        $config->set('color_low', '#00ff00');

        $instance = new \PluginInstance(1, 1, $config);
        $this->plugin->setTestActiveInstances([$instance]);

        $html = '<html><head></head><body></body></html>';
        $result = $this->plugin->injectAssetsIntoOutput($html);

        $cfg = $this->extractConfig($result);

        $this->assertEquals('#ff0000', $cfg['priorities']['Emergency']['color']);
        $this->assertEquals('#00ff00', $cfg['priorities']['Low']['color']);
        // Aliases should also be updated
        $this->assertEquals('#ff0000', $cfg['priorities']['Notfall']['color']);
        $this->assertEquals('#00ff00', $cfg['priorities']['Niedrig']['color']);
    }

    #[Test]
    public function invalidColorIsIgnored(): void
    {
        $config = new \PriorityIconsConfig();
        $config->set('enabled', true);
        $config->set('color_emergency', 'not-a-color');

        $instance = new \PluginInstance(1, 1, $config);
        $this->plugin->setTestActiveInstances([$instance]);

        $html = '<html><head></head><body></body></html>';
        $result = $this->plugin->injectAssetsIntoOutput($html);

        $cfg = $this->extractConfig($result);

        $this->assertEquals('#dc3545', $cfg['priorities']['Emergency']['color']);
    }

    // =========================================================================
    // Tests: XSS Prevention
    // =========================================================================

    #[Test]
    public function configJsonUsesSecureEncoding(): void
    {
        $html = '<html><head></head><body></body></html>';
        $result = $this->plugin->injectAssetsIntoOutput($html);

        // Extract only the config JSON (not the full JS that has </script> closing tags)
        preg_match('/window\.PriorityIconsConfig=(.+?);/', $result, $matches);
        $jsonStr = $matches[1] ?? '';

        // JSON_HEX_TAG should encode < as \u003C
        $this->assertStringNotContainsString('</', $jsonStr, 'JSON should not contain literal </');
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Extract PriorityIconsConfig from injected HTML
     */
    private function extractConfig(string $html): array
    {
        preg_match('/window\.PriorityIconsConfig=(.+?);/', $html, $matches);
        $this->assertNotEmpty($matches, 'Should find PriorityIconsConfig in output');

        $config = json_decode($matches[1], true);
        $this->assertIsArray($config, 'Config should be valid JSON');

        return $config;
    }
}
