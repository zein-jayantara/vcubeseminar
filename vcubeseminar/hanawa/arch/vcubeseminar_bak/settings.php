<?php

/**
 *
 * @package    mod
 * @subpackage vcubeseminar
 * @copyright  V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('vcseminar_domain',
        get_string('domain', 'vcubeseminar'), get_string('domaindesc', 'vcubeseminar'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('vcseminar_id',
    	get_string('account', 'vcubeseminar'), get_string('accountdesc', 'vcubeseminar'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configpasswordunmask('vcseminar_password',
    	get_string('password', 'vcubeseminar'), get_string('passworddesc', 'vcubeseminar'), '', PARAM_TEXT));
}
