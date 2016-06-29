<?php

/**
 *
 * @package    mod
 * @subpackage vcubemeeting
 * @copyright  V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    //--- general settings -----------------------------------------------------------------------------------
    $settings->add(new admin_setting_configtext('vcmeeting_domain',
        get_string('domain', 'vcubemeeting'), get_string('domaindesc', 'vcubemeeting'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configtext('vcmeeting_id',
    	get_string('account', 'vcubemeeting'), get_string('accountdesc', 'vcubemeeting'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configpasswordunmask('vcmeeting_password',
    	get_string('password', 'vcubemeeting'), get_string('passworddesc', 'vcubemeeting'), '', PARAM_TEXT));
    $settings->add(new admin_setting_configpasswordunmask('vcmeeting_adminpassword',
    	get_string('adminpassword', 'vcubemeeting'), get_string('adminpassworddesc', 'vcubemeeting'), '', PARAM_TEXT));

}
