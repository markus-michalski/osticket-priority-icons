<?php
/**
 * Priority Icons Plugin - Metadata
 *
 * osTicket plugin that replaces priority text labels with visual
 * color-coded icons using output buffer-based CSS/JS injection.
 *
 * @package    osTicket\Plugins\PriorityIcons
 * @author     Markus Michalski
 * @version    1.0.0
 * @license    GPL-2.0-or-later
 * @link       https://github.com/markus-michalski/osticket-priority-icons
 */

return [
    'id'          => 'osticket:priority-icons',
    'version' =>        '1.0.3',
    'name'        => /* trans */ 'Priority Icons',
    'author'      => 'Markus Michalski',
    'description' => /* trans */ 'Replaces priority text labels with visual color-coded icons',
    'url'         => 'https://github.com/markus-michalski/osticket-priority-icons',
    'plugin'      => 'class.PriorityIconsPlugin.php:PriorityIconsPlugin',
];
