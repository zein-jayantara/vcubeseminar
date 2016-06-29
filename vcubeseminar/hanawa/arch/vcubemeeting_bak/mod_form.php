<?php

/**
 * The main vcubemeeting configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod
 * @subpackage vcubemeeting
 * @copyright  V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/vcubemeeting/locallib.php');
require_once(dirname(__FILE__).'/config.php');
/**
 * Module instance settings form
 */
class mod_vcubemeeting_mod_form extends moodleform_mod {

	private $room_detail_status;

    /**
     * Defines forms elements
     */
    public function definition() {
		global $DB, $CFG, $USER,$DOCUMENT,$PIC;

        $mform = $this->_form;

        //アップロード可能な拡張子
        $enablefiles=array_merge($DOCUMENT,$PIC);

        //編集(1)か新規(0)かの判断
        $edit_flag = (!empty($this->_instance))? 1:0;

        $conf_status = 0;
        $vcdata = null;
        if($edit_flag == 1){//編集の時会議に状態を取得する
        	$vcdata = $DB->get_record('vcubemeeting', array('id' => $this->_instance));
        	$obj_vcm = new vcmeeting();
        	$status_ret = $obj_vcm->get_meeting_status($this->_instance, $vcdata->reservationid, $vcdata->roomid, $vcdata->password);
        	$conf_status = ($status_ret == 3)? 1:2; //1:開催前 2:それ以外
        }

        if($conf_status == 2){
        	$mform->addElement('static', 'edit_msg','', array('class'=>'error'));
        }
        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('confname', 'vcubemeeting'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        // Adding the standard "intro" and "introformat" fields
        $this->add_intro_editor(false);

        //-------------------------------------------------------------------------------
        // Adding the rest of vcubemeeting settings, spreeading all them into this fieldset
        // or adding more fieldsets ('header' elements) if needed for better logic
        if($conf_status <= 1){ //新規または開催前
	        $obj = new vcmeeting();
	        $current_datetime = array(
	        		'startyear' => date('Y'),
	        		'stopyear'  => date('Y')+1,
	        		'timezone'  => $obj->get_user_timezone($USER->timezone,false),
	        		'step'      => 5,
	        		'optional'  => false
	        );

			$mform->addElement('date_time_selector','start_datetime', get_string('start_datetime', 'vcubemeeting'), $current_datetime);
			$mform->addElement('date_time_selector','end_datetime', get_string('end_datetime', 'vcubemeeting'), $current_datetime);

			if( $edit_flag == 0){
				$rooms = $obj->get_allow_room_list();
			}else{
				$rooms = $obj->get_allow_room_list(1);
			}
			$choices = $obj->get_timezones();
			if($edit_flag == 0){//新規予約時
				if(count($rooms) != 0) {
					$rooms = array_merge(array(''=>''), $rooms);
				}else{
					$rooms = array(''=> get_string('room_error', 'vcubemeeting'));
				}
				$mform->addElement('select', 'roomid', get_string('room', 'vcubemeeting'), $rooms);

				$mform->addElement('select', 'timezone', get_string('timezone'), $choices);
				$mform->setDefault('timezone', $obj->get_user_timezone($USER->timezone));
				$mform->addRule('roomid', get_string('require_message', 'vcubemeeting'), 'required', null, 'client');
				$mform->addRule('timezone', get_string('require_message', 'vcubemeeting'), 'required', null, 'client');

				$mform->setDefault('end_datetime', date('U')+3600);
				$mform->setType('roomid', PARAM_RAW);
			}else{//編集時
				$ret = $obj->get_detail($vcdata->reservationid, $vcdata->password);
				$mform->addElement('static', 'sroom', get_string('room', 'vcubemeeting'));
				$mform->addElement('static', 'stimezone', get_string('timezone'));
				$mform->setDefault('sroom', $rooms[$ret['data']['reservation_info']['info']['room_id']]);

				$timezone = $ret['data']['reservation_info']['organizer']['timezone'];
				$timezone = ($timezone >= 0)? '+'.$timezone: $timezone;
				$mform->setDefault('stimezone','UTC'.$timezone);

				$mform->addElement('hidden', 'room', $ret['data']['reservation_info']['info']['room_id']);
				$mform->addElement('hidden', 'timezone',$ret['data']['reservation_info']['organizer']['timezone']);

				$mform->setType('room', PARAM_RAW);
				$mform->setType('timezone', PARAM_INT);
				//パスワードの初期値取得
				if($vcdata->pass_flag!=0){
					if(!empty($vcdata->password)){
						$setpass=$vcdata->password;
					}
				}
			}

			$opt = array('0'=>get_string('noseeting', 'vcubemeeting'), '1'=>get_string('materialenter', 'vcubemeeting'), '2'=>get_string('material', 'vcubemeeting'));
			$mform->addElement('select', 'pass_flag', get_string('passeffective', 'vcubemeeting'), $opt);
			$mform->setDefault('pass_flag', '0');

			$mform->addElement('password', 'meeting_password', get_string('password', 'vcubemeeting'));
			if(isset($setpass)){
				$mform->setDefault('meeting_password', $setpass);
			}
			$mform->addRule('name', get_string('err_no_name', 'vcubemeeting'), 'required', null, 'client');
			$mform->addRule('name', get_string('err_name', 'vcubemeeting'), 'maxlength', 50, 'client');
			$mform->addRule('start_datetime', get_string('require_message', 'vcubemeeting'), 'required', null, 'client');
			$mform->addRule('end_datetime', get_string('require_message', 'vcubemeeting'), 'required', null, 'client');


			$mform->setType('meeting_password', PARAM_TEXT);
			$mform->disabledIf('meeting_password', 'pass_flag', 'eq', 0);




			//ファイルアップロード
			$mform->addElement('checkbox', 'download',get_string('isenabledownload', 'vcubemeeting'));
			$mform->addElement('filemanager', 'attachments', get_string('fileupload', 'vcubemeeting'), null,
					array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 10, 'accepted_types' => $enablefiles));




        }
		if($conf_status == 2){ //開催中または開催後

			$mform->setDefault('edit_msg', '<span class="error">'.get_string('err_open_meeting', 'vcubemeeting').'</span>');

			if($status_ret != 0){
				//開催後は予約詳細を取得できない
				//開催後は開始日、終了日、タイムゾーンは表示できない

				$mform->addElement('static', 'start',get_string('start_datetime', 'vcubemeeting'));
				$mform->addElement('static', 'end' , get_string('end_datetime', 'vcubemeeting'));
				$mform->addElement('static', 'sroom', get_string('room', 'vcubemeeting'));
				$mform->addElement('static', 'tzone', get_string('timezone'));

				$obj = new vcmeeting();
				$ret = $obj->get_detail($vcdata->reservationid, $vcdata->password);

				//ユーザのタイムゾーン
				$timezone = $obj->get_user_timezone($USER->timezone,false);
				$timezone = ($timezone >= 0)? '+'.$timezone: $timezone;
				//ユーザのタイムゾーンで変換
				$seconds = date_offset_get(new DateTime); //Server timzone offset
				$offset=($timezone*3600)-$seconds;

				$mform->setDefault('start', date('Y-m-d H:i', $ret['data']['reservation_info']['info']['reservation_start_date']+$offset).' (UTC'.$timezone.')');
				$mform->setDefault('end', date('Y-m-d H:i', $ret['data']['reservation_info']['info']['reservation_end_date']+$offset).' (UTC'.$timezone.')');

				//予約のタイムゾーン
				$timezone = $ret['data']['reservation_info']['organizer']['timezone'];
				$timezone = ($timezone >= 0)? '+'.$timezone: $timezone;

				$rooms = $obj->get_allow_room_list(1);
				if(count($rooms) != 0) {
					$rooms = array_merge(array(''=>''), $rooms);
				}else{
					$rooms = array(''=> get_string('room_error', 'vcubemeeting'));
				}
				$mform->addElement('hidden', 'room', $rooms[$ret['data']['reservation_info']['info']['room_id']]);
				$mform->setDefault('sroom', $rooms[$ret['data']['reservation_info']['info']['room_id']]);
				$mform->setDefault('tzone', 'UTC'.$timezone);
				$mform->setType('room', PARAM_RAW);
			}
			$mform->addElement('static', 'passflag', get_string('passeffective', 'vcubemeeting'));
			$opt = array('0'=>get_string('noseeting', 'vcubemeeting'), '1'=>get_string('materialenter', 'vcubemeeting'), '2'=>get_string('material', 'vcubemeeting'));
			$mform->setDefault('passflag', $opt[$vcdata->pass_flag]);

			$mform->addRule('name', null, 'required', null, 'client');
			$mform->addRule('name', get_string('err_name', 'vcubemeeting'), 'maxlength', 50, 'client');


			//ファイルアップロード
			$mform->addElement('checkbox', 'download',get_string('isenabledownload', 'vcubemeeting'));
			$mform->addElement('filemanager', 'attachments', get_string('fileupload', 'vcubemeeting'), null,
					array('subdirs' => 0, 'maxbytes' =>0, 'maxfiles' => 10, 'accepted_types' => $enablefiles));

		}



        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }




    function set_data($default_values){
    	global $USER,$DB;
    	if(!$this->is_submitted()) {
    		$default_values;
    	}
    	$obj = new vcmeeting();
    	if(!empty($default_values->id)){

	    	$this->room_detail_status = $obj->get_meeting_status($default_values->instance, $default_values->reservationid, $default_values->roomid, $default_values->password);
	    	if($this->room_detail_status != 0){//編集時の開始日時、終了日時初期設定
	    		$ret = $obj->get_detail($default_values->reservationid, $default_values->password);
	    		$starttime=$ret['data']['reservation_info']['info']['reservation_start_date'];
		    	//取ってきたUNIXTIMEをサーバタイムゾーンで初期化後、VCに設定されたタイムゾーンで再計算
		    	$timezone = $ret['data']['reservation_info']['organizer']['timezone'];
		    	//タイムゾーンのキャンセル
		    	$seconds = date_offset_get(new DateTime); //Server timzone offset
		    	//$timezone=$obj->get_user_timezone($USER->timezone,false);
		    	$offset=($timezone*3600)-$seconds;
		    	$ret['data']['reservation_info']['info']['reservation_start_date']+=$offset;
		    	$ret['data']['reservation_info']['info']['reservation_end_date']+=$offset;
		    	//ここまでで、サーバタイムゾーンで初期化される
		    	//この後にMoodleのセレクタで”ユーザのタイムゾーン”で補正がかかるので、その逆算を行う
		    	$usertimezone=$obj->get_user_timezone($USER->timezone,false);
		    	$offset=$seconds-$usertimezone*3600;
		    	$ret['data']['reservation_info']['info']['reservation_start_date']+=$offset;
		    	$ret['data']['reservation_info']['info']['reservation_end_date']+=$offset;



		    	$default_values->start_datetime = $ret['data']['reservation_info']['info']['reservation_start_date'];
		    	$default_values->end_datetime = $ret['data']['reservation_info']['info']['reservation_end_date'];



		    	//会議室名
		    	$obj->unSetFormat($default_values->name, $starttime, $timezone);
	    	}else{
	    		$obj->unSetFormatForended($default_values->name);
	    	}


	    	//ファイルアップロード（上がっているファイルをピッカに戻す）
	    	$draftitemid = file_get_submitted_draft_itemid('attachments');
	    	$context = context_module::instance($default_values->coursemodule);
	    	file_prepare_draft_area($draftitemid, $context->id, 'mod_vcubemeeting', 'content', 0, array('subdirs'=>true));
	    	$default_values->attachments = $draftitemid;
	    	//ダウンロードのチェックボックス復元用
	    	$download=$obj->getDownloadOpt($default_values->id);
	    	$default_values->download=$download;

    	}
    	parent::set_data($default_values);
    }

	function validation($data, $files){
		global $USER;
		$tmp = '';
		if (isset($data['roomid'])){
				$tmp = $data['roomid'];
		}else{
			if (isset($data['room'])){
				$tmp = $data['room'];
			}
		}
		$data['roomid'] = $tmp;

		$obj = new vcmeeting();
		$errors = array();


		//ファイルサイズのチェック
		if(!$obj->fileSizeCheck($data['attachments'])){
			$errors['attachments'] = get_string('err_filesize', 'vcubemeeting');
		}



		if(isset($data['start_datetime'])){//開始日時が入っているとき
			//タイムゾーンのキャンセル
			$seconds = date_offset_get(new DateTime); //Server timzone offset
			$usertimezone=$obj->get_user_timezone($USER->timezone,false);
			$offset=($usertimezone*3600)-$seconds;
			$data['start_datetime']+=$offset;
			$data['end_datetime']+=$offset;
			//これで表記通りの時間となる＝サーバタイムゾーンでの表示時刻となる
			//ここから予約のタイムゾーンで再計算する
			//ex.サーバが9で予約が7なら+2する。
			$offset=$seconds-($data['timezone']*3600);
			$data['start_datetime']+=$offset;
			$data['end_datetime']+=$offset;
		}



		$vcdata = new stdClass();
		$vcdata->reservationid = 0;
		if($data['instance'] != '' ){//編集の時会議の状態を取得する
			global $DB;
			$vcdata = $DB->get_record('vcubemeeting', array('id' => $data['instance']));
			$obj_vcm = new vcmeeting();
			$status_ret = $obj_vcm->get_meeting_status($this->_instance, $vcdata->reservationid, $vcdata->roomid, $vcdata->password);
			if ($status_ret != 3){
				//return true; //開催中　開催後はこの関数でのvalidationは行わない
				if(count($errors) == 0){
					return true;
				}else{
					return $errors;
				}

			}
		}


		//名称文字列の長さ
		if(mb_strlen($data['name'],'UTF-8') > 50){
			$errors['name'] = get_string('err_name', 'vcubemeeting');
		}

		//パスワード必要な状況で、パスワードが入力されているかチェック
		if($data['pass_flag'] != 0){
			if( $data['meeting_password'] == ''){
				$errors['meeting_password'] = get_string('err_meetingpassword', 'vcubemeeting');
			}
		}
		//予約日時のチェック
		$date_flag = 0;
		if($data['start_datetime'] > $data['end_datetime']){
			$errors['start_datetime'] = get_string('err_datetime', 'vcubemeeting');
			$errors['end_datetime']   = get_string('err_datetime', 'vcubemeeting');
			$date_flag = 1;
		}
		if($data['start_datetime'] == $data['end_datetime']){
			$errors['start_datetime'] = get_string('err_datetime', 'vcubemeeting');
			$errors['end_datetime']   = get_string('err_datetime', 'vcubemeeting');
			$date_flag = 1;
		}
		if($data['end_datetime']<date('U')){
			$errors['start_datetime'] = get_string('err_datetime', 'vcubemeeting');
			$errors['end_datetime']   = get_string('err_datetime', 'vcubemeeting');
			$date_flag = 1;
		}

		//予約一覧チェック
		$obj = new vcmeeting();
		$ret = $obj->get_reseved($data);
		if($date_flag == 0){
			if($ret['data']['count'] == 1){
				$alredyexist=$ret['data']['reservations']['reservation'][0]['reservation_id'];
				if($alredyexist  !== $vcdata->reservationid){ //予約ID異なった時、エラー
					$errors['start_datetime'] = get_string('err_confdate', 'vcubemeeting');
					$errors['end_datetime']   = get_string('err_confdate', 'vcubemeeting');
				}
			}elseif( $ret['data']['count'] != 0){
				$errors['start_datetime'] = get_string('err_confdate', 'vcubemeeting');
				$errors['end_datetime']   = get_string('err_confdate', 'vcubemeeting');
			}
		}
		//パスワード文字数のチェック
		if($data['pass_flag'] != 0){
			$len = strlen($data['meeting_password']);
			if( ($len < 6) || ($len >16)){ //文字数チェック
				$errors['meeting_password'] = get_string('err_password', 'vcubemeeting');
			}
			if (!preg_match("/^[a-zA-Z0-9]+$/", $data['meeting_password'])) { //半角英数チェック
				$errors['meeting_password'] = get_string('err_password', 'vcubemeeting');
			}
		}



		if(count($errors) == 0){
			return true;
		}else{
			return $errors;
		}


	}
}
