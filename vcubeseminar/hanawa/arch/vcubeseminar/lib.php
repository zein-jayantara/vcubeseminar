<?php

/**
 * Library of interface functions and constants for module vcubeseminar
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the vcubeseminar specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod
 * @subpackage vcubeseminar
 * @copyright  V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once $CFG->dirroot.'/mod/vcubeseminar/locallib.php';

/** example constant */
//define('NEWMODULE_ULTIMATE_ANSWER', 42);

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function vcubeseminar_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_BACKUP_MOODLE2:          return false;
        default:                              return null;
    }
}

/**
 * Saves a new instance of the vcubeseminar into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $vcubeseminar An object from the form in mod_form.php
 * @param mod_vcubeseminar_mod_form $mform
 * @return int The id of the newly inserted vcubeseminar record
 */
function vcubeseminar_add_instance(stdClass $vcubeseminar, mod_vcubeseminar_mod_form $mform = null) {
	global $DB, $COURSE,$USER;

	// get form_paramater
	$tmp = $mform->get_data();
	$obj = new vcseminar();
	$timezoneobj= new TimeZoneCancel();

	//seminar_type 0:live_seminar 1:ondemand_seminar
	if($tmp->seminar_type == 0){
		$tmp2=clone $tmp;
		$timezoneobj->dateTimeSelectorForAPI($tmp2);//API用のタイムゾーン変換
		$seminar_key = $obj->set_new_reserve($tmp2);//APIから予約開始

		$timezoneobj->dateTimeSelectorForMoodle($tmp);
		$now = date('U');

		$data = new stdClass();
		$data->course = $COURSE->id;
		$obj->setFormat($tmp->name, $tmp->start_datetime, $tmp->timezone);
		$data->name   = $tmp->name;
		$data->intro  = $tmp->introeditor['text'];
		$data->introformat = $tmp->introeditor['format'];
		$data->showdescription = isset($tmp->showdescription)? $tmp->showdescription:0;
		$data->vcubeseminardomainid = $tmp->vcubeseminardomainid;
		$data->roomid = $tmp->roomid;
		$data->starttime = $tmp->start_datetime;
		$data->curtaintime = $tmp->curtaintime;
		$data->timezone = $tmp->timezone;
		$data->endtime = $tmp->end_datetime;
		$data->seminarkey = $seminar_key;
		$data->timecreated = $now;
		$data->timemodified = $now;
		$data->seminar_type = $tmp->seminar_type;

	}else{
		$now = date('U');
		$timezoneobj->dateTimeSelectorForMoodle($tmp);
		$seminar_key = $tmp->ondemand;
		$data = new stdClass();
		$data->course = $COURSE->id;
		$data->name   = $tmp->name;
		$data->intro  = $tmp->introeditor['text'];
		$data->introformat = $tmp->introeditor['format'];
		$data->showdescription = isset($tmp->showdescription)? $tmp->showdescription:0;
		$data->vcubeseminardomainid = $tmp->vcubeseminardomainid;
		$data->roomid = $tmp->roomid;
		$data->seminarkey = $seminar_key;
		$data->timecreated = $now;
		$data->timemodified = $now;
		$data->seminar_type = $tmp->seminar_type;

	}
	$data->id = $DB->insert_record('vcubeseminar', $data);
    return $data->id;
}

/**
 * Updates an instance of the vcubeseminar in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $vcubeseminar An object from the form in mod_form.php
 * @param mod_vcubeseminar_mod_form $mform
 * @return boolean Success/Fail
 */
function vcubeseminar_update_instance(stdClass $vcubeseminar, mod_vcubeseminar_mod_form $mform = null) {
    # You may have to add extra stuff in here #
    global $DB,$USER;

    $data = $DB->get_record('vcubeseminar', array('id'=>$vcubeseminar->instance));

	//APIで予約状況（ステータス）を確認
    $obj = new vcseminar();
	$ret = $obj->get_seminar_status($data);


	//値の更新
	$buff = $mform->get_data();



	//ステータスが「開始前」であれば更新
	if($ret == 3){

		$buff->roomid = $buff->room;
		$buff->seminarkey = $data->seminarkey;

		$timezoneobj= new TimeZoneCancel();
		$buff2=clone $buff;
		//API用のタイムゾーン変換
		$timezoneobj->dateTimeSelectorForAPI($buff2);

		//更新API
		$obj->update_reverve($buff2);


		//Moodle用のタイムゾーン変換
		$timezoneobj->dateTimeSelectorForMoodle($buff);

		$data->name            = $buff->name;
		$data->intro           = $buff->introeditor['text'];
		$data->showdescription = isset($buff->showdescription)? $buff->showdescription:0;
		$data->starttime       = $buff->start_datetime;
		$data->curtaintime     = $buff->curtaintime;
		$data->endtime         = $buff->end_datetime;
		$data->timezone         = $buff->timezone;

		$now = date('U');
		$data->timemodified = $now;
		//名前に日時付与
		$obj->setFormat($data->name, $buff->start_datetime, $buff->timezone);

		//ファイルアップロードの準備//
		$data->attachments_whiteboard=$buff->attachments_whiteboard;
		$data->attachments_filecabinet=$buff->attachments_filecabinet;
		$data->attachments_banner=$buff->attachments_banner;
		$data->coursemodule=$vcubeseminar->coursemodule;

		if(isset($buff->is_animation)) $data->is_animation = $buff->is_animation;
		else $data->is_animation=0;

		if(isset($buff->download_whiteboard)) $data->download_whiteboard = $buff->download_whiteboard;
		else $data->download_whiteboard=0;

		if(isset($buff->download_filecabinet)) $data->download_filecabinet = $buff->download_filecabinet;
		else $data->download_filecabinet=0;

		if(isset($buff->link_url)) $data->link_url = $buff->link_url;
		else $data->link_url='';

		//ファイルの保存
		$filelist=$obj->saveFile($data);
		//vcubemeeting_filesとAPIで上がったファイルを一旦削除する。
		$obj->delUpFiles($data);
		//APIを用いてMeetingへファイルを送る
		$obj->add_documents($data,$filelist,$data);

	}else if($ret == 4){

		$data->name            =$buff->name;
		$data->intro           = $buff->introeditor['text'];
		$data->showdescription = isset($buff->showdescription)? $buff->showdescription:0;
		$data->roomid = $buff->roomid;
		$data->seminarkey = $buff->ondemand;
		$obj->minutes_list_reset($data);
		$obj->get_minutes($data);
	}else{

		$data->name            =$buff->name;
		$data->intro           = $buff->introeditor['text'];
		$data->showdescription = isset($buff->showdescription)? $buff->showdescription:0;
		$obj->setFormat($data->name, $data->starttime, $data->timezone);
	}
	//DB更新
	return $DB->update_record('vcubeseminar', $data);
}

/**
 * Removes an instance of the vcubeseminar from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function vcubeseminar_delete_instance($id) {
    global $DB,$CFG;

    if (! $vcubeseminar = $DB->get_record('vcubeseminar', array('id' => $id))) {
        return false;
    }
	//予約削除
    $obj = new vcseminar();
    $obj->delete_seminar($vcubeseminar);

    $DB->delete_records('vcubeseminar', array('id' => $id));
    $DB->delete_records('vcubeseminarlog', array('instanceid' => $id));
    $DB->delete_records('vcubeseminarurl', array('instanceid' => $id));
    $DB->delete_records('vcubeseminar_files', array('instanceid' => $id));
    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function vcubeseminar_user_outline($course, $user, $mod, $vcubeseminar) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $vcubeseminar the module instance record
 * @return void, is supposed to echp directly
 */
function vcubeseminar_user_complete($course, $user, $mod, $vcubeseminar) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in vcubeseminar activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function vcubeseminar_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link vcubeseminar_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function vcubeseminar_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see vcubeseminar_get_recent_mod_activity()}

 * @return void
 */
function vcubeseminar_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function vcubeseminar_cron () {
    return false;
}

/**
 * Returns an array of users who are participanting in this vcubeseminar
 *
 * Must return an array of users who are participants for a given instance
 * of vcubeseminar. Must include every user involved in the instance,
 * independient of his role (student, teacher, admin...). The returned
 * objects must contain at least id property.
 * See other modules as example.
 *
 * @param int $vcubeseminarid ID of an instance of this module
 * @return boolean|array false if no participants, array of objects otherwise
 */
function vcubeseminar_get_participants($vcubeseminarid) {
    return false;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function vcubeseminar_get_extra_capabilities() {
    return array();
}

/**
 * This function return an array of valid vucubeseminar_domain records
 *
 * @global object
 * @global object
 * @return array
 */
function vcubeseminar_get_domains($id = 0) {
    global $CFG, $DB;
    $result = array();

    if($id) {
        $domains = $DB->get_records("vcubeseminar_domain", array('id' => $id));
    }
    else {
        $domains = $DB->get_records("vcubeseminar_domain");
    }

    uasort($domains, function($data1, $data2) {
        return strcmp($data1->id, $data2->id);
    });
    foreach($domains as $domain) {
        $result[] = array(
            "id" => $domain->id,
            "alias" => $domain->alias,
            "domain" => $domain->vcseminar_domain,
            "account" => $domain->vcseminar_id,
            "password" => $domain->vcseminar_password,
        );
    }

    return $result;
}

function vcubeseminar_get_domain_strings() {
    return array(
        "edit" => get_string('domainlist_edit', "vcubeseminar"),
        "update" => get_string('domainlist_update', "vcubeseminar"),
        "cancel" => get_string('domainlist_cancel', "vcubeseminar"),
        "delete" => get_string('domainlist_delete', "vcubeseminar"),
    );
}

//duplicate acrivity method//
//function mod_duplicate_activity($course, $cm, $sr = null) {};

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Is a given scale used by the instance of vcubeseminar?
 *
 * This function returns if a scale is being used by one vcubeseminar
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $vcubeseminarid ID of an instance of this module
 * @return bool true if the scale is used by the given vcubeseminar instance
 */
// function vcubeseminar_scale_used($vcubeseminarid, $scaleid) {
//     global $DB;

//     /** @example */
//     if ($scaleid and $DB->record_exists('vcubeseminar', array('id' => $vcubeseminarid, 'grade' => -$scaleid))) {
//         return true;
//     } else {
//         return false;
//     }
// }

/**
 * Checks if scale is being used by any instance of vcubeseminar.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any vcubeseminar instance
 */
// function vcubeseminar_scale_used_anywhere($scaleid) {
//     global $DB;

//     /** @example */
//     if ($scaleid and $DB->record_exists('vcubeseminar', array('grade' => -$scaleid))) {
//         return true;
//     } else {
//         return false;
//     }
// }

/**
 * Creates or updates grade item for the give vcubeseminar instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $vcubeseminar instance object with extra cmidnumber and modname property
 * @return void
 */
// function vcubeseminar_grade_item_update(stdClass $vcubeseminar,$grades=NULL) {
//     global $CFG,$COURSE,$DB;
//     require_once($CFG->libdir.'/gradelib.php');

//     if(!isset($vcubeseminar->name)) $vcubeseminar->name = $vcubeseminar->itemname;

//     $item = array();
//     $item['itemname'] = clean_param($vcubeseminar->name, PARAM_NOTAGS);
//     $item['gradetype'] = GRADE_TYPE_VALUE;
//     $item['grademax']  = 100;
//     $item['grademin']  = 0;


//     if ($grades  === 'reset') { //リセット処理
//     	$item['reset'] = true;
//     	$grades = NULL;
//     }
//  	if(!isset($vcubeseminar->instance)){ $vcubeseminar->instance = $vcubeseminar->iteminstance;}
//  	if(!isset($vcubeseminar->courseid)){ $vcubeseminar->courseid = $vcubeseminar->course;}

//  	if($vcubeseminar->instance == 0) return false;


//     return grade_update('mod/vcubeseminar', $vcubeseminar->courseid, 'mod', 'vcubeseminar', $vcubeseminar->instance, 0, $grades, $item);
// }

/**
 * Update vcubeseminar grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $vcubeseminar instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
// function vcubeseminar_update_grades(stdClass $vcubeseminar, $userid = 0,$nullifnone = true) {
//     global $CFG, $DB;
//     require_once($CFG->libdir.'/gradelib.php');

//     $tmp = new stdClass();
//     $tmp->id = $userid;
//     $tmp->userid = $userid;
//     $tmp->rawgrade = $vcubeseminar->rawgrade;
//     $tmp->timecreated = time();
//     $grades[$userid] = $tmp;

//     vcubeseminar_grade_item_update($vcubeseminar, $grades);
// }

// function vcubeseminar_get_user_grades($vcubeseminar, $userid){
// 	return false;
// }

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
// function vcubeseminar_get_file_areas($course, $cm, $context) {
//     return array();
// }

/**
 * Serves the files from the vcubeseminar file areas
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return void this should never return to the caller
 */
// function vcubeseminar_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload) {
//     global $DB, $CFG;

//     if ($context->contextlevel != CONTEXT_MODULE) {
//         send_file_not_found();
//     }

//     require_login($course, true, $cm);

//     $itemid = array_shift($args);
//     $filename = array_pop($args);
//     if (!$args) {
//     	$filepath = '/'; // $args is empty => the path is '/'
//     } else {
//     	$filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
//     }

//     $fs = get_file_storage();
//     $file = $fs->get_file($context->id, 'mod_vcubeseminar', $filearea, $itemid, $filepath, $filename);
//     if (!$file) {
//     	send_file_not_found(); // The file does not exist.
//     }

//     send_file($file, 86400, 0, $forcedownload, $options);
// //     send_file_not_found();
// }

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding vcubeseminar nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the vcubeseminar module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
// function vcubeseminar_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
// }

/**
 * Extends the settings navigation with the vcubeseminar settings
 *
 * This function is called when the context for the page is a vcubeseminar module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $vcubeseminarnode {@link navigation_node}
 */
// function vcubeseminar_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $vcubeseminarnode=null) {
// }

////////////////////////////////////////////////////////////////////////////////
// Reset API                                                                  //
////////////////////////////////////////////////////////////////////////////////
// function vcubeseminar_reset_course_form_definition(&$mform) {
// 	$mform->addElement('header', 'vcubeseminarheader', get_string('modulenameplural', 'vcubeseminar'));
// 	$mform->addElement('advcheckbox', 'reset_vcubeseminar', get_string('deleteallattempts', 'vcubeseminar'));
// }

// function vcubeseminar_reset_course_form_defaults($course) {
// 	return array('reset_vcubeseminar'=>1);
// }

// function vcubeseminar_reset_gradebook($courseid, $type='') {
// 	global $CFG, $DB;
// 	$sql = "SELECT s.*, cm.idnumber as cmidnumber, s.course as courseid
// 	FROM {vcubeseminar} s, {course_modules} cm, {modules} m
// 	WHERE m.name='vcubeseminar' AND m.id=cm.module AND cm.instance=s.id AND s.course=?";

// 	if ($vcubeseminars = $DB->get_records_sql($sql, array($courseid))) {
// 		foreach ($vcubeseminars as $vcubeseminar) {
// 			$vcubeseminar->iteminstance = $vcubeseminar->id;
// 			vcubeseminar_grade_item_update($vcubeseminar, 'reset');
// 		}
// 	}
// }

// function vcubeseminar_reset_userdata($data) {
// 	global $CFG, $DB;

// 	$componentstr = get_string('modulenameplural', 'vcubeseminar');
// 	$status = array();

// 	if (!empty($data->reset_vcubeseminar)) {
// 		$scormssql = "SELECT s.id
// 		FROM {vcubeseminar} s
// 		WHERE s.course=?";

// 		// remove all grades from gradebook
// 		if (empty($data->reset_gradebook_grades)) {
// 			vcubeseminar_reset_gradebook($data->courseid);
// 		}

// 		$status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallattempts', 'vcubeseminar'), 'error'=>false);
// 	}

// 	// no dates to shift here

// 	return $status;
// }
