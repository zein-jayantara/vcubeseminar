<?php

/**
 * Prints a particular instance of vcubeseminar
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
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

//教師フラグ
$tflag = optional_param('tflag2', 0, PARAM_INT);
$teacher_flag = ($tflag == 0)? '':'checked';

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

$obj = new vcseminar();
$timezoneobj=new TimeZoneCancel();
//ログの総数を取得
$totalcount = $obj->get_ondemand_log($vcubeseminar, $tflag, 0, 2000, 1);

//ログを取得
$logdata = $obj->get_ondemand_log($vcubeseminar, $tflag, 0, $totalcount);

//タイムゾーンの取得
$timezone = $vcubeseminar->timezone;
$timezone = ($timezone >= 0)? '+'.$timezone: $timezone;
global $USER;
$timezone = $obj->get_user_timezone($USER->timezone,false);
$timezone = ($timezone >= 0)? '+'.$timezone: $timezone;
//オフセット値取得
$offset=$timezoneobj->getOffset();

//CSVデータ生成
$data = '';
global $COURSE, $USER;
$header1 = array();
$header1[] = get_string('course_fullname', 'vcubeseminar');
$header1[] = $COURSE->fullname;
$header1[] = get_string('room_name', 'vcubeseminar');
$header1[] = $vcubeseminar->name;
$data = '"'.implode('","', $header1).'"'."\r\n";

$header2 = array();
$header2[] = 'No';
$header2[] = get_string('maccount', 'vcubeseminar');
$header2[] = get_string('lastname').' '.get_string('firstname');
$header2[] = get_string('email');
$header2[] = get_string('viewing_starttime', 'vcubeseminar');
$data .= '"'.implode('","', $header2).'"'."\r\n";

$i = 1;
while(list($key, $value) = each($logdata)){
	$timezoneobj->unixTimeToUserTime($value['starttime']);
	$tmp = array();
	$tmp[] = $i++;
	$tmp[] = $value['username'];
	$tmp[] = $value['lastname'].' '.$value['firstname'];
	$tmp[] = $value['email'];
	$tmp[] = date('Y/m/d H:i:s', $value['starttime']);

	$buff = '"'.implode('","', $tmp).'"';
	$data .= $buff."\r\n";
}
//ファイル名生成
$filename = $COURSE->fullname.'_'.$vcubeseminar->name.'_'.date('YmdHis').'.csv';

//ユーザ言語がjaかつIEの時、ファイル名をCP932に変換
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
