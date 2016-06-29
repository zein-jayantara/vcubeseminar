<?php

/**
 * Prints a particular instance of vcubemeeting
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
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

//教師フラグ
$tflag = optional_param('tflag2', 0, PARAM_INT);
$teacher_flag = ($tflag == 0)? '':'checked';

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

$obj = new vcmeeting();
$timezoneobj=new TimeZoneCancelmtg();

//ログの総数を取得
$totalcount = $obj->get_entering_log($cm->id, $tflag, 0, 1, 1);

//ログを取得
$logdata = $obj->get_entering_log($cm->id, $tflag, 0, $totalcount);

//コース内生徒一覧を取得
$students = $obj->get_students($cm->id);
$students = (Array)$students;

//タイムゾーン取得
global $USER;
$timezone=$obj->get_user_timezone($USER->timezone,false);
if($timezone>0){
	$timezone='+'.$timezone;
}

//CSVデータ生成
$data = '';
global $COURSE, $USER;
$header1 = array();
$header1[] = get_string('course_fullname', 'vcubemeeting');
$header1[] = $COURSE->fullname;
$header1[] = get_string('room_name', 'vcubemeeting');
$header1[] = $vcubemeeting->name;
//開始日、終了日の取得
$ret = $obj->get_meeting_detail($vcubemeeting->meetingid);
//タイムゾーン修正
$timezoneobj->apiDateForCsv($ret);

$header1[] = get_string('start_datetime', 'vcubemeeting');
$header1[] = date('Y/m/d H:i:s', $ret['data']['meeting_start_date']);
$header1[] = get_string('end_datetime', 'vcubemeeting');
$header1[] = date('Y/m/d H:i:s', $ret['data']['meeting_end_date']);
$header1[] = get_string('timezone', 'vcubemeeting');
$header1[] = 'UTC'.$timezone;
$data = '"'.implode('","', $header1).'"'."\r\n";

$header2 = array();
$header2[] = 'No';
$header2[] = get_string('maccount', 'vcubemeeting');
$header2[] = get_string('lastname').' '.get_string('firstname');
$header2[] = get_string('email');
$header2[] = get_string('entering_datetime', 'vcubemeeting');
$header2[] = get_string('teacher_flag', 'vcubemeeting');
$data .= '"'.implode('","', $header2).'"'."\r\n";

$i = 1;
while(list($key, $value) = each($logdata)){
	$timezoneobj->dateForHistory($value->time);
	$tmp = array();
	$tmp[] = $i++;
	$tmp[] = $value->username;
	$tmp[] = $value->lastname.' '.$value->firstname;
	$tmp[] = $value->email;
	$tmp[] = date('Y/m/d H:i:s', $value->time);
	$tmp[] = (array_key_exists($value->userid, $students) === true)? '':get_string('tflag', 'vcubemeeting');

	$buff = '"'.implode('","', $tmp).'"';
	$data .= $buff."\r\n";
}
//ファイル名生成
//$now=$timezoneobj->now();
$filename = $COURSE->fullname.'_'.$vcubemeeting->name.'_'.date('YmdHis').'.csv';

//ユーザ言語がja and IEの時、ファイル名をCP932に変換
if($USER->lang == 'ja'){
	if( core_useragent::check_browser_version('MSIE') === true){ //ブラウザがIEの時
		$filename = mb_convert_encoding($filename, 'CP932', 'UTF-8');
	}
}

//ファイルへ出力
@header('Cache-Control: max-age=10');
@header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
@header('Pragma: ');
header("Content-type: application/octet-streamn");
header("Content-Disposition: attachment; filename=\"{$filename}\"");
echo $data;
