<?php
/**
 * @package    mod
 * @subpackage vcubemeeting
 * @copyright  V-Cube Inc.
 *
 */

global $DB;

//Block設定の削除
$DB->delete_records('config', array('name'=>'vcmeeting_noblockingcourse'));