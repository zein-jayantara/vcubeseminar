<?php

/**
 * Internal library of functions for module vcubemeeting
 *
 * All the vcubemeeting specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod
 * @subpackage vcubemeeting
 * @copyright  V-Cube Inc.
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/config.php');


defined('MOODLE_INTERNAL') || die();

class vcmeeting{
	private $session;
	private $apiurl;
	private $domain;

	//モジュール限定仕様のセッティング
	private $servicename = 'vcubemeeting';
	private $accountparam=array('domain', 'id', 'password', 'adminpassword');

	function __construct(){
		$this->session = '';
		$this->apiurl = '';
		$this->domain = '';
	}

	/**
	 * APIにログインする。
	 * @throws moodle_exception エラーの時
	 */
	private function login(){
		global $SESSION,$USER;
		//選択された言語の取得
		if(isset($SESSION->lang)){
			$lang=$SESSION->lang;
		}else{
			$lang=$USER->lang;
		}
		if($lang !=='ja'){$lang = 'en';}//日本語でないならすべて英語
		try{
			//ログイン処理
			$param=$this->get_vcubeaccount();
			if( $param === false) return false;
			$this->apiurl = $param->domain.'/api/v1/user/';
			$this->domain = $param->domain;
			$post=array('action_login'=>'',
					'id'=>$param->id,
					'pw'=>$param->password,
					'lang' => $lang,
					'country' => 'auto'
			);
			//セッション変数からAPIセッションを取得
			if(isset($_SESSION['vcmeeting_login'])) { //すでにログイン済みかチェック
				$tmp = $_SESSION['vcmeeting_login'];
				if ( (date('U') - $tmp['createdatetime']) <= 86100 ){ // 24h - 5m = 86100s経過していないか
					$this->session = $tmp['session'];
					return;
				}
			}
			unset($_SESSION['vcmeeting_login']);
			//APIでログイン処理
			$ret = $this->execute_api($post);

			if($ret['status'] == 1){ //成功
				//save the data to session
				$this->session = $ret['data']['session'];
				$tmp = array();
				$tmp['session'] = $ret['data']['session'];
				$tmp['createdatetime'] = date('U');
				$_SESSION['vcmeeting_login'] = $tmp;
			}else{
				throw new moodle_exception($ret['error_msg']); //失敗
			}
		}catch(Exception $e){
			throw new moodle_exception($e->getMessage());
		}
	}

	/**
	 * アカウントのセットアップを確認する。
	 * 未セットの場合falseを返す。
	 * @return boolean
	 */
	function accountcheck(){
		$res=$this->get_vcubeaccount();
		if($res==false)return false;
		return true;
	}

	/**
	 * vcubeのアカウントを返す。
	 * @return stdClass
	 */
	private function get_vcubeaccount(){
		global $DB,$CFG;
		$ret=new stdClass();
		$reqparam=$this->accountparam;
		foreach($reqparam as $param){
			$tmp=$DB->get_field('config', 'value', array('name'=>'vcmeeting_'.$param));
			if($tmp==false)return false;
			$ret->$param=$tmp;
		}
		return $ret;
	}
	/**
	 * 部屋の一覧を取得
	 * @throws moodle_exception
	 * @return array $room 部屋の一覧
	 */
	public function get_room_list(){
		if( $this->login() === false) return array();
		$post=array('action_get_room_list'=>'', 'n2my_session'=>$this->session);
		$ret = $this->execute_api($post);
		if($ret['status'] == 1){
			$rooms = array();
			foreach ($ret['data']['rooms'] as $tmp){
				while(list($key, $value) = each($tmp)){
					$buff = array();
					@$buff['id']        = $value['room_info']['room_id'];
					@$buff['name']      = $value['room_info']['room_name'];
					@$buff['max_seats'] = $value['room_info']['max_seat'];
					@$buff['desktop_share'] = $value['options']['desktop_share'];
					$rooms[$value['room_info']['room_id']] = $buff;
				}
			}
			return $rooms;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * 指定したAPIを叩く
	 * $paramの配列に変数を入れる（ex:'id'=>'test'）
	 * @param string $apiurl APIのURL http://は不要
	 * @param array $param 変数配列
	 * @throws moodle_exception
	 * @return mixed APIの戻り値
	 */
	private function execute_api($param, $apiurl = ''){
		try{
			if( $this->apiurl == '') throw new moodle_exception('Not set API url');
			$apiurl = ($apiurl == '')? $this->apiurl:$apiurl;

			$param = array_merge($param, array('output_type'=>'php')); //PHP前提
			$ch=curl_init($apiurl);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
			$result = curl_exec($ch);
			curl_close($ch);
			return unserialize($result);
		}catch(Exception $e){
			throw new moodle_exception($e->getMessage());
		}
	}

	/**
	 * ブロックの表示モード時に利用可能な部屋の一覧を返す関数
	 *
	 * @param number $force_all_rooms
	 * @return multitype:string
	 */
	function get_allow_room_list($force_all_rooms = 0){
		global $DB, $COURSE;
		$sql = <<< SQL
SELECT configdata FROM {block_instances} AS bi
INNER JOIN {context} AS c ON
bi.parentcontextid = c.id
AND c.contextlevel = 50
AND c.instanceid = ?
AND bi.blockname = 'vcubemeeting_roomsettings'
SQL;
		$ret = $DB->get_record_sql($sql, array($COURSE->id));
		if ($ret === false){
			$ret = $DB->get_field('config', 'value', array('name'=>'vcmeeting_noblockingcourse'));
			if($ret === false){
				//ブロックがインストールがされていない
				$setting_flag  = 0;
				$block_setting = 0;
			}else{
				//ブロックモジュールがインストールされて、コースに設置されていない
				$block_setting = ($ret == 'allallow')? 0:1;
				$setting_flag  = 1;
			}
		}else{
			//ブロックモジュールがある時
			$configdata = (array)unserialize(base64_decode($ret->configdata));
			$setting_flag = 2;
		}
		//強制的にすべての部屋のリストを返す
		if($force_all_rooms == 1){
			$setting_flag  = 0;
			$block_setting = 0;
		}

		//部屋名の一覧の取得
		$rooms = $this->get_room_list();
		$allow_rooms = array();
		switch ($setting_flag) {
			case 0:
			case 1:
				if ( $block_setting == 0){
					reset($rooms);
					while(list($rkey, $rvalue) = each($rooms)) {//取得された部屋の一覧
						$allow_rooms[$rvalue['id']] = $rvalue['name']
						.' ('.get_string('maxseat','vcubemeeting',$rvalue['max_seats'])
						.get_string('desktopshare'.$rvalue['desktop_share'],'vcubemeeting').')';
					}
				}
				break;
			case 2:
				foreach ($configdata as $key=>$value){
					reset($rooms);
					while(list($rkey, $rvalue) = each($rooms)) {//利用を許可された部屋の一覧
						if( ($key == $rkey) && ($value == 1)) {
							$allow_rooms[$rvalue['id']] = $rvalue['name']
							.' ('.get_string('maxseat','vcubemeeting',$rvalue['max_seats'])
							.get_string('desktopshare'.$rvalue['desktop_share'],'vcubemeeting').')';
						}
					}
				}
				break;
		}
		return $allow_rooms;
	}

	/**
	 * タイムゾーン生成
	 * @return Ambigous <multitype:, string>
	 */
	function get_timezones(){
		$tmp = array();
		$i = -11;
		for($i = -11; $i <= 13; $i++){
			$tmp[$i] = ($i >=0)? 'UTC+'.$i : 'UTC'.$i;
		}
		return $tmp;
	}

	/**
	 * ユーザのタイムゾーンを正規化する
	 * @param unknown $usertimezone
	 * @return number|Ambigous <number, unknown>
	 */
	static function get_user_timezone($usertimezone,$round=true){
		if($usertimezone == 99 ) $usertimezone = date('Z') / 3600; //サーバのタイムゾーン
		if($round){
			$usertimezone =  round($usertimezone);
		}else{
			//.0を表示しない処理
			$num=explode('.', $usertimezone);
			//
			if(isset($num[1]) && $num[1]==0){
				$usertimezone =  round($usertimezone);
			}
		}
		if($usertimezone < -11) return -11;
		if($usertimezone > 13 )  return 13;
		return $usertimezone;
	}

	/**
	 * 会議入室用URLの取得
	 * @param unknown $vcubemeeting data record
	 * @param number $teacher_flag 0:studnet 1:teacher
	 * @param number $mobile_flag 0:pc 1:mobile
	 * @throws moodle_exception
	 * @return mixed
	 */
	function get_attend_url($vcubemeeting, $teacher_flag = 0, $mobile_flag = 0){
		if( $teacher_flag == 0){ //学生
			return $vcubemeeting->inviteurl;
		}

		$this->login();
		if($mobile_flag == 0){ //PCの時
			$post = array('action_start'=>'',
					'n2my_session' => $this->session,
					'room_id' => $vcubemeeting->roomid,
					'meeting_id' => $vcubemeeting->meetingid,
					'flash_version' => 'as3',
					'screen_mode' => 'wide'
			);
		}else{ //モバイルの時
			$post = array('action_start_mobile'=>'',
					'n2my_session' => $this->session,
					'room_id' => $vcubemeeting->roomid,
					'meeting_id' => $vcubemeeting->meetingid
			);
		}
		$apiurl = $this->apiurl.'meeting/';
		$ret = $this->execute_api($post, $apiurl);

		if($ret['status'] == 1){
			return $ret['data']['url'];
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

/**
 * 会議室予約API
 *
 */

	/**
	 * 会議室予約を取る関数
	 * @param mixed $params
	 * @throws moodle_enrol_external
	 * @return mixed API return values
	 */
	function set_new_reserve( $params){
		global $USER;

		$start             = $params->start_datetime;
		$end               = $params->end_datetime;
		$send_email        = $USER->email;
		$orgnizer_timezone = $params->timezone;

		$this->login();
		$post = array('action_add'=>'',
				'n2my_session' => $this->session,
				'room_id' => $params->roomid,
				'name'    => $params->name,
				'start'   => $start,
				'end'     => $end,
				'send_mail' => 0,
				//'sender_email' => $send_email,
				'organizer_flag' => 1,
				'organizer[name]' => $USER->lastname.' '.$USER->firstname,
				'organizer[email]' => $send_email,
				'organizer[timezone]'=>$orgnizer_timezone,
				'organizer[lang]' => 'ja',
				'is_desktop_share' => 1,
				'is_invite' => 1,
				'is_rec' => 1,
				'is_convert_wb_to_pdf'=>1,
				'is_cabinet' => 1

		);

		if($params->pass_flag != 0){ //パスワードが必須になっているとき
			$post = array_merge($post, array('password'=>$params->meeting_password, 'password_type'=>$params->pass_flag));
		}

		$apiurl = $this->apiurl.'reservation/';
		$ret = $this->execute_api($post, $apiurl);

		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * 会議室予約変更
	 * @param unknown $param
	 */
	function update_reverve($params){
		global $USER;
		//$start             = date('Y-m-d H:i:s', $params->start_datetime);
		//$end               = date('Y-m-d H:i:s', $params->end_datetime);
		$start             = $params->start_datetime;
		$end               = $params->end_datetime;
		$send_email        = $USER->email;
		$orgnizer_timezone = $params->timezone;

		$this->login();
		$post = array('action_update'=>'',
				'n2my_session' => $this->session,
				'reservation_id' => $params->reservationid,
				'room_id' => $params->roomid,
				'name'    => $params->name,
				'start'   => $start,
				'end'     => $end,
				'send_mail' => 0,
				//'sender_email' => $send_email,
				'organizer_flag' => 1,
				'organizer[name]' => $USER->lastname.' '.$USER->firstname,
				'organizer[email]' => $send_email,
				'organizer[timezone]'=>$orgnizer_timezone,
				'organizer[lang]' => 'ja',
				'is_desktop_share' => 1,
				'is_invite' => 1,
				'is_rec' => 1,
				'is_convert_wb_to_pdf'=>1,
				'is_cabinet' => 1

		);

		if($params->pass_flag != 0){ //パスワードが必須になっているとき
			$post = array_merge($post, array('password'=>$params->meeting_password, 'password_type'=>$params->pass_flag));
		}

		$apiurl = $this->apiurl.'reservation/';
		$ret = $this->execute_api($post, $apiurl);

		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * 予約一覧を取得
	 * @param unknown $param フォームデータ
	 * @throws moodle_enrol_external
	 * @return mixed API return values
	 */
	function get_reseved($param){
		$this->login();
		$post = array('action_get_list'=>'',
				'n2my_session' => $this->session,
				'room_id' => $param['roomid'],
				'start_limit' => $param['start_datetime'] + 60,
				'end_limit' => $param['end_datetime'] - 60
		);
		$apiurl = $this->apiurl.'reservation/';
		$ret = $this->execute_api($post, $apiurl);

		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * 予約削除
	 * @param unknown $vcubemeeting
	 * @param unknown $reservation_id
	 * @throws moodle_enrol_external
	 * @return mixed
	 */
	function delete_conference($vcubemeeting){
		$status = $this->get_meeting_status($vcubemeeting->id, $vcubemeeting->reservationid, $vcubemeeting->roomid, $vcubemeeting->password);
		$this->login();
		switch ($status) {
			case 3: //予約済み
				$post = array('action_delete'=>'',
						'n2my_session' => $this->session,
						'reservation_id' => $vcubemeeting->reservationid
				);
				$apiurl = $this->apiurl.'reservation/';

			break;
			case 2: //開催中
				$post = array('action_stop'=>'',
						'n2my_session' => $this->session,
						'room_id' => $vcubemeeting->roomid
				);
				$apiurl = $this->apiurl.'meeting/';
			break;
			default:
				return true;
			break;
		}

		$ret = $this->execute_api($post, $apiurl);
		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	//会議中断
	function stop_confernece($vcubemeeting){
		$this->login();
		$post = array('action_stop'=>'',
				'n2my_session' => $this->session,
				'room_id' => $vcubemeeting->roomid
		);
		$apiurl = $this->apiurl.'meeting/';
		$ret = $this->execute_api($post, $apiurl);
		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * 予約内容詳細情報取得
	 * @param unknown $reservation_id
	 * @param unknown $password
	 * @throws moodle_enrol_external
	 * @return mixed
	 */
	function get_detail($reservation_id, $password){
		$this->login();
		$post = array('action_get_detail'=>'',
				'n2my_session' => $this->session,
				'reservation_id' => $reservation_id,
				'password' => $password
		);
		$apiurl = $this->apiurl.'reservation/';
		$ret = $this->execute_api($post, $apiurl);

		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * 部屋状態取得
	 * @param unknown $room_id
	 * @throws moodle_enrol_external
	 * @return mixed
	 */
	public function get_room_detail($room_id){
		$this->login();
		$post = array('action_get_room_status'=>'',
				'n2my_session' => $this->session,
				'room_id' => $room_id,
		);
		$apiurl = $this->apiurl;
		$ret = $this->execute_api($post, $apiurl);

		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * ミーティング状態の取得
	 * @param unknown $instanceid
	 * @param unknown $reservation_id
	 * @param unknown $room_id
	 * @param unknown $password
	 * @return number 0:終了 1:中止された終了 2:実施中 3:開始前
	 */
	function get_meeting_status($instanceid, $reservation_id, $room_id, $password){
		global $DB;

		$ret = $DB->count_records('vcubemeetingurl',array('instanceid'=> $instanceid));

		if($ret !== 0) return 0; //終了
		try{
			$ret = $this->get_detail($reservation_id, $password);
			$detail_status = $ret['data']['reservation_info']['info']['status'];
			if($detail_status == 'end') {
				//部屋状態の確認
				$ret2 = $this->get_room_detail($room_id);
				if($ret2['status'] == 1){
					$tmp = $ret['data']['reservation_info']['info']['meeting_id'];
					if(!isset($ret2['data']['room_status'][0]['meeting_id'])) return 0; //終了
					if($tmp == $ret2['data']['room_status'][0]['meeting_id']){
						return 2; //実施中
					}else{
						return 0; //終了
					}
				}else{
					throw new moodle_exception($ret2['error_msg']);
				}
			}
			if($detail_status == 'now' ) return 2; //実施中
			if($detail_status == 'wait') return 3; //開始前
		}catch(Exception $e){
			if($e->getMessage() == 'error/PARAMETER_ERROR'){
				return 0; //中止された終了
			}else{
				throw new moodle_exception('PARAMETER_ERROR');
			}
		}
	}

	/**
	 * 会議記録詳細
	 * @param unknown $meeting_id
	 * @throws moodle_exception
	 * @return mixed
	 */
	function get_meeting_detail($meeting_id){
		$this->login();
		$post = array('action_get_detail'=>'',
				'n2my_session' => $this->session,
				'meeting_id' => $meeting_id,
		);
		$apiurl = $this->apiurl.'meetinglog/';
		$ret = $this->execute_api($post, $apiurl);

		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * 管理者ログイン
	 * @throws moodle_exception
	 * @return mixed
	 */
	protected function admin_login(){
		global $DB;
		$ret = $DB->get_field('config', 'value', array('name'=>'vcmeeting_adminpassword'));

		$this->login();
		$post = array('action_login'=>'',
				'n2my_session' => $this->session,
				'admin_pw' => $ret
		);
		$apiurl = $this->domain.'/api/v1/admin/';
		$ret = $this->execute_api($post, $apiurl);

		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * 管理者ログアウト
	 * @throws moodle_exception
	 * @return mixed
	 */
	protected function admin_logout(){
		$this->login();
		$post = array('action_logout'=>'',
				'n2my_session' => $this->session
		);
		$apiurl = $this->domain.'/api/v1/admin/';
		$ret = $this->execute_api($post, $apiurl);

		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * 保護有効
	 * @param unknown $meeting_id
	 * @throws moodle_exception
	 * @return mixed
	 */
	protected function set_protect($meeting_id){
		$this->login();
		$post = array('action_set_protect'=>'',
				'n2my_session' => $this->session,
				'meeting_id' => $meeting_id
		);
		$apiurl = $this->domain.'/api/v1/admin/meetinglog/';
		$ret = $this->execute_api($post, $apiurl);

		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * パスワード設定
	 * @param unknown $meeting_id
	 * @param unknown $password
	 * @throws moodle_exception
	 * @return mixed
	 */
	function set_password($meeting_id, $password){
		//管理者ログイン
		$this->admin_login();

		$this->login();
		$post = array('action_set_password'=>'',
				'n2my_session' => $this->session,
				'meeting_id' => $meeting_id,
				'pw' => $password
		);
		$apiurl = $this->domain.'/api/v1/admin/meetinglog/';
		$ret = $this->execute_api($post, $apiurl);

		//管理者ログアウト
		$this->admin_logout();
		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * パスワード解除
	 * @param unknown $meeting_id
	 * @throws moodle_exception
	 * @return mixed
	 */
	function unset_password($meeting_id){
		//管理者ログイン
		$this->admin_login();

		$this->login();
		$post = array('action_unset_password'=>'',
				'n2my_session' => $this->session,
				'meeting_id' => $meeting_id
		);
		$apiurl = $this->domain.'/api/v1/admin/meetinglog/';
		$ret = $this->execute_api($post, $apiurl);

		//管理者ログアウト
		$this->admin_logout();
		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * 録画再生
	 * @param unknown $meeting_id
	 * @param unknown $sequence_id
	 * @throws moodle_exception
	 * @return mixed
	 */
	protected function get_video_url($meeting_id, $sequence_id){
		global $USER;
		$this->login();
		$post = array('action_video_player'=>'',
				'n2my_session' => $this->session,
				'meeting_id' => $meeting_id,
				'sequence_id' => $sequence_id,
				'lang' => $USER->lang
		);
		$apiurl = $this->apiurl.'meetinglog/';
		$ret = $this->execute_api($post, $apiurl);

		if($ret['status'] == 1){
			return $ret['data']['url'];
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * 議事録再生
	 * @param unknown $meeting_id
	 * @param unknown $sequence_id
	 * @throws moodle_exception
	 * @return mixed
	 */
	protected function get_minute_url($meeting_id, $sequence_id){
		global $USER;
		$this->login();
		$post = array('action_minute_player'=>'',
				'n2my_session' => $this->session,
				'meeting_id' => $meeting_id,
				'sequence_id' => $sequence_id,
				'lang' => $USER->lang
		);
		$apiurl = $this->apiurl.'meetinglog/';
		$ret = $this->execute_api($post, $apiurl);

		if($ret['status'] == 1){
			return $ret['data']['url'];
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}


	/**
	 * 会議記録リスト作成
	 * @param unknown $instanceid
	 * @param unknown $meeting_id
	 */
	function get_minutes_list($instanceid, $meeting_id){
		global $DB;
		$ret = $DB->count_records('vcubemeetingurl', array('instanceid' => $instanceid));
		if($ret == 0){
			//会議記録詳細
			$ret = $this->get_meeting_detail($meeting_id);
			if(($ret['data']['meeting_end_date']+COOLTIME) > date('U')){//終わってから90秒経過していない時、処理終了
				return array('minutes'=>array(),'videos'=>array());
			}
			//管理者ログイン
			$this->admin_login();
			//保護(会議が行われているときのみ行う）
			if($ret['data']['meeting_use_minute'] != 0) $this->set_protect($meeting_id);
			//管理者ログアウト
			$this->admin_logout();

			try{
				$transaction = $DB->start_delegated_transaction(); //トランザクション開始
				if($ret['data']['meeting_use_minute'] != 0){ //会議時間が0の場合は誰も会議に参加していないとみなす
					foreach ($ret['data']['meeting_sequences']['meeting_sequence'] as $tmp){
						$data = new stdClass();
						$data->instanceid = $instanceid;
						$data->meetingsequencekey = $tmp['meeting_sequence_key'];
						$data->minutesflag = $tmp['is_recorded_minutes'];
						$data->videoflag = $tmp['is_recorded_video'];
						$DB->insert_record('vcubemeetingurl', $data);
					}
				}
				$transaction->allow_commit(); //コミット
			}catch(Exception $e){
				$transaction->rollback($e); //ロールバック
				throw new moodle_exception($e->getMessage());
			}
		}

		//リスト作成
		$ret = $DB->get_records('vcubemeetingurl', array('instanceid'=> $instanceid),'meetingsequencekey');
		$minutes = array();
		$videos = array();
		foreach ($ret as $tmp){
			if($tmp->minutesflag == 1) $minutes[]['seq_id'] = $tmp->meetingsequencekey;
			if($tmp->videoflag == 1) $videos[]['seq_id'] = $tmp->meetingsequencekey;
		}
		$work = array();
		$work['meeting_id'] = $meeting_id;
		$work['minutes'] = $minutes;
		$work['videos'] = $videos;
		return $work;
	}

	/**
	 * 取得した会議記録リストの削除
	 * @param unknown $instanceid
	 * @param unknown $meeting_id
	 */
	function minutes_list_reset($instanceid, $meeting_id){
		global $DB;
		$ret = $DB->count_records('vcubemeetingurl', array('instanceid' => $instanceid));
		if($ret !== 0){
			//レコード削除
			$DB->delete_records('vcubemeetingurl', array('instanceid' => $instanceid));
		}
	}


	/**
	 * 議事録リセットブロック設置
	 */
	function print_minutes_reset_block(){
		$btn=$this->get_minutes_reset_btn();
		$html=<<<HTML
<div class="desc">
{$btn}
</div>
HTML;
		echo $html;
	}



	/**
	 * 議事録リセットボタン作成
	 */
	function get_minutes_reset_btn(){

		$label=get_string('resetbtn','vcubemeeting');
		$html=<<<HTML
<form method="POST" action="#">
	<div>
		<input value="{$label}" type="submit">
		<input type="hidden" name="act" value="reset">
	</div>
</form>
HTML;
		return $html;
	}




	/**
	 * 会議資料のURLを生成する
	 * @param unknown $minutes
	 * @return Ambigous <string, mixed>
	 */
	function get_minutes_url($minutes){
		//会議資料
		$num = count($minutes['minutes']);
		for($i = 0; $i < $num; $i++){
			try{
				$url = $this->get_minute_url($minutes['meeting_id'], $minutes['minutes'][$i]['seq_id']);
				$minutes['minutes'][$i]['url'] = $url;
			}catch(Exception $e){
				$minutes['minutes'][$i]['url'] = '';
			}
		}
		//録画ビデオ
		$num = count($minutes['videos']);
		for($i = 0; $i < $num; $i++){
			try{
				$url = $this->get_video_url($minutes['meeting_id'], $minutes['videos'][$i]['seq_id']);
				$minutes['videos'][$i]['url'] = $url;
			}catch(Exception $e){
				$minutes['videos'][$i]['url'] = '';
			}
		}
		return $minutes;
	}
/**
 * 入室ログ
 */
	/**
	 * ログの取得
	 * @param unknown $cmid
	 * @param number $tflag
	 * @param number $start
	 * @param number $pagelimit
	 * @param number $page_flag 0:実際のログ 1:ログの総数
	 * @return multitype:|boolean
	 */
	function get_entering_log($cmid, $tflag = 0, $start = 0, $pagelimit = 50, $page_flag = 0){
		global $DB, $USER;
		$ver = $DB->get_field('config', 'value', array('name'=>'version'));
		$tsql = '';
		if($tflag == 0){
			$tsql = <<< SQL

AND u.id IN (
SELECT ra.userid FROM {role_assignments} AS ra
INNER JOIN {context} as c ON c.contextlevel=50 AND c.id = ra.contextid
INNER JOIN {course_modules} as cm ON  cm.id = {$cmid} AND c.instanceid = cm.course
INNER JOIN {role} AS r ON r.archetype = 'student' AND ra.roleid = r.id
)

SQL;
		}
		if($tflag == 2){
			$tsql = <<< SQL

AND u.id = {$USER->id}

SQL;
		}

		if($ver < 2014051200){
			//moodle2.6
			if($page_flag == 0){
				$sql = <<< SQL
SELECT l.id, u.id as userid, u.username, u.lastname, u.firstname, u.email, l.time FROM {log} As l
INNER JOIN {user} AS u ON l.userid = u.id
WHERE l.module = 'vcubemeeting' AND l.cmid = ? AND l.action = 'entering' AND l.module = 'vcubemeeting'
{$tsql}
ORDER BY l.time ASC
SQL;
				$start = ($start * $pagelimit);
				$ret = $DB->get_records_sql($sql, array($cmid), $start, $pagelimit);
			}else{
				$sql = <<< SQL
SELECT count(l.id) FROM {log} As l
INNER JOIN {user} AS u ON l.userid = u.id
WHERE l.module = 'vcubemeeting' AND l.cmid = ? AND l.action = 'entering' AND l.module = 'vcubemeeting'
{$tsql}
SQL;
				$ret = $DB->count_records_sql($sql, array($cmid));
			}
			if($ret !== false){
				return $ret;
			}else{
				return false;
			}
		}else{
			//moodle2.7 or later
			if($page_flag == 0){
				$sql = <<< SQL
SELECT lsl.id, u.id as userid, u.username, u.lastname, u.firstname, u.email, lsl.timecreated AS time FROM {logstore_standard_log} AS lsl
INNER JOIN {user} AS u ON lsl.userid = u.id
WHERE lsl.objecttable = 'vcubemeeting' AND objectid =  ? AND lsl.action = 'entering' AND lsl.component = 'mod_vcubemeeting'
{$tsql}
ORDER BY lsl.timecreated ASC
SQL;
				$ret = $DB->get_records_sql($sql, array($cmid), $start, $pagelimit);
			}else{
				$sql = <<< SQL
SELECT count(lsl.id) FROM {logstore_standard_log} AS lsl
INNER JOIN {user} AS u ON lsl.userid = u.id
WHERE lsl.objecttable = 'vcubemeeting' AND objectid =  ? AND lsl.action = 'entering' AND lsl.component = 'mod_vcubemeeting'
{$tsql}
SQL;
				$ret = $DB->count_records_sql($sql, array($cmid));
			}
			if($ret !== false){
				return $ret;
			}else{
				return false;
			}
		}
	}

	/**
	 * コース内の学生ID一覧を返す
	 * @param unknown $cmid
	 */
	function get_students($cmid){
		global $DB;
		$sql = <<< SQL
SELECT ra.userid FROM {role_assignments} AS ra
INNER JOIN {context} as c ON c.contextlevel=50 AND c.id = ra.contextid
INNER JOIN {course_modules} as cm ON  cm.id = ? AND c.instanceid = cm.course
INNER JOIN {role} AS r ON r.archetype = 'student' AND ra.roleid = r.id
SQL;
		$ret = $DB->get_records_sql($sql,array($cmid));
		return $ret;
	}

	/**
	 * 部屋名、開始日時、終了日時を表示する
	 * @param unknown $roomname
	 * @param string $starttime
	 * @param string $endtime
	 */
	function outputstatus($roomname,$starttime='',$endtime='',$attend_num=null){
		$roomtag=get_string('room', 'vcubemeeting');
		$starttag=get_string('start_datetime', 'vcubemeeting');
		$endtag=get_string('end_datetime', 'vcubemeeting');
		$ext_row='';
		if($attend_num!==null){
			$attendtag=get_string('participants', 'vcubemeeting');
			$attend=get_string('number_of_participants', 'vcubemeeting', $attend_num);
			$ext_row=<<<HTML
	<tr class='attendrow'>
		<td class='tag'>{$attendtag}</td>
		<td>:</td>
		<td class='data'>{$attend}</td>
	</tr>
HTML;
		}

		$html=<<<HTML
<table class='vctable'>
	<tr class='roomrow'>
			<td class='tag'>{$roomtag}</td>
			<td>:</td>
			<td class='data'>{$roomname}</td>
	</tr>
		<tr class='starttimerow'>
			<td class='tag'>{$starttag}</td>
			<td>:</td>
			<td class='data'>{$starttime}</td>
	</tr>
	<tr class='endtimerow'>
			<td class='tag'>{$endtag}</td>
			<td>:</td>
			<td class='data'>{$endtime}</td>
	</tr>
	{$ext_row}
</table>
HTML;
		echo $html;
	}


	/**
	 * UNITXTIMEとタイムゾーンの値から、モジュールタイトルの頭につける文字列を返す。
	 * @param unknown $unixtime
	 * @param unknown $timezone
	 * @return string
	 */
	function createDateFormat($unixtime,$timezone){
		//時間のフォーマット
		//date関数を使うことでサーバのタイムゾーンで変換されてしまう。
		//なので、いったんサーバのタイムゾーンoffsetを引き、指定タイムゾーン分を足す。
		$seconds = date_offset_get(new DateTime); //Server timzone offset
		$unixtime=$unixtime-$seconds+$timezone*3600;
		$time = date(FORMAT,$unixtime);
		//タイムゾーンのフォーマット
		if($timezone <0){
			$utc="(UTC{$timezone})";
		}else{
			$utc="(UTC+{$timezone})";
		}
		//上記ふたつをくっつける
		$format=" ({$time}{$utc})";

		return $format;
	}

	/**
	 * $titleの最後に開始日時をセットする
	 * @param unknown $title
	 * @param unknown $unixtime
	 * @param unknown $timezone
	 */
	function setFormat(&$title,$unixtime, $timezone){
		$format=$this->createdateformat($unixtime, $timezone);
		$title=$title.$format;
	}

	/**
	 * $titleについた開始日時を削除する
	 * @param unknown $title
	 * @param unknown $unixtime
	 * @param unknown $timezone
	 */
	function unSetFormat(&$title,$unixtime, $timezone){
		$format=$this->createdateformat($unixtime, $timezone);
		$title=str_replace($format,'', $title);
	}

	/**
	 * 終わったミーティングに対する開始日時削除処理
	 */
	function unSetFormatForended(&$title){
		//適当な値でフォーマットを取得する
		$format=$this->createdateformat(0, 9);
		//フォーマットの文字数カウント
		$len=strlen($format);
		//オリジナルタイトルの長さ取得
		$orglen=strlen($title);
		$title=substr($title,0,$orglen - $len);

		//半角スペースで終わっているかをチェック
		$lastchar=substr($title,-1);
		if($lastchar===' '){
			$title=substr($title,0,strlen($title)-1);
		}
	}

	/**
	 * 古い名前から開始日時を持ってくる
	 * @param unknown $title
	 * @param unknown $oldname
	 */
	function setFormatFromOldname(&$title,$oldname){
		//適当な値でフォーマットを取得する
		$format=$this->createdateformat(0, 9);
		//フォーマットの文字数カウント
		$len=strlen($format);
		//旧タイトルの長さ取得
		$oldlen=strlen($oldname);
		//旧タイトルの開始日時取得
		$starttime=substr($oldname,$oldlen-$len);
		$firstchar=substr($starttime,0,1);
		if($firstchar!==' '){
			$title=$title.' '.$starttime;
		}else{
			$title=$title.$starttime;
		}
	}



	/**
	 * ファイルピッカに乗っているファイルを保存する
	 * @param unknown $data
	 */
	function saveFile($data) {
		global $DB;
		$fs = get_file_storage();
		$cmid = $data->coursemodule;
		$draftitemid = $data->attachments;

		//APIで使うデータの格納用変数
		$filesforapi=array();

		$context = context_module::instance($cmid);
		if ($draftitemid) {
			file_save_draft_area_files($draftitemid, $context->id, 'mod_vcubemeeting', 'content', 0, array('subdirs'=>true));
		}
		$files = $fs->get_area_files($context->id, 'mod_vcubemeeting', 'content', 0, 'sortorder', false);
		if (count($files) >= 1) {
			foreach ($files AS $file){
				file_set_sortorder($context->id, 'mod_vcubemeeting', 'content', 0, $file->get_filepath(), $file->get_filename(), 1);
				//ファイルのパスとfileidとオリジナルファイルネームを保存
				$tmp=new stdClass();
				$tmp->filepath=$this->getFilePass($file);
				$tmp->filename=$file->get_filename();
				$fileid=$file->get_id();
				$tmp->id=$fileid;
				$tmp->mimetype=$file->get_mimetype();
				$filesforapi[$fileid]=$tmp;
			}
		}
		return $filesforapi;
	}


	/**
	 * ファイルの実体へのパス
	 * @param unknown $file
	 * @return string
	 */
	function getFilePass($file){
		global $CFG;
		$contenthash=$file->get_contenthash();
		// $contenthashの頭2文字ずつを区切ったディレクトリ内に$contenthashのファイルが保存されている。
		$filepath=$CFG->dataroot.'/filedir/'.substr($contenthash, 0,2).'/'.substr($contenthash, 2,2).'/'.$contenthash;
		return $filepath;
	}



	/**
	 * アップロードされたファイルをMeetingに飛ばし、MoodleのDBに保存する。
	 * @param unknown $data
	 * @param unknown $reservationid
	 * @param unknown $filelist
	 */
	function add_documents($data,$reservationid,$filelist){
		foreach ($filelist AS $file){
			//STEP1 APIでMeetingにファイルを転送
			$ret=$this->add_document($reservationid, $file);

			//STEP2 APIから発行されたドキュメントIDを保存
			$this->saveFiledata($data,$ret['data']['document_id'],$file);

			//このファイルの変換が完了するまでSLEEPする。
			while($this->isConverting($reservationid, $ret['data']['document_id'])){
				sleep($this->getSleepTime($file));
			}
		}
	}


	/**
	 * アップロードされたファイルをMeetingに飛ばす
	 * @throws moodle_exception
	 * @return mixed
	 */
	function add_document($reservationid,$file){
		$this->login();
		$post = array('action_add_document' => '',
				'n2my_session' => $this->session,
				'reservation_id' => $reservationid,
				'file' => new CURLFile($file->filepath,$file->mimetype,$file->filename),
				'name' => $file->filename,
				'format' => 'bitmap'
		);

		$apiurl = $this->apiurl.'reservation/';
		$ret = $this->execute_api($post, $apiurl);

		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}


	/**
	 * 変換ステータスを返す
	 * @param unknown $reservationid
	 * @param unknown $documentid
	 * @return boolean|unknown
	 */
	function getConvertStatus($reservationid,$documentid){
		$filestatustmp=$this->action_get_document($reservationid);
		if(!isset($filestatustmp))return false;
		//$filestatus['data']['documents']['document']の中にファイル数分だけ配列があり、その中の['status']が変換状態である。
		$filestatus=$filestatustmp['data']['documents']['document'];
		if(isset($filestatus)){//変換状態取得
			foreach ($filestatus AS $value){
				if($value['document_id']===$documentid){
					return $value['status'];
				}
			}
		}
		return false;
	}


	/**
	 * 変換中かを返す
	 * 変換中の場合、trueを返す。
	 * 変換中でないとき、falseを返す
	 * @param unknown $reservationid
	 * @param unknown $documentid
	 * @return boolean
	 */
	function isConverting($reservationid,$documentid){
		$status=$this->getConvertStatus($reservationid, $documentid);
		if($status === false)return false;
		if($status ==='error' || $status ==='done')return false;
		return true;
	}

	/**
	 * ファイルタイプに応じてsleeptimeを返す
	 * @param unknown $file
	 * @return string|number
	 */
	function getSleepTime($file){
		global $DOCUMENT,$PIC;

		$extension=pathinfo($file->filename, PATHINFO_EXTENSION);
		$extension='.'.$extension;
		if(array_search($extension, $DOCUMENT)!==false){
			return DOCUMENT_SLEEP_TIME;
		}
		if(array_search($extension, $PIC)!==false){
			return PIC_SLEEP_TIME;
		}
		return 1;
	}


	/**
	 * vcubemeeting_filesテーブルに書き込む
	 * @param unknown $data
	 * @param unknown $documentid
	 * @param unknown $file
	 */
	function saveFiledata($data,$documentid,$file){
		global $CFG,$DB;

		if(isset($data->download)){
			$download=$data->download;
		}else{
			$download=0;
		}

		$dataobject=new stdClass();
		$dataobject->instanceid=$data->id;
		$dataobject->fileid=$file->id;
		$dataobject->documentid=$documentid;
		$dataobject->download=$download;
		$dataobject->timecreated=time();
		$dataobject->timemodified=time();

		$DB->insert_record('vcubemeeting_files', $dataobject);

	}



	/**
	 * 会議に上がっているファイルの情報をVmeetingとMoodleの両方から削除する。
	 * @param unknown $data
	 * @param unknown $filelist
	 */
	function delUpFiles($data,$withfilepicker=false){
		global $CFG,$DB;

		//MoodleのDBから上がっているファイルの情報を取得
		$filedata=$DB->get_records('vcubemeeting_files',array('instanceid' => $data->id));
		if($filedata){
			//上がっているファイルがあるとき、削除を実行する
			foreach ($filedata AS $file){
				if($withfilepicker){
					//Moodleのfilepicker上からも削除
					$DB->delete_records('files',array('id'=>$file->fileid));
				}else{
					//Vmeetingからの削除
					$this->action_delete_document($data->reservationid,$file->documentid);
				}
			}
			//moodleからの削除
			$DB->delete_records('vcubemeeting_files',array('instanceid' => $data->id));
		}
	}


	function fileManage($data,&$filelist){
		global $DB;

		//MoodleのDBから上がっているファイルの情報を取得
		$filedata=$DB->get_records('vcubemeeting_files',array('instanceid' => $data->id));

		foreach ($filedata AS $file){
			if(array_key_exists($file->fileid, $filelist)){
				//どちらにもある
				//処理リストから外す
				unset($filelist[$file->fileid]);

			}else{
				//mdl_filesになくて、mdl_vcubemeeting_filesにあるファイル
				//→APIで削除する
				$this->action_delete_document($data->reservationid,$file->documentid);
			}
		}
		//mdl_filesにあって、mdl_vcubemeeting_filesにないファイル
		//→APIでアップロードする(この関数では何もしない)
	}

	/**
	 * 会議に上がったファイルを削除するAPI
	 * @param unknown $reservationid
	 * @throws moodle_exception
	 * @return mixed
	 */
	function action_delete_document($reservationid,$documentid){
		$this->login();
		$post = array('action_delete_document' => '',
				'n2my_session' => $this->session,
				'reservation_id' => $reservationid,
				'document_id' =>$documentid
		);
		$apiurl = $this->apiurl.'reservation/';
		$ret = $this->execute_api($post, $apiurl);
		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}

	/**
	 * 会議に上がっているファイルとその状態を取得するAPI
	 * @param unknown $reservationid
	 * @throws moodle_exception
	 * @return mixed
	 */
	function action_get_document($reservationid){
		$this->login();
		$post = array('action_get_document' => '',
				'n2my_session' => $this->session,
				'reservation_id' => $reservationid,
		);
		$apiurl = $this->apiurl.'reservation/';
		$ret = $this->execute_api($post, $apiurl);
		if($ret['status'] == 1){
			return $ret;
		}else{
			throw new moodle_exception($ret['error_msg']);
		}
	}


	/**
	 * 終了した会議への初回アクセス時の処理
	 * @param unknown $instanceid
	 */
	function endMeetingFirstAccess($data){
		global $CFG,$DB;

		$instanceid=$data->id;
		//初回アクセスかチェック
		$status=false;
		$statuscheck=$DB->get_records('vcubemeeting_status', array('instanceid'=>$instanceid));
		foreach ($statuscheck AS $check){
			if(isset($check->status) && $check->status=="endmeetingfirstaccess"){
				$status=1;
				continue;
			}
		}
		if(!$status){
			//初回アクセス時処理開始
			//ファイルのダウンロードが不可なら上がっているファイルをすべて削除。ファイルピッカからも削除する
			if($this->getDownloadOpt($instanceid) == 0){
				$this->delUpFiles($data,true);
			}

			//初回アクセス処理完了
			$dataobject=new stdClass();
			$dataobject->instanceid=$instanceid;
			$dataobject->status='endmeetingfirstaccess';
			$dataobject->value=1;
			$dataobject->timecreated=time();
			$dataobject->timemodified=time();
			$DB->insert_record('vcubemeeting_status', $dataobject);
		}
	}

	/**
	 * ファイルのダウンロード許可の値を返す。
	 * 0:無効　1:有効
	 */
	function getDownloadOpt($instanceid){
		global $DB;
		//ダウンロードのチェックボックス復元用
		$download=0;
		$vcfiledata = $DB->get_records('vcubemeeting_files', array('instanceid' => $instanceid));
		if($vcfiledata){
			foreach ($vcfiledata AS $filedata){
				$download=$filedata->download;
				break;
			}

		}
		return $download;
	}


	/**
	 * ファイルのダウンロード許可の値を保存する
	 * @param unknown $instanceid
	 * @param unknown $download
	 */
	function setDownloadOpt($instanceid,$download){
		global $DB,$CFG;
		$sql=<<<SQL
UPDATE {$CFG->prefix}vcubemeeting_files SET download ={$download}
		WHERE instanceid = {$instanceid}
SQL;
		$DB->execute($sql);
	}

	/**
	 * アップロードされたファイル一覧とその変換状態を表示する
	 *
	 */
	function showFilesBlock($instanceid,$cmid,$status,$isteacher=false,$notitle=false){
		global $CFG,$DB;

		//contextid取得
		$contexttmp=$DB->get_record('context',array('instanceid'=>$cmid,'contextlevel'=>70));
		$contextid=$contexttmp->id;

		$header[]=get_string('filename','vcubemeeting');

		if($status>1){//開催前or開催中の場合、変換ステータス取得
			$header[]=get_string('status','vcubemeeting');
			$vc=$DB->get_record('vcubemeeting',array('id'=>$instanceid));
			$filestatustmp=$this->action_get_document($vc->reservationid);
			//$filestatus['data']['documents']['document']の中にファイル数分だけ配列があり、その中の['status']が変換状態である。
			$filestatus=$filestatustmp['data']['documents']['document'];
		}
		//ファイル一覧
		$files=$DB->get_records('files',array('contextid'=>$contextid,'component'=>'mod_vcubemeeting'));
		if(!$files)return ;

		//ダウンロード可能かを取得
		$download=0;
		if($status<=1){//終了した会議の場合、上がっているファイルがある場合にダウンロード有効
			if(isset($files))$download=1;
		}else{
			//それ以外の場合、ダウンロード許可の設定があるときダウンロード有効
			$fileonmoodle=$DB->get_records('vcubemeeting_files', array('instanceid'=>$instanceid));
			foreach ($fileonmoodle AS $value){
				$download=$value->download;
			}
		}
		//開催前or開催中のとき、ダウンロード許可がない場合で学生の場合、ファイル一覧を表示しない
		if($status>1 && $download==0 && $isteacher == false){
			return;
		}



		foreach ($files AS $file){
			if($file->filename==='.')continue;
			$tmp=new stdClass();
			$tmp->id=$file->id;
			$tmp->filename=$file->filename;

			if(isset($filestatus)){//変換状態取得
				$fileonmoodle=$DB->get_record('vcubemeeting_files', array('fileid'=>$file->id,'instanceid'=>$instanceid));
				foreach ($filestatus AS $value){
					if(isset($fileonmoodle->documentid) && $value['document_id']===$fileonmoodle->documentid){
						$tmp->status=$value['status'];
					}
				}
			}

			if($download==1){
				//ファイルダウンロードURL取得
				$path='/'.$contextid.'/mod_vcubemeeting/content/'.$file->filename;
				$fullurl = moodle_url::make_file_url('/pluginfile.php', $path);
				$url = $fullurl->out(false);

				/*
				$path = '/'.$context->id.'/mod_resource/content/'.$resource->revision.$file->get_filepath().$file->get_filename();
				$fullurl = moodle_url::make_file_url('/pluginfile.php', $path, $displaytype == RESOURCELIB_DISPLAY_DOWNLOAD);
				redirect($fullurl);
				*/
			}

			$body[]=$tmp;
		}


		if(empty($body))return;
		//テーブル作成
		//ヘッダ
		$title=get_string('uploadfiles', 'vcubemeeting');
		if($notitle==false){
			$tablemaster="<div class='desc'><h2>{$title}</h2><table class='vctable'>";
		}else{
			$tablemaster="<div class='desc'><table class='vctable'>";
		}

		//<table class='filelist'>
		$tableheader="<tr><th>";
		$tableheader.=implode('</th><th>', $header);
		$tableheader.='</th></tr>';

		//本体
		$tablebody="";
		$count=count($header);
		foreach ($body AS $part){
			$tmp="<tr>";
			for($i=0;$i<$count;$i++){
				if($i==0){//ファイル名の部分
					if($download==1){//ダウンロード可能版
						$link=$CFG->wwwroot.'/mod/vcubemeeting/download.php?id='.$part->id;
						$tmp.="<td><a href='$link'>".$part->filename."</a></td>";
					}else{
						$tmp.="<td>".$part->filename."</td>";
					}
				}
				if($i==1 && isset($part->status) ){//ステータスの部分
					$convstatus=get_string($part->status,'vcubemeeting');
					$tmp.="<td>".$convstatus."</td>";
				}
			}
			$tmp.="</tr>";
			$tablebody.=$tmp;
		}

		//全部結合する
		$tablemaster.=$tableheader.$tablebody."</table></div>";

		echo $tablemaster;
	}


	/**
	 * filepickerのitemidから、アップロードされたファイルサイズの容量をチェックする
	 * @param unknown $itemid
	 * @return boolean
	 */
	function fileSizeCheck($itemid){
		global $DB,$CFG,$DOCUMENT,$PIC;
		$files=$DB->get_records('files',array('itemid'=>$itemid));

		foreach ($files AS $file){
			if($file->filename==='.')continue;
			$extension=pathinfo($file->filename, PATHINFO_EXTENSION);
			$extension='.'.$extension;
			if(array_search($extension, $DOCUMENT)!==false){
				//ドキュメントの場合
				if($file->filesize >= DOCUMENT_SIZE_LIMIT)return false;
			}
			if(array_search($extension, $PIC)!==false){
				//画像の場合
				if($file->filesize >= PIC_SIZE_LIMIT)return false;
			}
		}
		return true;
	}
}



















class TimeZoneCancelmtg{
	public $seconds=0;
	public $usertimezone=0;

	function __construct(){
		global $CFG,$USER;
		$this->seconds = date_offset_get(new DateTime); //Server timzone offset
		$usertimezone=vcmeeting::get_user_timezone($USER->timezone,false);
		$this->usertimezone=($usertimezone*3600);
	}


	/**
	 * 入室履歴表示用の時間変更関数
	 * @param unknown $time
	 */
	function dateForHistory(&$time){
		global $CFG,$USER;


		$offset=$this->usertimezone-$this->seconds;
		$time+=$offset;
	}


	/**
	 * APIから取得した時間をログインユーザのタイムゾーンで再変換する
	 * @param unknown $room_detail
	 */
	function apiDateForMoodle(&$room_detail){
		global $CFG,$USER;


		$offset=$this->usertimezone-$this->seconds;
		$room_detail['data']['reservation_info']['info']['reservation_start_date']+=$offset;
		$room_detail['data']['reservation_info']['info']['reservation_end_date']+=$offset;
	}

	/**
	 * APIから取得した時間をログインユーザのタイムゾーンで再変換する
	 * CSV用
	 * @param unknown $room_detail
	 */
	function apiDateForCsv(&$room_detail){
		global $CFG,$USER;

		$offset=$this->usertimezone-$this->seconds;
		$room_detail['data']['meeting_start_date']+=$offset;
		$room_detail['data']['meeting_end_date']+=$offset;
	}


	/**
	 * ローカルタイム変換したときに、ログインユーザの時間になるように、
	 * ずれたUNIXTIMEを返す
	 */
	function now(){
		$now=time();

		$offset=$this->usertimezone-$this->seconds;
		$now+=$offset;
		return $now;
	}


}
