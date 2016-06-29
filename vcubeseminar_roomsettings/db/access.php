<?php

/**
 * vcubeseminar_roomsettings block caps.
 *
 * @package    block_vcubeseminar_roomsettings
 * @copyright  V-cube,Inc
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'block/vcubeseminar_roomsettings:view' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

    'block/vcubeseminar_roomsettings:addinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
);
