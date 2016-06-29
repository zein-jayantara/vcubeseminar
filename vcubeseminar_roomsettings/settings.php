<?php

/**
 * @package   block_vcubeseminar_roomsettings
 * @copyright V-cube,Inc
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $options = array('allallow'=>get_string('allallow', 'block_vcubeseminar_roomsettings'), 'alldeny'=>get_string('alldeny', 'block_vcubeseminar_roomsettings'));
    $settings->add(new admin_setting_configselect('vcseminar_noblockingcourse',  get_string('noblockcourse', 'block_vcubeseminar_roomsettings'),
    		get_string('noblockcoursedescription', 'block_vcubeseminar_roomsettings'), 'allallow', $options));

}