<?php

/**
 * The main vcubeseminar configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod
 * @subpackage vcubeseminar
 * @copyright  V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/vcubeseminar/locallib.php');

/**
 * Module instance settings form
 */
class mod_vcubeseminar_mod_form extends moodleform_mod {

	private $room_detail_status;

    /**
     * Defines forms elements
     */
    public function definition() {
		global $DB, $CFG, $USER;

        $mform = $this->_form;

        //編集(1)か新規(0)かの判断
        $edit_flag = (!empty($this->_instance))? 1:0;

        $conf_status = 0;
        $vcdata = null;
        if($edit_flag == 1){//編集の時会議に状態を取得する
        	$vcdata = $DB->get_record('vcubeseminar', array('id' => $this->_instance));
        	$obj_vcm = new vcseminar();
        	$status_ret = $obj_vcm->get_seminar_status($vcdata);
        	$conf_status = ($status_ret == 3)? 1:2; //1:開催前 2:それ以外
        }

        if($conf_status == 2){
        	$mform->addElement('static', 'edit_msg','', array('class'=>'error'));
        }
        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('confname', 'mod_vcubeseminar'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        // Adding the standard "intro" and "introformat" fields
        $this->add_intro_editor(false);

        //-------------------------------------------------------------------------------
        // Adding the rest of vcubeseminar settings, spreeading all them into this fieldset
        // or adding more fieldsets ('header' elements) if needed for better logic
        if($conf_status <= 1){ //新規または開催前
	        $obj = new vcseminar();
	        $current_datetime = array(
	        		'startyear' => date('Y'),
	        		'stopyear'  => date('Y')+1,
	        		'timezone'  => $obj->get_user_timezone($USER->timezone,false),
	        		'step'      => 5,
	        		'optional'  => false
	        );

			$mform->addElement('date_time_selector','start_datetime', get_string('start_datetime', 'vcubeseminar'), $current_datetime);
			$mform->addElement('date_time_selector','curtaintime', get_string('curtaintime', 'vcubeseminar'), $current_datetime);
			$mform->addElement('date_time_selector','end_datetime', get_string('end_datetime', 'vcubeseminar'), $current_datetime);

        	if( $edit_flag == 0){
				$rooms = $obj->get_allow_room_list();
			}else{
				$rooms = $obj->get_allow_room_list(1);
			}
			$choices = $obj->get_timezones();
			if($edit_flag == 0){
				if(count($rooms) != 0) {
					$rooms = array(''=>'') + $rooms;
				}else{
					$rooms = array(''=> get_string('room_error', 'vcubeseminar'));
				}
				$mform->addElement('select', 'roomid', get_string('room', 'vcubeseminar'), $rooms);

				$mform->addElement('select', 'timezone', get_string('timezone'), $choices);
				$mform->setDefault('timezone', $obj->get_user_timezone($USER->timezone));

				$mform->addElement('text', 'max', get_string('max_user', 'vcubeseminar'));

				$mform->addRule('roomid', get_string('require_message', 'vcubeseminar'), 'required', null, 'client');
				$mform->addRule('timezone', get_string('require_message', 'vcubeseminar'), 'required', null, 'client');

				$mform->setDefault('curtaintime', date('U'));
				$mform->setDefault('end_datetime', date('U')+3600);
				$mform->setType('roomid', PARAM_RAW);
				$mform->setType('max', PARAM_INT);
			}else{//編集
				$mform->addElement('static', 'sroom', get_string('room', 'vcubeseminar'));
				//$mform->addElement('static', 'stimezone', get_string('timezone'));

				//タイムゾーンは編集可能とする。
				$mform->addElement('select', 'timezone', get_string('timezone'), $choices);
				$mform->addRule('timezone', get_string('require_message', 'vcubeseminar'), 'required', null, 'client');
				$mform->setDefault('timezone', $vcdata->timezone);


				$mform->addElement('text', 'max', get_string('max_user', 'vcubeseminar'));

				$mform->setDefault('sroom', $rooms[$vcdata->roomid]);

				//$timezone = $vcdata->timezone;
				//$timezone = ($timezone >= 0)? '+'.$timezone: $timezone;
				//$mform->setDefault('stimezone','UTC'.$timezone);

				$mform->addElement('hidden', 'room', $vcdata->roomid);



				$mform->setType('room', PARAM_RAW);
				//$mform->setType('timezone', PARAM_INT);
				$mform->setType('max', PARAM_INT);
			}

			$mform->addRule('name', get_string('err_no_name', 'vcubeseminar'), 'required', null, 'client');
			$mform->addRule('name', get_string('err_name', 'vcubeseminar'), 'maxlength', 50, 'client');
			$mform->addRule('start_datetime', get_string('require_message', 'vcubeseminar'), 'required', null, 'client');
			$mform->addRule('curtaintime', get_string('require_message', 'vcubeseminar'), 'required', null, 'client');
			$mform->addRule('end_datetime', get_string('require_message', 'vcubeseminar'), 'required', null, 'client');
			$mform->addRule('max', get_string('err_max', 'vcubeseminar'), 'required', null, 'client');
			$mform->addRule('max', get_string('require_numeric', 'vcubeseminar'), 'numeric', null, 'client');
        }
		if($conf_status == 2){ //開催中または開催後
			$obj = new vcseminar();

			$mform->setDefault('edit_msg', '<span class="error">'.get_string('err_open_meeting', 'vcubeseminar').'</span>');

			$mform->setType('start_datetime', PARAM_RAW);
			$mform->setType('curtaintime', PARAM_RAW);
			$mform->setType('end_datetime', PARAM_RAW);

			$mform->addElement('static', 'start',get_string('start_datetime', 'vcubeseminar'));
			$mform->addElement('static', 'ctime', get_string('curtaintime', 'vcubeseminar'));
			$mform->addElement('static', 'end' , get_string('end_datetime', 'vcubeseminar'));
			$mform->addElement('static', 'room', get_string('room', 'vcubeseminar'));
			$mform->addElement('static', 'tzone', get_string('timezone'));

			$mform->addElement('hidden', 'roomid', $vcdata->roomid);
			$mform->addElement('hidden','start_datetime', $vcdata->starttime);
			$mform->addElement('hidden','curtaintime', $vcdata->curtaintime);
			$mform->addElement('hidden','end_datetime', $vcdata->endtime);


			//ユーザのタイムゾーン
			$timezone = $obj->get_user_timezone($USER->timezone,false);
			$timezone = ($timezone >= 0)? '+'.$timezone: $timezone;
			//ユーザのタイムゾーンで変換
			$seconds = date_offset_get(new DateTime); //Server timzone offset
			$offset=($timezone*3600)-$seconds;

			$mform->setDefault('start', date('Y-m-d H:i', $vcdata->starttime+$offset).' (UTC'.$timezone.')');
			$mform->setDefault('ctime', date('Y-m-d H:i', $vcdata->curtaintime+$offset).' (UTC'.$timezone.')');
			$mform->setDefault('end', date('Y-m-d H:i',   $vcdata->endtime+$offset).' (UTC'.$timezone.')');

			//予約のタイムゾーン
			$timezone = $vcdata->timezone;
			$timezone = ($timezone >= 0)? '+'.$timezone: $timezone;

			$rooms = $obj->get_allow_room_list(1);
			if(count($rooms) != 0) {
				$rooms = array(''=>'') + $rooms;
			}else{
				$rooms = array(''=> get_string('room_error', 'vcubeseminar'));
			}

			$mform->setDefault('room', $rooms[$vcdata->roomid]);
			$mform->setDefault('roomid', $vcdata->roomid);
			$mform->setDefault('tzone', 'UTC'.$timezone);
			$info = $obj->get_semianr_info($vcdata->seminarkey);
			if(@function_exists($info->seminar->max->__toString)){
				$max = $info->seminar->max->__toString();
			}else{
				$max='';
			}
			if($max != ''){
				$mform->addElement('static', 'smax', get_string('max_user', 'vcubeseminar'));
				$mform->setDefault('smax', $max);
				$mform->addElement('hidden', 'max', $max);
				$mform->setType('max', PARAM_INT);
			}

			$mform->setType('roomid', PARAM_RAW);

			$mform->addRule('name', get_string('err_no_name', 'vcubeseminar'), 'required', null, 'client');
			$mform->addRule('name', get_string('err_name', 'vcubeseminar'), 'maxlength', 50, 'client');
		}

        //-------------------------------------------------------------------------------
        // add standard elements, common to all modules
        $this->standard_coursemodule_elements();
        //-------------------------------------------------------------------------------
        // add standard buttons, common to all modules
        $this->add_action_buttons();
    }

    function set_data($default_values){
    	if(!empty($default_values->id)){
    		//会議の最大人数の取得
    		global $DB,$USER;
    		$vcdata = $DB->get_record('vcubeseminar', array('id' => $default_values->id));
    		$starttime=$vcdata->starttime;
    		$obj = new vcseminar();
    		$ret_xml = $obj->get_semianr_info($vcdata->seminarkey);
    		if($ret_xml->count->__toString() != 0){
				$default_values->max = $ret_xml->seminar->max->__toString();
    		}else{
    			$default_values->max = '';
    		}


    		//セレクター用タイムゾーン変換
    		$timezoneobj = new TimeZoneCancel();
    		$timezoneobj->moodletimeForMoodleSelector($vcdata);



			//時間のセット
			$default_values->start_datetime = $vcdata->starttime;
			$default_values->curtaintime = $vcdata->curtaintime;
			$default_values->end_datetime = $vcdata->endtime;


			//セミナー名
			$obj->unSetFormat($default_values->name, $starttime, $default_values->timezone);

    	}
    	parent::set_data($default_values);
    }

	function validation($data, $files){
		global $USER;
		$data['roomid'] = (isset($data['roomid']) !== false)? $data['roomid']: $data['room'];

		$errors = array();
		$obj = new vcseminar();

		//UNIXTIME変換
		$timezoneobj=new TimeZoneCancel();
		$timezoneobj->dateTimeSelectorForValidation($data);

		//セミナー状態取得
		$seminar_status = 0;
		if($data['instance'] != '' ){ //更新時のみ動作
			global $DB;
			$instanceid = $data['instance'];
			$vcdata = $DB->get_record('vcubeseminar', array('id'=>$instanceid));
			$ret = $obj->get_seminar_status($vcdata);
			if($ret <= 2) $seminar_status = 1;
			$data['seminarkey']=$vcdata->seminarkey;
			$data['seminarstatus']=$ret;
		}

		if($seminar_status == 0){ //開催前のみチェックする
			//日付のチェック
			if( !(($data['start_datetime'] <= $data['curtaintime']) && ($data['curtaintime'] < $data['end_datetime'])) ){
				$errors['start_datetime'] = get_string('err_datetime', 'vcubeseminar');
				$errors['curtaintime'] = get_string('err_datetime', 'vcubeseminar');
				$errors['end_datetime'] = get_string('err_datetime', 'vcubeseminar');
			}
			if( $data['end_datetime']<time() ){
				$errors['start_datetime'] = get_string('err_datetime', 'vcubeseminar');
				$errors['curtaintime'] = get_string('err_datetime', 'vcubeseminar');
				$errors['end_datetime'] = get_string('err_datetime', 'vcubeseminar');
			}
			//既に予約されている場合
			if($obj->is_set_seminar($data)===true){
				$errors['start_datetime'] = get_string('err_confdate', 'vcubeseminar');
				$errors['curtaintime'] = get_string('err_confdate', 'vcubeseminar');
				$errors['end_datetime'] = get_string('err_confdate', 'vcubeseminar');
			}
		}



		if(count($errors) == 0){
			return true;
		}else{
			return $errors;
		}
	}
}
