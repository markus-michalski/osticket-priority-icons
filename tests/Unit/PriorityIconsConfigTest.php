<?php
/**
 * PHPUnit Tests for PriorityIconsConfig
 *
 * TDD Red Phase: These tests define expected behavior BEFORE implementation.
 * All tests should FAIL initially until the config class is implemented.
 *
 * Test Coverage:
 * - getOptions() - All configuration fields present
 * - Default values for all options
 * - Color validation (hex format)
 * - Field types and constraints
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
 * Test suite for PriorityIconsConfig configuration class.
 *
 * Expected class structure (to be implemented):
 *
 * class PriorityIconsConfig extends PluginConfig
 * {
 *     public function getOptions(): array;
 * }
 *
 * Expected options:
 * - enabled: BooleanField (default: true)
 * - show_tooltip: BooleanField (default: true)
 * - icon_style: ChoiceField (default: 'circle', choices: circle/badge/dot)
 * - color_emergency: TextboxField (default: '#dc3545')
 * - color_high: TextboxField (default: '#fd7e14')
 * - color_normal: TextboxField (default: '#ffc107')
 * - color_low: TextboxField (default: '#28a745')
 */
#[CoversClass(\PriorityIconsConfig::class)]
#[Group('unit')]
class PriorityIconsConfigTest extends TestCase
{
    /**
     * Config instance under test.
     */
    private \PriorityIconsConfig $config;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Instantiate config - this will FAIL until class exists
        $this->config = new \PriorityIconsConfig();
    }

    // =========================================================================
    // Tests: Class Structure
    // =========================================================================

    /**
     * Test that config class extends PluginConfig.
     *
     * Expected: PriorityIconsConfig must extend osTicket's PluginConfig base class.
     */
    #[Test]
    public function configExtendsPluginConfig(): void
    {
        $this->assertInstanceOf(
            \PluginConfig::class,
            $this->config,
            'Config must extend PluginConfig'
        );
    }

    /**
     * Test that getOptions() returns an array.
     *
     * Expected: getOptions() should return an array of form field definitions.
     */
    #[Test]
    public function getOptionsReturnsArray(): void
    {
        $options = $this->config->getOptions();

        $this->assertIsArray($options, 'getOptions() must return an array');
        $this->assertNotEmpty($options, 'Options array should not be empty');
    }

    // =========================================================================
    // Tests: Required Configuration Fields
    // =========================================================================

    /**
     * Test that all required option keys are present.
     *
     * Expected: Options array should contain all documented configuration keys.
     */
    #[Test]
    #[DataProvider('requiredOptionKeysProvider')]
    public function getOptionsContainsRequiredKey(string $key, string $description): void
    {
        $options = $this->config->getOptions();

        $this->assertArrayHasKey(
            $key,
            $options,
            "Options should contain '$key' field: $description"
        );
    }

    /**
     * Data provider for required option keys.
     *
     * @return array<string, array{string, string}>
     */
    public static function requiredOptionKeysProvider(): array
    {
        return [
            'enabled toggle'    => ['enabled', 'Master enable/disable toggle'],
            'tooltip toggle'    => ['show_tooltip', 'Tooltip visibility toggle'],
            'icon style'        => ['icon_style', 'Icon display style selector'],
            'emergency color'   => ['color_emergency', 'Emergency priority color'],
            'high color'        => ['color_high', 'High priority color'],
            'normal color'      => ['color_normal', 'Normal priority color'],
            'low color'         => ['color_low', 'Low priority color'],
        ];
    }

    // =========================================================================
    // Tests: Field Types
    // =========================================================================

    /**
     * Test that 'enabled' is a BooleanField.
     *
     * Expected: The enabled option should be a boolean checkbox field.
     */
    #[Test]
    public function enabledFieldIsBooleanField(): void
    {
        $options = $this->config->getOptions();

        $this->assertInstanceOf(
            \BooleanField::class,
            $options['enabled'],
            "'enabled' should be a BooleanField"
        );
    }

    /**
     * Test that 'show_tooltip' is a BooleanField.
     *
     * Expected: The show_tooltip option should be a boolean checkbox field.
     */
    #[Test]
    public function showTooltipFieldIsBooleanField(): void
    {
        $options = $this->config->getOptions();

        $this->assertInstanceOf(
            \BooleanField::class,
            $options['show_tooltip'],
            "'show_tooltip' should be a BooleanField"
        );
    }

    /**
     * Test that 'icon_style' is a ChoiceField.
     *
     * Expected: The icon_style option should be a select/dropdown field.
     */
    #[Test]
    public function iconStyleFieldIsChoiceField(): void
    {
        $options = $this->config->getOptions();

        $this->assertInstanceOf(
            \ChoiceField::class,
            $options['icon_style'],
            "'icon_style' should be a ChoiceField"
        );
    }

    /**
     * Test that color fields are TextboxFields.
     *
     * Expected: All color_* options should be text input fields.
     */
    #[Test]
    #[DataProvider('colorFieldKeysProvider')]
    public function colorFieldIsTextboxField(string $key): void
    {
        $options = $this->config->getOptions();

        $this->assertInstanceOf(
            \TextboxField::class,
            $options[$key],
            "'$key' should be a TextboxField"
        );
    }

    /**
     * Data provider for color field keys.
     *
     * @return array<string, array{string}>
     */
    public static function colorFieldKeysProvider(): array
    {
        return [
            'emergency color' => ['color_emergency'],
            'high color'      => ['color_high'],
            'normal color'    => ['color_normal'],
            'low color'       => ['color_low'],
        ];
    }

    // =========================================================================
    // Tests: Default Values
    // =========================================================================

    /**
     * Test that 'enabled' defaults to true.
     *
     * Expected: Plugin should be enabled by default after installation.
     */
    #[Test]
    public function enabledDefaultsToTrue(): void
    {
        $options = $this->config->getOptions();

        $this->assertTrue(
            $options['enabled']->getDefault(),
            "'enabled' should default to true"
        );
    }

    /**
     * Test that 'show_tooltip' defaults to true.
     *
     * Expected: Tooltips should be shown by default for accessibility.
     */
    #[Test]
    public function showTooltipDefaultsToTrue(): void
    {
        $options = $this->config->getOptions();

        $this->assertTrue(
            $options['show_tooltip']->getDefault(),
            "'show_tooltip' should default to true"
        );
    }

    /**
     * Test that 'icon_style' defaults to 'circle'.
     *
     * Expected: Default style should be the simple circle indicator.
     */
    #[Test]
    public function iconStyleDefaultsToCircle(): void
    {
        $options = $this->config->getOptions();

        $this->assertEquals(
            'circle',
            $options['icon_style']->getDefault(),
            "'icon_style' should default to 'circle'"
        );
    }

    /**
     * Test that color fields have correct default hex values.
     *
     * Expected: Each priority color should have a specific Bootstrap-like default.
     */
    #[Test]
    #[DataProvider('colorDefaultsProvider')]
    public function colorFieldHasCorrectDefault(string $key, string $expectedColor): void
    {
        $options = $this->config->getOptions();

        $this->assertEquals(
            $expectedColor,
            $options[$key]->getDefault(),
            "'$key' should default to '$expectedColor'"
        );
    }

    /**
     * Data provider for color default values.
     *
     * @return array<string, array{string, string}>
     */
    public static function colorDefaultsProvider(): array
    {
        return [
            'emergency red'    => ['color_emergency', '#dc3545'],
            'high orange'      => ['color_high', '#fd7e14'],
            'normal yellow'    => ['color_normal', '#ffc107'],
            'low green'        => ['color_low', '#28a745'],
        ];
    }

    // =========================================================================
    // Tests: Icon Style Choices
    // =========================================================================

    /**
     * Test that icon_style has correct choice options.
     *
     * Expected: Choices should include 'circle', 'badge', and 'dot'.
     */
    #[Test]
    public function iconStyleHasCorrectChoices(): void
    {
        $options = $this->config->getOptions();
        $choices = $options['icon_style']->getChoices();

        $this->assertArrayHasKey('circle', $choices, 'Should have circle option');
        $this->assertArrayHasKey('badge', $choices, 'Should have badge option');
        $this->assertArrayHasKey('dot', $choices, 'Should have dot option');
    }

    /**
     * Test that icon_style has exactly 3 choices.
     *
     * Expected: Only the three documented styles should be available.
     */
    #[Test]
    public function iconStyleHasExactlyThreeChoices(): void
    {
        $options = $this->config->getOptions();
        $choices = $options['icon_style']->getChoices();

        $this->assertCount(
            3,
            $choices,
            'icon_style should have exactly 3 choices'
        );
    }

    // =========================================================================
    // Tests: Field Labels
    // =========================================================================

    /**
     * Test that all fields have labels.
     *
     * Expected: Every field should have a human-readable label for the admin UI.
     */
    #[Test]
    public function allFieldsHaveLabels(): void
    {
        $options = $this->config->getOptions();

        foreach ($options as $key => $field) {
            $this->assertNotEmpty(
                $field->getLabel(),
                "Field '$key' should have a label"
            );
        }
    }

    /**
     * Test that all fields have hints.
     *
     * Expected: Every field should have a help text hint.
     */
    #[Test]
    public function allFieldsHaveHints(): void
    {
        $options = $this->config->getOptions();

        foreach ($options as $key => $field) {
            $this->assertNotEmpty(
                $field->getHint(),
                "Field '$key' should have a hint"
            );
        }
    }

    // =========================================================================
    // Tests: Color Field Constraints
    // =========================================================================

    /**
     * Test that color fields have appropriate max length.
     *
     * Expected: Color fields should be limited to 7 characters (#RRGGBB).
     */
    #[Test]
    #[DataProvider('colorFieldKeysProvider')]
    public function colorFieldHasMaxLength7(string $key): void
    {
        $options = $this->config->getOptions();

        $this->assertEquals(
            7,
            $options[$key]->getMaxLength(),
            "'$key' maxlength should be 7 for hex color"
        );
    }

    /**
     * Test that color fields have appropriate display size.
     *
     * Expected: Color fields should have a small input size (around 10 chars).
     */
    #[Test]
    #[DataProvider('colorFieldKeysProvider')]
    public function colorFieldHasSmallDisplaySize(string $key): void
    {
        $options = $this->config->getOptions();

        $this->assertLessThanOrEqual(
            15,
            $options[$key]->getSize(),
            "'$key' display size should be small"
        );
    }

    // =========================================================================
    // Tests: Color Validation (Hex Format)
    // =========================================================================

    /**
     * Test that default colors are valid hex format.
     *
     * Expected: All default colors should match #RRGGBB pattern.
     */
    #[Test]
    #[DataProvider('colorFieldKeysProvider')]
    public function defaultColorIsValidHexFormat(string $key): void
    {
        $options = $this->config->getOptions();
        $color = $options[$key]->getDefault();

        $this->assertMatchesRegularExpression(
            '/^#[0-9a-fA-F]{6}$/',
            $color,
            "'$key' default should be valid hex color format (#RRGGBB)"
        );
    }

    /**
     * Test that color defaults are lowercase hex.
     *
     * Expected: For consistency, hex colors should use lowercase letters.
     */
    #[Test]
    #[DataProvider('colorFieldKeysProvider')]
    public function defaultColorUsesLowercaseHex(string $key): void
    {
        $options = $this->config->getOptions();
        $color = $options[$key]->getDefault();

        $this->assertEquals(
            strtolower($color),
            $color,
            "'$key' default should use lowercase hex for consistency"
        );
    }

    // =========================================================================
    // Tests: Configuration Access
    // =========================================================================

    /**
     * Test that config values can be retrieved via get() method.
     *
     * Expected: PluginConfig::get() should return field values.
     */
    #[Test]
    public function configValuesCanBeRetrievedViaGet(): void
    {
        // Test retrieving default value
        $enabled = $this->config->get('enabled', false);

        // Should return the default value (true) or the provided fallback
        $this->assertIsBool($enabled);
    }

    /**
     * Test that get() returns default for unknown keys.
     *
     * Expected: Unknown keys should return the provided default value.
     */
    #[Test]
    public function getReturnsDefaultForUnknownKey(): void
    {
        $value = $this->config->get('nonexistent_key', 'default_value');

        $this->assertEquals(
            'default_value',
            $value,
            'Unknown key should return provided default'
        );
    }

    // =========================================================================
    // Tests: Field Configuration Arrays
    // =========================================================================

    /**
     * Test that field config arrays have required keys.
     *
     * Expected: Each field's config should include label, default, and hint.
     */
    #[Test]
    public function fieldsHaveRequiredConfigKeys(): void
    {
        $options = $this->config->getOptions();

        foreach ($options as $key => $field) {
            $config = $field->getConfig();

            $this->assertArrayHasKey(
                'label',
                $config,
                "Field '$key' config should have 'label'"
            );
            $this->assertArrayHasKey(
                'hint',
                $config,
                "Field '$key' config should have 'hint'"
            );
        }
    }

    /**
     * Test that choice field has 'choices' key in config.
     *
     * Expected: ChoiceField should have choices array in its config.
     */
    #[Test]
    public function iconStyleFieldHasChoicesInConfig(): void
    {
        $options = $this->config->getOptions();
        $config = $options['icon_style']->getConfig();

        $this->assertArrayHasKey(
            'choices',
            $config,
            "icon_style config should have 'choices' key"
        );
    }

    // =========================================================================
    // Tests: i18n Support (Translation)
    // =========================================================================

    /**
     * Test that labels use translation wrapper.
     *
     * Expected: Labels should be wrapped in __() for internationalization.
     * Note: In mock environment, __() returns input unchanged.
     */
    #[Test]
    public function labelsAreTranslatable(): void
    {
        $options = $this->config->getOptions();

        // Check that labels are non-empty strings (translation wrapper returns strings)
        foreach ($options as $key => $field) {
            $label = $field->getLabel();
            $this->assertIsString($label, "Label for '$key' should be a string");
            $this->assertGreaterThan(
                3,
                strlen($label),
                "Label for '$key' should be meaningful"
            );
        }
    }

    /**
     * Test that hints use translation wrapper.
     *
     * Expected: Hints should be wrapped in __() for internationalization.
     */
    #[Test]
    public function hintsAreTranslatable(): void
    {
        $options = $this->config->getOptions();

        foreach ($options as $key => $field) {
            $hint = $field->getHint();
            $this->assertIsString($hint, "Hint for '$key' should be a string");
            $this->assertGreaterThan(
                5,
                strlen($hint),
                "Hint for '$key' should be descriptive"
            );
        }
    }

    /**
     * Test that choice labels use translation wrapper.
     *
     * Expected: Choice option labels should be translatable.
     */
    #[Test]
    public function choiceLabelsAreTranslatable(): void
    {
        $options = $this->config->getOptions();
        $choices = $options['icon_style']->getChoices();

        foreach ($choices as $value => $label) {
            $this->assertIsString($label, "Choice label should be a string");
            $this->assertNotEmpty($label, "Choice label should not be empty");
        }
    }
}
