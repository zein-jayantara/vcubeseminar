<?php

/**
 *
 * @package    mod
 * @subpackage vcubemeeting
 * @copyright  V-Cube Inc.
 */

/// (Replace vcubemeeting with the name of your module and remove this line)

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // vcubemeeting instance ID - it should be named as the first character of the module

$number = optional_param('number', 0, PARAM_INT);//議事、録画番号
$status = required_param('status', PARAM_INT); //記録するログを決める
$url = required_param('url', PARAM_RAW); //記録するログを決める

if ($id) {
	$cm         = get_coursemodule_from_id('vcubemeeting', $id, 0, false, MUST_EXIST);
	$course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
	$vcubemeeting    = $DB->get_record('vcubemeeting', array('id' => $cm->instance), '*', MUST_EXIST);
} elseif ($n) {
	$vcubemeeting    = $DB->get_record('vcubemeeting', array('id' => $n), '*', MUST_EXIST);
	$course     = $DB->get_record('course', array('id' => $vcubemeeting->course), '*', MUST_EXIST);
	$cm         = get_coursemodule_from_instance('vcubemeeting', $vcubemeeting->id, $course->id, false, MUST_EXIST);
} else {
	error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_course::instance($course->id);

switch ($status){
	case 0: //入室
		$event = \mod_vcubemeeting\event\vcubemeeting_room_entering::create(array(
				'objectid' => $cm->id,
				'context' => context_module::instance($cm->id)
		));
		$event->trigger();
		break;
	case 1: //議事録参照
		$event = \mod_vcubemeeting\event\vcubemeeting_minute_view::create(array(
			'objectid' => $cm->id,
			'context' => context_module::instance($cm->id),
			'other' => $number
		));
		$event->trigger();
		break;
	case 2: //録画参照
		$event = \mod_vcubemeeting\event\vcubemeeting_recording_view::create(array(
			'objectid' => $cm->id,
			'context' => context_module::instance($cm->id),
			'other' => $number
		));
		$event->trigger();
		break;
}
header("Location: {$url}");
