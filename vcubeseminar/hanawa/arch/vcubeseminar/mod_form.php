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
require_once(dirname(__FILE__).'/config.php');

/**
 * Module instance settings form
 */
class mod_vcubeseminar_mod_form extends moodleform_mod {

	private $room_detail_status;

    /**
     * Defines forms elements
     */
    public function definition() {
        global $DB, $CFG, $USER, $PAGE,$VCUBESEMINAR_DOCUMENT,$VCUBESEMINAR_PIC;
        $PAGE->requires->js('/mod/vcubeseminar/vcubeseminar.js');
        $PAGE->requires->jquery();
        $mform = $this->_form;

        //アップロード可能な拡張子
        $enablefiles=array_merge($VCUBESEMINAR_DOCUMENT,$VCUBESEMINAR_PIC);

        //edit_flag : 0=new activity 1=edit activity//
        $edit_flag = (!empty($this->_instance))? 1:0;
        //conf_status 0=new Seminar 1=befor Seminar 2=insession or after Seminar  3= ondemand Seminar
        $conf_status = 0;
        $vcdata = null;
        if($edit_flag == 1){
            $vcdata = $DB->get_record('vcubeseminar', array('id' => $this->_instance));
            $obj_vcm = new vcseminar();
            $status_ret = $obj_vcm->get_seminar_status($vcdata);
            if($status_ret == 4) $conf_status = 3;
            else if($status_ret == 3) $conf_status = 1;
            else $conf_status = 2;
        }

        if($conf_status == 2){
            $mform->addElement('static', 'edit_msg','', array('class'=>'error'));
        }

        //-------------------------------------------------------------------------------
        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('confname', 'mod_vcubeseminar'), array('size'=>'64'));
        $mform->addRule('name', get_string('err_no_name', 'vcubeseminar'), 'required', null, 'client');
        $mform->addRule('name', get_string('err_name', 'vcubeseminar'), 'maxlength', 50, 'client');

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        // Adding the standard "intro" and "introformat" fields
        $this->standard_intro_elements(false);
        //-------------------------------------------------------------------------------
        // Adding the rest of vcubeseminar settings, spreeading all them into this fieldset
        // or adding more fieldsets ('header' elements) if needed for better logic

        // ライブセミナー 開催前 //
        if($conf_status <= 1){
            $obj = new vcseminar();
            $current_datetime = array(
                    'startyear' => date('Y'),
                    'stopyear'  => date('Y')+1,
                    'timezone'  => $obj->get_user_timezone($USER->timezone,false),
                    'step'      => 5,
                    'optional'  => false
            );

            if( $edit_flag == 0){
                $rooms = $obj->get_allow_room_list();
            }else{
                $rooms = $obj->get_allow_room_list(1);
            }
            $choices = $obj->get_timezones();
            // 活動:新規 //
            if($edit_flag == 0){
                if(count($rooms) != 0) {
                    $rooms = array(''=>'') + $rooms;
                }else{
                    $rooms = array(''=> get_string('room_error', 'vcubeseminar'));
                }
                $attributes = 'onChange="change_room(); "';
                $mform->addElement('select', 'roomdata', get_string('room', 'vcubeseminar'), $rooms, $attributes);

                //  ---- seminar_type ---- //
                $seminar_type = array(0=>get_string('live_seminar','vcubeseminar') , 1=>get_string('ondemand_seminar','vcubeseminar'));
                $mform->addElement('select', 'seminar_type',get_string('seminar_type','vcubeseminar'),$seminar_type);

                //  ---- get all ondemand list ---- //
                //  ---- after vanish all ondemand to vcubeseminar.js ---- //
                $tmp = array();
                foreach($rooms as $r_key => $r_value){
                    $key_values = explode('_', $r_key);
                    if(isset($key_values[0]) && isset($key_values[1]) && $key_values[0] && $key_values[1]){
                        $seminars = $obj->get_allow_room_seminar($key_values[0], $key_values[1]);
                        foreach((array)$seminars as $seminar) $tmp[$seminar['key']] = $seminar['name'];
                    }
                }
                $mform->addElement('select','ondemand',get_string('ondemand','vcubeseminar'),$tmp);
                $mform->addElement('date_time_selector','start_datetime', get_string('start_datetime', 'vcubeseminar'), $current_datetime);
                $mform->addElement('date_time_selector','curtaintime', get_string('curtaintime', 'vcubeseminar'), $current_datetime);
                $mform->addElement('date_time_selector','end_datetime', get_string('end_datetime', 'vcubeseminar'), $current_datetime);
                $mform->addElement('select', 'timezone', get_string('timezone'), $choices);
                $mform->addElement('text', 'max', get_string('max_user', 'vcubeseminar'));
                $mform->addElement('hidden', 'roomid', '');
                $mform->addElement('hidden', 'vcubeseminardomainid', '');

                $mform->setDefault('timezone', $obj->get_user_timezone($USER->timezone));
                $mform->setDefault('curtaintime', date('U'));
                $mform->setDefault('end_datetime', date('U')+3600);

                $mform->setType('roomdata', PARAM_RAW);
                $mform->setType('roomid', PARAM_RAW);
                $mform->setType('vcubeseminardomainid', PARAM_RAW);
                $mform->setType('max', PARAM_INT);

                //Seminar_typeセレクトボックスの選択値による表示/非表示の切替//
                $mform->disabledIf('ondemand','seminar_type','eq',0);
                $mform->disabledIf('start_datetime','seminar_type','eq',1);
                $mform->disabledIf('curtaintime','seminar_type','eq',1);
                $mform->disabledIf('end_datetime','seminar_type','eq',1);
                $mform->disabledIf('timezone','seminar_type','eq',1);
                $mform->disabledIf('max','seminar_type','eq',1);
                $mform->addRule('roomdata', get_string('require_message', 'vcubeseminar'), 'required', null, 'client');

            //活動:編集//
            }else{
                $mform->addElement('date_time_selector','start_datetime', get_string('start_datetime', 'vcubeseminar'), $current_datetime);
                $mform->addElement('date_time_selector','curtaintime', get_string('curtaintime', 'vcubeseminar'), $current_datetime);
                $mform->addElement('date_time_selector','end_datetime', get_string('end_datetime', 'vcubeseminar'), $current_datetime);
                $mform->addElement('static', 'sroom', get_string('room', 'vcubeseminar'));
                $mform->addElement('select', 'timezone', get_string('timezone'), $choices);
                $mform->addElement('text', 'max', get_string('max_user', 'vcubeseminar'));
                $mform->addElement('hidden', 'roomid', $vcdata->roomid);
                $mform->addElement('hidden', 'vcubeseminardomainid', $vcdata->vcubeseminardomainid);
                $mform->addElement('hidden', 'room', $vcdata->roomid);
                $mform->addElement('hidden', 'seminar_type' , '0');
                $mform->setDefault('timezone', $vcdata->timezone);
                $mform->setDefault('sroom', $rooms[$vcdata->vcubeseminardomainid.'_'.$vcdata->roomid]);

                // Adding the "whiteboard" fieldset //
                $mform->addElement('header', 'whiteboard', get_string('whiteboard', 'vcubeseminar'));
                $mform->addElement('checkbox', 'is_animation',get_string('is_animation', 'vcubeseminar'));
                $mform->addElement('checkbox', 'download_whiteboard',get_string('isenabledownload', 'vcubeseminar'));
                $mform->addElement('filemanager', 'attachments_whiteboard', get_string('fileupload', 'vcubeseminar'), null,
                		array('subdirs' => 0, 'maxbytes' =>VCUBESEMINAR_DOCUMENT_SIZE_LIMIT , 'maxfiles' => 10, 'accepted_types' => $enablefiles));

                // Adding the "filecabinet" fieldset //
                $mform->addElement('header', 'filecabinet', get_string('filecabinet', 'vcubeseminar'));
                $mform->addElement('checkbox', 'download_filecabinet',get_string('isenabledownload', 'vcubeseminar'));
                $mform->addElement('filemanager', 'attachments_filecabinet', get_string('fileupload', 'vcubeseminar') , null,
                		array('subdirs' => 0, 'maxbytes' => VCUBESEMINAR_DOCUMENT_SIZE_LIMIT , 'maxfiles' => 10, 'accepted_types' => $enablefiles));

                // Adding the "banner" fieldset //
                $mform->addElement('header', 'banner', get_string('banner', 'vcubeseminar'));
                $mform->addElement('text', 'link_url', get_string('link_url', 'vcubeseminar'), array('size'=>'128','maxlength'=>'1333'));
                $mform->addElement('filemanager', 'attachments_banner', get_string('fileupload', 'vcubeseminar') , null,
                		array('subdirs' => 0, 'maxbytes' => VCUBESEMINAR_DOCUMENT_SIZE_LIMIT, 'maxfiles' => 1, 'accepted_types' => $VCUBESEMINAR_PIC));

                $mform->setType('roomid', PARAM_RAW);
                $mform->setType('vcubeseminardomainid', PARAM_RAW);
                $mform->setType('room', PARAM_RAW);
                $mform->setType('max', PARAM_INT);
                $mform->setType('seminar_type', PARAM_INT);
                $mform->setType('link_url',PARAM_TEXT);

            }

            //入力必須項目
            $mform->addRule('seminar_type', get_string('require_message', 'vcubeseminar'), 'required', null, 'client');

        }

        // ライブセミナー 開催中or開催後 //
        if($conf_status == 2){
            $obj = new vcseminar();

            $mform->setDefault('edit_msg', '<span class="error">'.get_string('err_open_meeting', 'vcubeseminar').'</span>');

            $mform->setType('start_datetime', PARAM_RAW);
            $mform->setType('curtaintime', PARAM_RAW);
            $mform->setType('end_datetime', PARAM_RAW);
            $mform->setType('seminar_type', PARAM_INT);

            $mform->addElement('static','start',get_string('start_datetime', 'vcubeseminar'));
            $mform->addElement('static','ctime', get_string('curtaintime', 'vcubeseminar'));
            $mform->addElement('static','end' , get_string('end_datetime', 'vcubeseminar'));
            $mform->addElement('static','room', get_string('room', 'vcubeseminar'));
            $mform->addElement('static','tzone', get_string('timezone'));

            $mform->addElement('hidden','roomid', $vcdata->roomid);
            $mform->addElement('hidden','start_datetime', $vcdata->starttime);
            $mform->addElement('hidden','curtaintime', $vcdata->curtaintime);
            $mform->addElement('hidden','end_datetime', $vcdata->endtime);
            $mform->addElement('hidden','seminar_type','0');

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

            $mform->setDefault('room', $rooms[$vcdata->vcubeseminardomainid.'_'.$vcdata->roomid]);
            $mform->setDefault('roomid', $vcdata->roomid);
            $mform->setDefault('tzone', 'UTC'.$timezone);
            $info = $obj->get_seminar_info($vcdata->seminarkey, $vcdata->vcubeseminardomainid);
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
        }

        // オンデマンドセミナー //
        if($conf_status == 3){
            $obj = new vcseminar();
            // ---- room ---- //
            $attributes = 'onChange="change_room(); "';
            $rooms = $obj->get_allow_room_list(1);
            $mform->addElement('select', 'roomid', get_string('room', 'vcubeseminar'), $rooms,$attributes);
            $mform->setDefault('roomid', $vcdata->roomid);

            // ---- ondemand ---- //
            $tmp = array();
            foreach($rooms as $r_key => $r_value){
                if($r_key){
                    $seminars = $obj->get_allow_room_seminar($r_key);
                    foreach((array)$seminars as $seminar){
                        if($seminar) $tmp[$seminar['key']] = $seminar['name'];
                    }
                }
            }
            $mform->addElement('select','ondemand',get_string('ondemand','vcubeseminar'),$tmp);
            $mform->setDefault('ondemand', $vcdata->seminarkey);
            // ---- seminar_type ---- //
            $mform->setType('seminar_type', PARAM_INT);
            $mform->addElement('hidden','seminar_type','1');
        }

        //共通フォーム//
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
            $ret_xml = $obj->get_seminar_info($vcdata->seminarkey, $vcdata->vcubeseminardomainid);
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

			//ファイルアップロード（上がっているファイルをピッカに戻す）
			$context = context_module::instance($default_values->coursemodule);

			//whiteboard
			$whiteboarditem = file_get_submitted_draft_itemid('attachments_whiteboard');
			file_prepare_draft_area($whiteboarditem, $context->id, 'mod_vcubeseminar', 'attachments_whiteboard', 0, array('subdirs'=>true));
			$default_values->attachments_whiteboard = $whiteboarditem;

			//filecabinet
			$cabinetitem = file_get_submitted_draft_itemid('attachments_filecabinet');
			file_prepare_draft_area($cabinetitem, $context->id, 'mod_vcubeseminar', 'attachments_filecabinet', 0, array('subdirs'=>true));
			$default_values->attachments_filecabinet = $cabinetitem;

			//banner
			$banneritem = file_get_submitted_draft_itemid('attachments_banner');
			file_prepare_draft_area($banneritem, $context->id, 'mod_vcubeseminar', 'attachments_banner', 0, array('subdirs'=>true));
			$default_values->attachments_banner = $banneritem;

			if(isset($vcdata->is_animation)) $default_values->is_animation=$vcdata->is_animation;
			if(isset($vcdata->download_whiteboard)) $default_values->download_whiteboard=$vcdata->download_whiteboard;
			if(isset($vcdata->download_filecabinet)) $default_values->download_filecabinet=$vcdata->download_filecabinet;
			if(isset($vcdata->link_url)) $default_values->link_url=$vcdata->link_url;
    	}
    	parent::set_data($default_values);
    }

	function validation($data, $files){
		global $USER;
		$data['roomid'] = (isset($data['roomid']) !== false)? $data['roomid']: $data['room'];

		$errors = array();
		$obj = new vcseminar();
		// validate only liveseminar //
        if($data['seminar_type'] == 0){
        	if(empty($data['max'])){
        		$errors['max'] = get_string('err_max', 'vcubeseminar');
        	}
			//exchange unixtime//
			$timezoneobj=new TimeZoneCancel();
			$timezoneobj->dateTimeSelectorForValidation($data);
			//get seminar status//
			$seminar_status = 0;
			if($data['instance'] != '' ){ //only edit seminar
				global $DB;
				$instanceid = $data['instance'];
				$vcdata = $DB->get_record('vcubeseminar', array('id'=>$instanceid));
				$ret = $obj->get_seminar_status($vcdata);
				if($ret <= 2) $seminar_status = 1;
				$data['seminarkey']=$vcdata->seminarkey;
				$data['seminarstatus']=$ret;
			}
			if((isset($ret) && $ret == 3)){
				//validate url_form
				if($data['link_url'] != ''){
					$bnr = $DB->get_records('files',array('itemid'=>$data['attachments_banner']));
					if(empty($bnr)){
						$errors['link_url'] = get_string('err_banner_noimage', 'vcubeseminar');
					}
					if(strpos($data['link_url'],'http://') !== 0 && strpos($data['link_url'],'https://') !== 0){
						$errors['link_url'] = get_string('err_noturl', 'vcubeseminar');
					}
				}
				//validate uploadfile size
				if(!$obj->fileSizeCheck($data['attachments_whiteboard'],0)) {
					$errors['attachments_whiteboard'] = get_string('err_filesize', 'vcubeseminar');
				}
				if(!$obj->fileSizeCheck($data['attachments_filecabinet'],1)){
					$errors['attachments_filecabinet'] = get_string('err_filesize', 'vcubeseminar');
				}
				if(!$obj->fileSizeCheck($data['attachments_banner'],2)){
					$errors['attachments_banner'] = get_string('err_bannersize', 'vcubeseminar');
				}
			}
			if($seminar_status == 0){ //befor session//
				//validate time
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
        }
		if(count($errors) == 0) return true;
		else return $errors;
	}

}
