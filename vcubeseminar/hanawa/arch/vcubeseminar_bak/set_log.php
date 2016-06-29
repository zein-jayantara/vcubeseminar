<?php

/**
 *
 * @package    mod
 * @subpackage vcubeseminar
 * @copyright  V-Cube Inc.
 */

/// (Replace vcubeseminar with the name of your module and remove this line)

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // vcubeseminar instance ID - it should be named as the first character of the module

$status = required_param('status', PARAM_INT); //記録するログを決める
$url = required_param('url', PARAM_RAW); //記録するログを決める

if ($id) {
	$cm         = get_coursemodule_from_id('vcubeseminar', $id, 0, false, MUST_EXIST);
	$course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
	$vcubeseminar    = $DB->get_record('vcubeseminar', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
	$vcubeseminar    = $DB->get_record('vcubeseminar', array('id' => $n), '*', MUST_EXIST);
	$course     = $DB->get_record('course', array('id' => $vcubeseminar->course), '*', MUST_EXIST);
	$cm         = get_coursemodule_from_instance('vcubeseminar', $vcubeseminar->id, $course->id, false, MUST_EXIST);
} else {
	error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_course::instance($course->id);

switch ($status){
	case 0:
		break;
	case 1: //議事録参照
		$event = \mod_vcubeseminar\event\vcubeseminar_minute_view::create(array(
			'objectid' => $cm->id,
			'context' => context_module::instance($cm->id),
		));
		$event->trigger();
		break;
	case 2: //録画参照
		$event = \mod_vcubeseminar\event\vcubeseminar_recording_view::create(array(
			'objectid' => $cm->id,
			'context' => context_module::instance($cm->id),
		));
		$event->trigger();
		break;
	case 3: //モバイル参照
		$event = \mod_vcubeseminar\event\vcubeseminar_mobile_view::create(array(
		'objectid' => $cm->id,
		'context' => context_module::instance($cm->id),
		));
		$event->trigger();
		break;
}
header("Location: {$url}");