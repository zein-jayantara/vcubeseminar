<?php

/**
 * vcubemeeting_roomsettings block caps.
 *
 * @package    block_vcubemeeting_roomsettings
 * @copyright  V-cube,Inc
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'block/vcubemeeting_roomsettings:view' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

    'block/vcubemeeting_roomsettings:addinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
);
