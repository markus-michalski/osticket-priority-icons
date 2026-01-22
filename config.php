<?php
/**
 * Priority Icons Plugin - Configuration
 *
 * Admin configuration form for the Priority Icons plugin.
 * Provides settings for colors, styles, and tooltips.
 *
 * @package    osTicket\Plugins\PriorityIcons
 * @author     Markus Michalski
 * @version    1.0.0
 * @license    GPL-2.0-or-later
 */

declare(strict_types=1);

// Only require base class if not already defined (e.g., by test bootstrap)
if (!class_exists('PluginConfig')) {
    require_once INCLUDE_DIR . 'class.plugin.php';
}

/**
 * Configuration class for Priority Icons plugin settings.
 *
 * Defines admin-configurable options:
 * - Enable/disable toggle
 * - Tooltip visibility
 * - Icon style selection
 * - Priority color customization
 */
class PriorityIconsConfig extends PluginConfig
{
    /**
     * Get configuration form options.
     *
     * Returns array of FormField objects defining the admin
     * configuration interface.
     *
     * @return array<string, FormField> Configuration field definitions
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
                'hint'    => __('Display priority name on hover for accessibility'),
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

            'color_emergency' => new TextboxField([
                'label'     => __('Emergency Color'),
                'default'   => '#dc3545',
                'hint'      => __('Hex color code for emergency priority (e.g., #dc3545)'),
                'size'      => 10,
                'maxlength' => 7,
            ]),

            'color_high' => new TextboxField([
                'label'     => __('High Color'),
                'default'   => '#fd7e14',
                'hint'      => __('Hex color code for high priority (e.g., #fd7e14)'),
                'size'      => 10,
                'maxlength' => 7,
            ]),

            'color_normal' => new TextboxField([
                'label'     => __('Normal Color'),
                'default'   => '#ffc107',
                'hint'      => __('Hex color code for normal priority (e.g., #ffc107)'),
                'size'      => 10,
                'maxlength' => 7,
            ]),

            'color_low' => new TextboxField([
                'label'     => __('Low Color'),
                'default'   => '#28a745',
                'hint'      => __('Hex color code for low priority (e.g., #28a745)'),
                'size'      => 10,
                'maxlength' => 7,
            ]),
        ];
    }
}
