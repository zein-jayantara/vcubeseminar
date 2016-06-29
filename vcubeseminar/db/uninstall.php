<?php
/**
 * @package    mod
 * @subpackage vcubeseminar
 * @copyright  V-Cube Inc.
 *
 */


defined('MOODLE_INTERNAL') || die();

global $DB;

$DB->delete_records('config', array('name'=>'vcseminar_domain'));
$DB->delete_records('config', array('name'=>'vcseminar_password'));
$DB->delete_records('config', array('name'=>'vcseminar_id'));