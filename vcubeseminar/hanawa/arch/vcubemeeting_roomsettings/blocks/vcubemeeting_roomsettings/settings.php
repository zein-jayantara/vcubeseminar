<?php

/**
 * @package   block_vcubemeeting_roomsettings
 * @copyright V-cube,Inc
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $options = array('allallow'=>get_string('allallow', 'block_vcubemeeting_roomsettings'), 'alldeny'=>get_string('alldeny', 'block_vcubemeeting_roomsettings'));
    $settings->add(new admin_setting_configselect('vcmeeting_noblockingcourse',  get_string('noblockcourse', 'block_vcubemeeting_roomsettings'),
    		get_string('noblockcoursedescription', 'block_vcubemeeting_roomsettings'), 'allallow', $options));

}