<?php
/**
 * @package    mod
 * @subpackage vcubemeeting
 * @copyright  V-Cube Inc.
 *
 */


defined('MOODLE_INTERNAL') || die();

global $DB;
$DB->delete_records('config', array('name'=>'vcmeeting_password'));
$DB->delete_records('config', array('name'=>'vcmeeting_adminpassword'));
$DB->delete_records('config', array('name'=>'vcmeeting_id'));
$DB->delete_records('config', array('name'=>'vcmeeting_domain'));