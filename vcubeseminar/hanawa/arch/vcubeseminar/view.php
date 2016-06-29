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

$page = optional_param('page', 0, PARAM_INT);
$perpage = 10; //1ページ当たりの履歴数
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

$act = optional_param('act', null, PARAM_TEXT);
require_login($course, true, $cm);
$context = context_course::instance($course->id);

$completion=new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->https_required();
/// Print the page header

$PAGE->set_url('/mod/vcubeseminar/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($vcubeseminar->name));
$PAGE->set_heading(format_string($course->fullname));

// other things you may want to set - remove if not needed
$PAGE->set_cacheable(false);

//JS Scriptの言語パック参照に必要
global $USER;
$strmgr = get_string_manager();
$strings = $strmgr->load_component_strings('vcubeseminar', $USER->lang);
$PAGE->requires->strings_for_js(array_keys($strings), 'vcubeseminar');
/////////

$PAGE->requires->jquery();
$PAGE->requires->js('/mod/vcubeseminar/setlog.js');
$PAGE->requires->css('/mod/vcubeseminar/style.css');

// Replace the following lines with you own code
$obj_vcs = new vcseminar();
$timezoneobj=new TimeZoneCancel();

$domains = $obj_vcs->get_domain_list($vcubeseminar->vcubeseminardomainid);
$domain = $domains[$vcubeseminar->vcubeseminardomainid];

if(has_capability('mod/vcubeseminar:addinstance', $context)){
	//パスワード設定・削除が押された時
	$change_pass_flag = optional_param('change_pass', 0, PARAM_INT);
	if($change_pass_flag == 1){ //設定
		$_password = required_param('password', PARAM_ALPHANUM);
		$obj_vcs->set_password($vcubeseminar, $_password);
	}
	if($change_pass_flag == 2){ //削除
		$obj_vcs->set_password($vcubeseminar, '');
	}
}

// Output starts here
echo $OUTPUT->header();
//開催状態判定
$ret = $obj_vcs->get_seminar_status($vcubeseminar);
if($ret==2){
	//DB上、開催中だったら中断されたかを確認する
	if($obj_vcs->ishold($vcubeseminar)){//この関数にて、中断を検知したら再度状態取得
		$ret = $obj_vcs->get_seminar_status($vcubeseminar);
	}
}
//部屋名の取得
$rooms = $obj_vcs->get_room_list($domain->vcseminar_domain, $domain->vcseminar_id, $domain->vcseminar_password);

//タイムゾーンのキャンセル
$seconds = date_offset_get(new DateTime); //Server timzone offset
$timezone=$obj_vcs->get_user_timezone($USER->timezone,false);
$offset=$seconds-($timezone*3600);
//保存しているタイムゾーンによって差を計算する

$timezone = ($timezone >= 0)? '+'.$timezone: $timezone;

echo $OUTPUT->heading($vcubeseminar->name);
switch ($ret) {

	// ----ライブセミナー開催前 befor session live seminar ----------------------------
	case 3:
		echo '<div class="desc">';
		echo $vcubeseminar->intro.'<br>';
		echo '</div>';
		echo '<div class="desc">';
		$obj_vcs->outputstatus($rooms[$vcubeseminar->roomid]['name'],date('Y-m-d H:i', $vcubeseminar->starttime-$offset).' (UTC'.$timezone.')',date('Y-m-d H:i', $vcubeseminar->curtaintime-$offset).' (UTC'.$timezone.')',date('Y-m-d H:i', $vcubeseminar->endtime-$offset).' (UTC'.$timezone.')');
		echo '<h2>'.get_string('pre_open_meeting', 'vcubeseminar').'</h2><br>';
		echo '</div>';
		//アップロードされたファイルの状態表示
		$obj_vcs->showFilesBlock($vcubeseminar->id,$cm->id,$ret,has_capability('mod/vcubeseminar:addinstance', $context),false);
	break;

	// ----ライブセミナー開催中 insession live seminar  ------------------------------
	case 2:
		echo '<div class="desc">';
		echo $vcubeseminar->intro;
		echo '</div>';

		echo '<div class="desc">';
		$obj_vcs->outputstatus($rooms[$vcubeseminar->roomid]['name'],date('Y-m-d H:i', $vcubeseminar->starttime-$offset).' (UTC'.$timezone.')',date('Y-m-d H:i', $vcubeseminar->curtaintime-$offset).' (UTC'.$timezone.')',date('Y-m-d H:i', $vcubeseminar->endtime-$offset).' (UTC'.$timezone.')');
		echo '<h2>'.get_string('open_meeting', 'vcubeseminar').'</h2><br>';
		echo '</div>';
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
		if(has_capability('mod/vcubeseminar:addinstance', $context) || is_siteadmin($USER->id)){
			//教師
			$url = $obj_vcs->get_seminar_url($vcubeseminar, 1);
		}else{
			//学生
			if($obj_vcs->ishold($vcubeseminar)==false){//開催が終わっていなければ入室URLを取得
				$url =  $obj_vcs->get_seminar_url($vcubeseminar, 0);
			}

		}
		$msg = get_string('entering', 'vcubeseminar');
		if(!empty($url)){
		echo <<< HTML
<input type="button" name="enter" value="{$msg}" onClick="set_jump(0, '{$url}', 0)" />
</form>
HTML;
		}
		echo '</div>';
		//アップロードされたファイルの状態表示
		$obj_vcs->showFilesBlock($vcubeseminar->id,$cm->id,$ret,has_capability('mod/vcubeseminar:addinstance', $context),false);
	break;

	// ---- オンデマンドセミナー ondemand seminar -------------------------------
	case 4:
		echo '<div class="desc">';
		echo $vcubeseminar->intro;
		echo '</div>';

		//議事録リセット
		if(has_capability('mod/vcubeseminar:addinstance', $context) && $act=='reset'){//議事録再取得
			$obj_vcs->minutes_list_reset($vcubeseminar);
		}
		$lists = $obj_vcs->get_minutes($vcubeseminar);
		if( $lists != false){

			//議事録表示
			echo '<div class="desc"><h2>'.get_string('minute_log', 'vcubeseminar').'</h2><br>';
			$msg1 = get_string('set_password', 'vcubeseminar');
			$msg2 = get_string('delete_password', 'vcubeseminar');
			if(has_capability('mod/vcubeseminar:addinstance', $context)){
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
			//デバイスごとにURLを変える
			$type = core_useragent::get_device_type();
			if( ($type != 'mobile') && ($type != 'tablet') ){

				if( $lists['whiteboardurl'] != ''){
					$str = get_string('minute_reference', 'vcubeseminar');
					//画像ボタン
					$html = <<< HTML
<input type="image" class="hsbtn minutebtn" src="pix/icon_note.gif" name="minute" value="{$str}" onClick="set_jump(1, '{$lists['whiteboardurl']}')" />
<a onClick="set_jump(1, '{$lists['whiteboardurl']}')">$str</a>
HTML;
					echo $html;
					echo '<br /><br />';
				}

				if( $lists['ondemandurl'] != ''){
					$str = get_string('recording_reference', 'vcubeseminar');
					//画像ボタン
					$html = <<< HTML
<input type="image" class="hsbtn videobtn" src="pix/icon_video.gif" name="video" value="{$str}" onClick="set_jump(4, '{$lists['ondemandurl']}')" />
<a onClick="set_jump(4, '{$lists['ondemandurl']}')">$str</a>
HTML;
					echo $html;
					echo '<br /><br />';
				}

			}else{
				$str = get_string('mobile_reference', 'vcubeseminar');
				$html = <<< HTML
<input type="button" class="hsbtn" name="mobile" value="{$str}" onClick="set_jump(5, '{$lists['mobileurl']}')" />
<a onClick="set_jump(5, '{$lists['mobileurl']}')">$str</a>
HTML;
				echo $html;
				echo '<br /><br />';
			}
			echo '</form>';
			echo '</div>';
			echo '</div>';
		}
		echo '<div class="desc"><h2>'.get_string('viewing_history', 'vcubeseminar').'</h2><br>';
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

		if(has_capability('mod/vcubeseminar:addinstance', $context)){
			$table->head = array('No', 'ID', get_string('name', 'vcubeseminar'), get_string('viewing_starttime', 'vcubeseminar'));
		}else{
			//学生は必ず2にする
			$tflag = 2;
			$table->head = array('No',get_string('viewing_starttime', 'vcubeseminar'));
		}
		//ログの一覧の取得
		$data = $obj_vcs->get_ondemand_log($vcubeseminar, $tflag, $page, $perpage, 0);

		if($data === false){
			throw new moodle_exception('Error getting log');
		}
		$totalcount = $obj_vcs->get_ondemand_log($vcubeseminar, $tflag, $page, $perpage, 1);
		$baseurl = new moodle_url('view.php',array('id'=>$id, 'tflag2'=>$tflag));
		$log_num = count($data);
		$table->data = array();
		$i = ($page * $perpage) + 1;
		$teacher_num = 0;
		foreach($data as $tmp){
			$timezoneobj->unixTimeToUserTime($tmp['starttime']);
			if($tflag != 2){
				$row = array($i++, $tmp['username'], $tmp['lastname'].' '.$tmp['firstname'], date('Y/m/d H:i:s', $tmp['starttime']));
			}else{
				$row = array($i++, date('Y/m/d H:i:s', $tmp['starttime']));
			}
			$table->data[] = $row;
		}
		if(has_capability('mod/vcubeseminar:addinstance', $context)){
			if($log_num != 0){
				$str = get_string('download');
				echo '<input type="button" name="download" value="'.$str.'" onClick="jump(\'download_csv2.php\', '.$cm->id.')"><br />';
			}
		}
		if($log_num != 0){
			echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
			echo html_writer::table($table);
			echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
		}
		if($log_num == 0){
			echo '<h3>'.get_string('no_viewinglog', 'vcubeseminar').'</h3>';
		}
		echo '</form>';
		echo '</div>';

		//議事録のリセットボタン
		if(has_capability('mod/vcubeseminar:addinstance', $context)){
			$obj_vcs->print_minutes_reset_block();
		}
	break;

	// ---- live seminar after session--------------------------------------
	default:
		echo '<div class="desc">';
		echo $vcubeseminar->intro;
		echo '</div>';
		echo '<div class="desc">';
		$obj_vcs->outputstatus($rooms[$vcubeseminar->roomid]['name'],date('Y-m-d H:i', $vcubeseminar->starttime-$offset).' (UTC'.$timezone.')',date('Y-m-d H:i', $vcubeseminar->curtaintime-$offset).' (UTC'.$timezone.')',date('Y-m-d H:i', $vcubeseminar->endtime-$offset).' (UTC'.$timezone.')');
		echo '</div>';

		//議事録リセット
		if(has_capability('mod/vcubeseminar:addinstance', $context) && $act=='reset'){//議事録再取得
			$obj_vcs->minutes_list_reset($vcubeseminar);
		}
		$lists = $obj_vcs->get_minutes($vcubeseminar);
		if( $lists != false){

			//議事録表示
			echo '<div class="desc"><h2>'.get_string('minute_log', 'vcubeseminar').'</h2><br>';
			$msg1 = get_string('set_password', 'vcubeseminar');
			$msg2 = get_string('delete_password', 'vcubeseminar');
			if(has_capability('mod/vcubeseminar:addinstance', $context)){
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
			//デバイスごとにURLを変える
			$type = core_useragent::get_device_type();
			if( ($type != 'mobile') && ($type != 'tablet') ){

				if( $lists['whiteboardurl'] != ''){
					$str = get_string('minute_reference', 'vcubeseminar');
					/*文字ボタン
					 $html = <<< HTML
					<input type="button" class="hsbtn" name="minute" value="{$str}" onClick="set_jump(1, '{$lists['whiteboardurl']}')" />
					HTML;
					*/
					//画像ボタン
					$html = <<< HTML
<input type="image" class="hsbtn minutebtn" src="pix/icon_note.gif" name="minute" value="{$str}" onClick="set_jump(1, '{$lists['whiteboardurl']}')" />
<a onClick="set_jump(1, '{$lists['whiteboardurl']}')">$str</a>
HTML;
					echo $html;
					echo '<br /><br />';
				}

				if( $lists['ondemandurl'] != ''){
					$str = get_string('recording_reference', 'vcubeseminar');
					/*文字ボタン
					$html = <<< HTML
<input type="button" class="hsbtn" name="video" value="{$str}" onClick="set_jump(2, '{$lists['ondemandurl']}')" />
HTML;
					*/
					//画像ボタン
					$html = <<< HTML
<input type="image" class="hsbtn videobtn" src="pix/icon_video.gif" name="video" value="{$str}" onClick="set_jump(2, '{$lists['ondemandurl']}')" />
<a onClick="set_jump(2, '{$lists['ondemandurl']}')">$str</a>
HTML;
					echo $html;
					echo '<br /><br />';
				}


			}else{
				$str = get_string('mobile_reference', 'vcubeseminar');
				$html = <<< HTML
<input type="button" class="hsbtn" name="mobile" value="{$str}" onClick="set_jump(3, '{$lists['mobileurl']}')" />
<a onClick="set_jump(3, '{$lists['mobileurl']}')">$str</a>
HTML;
				echo $html;
				echo '<br /><br />';
			}
			echo '</form>';
			echo '</div>';
			echo '</div>';
		}
		echo '<div class="desc"><h2>'.get_string('entry_history', 'vcubeseminar').'</h2><br>';
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

		if(has_capability('mod/vcubeseminar:addinstance', $context)){
			$table->head = array('No', 'ID', get_string('name', 'vcubeseminar'), get_string('entry_datetime', 'vcubeseminar'), get_string('leave_datetime', 'vcubeseminar'));
		}else{
			//学生は必ず2にする
			$tflag = 2;
			$table->head = array('No', get_string('entry_datetime', 'vcubeseminar'), get_string('leave_datetime', 'vcubeseminar'));
		}
		//ログの一覧の取得
		$rawdata = $obj_vcs->get_entering_log($vcubeseminar, $tflag, $page, $perpage);
		$data = $obj_vcs->make_tabledata($vcubeseminar, $rawdata, $tflag);

		if($data === false){
			throw new moodle_exception('Error getting log');
		}
		$totalcount = $obj_vcs->get_entering_log($vcubeseminar, $tflag, $page, $perpage, 1);
		$baseurl = new moodle_url('view.php',array('id'=>$id, 'tflag2'=>$tflag));

		$log_num = count($data);

		$table->data = array();
		$i = ($page * $perpage) + 1;
		$teacher_num = 0;
		foreach ($data as $tmp){
			$timezoneobj->unixTimeToUserTime($tmp['enter']);
			$timezoneobj->unixTimeToUserTime($tmp['leave']);
			if($tflag != 2){
				$row = array($i++, $tmp['username'], $tmp['lastname'].' '.$tmp['firstname'], date('Y/m/d H:i:s', $tmp['enter']), date('Y/m/d H:i:s', $tmp['leave']));
			}else{
				$row = array($i++, date('Y/m/d H:i:s', $tmp['enter']), date('Y/m/d H:i:s', $tmp['leave']));
			}
			$table->data[] = $row;
		}
		if(has_capability('mod/vcubeseminar:addinstance', $context)){
			if($log_num != 0){
				$str = get_string('download');
				echo '<input type="button" name="download" value="'.$str.'" onClick="jump(\'download_csv.php\', '.$cm->id.')"><br />';
			}
			if($rawdata->count->__toString() != 0){
				$str = get_string('include_teacher', 'vcubeseminar');
				echo '<input type="checkbox" name="teacher" id="teacher" value="1" '.$teacher_flag.' onChange="jump(\'\')">'.$str.'<br />';
			}
		}
		if($log_num != 0){
			echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
			echo html_writer::table($table);
			echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
		}
		if($log_num == 0){
			echo '<h3>'.get_string('no_enteringlog', 'vcubeseminar').'</h3>';
		}
		echo '</form>';
		echo '</div>';

		//議事録のリセットボタン
		if(has_capability('mod/vcubeseminar:addinstance', $context)){
			$obj_vcs->print_minutes_reset_block();
		}
		//アップロードされたファイルの状態表示
		$obj_vcs->showFilesBlock($vcubeseminar->id,$cm->id,$ret,has_capability('mod/vcubeseminar:addinstance', $context),false);
	break;
}

// Finish the page
echo $OUTPUT->footer();
