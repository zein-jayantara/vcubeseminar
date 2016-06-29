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

$page = optional_param('page', 0, PARAM_INT);
$act = optional_param('act', null, PARAM_TEXT);

$perpage = 10; //1ページ当たりの履歴数
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

$completion=new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->https_required();
/// Print the page header

$PAGE->set_url('/mod/vcubemeeting/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($vcubemeeting->name));
$PAGE->set_heading(format_string($course->fullname));

// other things you may want to set - remove if not needed
$PAGE->set_cacheable(false);

//JS Scriptの言語パック参照に必要
global $USER;
$strmgr = get_string_manager();
$strings = $strmgr->load_component_strings('vcubemeeting', $USER->lang);
$PAGE->requires->strings_for_js(array_keys($strings), 'vcubemeeting');
/////////

$PAGE->requires->jquery();
$PAGE->requires->js('/mod/vcubemeeting/setlog.js');
$PAGE->requires->css('/mod/vcubemeeting/style.css');

// Replace the following lines with you own code
$obj_vcm = new vcmeeting();
$timezoneobj=new TimeZoneCancelmtg();

if(has_capability('mod/vcubemeeting:addinstance', $context)){
	//会議中止が押された時の処理
	$stop_flag = optional_param('stop', 0, PARAM_INT);
	if($stop_flag == 1){
		$obj_vcm->stop_confernece($vcubemeeting);
		$url = $CFG->wwwroot.'/course/view.php?id='.$vcubemeeting->course;
		header("Location: {$url}");
	}
	//パスワード設定・削除が押された時
	$change_pass_flag = optional_param('change_pass', 0, PARAM_INT);
	if($change_pass_flag == 1){ //設定
		$_password = required_param('password', PARAM_ALPHANUM);
		$obj_vcm->set_password($vcubemeeting->meetingid, $_password);
	}
	if($change_pass_flag == 2){ //削除
		$obj_vcm->unset_password($vcubemeeting->meetingid);
	}

}
// Output starts here
echo $OUTPUT->header();
//開催状態判定
$ret = $obj_vcm->get_meeting_status($vcubemeeting->id, $vcubemeeting->reservationid, $vcubemeeting->roomid, $vcubemeeting->password);
//部屋名の取得
$rooms = $obj_vcm->get_room_list();

if($ret != 0){
	//部屋詳細取得
	$room_detail = $obj_vcm->get_detail($vcubemeeting->reservationid, $vcubemeeting->password);
	//タイムゾーンのキャンセル
	$timezoneobj->apiDateForMoodle($room_detail);

	$timezone=$obj_vcm->get_user_timezone($USER->timezone,false);
	$timezone = ($timezone >= 0)? '+'.$timezone: $timezone;
}

//部屋状態の取得
$room_status = $obj_vcm->get_room_detail($vcubemeeting->roomid);
$extention_flag = ($room_status['data']['room_status'][0]['meeting_id'] == $vcubemeeting->meetingid)? true:false;
$atend_num = $room_status['data']['room_status'][0]['pcount'];
echo $OUTPUT->heading($vcubemeeting->name);
switch ($ret) {
	case 3://開催前

		echo '<div class="desc">';
		echo $vcubemeeting->intro.'<br>';
		echo '</div>';
		echo '<div class="desc">';

		//日時、部屋名表示
		$obj_vcm->outputstatus($rooms[$vcubemeeting->roomid]['name'],date('Y-m-d H:i', $room_detail['data']['reservation_info']['info']['reservation_start_date']).' (UTC'.$timezone.')',date('Y-m-d H:i', $room_detail['data']['reservation_info']['info']['reservation_end_date']).' (UTC'.$timezone.')');

		//ミーティングの状態
		echo '<h2 class="status">'.get_string('pre_open_meeting', 'vcubemeeting').'</h2>';

		echo '</div>';

		//アップロードされたファイルの状態表示
		$obj_vcm->showFilesBlock($vcubemeeting->id,$cm->id,3,has_capability('mod/vcubemeeting:addinstance', $context),true);


	break;
	case 2://開催中
		echo '<div class="desc">';
		echo $vcubemeeting->intro;
		echo '</div>';

		echo '<div class="desc">';



		//日時、部屋名、参加人数表示
		if($extention_flag == true){
			$obj_vcm->outputstatus($rooms[$vcubemeeting->roomid]['name'],date('Y-m-d H:i', $room_detail['data']['reservation_info']['info']['reservation_start_date']).' (UTC'.$timezone.')',date('Y-m-d H:i', $room_detail['data']['reservation_info']['info']['reservation_end_date']).' (UTC'.$timezone.')',$atend_num);

		}else{
			$obj_vcm->outputstatus($rooms[$vcubemeeting->roomid]['name'],date('Y-m-d H:i', $room_detail['data']['reservation_info']['info']['reservation_start_date']).' (UTC'.$timezone.')',date('Y-m-d H:i', $room_detail['data']['reservation_info']['info']['reservation_end_date']).' (UTC'.$timezone.')');

		}

		//ミーティングの状態
		echo '<h2 class="status">'.get_string('open_meeting', 'vcubemeeting').'</h2>';


		echo '</div>';

		//アップロードされたファイルの状態表示
		$obj_vcm->showFilesBlock($vcubemeeting->id,$cm->id,2,has_capability('mod/vcubemeeting:addinstance', $context),true);

		//入室用ボタン表示
		echo '<div class="hscenter">';
		echo <<< HTMLFORM
<form name="form3" id="form3" action="set_log.php" method="post" target="new">
<input type="hidden" name="url" id="url" value="" />
<input type="hidden" name="status" id="status" value="" />
<input type="hidden" name="number" id="number" value="0" />
<input type="hidden" name="id" id="id" value="{$cm->id}" />
HTMLFORM;
		$url = '';
		global $USER;
		if(has_capability('mod/vcubemeeting:addinstance', $context) || is_siteadmin($USER->id)){
			//教師
			$type = core_useragent::get_device_type();
			if( ($type == 'mobile') || ($type == 'tablet')){
				//モバイル
				$url =  $obj_vcm->get_attend_url($vcubemeeting, 1, 1);
			}else{
				//PC
				$url =  $obj_vcm->get_attend_url($vcubemeeting, 1, 0);
			}
		}else{
			//学生
			$url =  $obj_vcm->get_attend_url($vcubemeeting, 0, 0);
		}
		$msg = get_string('entering', 'vcubemeeting');
		echo <<< HTML
<input type="button" name="enter" value="{$msg}" onClick="set_jump(0, '{$url}', 0)" />
</form>
HTML;

		if(has_capability('mod/vcubemeeting:addinstance', $context)&&($extention_flag == true)){
			//会議中断ボタンを表示
			$msg = get_string('stop_confirence', 'vcubemeeting');
			$conf_stop_button = <<< HTML
<form action="" name="form1" method="get" onSubmit="return stop_confernce();return false;" >
<input type="hidden" name="stop" value="1">
<input type="hidden" name="id" value="{$id}">
<input type="hidden" name="n" value="{$n}">
<input type="submit" name="stop_conf" value="{$msg}" >
</form><br>
HTML;
			echo $conf_stop_button;
		}

		echo '</div>';






	break;
	default://開催後

		//会議終了後、初回アクセス時の処理
		$obj_vcm->endMeetingFirstAccess($vcubemeeting);
		$ret = $obj_vcm->get_meeting_detail($vcubemeeting->meetingid);
		echo '<div class="desc">';
		echo $vcubemeeting->intro;
		echo '</div>';
		echo '<div class="desc">';
		//部屋名
		$seconds = date_offset_get(new DateTime); //Server timzone offset
		$meeting_start_date=$ret['data']['meeting_start_date'];
		if($meeting_start_date==0){//スタートタイムが取れない時(=始まらなかった会議)
			//日時、部屋名表示
			$obj_vcm->outputstatus($rooms[$vcubemeeting->roomid]['name']);
		}else{//スタートタイムが取れたとき
			//日時、部屋名表示
			$obj_vcm->outputstatus($rooms[$vcubemeeting->roomid]['name'],date('Y-m-d H:i', $ret['data']['meeting_start_date'] + ($obj_vcm->get_user_timezone($USER->timezone) * 3600) - $seconds ),date('Y-m-d H:i', $ret['data']['meeting_end_date'] + ($obj_vcm->get_user_timezone($USER->timezone) * 3600) -$seconds ));
		}

		echo '</div>';
		if(has_capability('mod/vcubemeeting:addinstance', $context) && $act=='reset'){//議事録再取得
			$obj_vcm->minutes_list_reset($vcubemeeting->id, $vcubemeeting->meetingid);
		}
		$lists = $obj_vcm->get_minutes_list($vcubemeeting->id, $vcubemeeting->meetingid);
		if( (count($lists['minutes']) != 0) || (count($lists['videos']) != 0)){

			//議事録表示
			$type = core_useragent::get_device_type();
			if( ($type != 'mobile') && ($type != 'tablet') ){
				echo '<div class="desc"><h2>'.get_string('minute_log', 'vcubemeeting').'</h2><br>';
				$msg1 = get_string('set_password', 'vcubemeeting');
				$msg2 = get_string('delete_password', 'vcubemeeting');
				if(has_capability('mod/vcubemeeting:addinstance', $context)){
					echo <<< HTML
<div><span id="password_error" class="" ></span></div>
<form action="" name="form2" method="post" id="form2" onSubmit="return check_password();">
<input type="hidden" name="change_pass" value="1">
<input type="hidden" name="id" value="{$id}">
<input type="hidden" name="n" value="{$n}">
<input type="text" name="password" id="pass" value="">
<input type="submit" name="submit" value="{$msg1}">
</form>
<form action="" name="form4" id="form4" method="post">
<input type="hidden" name="change_pass" value="2">
<input type="hidden" name="id" value="{$id}">
<input type="hidden" name="n" value="{$n}">
<input type="submit" name="submit" value="{$msg2}">
</form>
HTML;
				}
				//議事録アクセスURLの取得
				$lists = $obj_vcm->get_minutes_url($lists);
				//議事録リスト作成
				echo '<div id="minute">';
				echo <<< HTMLFORM
<form name="form3" id="form3" action="set_log.php" method="post" target="new">
<input type="hidden" name="url" id="url" value="" />
<input type="hidden" name="status" id="status" value="" />
<input type="hidden" name="number" id="number" value="0" />
<input type="hidden" name="data" id="data" value="0" />
<input type="hidden" name="tflag" id="tflag" value="{$tflag}" />
<input type="hidden" name="id" id="id" value="{$cm->id}" />
<br />
HTMLFORM;
				$i = 1;
				foreach ($lists['minutes'] as $tmp){
					$str = get_string('minute', 'vcubemeeting', $i);
					/*文字ボタン
					$html = <<< HTML
<input type="button" class="hsbtn minutebtn" name="minute{$i}" value="{$str}" onClick="set_jump(1, '{$tmp['url']}', {$i})" />
HTML;
					*/
					//画像ボタン
					$html = <<< HTML
<input type="image" class="hsbtn minutebtn" src="pix/icon_note.gif" name="minute{$i}" value="{$str}" onClick="set_jump(1, '{$tmp['url']}', {$i})" />
HTML;
					echo $html;
					$i++;
				}
				echo '<br /><br />';
				$i = 1;
				foreach ($lists['videos'] as $tmp){
					$str = get_string('video', 'vcubemeeting', $i);
					/*文字ボタン
					$html = <<< HTML
<input type="button" class="hsbtn videobtn" name="video{$i}" value="{$str}" onClick="set_jump(2, '{$tmp['url']}', {$i})" />
HTML;
					*/
					//画像ボタン
					$html = <<< HTML
<input type="image" class="hsbtn videobtn" src="pix/icon_video.gif" name="video{$i}" value="{$str}" onClick="set_jump(2, '{$tmp['url']}', {$i})" />
HTML;
					echo $html;
					$i++;
				}
				echo '</form>';
				echo '</div>';
				echo '</div>';
			}
		}




		//入室履歴ブロック
		echo '<div class="desc"><h2>'.get_string('entry_history', 'vcubemeeting').'</h2><br>';
		echo <<< HTMLFORM
<form name="form5" id="form5" action="set_log.php" method="post" target="new">
<input type="hidden" name="url" id="url" value="" />
<input type="hidden" name="status" id="status" value="" />
<input type="hidden" name="number" id="number" value="0" />
<input type="hidden" name="data" id="data" value="0" />
<input type="hidden" name="tflag2" id="tflag2" value="{$tflag}" />
<input type="hidden" name="id" id="id" value="{$cm->id}" />
<br />
HTMLFORM;

		$table = new html_table();

		if(has_capability('mod/vcubemeeting:addinstance', $context)){
			$table->head = array('No', 'ID', get_string('name', 'vcubemeeting'), get_string('entry_datetime', 'vcubemeeting'));
		}else{
			//学生は必ず2にする
			$tflag = 2;
			$table->head = array('No', get_string('entry_datetime', 'vcubemeeting'));
		}
		//ログの一覧の取得
		$data = $obj_vcm->get_entering_log($cm->id, $tflag, $page, $perpage);

		if($data === false){
			throw new moodle_exception('Error getting log');
		}
		$totalcount = $obj_vcm->get_entering_log($cm->id, $tflag, $page, $perpage, 1);
		$baseurl = new moodle_url('view.php',array('id'=>$id, 'tflag2'=>$tflag));

		$log_num = count($data);

		$table->data = array();
		$i = ($page * $perpage) + 1;
		$teacher_num = 0;

		foreach ($data as $tmp){
			$timezoneobj->dateForHistory($tmp->time);
			if($tflag != 2){
				$row = array($i++, $tmp->username, $tmp->lastname.' '.$tmp->firstname, date('Y/m/d H:i:s', $tmp->time));
			}else{
				$row = array($i++, date('Y/m/d H:i:s', $tmp->time));
			}
			$table->data[] = $row;
		}
		if(has_capability('mod/vcubemeeting:addinstance', $context)){
			if($log_num != 0){
				$str = get_string('download');
				echo '<input type="button" name="download" value="'.$str.'" onClick="jump(\'download_csv.php\', '.$cm->id.')"><br />';
			}
			$buff = $data = $obj_vcm->get_entering_log($cm->id, 1, $page, $perpage);
			if( count($buff) != 0){
				//教師を含むチェックボックス

				$str = get_string('include_teacher', 'vcubemeeting');
				echo '<br /><input type="checkbox" name="teacher" id="teacher" value="1" '.$teacher_flag.' onChange="jump(\'\')">'.$str.'<br />';
			}
		}
		if($log_num != 0){
			echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
			echo html_writer::table($table);
			echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
		}

		if($log_num == 0){
			echo '<h3>'.get_string('no_enteringlog', 'vcubemeeting').'</h3>';
		}
		echo '</form>';
		echo '</div>';



		//アップロードされたファイルの状態表示
		$obj_vcm->showFilesBlock($vcubemeeting->id,$cm->id,0);

		//議事録のリセットボタン
		if(has_capability('mod/vcubemeeting:addinstance', $context)){
			$obj_vcm->print_minutes_reset_block();
		}
	break;
}

// Finish the page
echo $OUTPUT->footer();
