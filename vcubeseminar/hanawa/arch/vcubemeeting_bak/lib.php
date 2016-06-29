<?php

/**
 * Library of interface functions and constants for module vcubemeeting
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the vcubemeeting specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod
 * @subpackage vcubemeeting
 * @copyright  V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once $CFG->dirroot.'/mod/vcubemeeting/locallib.php';

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
function vcubemeeting_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        default:                              return null;
    }
}

/**
 * Saves a new instance of the vcubemeeting into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $vcubemeeting An object from the form in mod_form.php
 * @param mod_vcubemeeting_mod_form $mform
 * @return int The id of the newly inserted vcubemeeting record
 */
function vcubemeeting_add_instance(stdClass $vcubemeeting, mod_vcubemeeting_mod_form $mform = null) {
	global $DB, $COURSE,$USER;

	//フォームの値取得
	$tmp = $mform->get_data();

	$obj = new vcmeeting();

	//タイムゾーンのキャンセル
	$seconds = date_offset_get(new DateTime); //Server timzone offset
	$usertimezone=$obj->get_user_timezone($USER->timezone,false);
	$offset=($usertimezone*3600)-$seconds;
	$tmp->start_datetime+=$offset;
	$tmp->end_datetime+=$offset;
	//これで表記通りの時間となる＝サーバタイムゾーンでの表示時刻となる
	//ここから予約のタイムゾーンで再計算する
	//ex.サーバが9で予約が7なら+2する。
	$offset=$seconds-($tmp->timezone*3600);
	$tmp->start_datetime+=$offset;
	$tmp->end_datetime+=$offset;


	//予約開始
	$ret = $obj->set_new_reserve($tmp);

	//meeting_idの取得
	if($tmp->pass_flag == 0){
		$ret2 = $obj->get_detail($ret['data']['reservation_id'], '');
	}else{
		$ret2 = $obj->get_detail($ret['data']['reservation_id'], $tmp->meeting_password);
	}


	$data = new stdClass();
	$data->course = $COURSE->id;
	//会議名にスタート日時追加
	$obj->setFormat($tmp->name,$tmp->start_datetime, $tmp->timezone);
	$data->name   = $tmp->name;
	$data->intro  = $tmp->introeditor['text'];
	$data->introformat = $tmp->introeditor['format'];
	$data->showdescription = isset($tmp->showdescription)? $tmp->showdescription:0;
	$data->roomid = $tmp->roomid;
	$data->meetingid = $ret2['data']['reservation_info']['info']['meeting_id'];
	$data->reservationid = $ret['data']['reservation_id'];
	$data->inviteurl = $ret['data']['url'];
	$data->pass_flag = $tmp->pass_flag;
	if($tmp->pass_flag != 0) $data->password = $tmp->meeting_password;
	$now = date('U');
	$data->timecreated = $now;
	$data->timemodified = $now;

	$data->id = $DB->insert_record('vcubemeeting', $data);


	//ファイルアップロードの準備
	$data->attachments=$tmp->attachments;
	$data->coursemodule=$vcubemeeting->coursemodule;
	if(isset($tmp->download)){
		$data->download=$tmp->download;
	}else{
		$data->download=0;
	}


	// we need to use context now, so we need to make sure all needed info is already in db
	$DB->set_field('course_modules', 'instance', $data->id, array('id'=>$data->coursemodule));
	//ファイルの保存
	$filelist=$obj->saveFile($data);

	//APIを用いてMeetingへファイルを送る
	$obj->add_documents($data,$ret['data']['reservation_id'],$filelist);


    return $data->id;
}

/**
 * Updates an instance of the vcubemeeting in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $vcubemeeting An object from the form in mod_form.php
 * @param mod_vcubemeeting_mod_form $mform
 * @return boolean Success/Fail
 */
function vcubemeeting_update_instance(stdClass $vcubemeeting, mod_vcubemeeting_mod_form $mform = null) {
    # You may have to add extra stuff in here #
    global $DB,$USER;

    $data = $DB->get_record('vcubemeeting', array('id'=>$vcubemeeting->instance));
    $oldname=$data->name;

	//APIで予約状況（ステータス）を確認
    $obj = new vcmeeting();

    //値の更新
    $buff = $mform->get_data();

    //タイムゾーンのキャンセル
    if(isset($buff->start_datetime)){
	    $seconds = date_offset_get(new DateTime); //Server timzone offset
	    $usertimezone=$obj->get_user_timezone($USER->timezone,false);
	    $offset=($usertimezone*3600)-$seconds;
	    $buff->start_datetime+=$offset;
	    $buff->end_datetime+=$offset;
	    //これで表記通りの時間となる＝サーバタイムゾーンでの表示時刻となる
	    //ここから予約のタイムゾーンで再計算する
	    //ex.サーバが9で予約が7なら+2する。
	    $offset=$seconds-($buff->timezone*3600);
	    $buff->start_datetime+=$offset;
	    $buff->end_datetime+=$offset;
    }

	$ret = $obj->get_meeting_status($vcubemeeting->instance, $data->reservationid, $data->roomid, $data->password);

	//値の更新
	//$buff = $mform->get_data();


	//ステータスが「開始前」であれば更新
	if($ret == 3){
		$buff->roomid = $buff->room;
		$buff->reservationid = $data->reservationid;
		$buff->oldpass = $data->password;
		$buff->pass_flag = (isset($buff->pass_flag))? $buff->pass_flag:0;
		$data->name            =$buff->name;
		$data->intro           = $buff->introeditor['text'];
		$data->showdescription = isset($buff->showdescription)? $buff->showdescription:0;
		$data->pass_flag       = $buff->pass_flag;
		if($buff->pass_flag != 0) $data->password = $buff->meeting_password;
		$now = date('U');
		$data->timemodified = $now;

		//更新API
		$obj->update_reverve($buff);
	}else{
		$data->name            =$buff->name;
		$data->intro           = $buff->introeditor['text'];
		$data->showdescription = isset($buff->showdescription)? $buff->showdescription:0;
	}



	//会議名にスタート日時追加
	if(isset($buff->start_datetime)){
		$obj->setFormat($buff->name,$buff->start_datetime, $buff->timezone);
	}else{
		//旧会議名から開始日時を今の会議名に移植する
		$obj->setFormatFromOldname($buff->name,$oldname);
	}
	$data->name=$buff->name;

	//ファイルアップロードの準備
	$data->attachments=$buff->attachments;
	$data->coursemodule=$vcubemeeting->coursemodule;
	if(isset($buff->download)){
		$data->download=$buff->download;
	}else{
		$data->download=0;
	}

	//ファイルの保存
	$filelist=$obj->saveFile($data);


	if($ret==3){//開催前であればファイルをVmeetingへアップロードする
		//vcubemeeting_filesとAPIで上がったファイルを一旦削除する。
		//$obj->delUpFiles($data);
		$obj->fileManage($data,$filelist);
		//APIを用いてMeetingへファイルを送る
		$obj->add_documents($data,$data->reservationid,$filelist);
	}else if($ret===2){
		//開催中でもダウンロード可否の値は保存する
		$obj->setDownloadOpt($vcubemeeting->instance,$data->download);

	}
	//DB更新
	return $DB->update_record('vcubemeeting', $data);
}

/**
 * Removes an instance of the vcubemeeting from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function vcubemeeting_delete_instance($id) {
    global $DB,$CFG;

    if (! $vcubemeeting = $DB->get_record('vcubemeeting', array('id' => $id))) {
        return false;
    }
	//予約削除
    $obj = new vcmeeting();
    $obj->stop_confernece($vcubemeeting); //会議中止
    $obj->delete_conference($vcubemeeting); //会議削除

    $DB->delete_records('vcubemeeting', array('id' => $id));
    //ファイル削除
    $DB->delete_records('vcubemeeting_files',array('instanceid' => $id));
    $DB->delete_records('vcubemeeting_status',array('instanceid' => $id));

    $DB->delete_records('vcubemeetingurl',array('instanceid' => $id));
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
function vcubemeeting_user_outline($course, $user, $mod, $vcubemeeting) {

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
 * @param stdClass $vcubemeeting the module instance record
 * @return void, is supposed to echp directly
 */
function vcubemeeting_user_complete($course, $user, $mod, $vcubemeeting) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in vcubemeeting activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function vcubemeeting_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link vcubemeeting_print_recent_mod_activity()}.
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
function vcubemeeting_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see vcubemeeting_get_recent_mod_activity()}

 * @return void
 */
function vcubemeeting_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function vcubemeeting_cron () {
    return false;
}

/**
 * Returns an array of users who are participanting in this vcubemeeting
 *
 * Must return an array of users who are participants for a given instance
 * of vcubemeeting. Must include every user involved in the instance,
 * independient of his role (student, teacher, admin...). The returned
 * objects must contain at least id property.
 * See other modules as example.
 *
 * @param int $vcubemeetingid ID of an instance of this module
 * @return boolean|array false if no participants, array of objects otherwise
 */
function vcubemeeting_get_participants($vcubemeetingid) {
    return false;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function vcubemeeting_get_extra_capabilities() {
    return array();
}

////////////////////////////////////////////////////////////////////////////////
// Gradebook API                                                              //
////////////////////////////////////////////////////////////////////////////////

/**
 * Is a given scale used by the instance of vcubemeeting?
 *
 * This function returns if a scale is being used by one vcubemeeting
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $vcubemeetingid ID of an instance of this module
 * @return bool true if the scale is used by the given vcubemeeting instance
 */
// function vcubemeeting_scale_used($vcubemeetingid, $scaleid) {
//     global $DB;

//     /** @example */
//     if ($scaleid and $DB->record_exists('vcubemeeting', array('id' => $vcubemeetingid, 'grade' => -$scaleid))) {
//         return true;
//     } else {
//         return false;
//     }
// }

/**
 * Checks if scale is being used by any instance of vcubemeeting.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean true if the scale is used by any vcubemeeting instance
 */
// function vcubemeeting_scale_used_anywhere($scaleid) {
//     global $DB;

//     /** @example */
//     if ($scaleid and $DB->record_exists('vcubemeeting', array('grade' => -$scaleid))) {
//         return true;
//     } else {
//         return false;
//     }
// }

/**
 * Creates or updates grade item for the give vcubemeeting instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $vcubemeeting instance object with extra cmidnumber and modname property
 * @return void
 */
// function vcubemeeting_grade_item_update(stdClass $vcubemeeting,$grades=NULL) {
//     global $CFG,$COURSE,$DB;
//     require_once($CFG->libdir.'/gradelib.php');

//     if(!isset($vcubemeeting->name)) $vcubemeeting->name = $vcubemeeting->itemname;

//     $item = array();
//     $item['itemname'] = clean_param($vcubemeeting->name, PARAM_NOTAGS);
//     $item['gradetype'] = GRADE_TYPE_VALUE;
//     $item['grademax']  = 100;
//     $item['grademin']  = 0;


//     if ($grades  === 'reset') { //リセット処理
//     	$item['reset'] = true;
//     	$grades = NULL;
//     }
//  	if(!isset($vcubemeeting->instance)){ $vcubemeeting->instance = $vcubemeeting->iteminstance;}
//  	if(!isset($vcubemeeting->courseid)){ $vcubemeeting->courseid = $vcubemeeting->course;}

//  	if($vcubemeeting->instance == 0) return false;


//     return grade_update('mod/vcubemeeting', $vcubemeeting->courseid, 'mod', 'vcubemeeting', $vcubemeeting->instance, 0, $grades, $item);
// }

/**
 * Update vcubemeeting grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $vcubemeeting instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
// function vcubemeeting_update_grades(stdClass $vcubemeeting, $userid = 0,$nullifnone = true) {
//     global $CFG, $DB;
//     require_once($CFG->libdir.'/gradelib.php');

//     $tmp = new stdClass();
//     $tmp->id = $userid;
//     $tmp->userid = $userid;
//     $tmp->rawgrade = $vcubemeeting->rawgrade;
//     $tmp->timecreated = time();
//     $grades[$userid] = $tmp;

//     vcubemeeting_grade_item_update($vcubemeeting, $grades);
// }

// function vcubemeeting_get_user_grades($vcubemeeting, $userid){
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
// function vcubemeeting_get_file_areas($course, $cm, $context) {
//     return array();
// }

/**
 * Serves the files from the vcubemeeting file areas
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return void this should never return to the caller
 */
// function vcubemeeting_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload) {
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
//     $file = $fs->get_file($context->id, 'mod_vcubemeeting', $filearea, $itemid, $filepath, $filename);
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
 * Extends the global navigation tree by adding vcubemeeting nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the vcubemeeting module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
// function vcubemeeting_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
// }

/**
 * Extends the settings navigation with the vcubemeeting settings
 *
 * This function is called when the context for the page is a vcubemeeting module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $vcubemeetingnode {@link navigation_node}
 */
// function vcubemeeting_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $vcubemeetingnode=null) {
// }

////////////////////////////////////////////////////////////////////////////////
// Reset API                                                                  //
////////////////////////////////////////////////////////////////////////////////
// function vcubemeeting_reset_course_form_definition(&$mform) {
// 	$mform->addElement('header', 'vcubemeetingheader', get_string('modulenameplural', 'vcubemeeting'));
// 	$mform->addElement('advcheckbox', 'reset_vcubemeeting', get_string('deleteallattempts', 'vcubemeeting'));
// }

// function vcubemeeting_reset_course_form_defaults($course) {
// 	return array('reset_vcubemeeting'=>1);
// }

// function vcubemeeting_reset_gradebook($courseid, $type='') {
// 	global $CFG, $DB;
// 	$sql = "SELECT s.*, cm.idnumber as cmidnumber, s.course as courseid
// 	FROM {vcubemeeting} s, {course_modules} cm, {modules} m
// 	WHERE m.name='vcubemeeting' AND m.id=cm.module AND cm.instance=s.id AND s.course=?";

// 	if ($vcubemeetings = $DB->get_records_sql($sql, array($courseid))) {
// 		foreach ($vcubemeetings as $vcubemeeting) {
// 			$vcubemeeting->iteminstance = $vcubemeeting->id;
// 			vcubemeeting_grade_item_update($vcubemeeting, 'reset');
// 		}
// 	}
// }

// function vcubemeeting_reset_userdata($data) {
// 	global $CFG, $DB;

// 	$componentstr = get_string('modulenameplural', 'vcubemeeting');
// 	$status = array();

// 	if (!empty($data->reset_vcubemeeting)) {
// 		$scormssql = "SELECT s.id
// 		FROM {vcubemeeting} s
// 		WHERE s.course=?";

// 		// remove all grades from gradebook
// 		if (empty($data->reset_gradebook_grades)) {
// 			vcubemeeting_reset_gradebook($data->courseid);
// 		}

// 		$status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallattempts', 'vcubemeeting'), 'error'=>false);
// 	}

// 	// no dates to shift here

// 	return $status;
// }
