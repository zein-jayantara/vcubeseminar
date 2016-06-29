<?php

/**
 * Internal library of functions for module vcubeseminar
 *
 * All the vcubeseminar specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod
 * @subpackage vcubeseminar
 * @copyright  V-Cube Inc.
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/config.php');


defined('MOODLE_INTERNAL') || die();


class vcseminar{
	private $session;
	private $apiurl;
	private $domain;

	//モジュール限定仕様のセッティング
	private $servicename = 'vcubeseminar';
	private $accountparam=array('domain', 'id', 'password');

	function __construct(){
		$this->session = '';
		$this->apiurl = '';
		$this->domain = '';
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
		$reqparam=$this->accountparam;
		$flag = 0;
		foreach($reqparam as $param){
			$tmp=$DB->get_field('config', 'value', array('name'=>'vcseminar_'.$param));
			if($tmp==false)return false;
			$this->{$param}=$tmp;
			$flag = 1;
		}
		return ($flag == 0)? false:true;
	}
	/**
	 * 部屋の一覧を取得
	 * @throws moodle_exception
	 * @return array $room 部屋の一覧
	 */
	public function get_room_list(){
		$ret = $this->execute_api('','/api/atom/room');
		$xml = simplexml_load_string($ret);

		$rooms = array();
		if($ret === false) return $rooms;

		$room_num = $xml->count->__toString();
		for ($i = 0; $i < $room_num; $i++){
			$buff = array();
			$buff['id']   = $xml->room[$i]->room_key->__toString();
			$buff['name'] = $xml->room[$i]->room_name->__toString();
			$rooms[$xml->room[$i]->room_key->__toString()] = $buff;
		}
		return $rooms;
	}

	/**
	 * 指定したAPIを叩く
	 * $paramの配列に変数を入れる（ex:'id'=>'test'）
	 * @param string $apiurl APIのURL http://は不要
	 * @param array $param 変数配列
	 * @throws moodle_exception
	 * @return mixed APIの戻り値
	 */
	private function execute_api($param, $apiurl = '', $method = 'GET'){
		try{
			if ( $apiurl != '' ){
				$_apiurl = $apiurl;
			}else{
				throw new moodle_exception('No endpoint');
			}

			if( $this->get_vcubeaccount() === false) return false;

			$nonce    = sha1( uniqid( rand(), true ) );
			$created  = date( 'Y-m-d\TH:i:s\Z', time() );
			$pdigest  = sha1( $nonce . $created . sha1($this->{$this->accountparam[2]}) );
			$nonce    = base64_encode( $nonce );
			$pdigest  = base64_encode( $pdigest );
			$tmp = <<< WSSE
X-WSSE: UsernameToken Username="{$this->{$this->accountparam[1]}}", PasswordDigest="{$pdigest}", Nonce="{$nonce}", Created="{$created}"
WSSE;
			$x_wsse = array($tmp);
			$url = $this->{$this->accountparam[0]}.$_apiurl;
			$ch=curl_init($url);
			if($method == 'POST') curl_setopt($ch, CURLOPT_POST, true);
			if($method == 'POST') curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
			if($method == 'GET' ) {
				if(!empty($param)){
					$query=http_build_query($param,null,'&');
					curl_setopt($ch, CURLOPT_URL,$url.'?'.$query);
				}
				curl_setopt($ch, CURLOPT_HTTPGET, true);
			}
			if($method == 'PUT' ){
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
			}
			if($method == 'DELETE') curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $x_wsse);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
			$result = curl_exec($ch);
			curl_close($ch);
			return $result;
		}catch(Exception $e){
			throw new moodle_exception($e->getMessage());
		}
	}

		/**
	 * ブロックの表示モード時に利用可能な部屋の一覧を返す関数
	 *
	 * @return boolean|Ambigous <boolean, multitype:string >
	 */
	function get_allow_room_list($force_all_rooms = 0){
		global $DB, $COURSE;
		$sql = <<< SQL
SELECT configdata FROM {block_instances} AS bi
INNER JOIN {context} AS c ON
bi.parentcontextid = c.id
AND c.contextlevel = 50
AND c.instanceid = ?
AND bi.blockname = 'vcubeseminar_roomsettings'
SQL;
		$ret = $DB->get_record_sql($sql, array($COURSE->id));
		if ($ret === false){
			$ret = $DB->get_field('config', 'value', array('name'=>'vcseminar_noblockingcourse'));
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
					while(list($rkey, $rvalue) = each($rooms)) {//すべての部屋の一覧
							$allow_rooms[$rvalue['id']] = $rvalue['name'];
					}
				}
			break;
			case 2:
				foreach ($configdata as $key=>$value){
					reset($rooms);
					while(list($rkey, $rvalue) = each($rooms)) {//利用を許可された部屋の一覧
						if( ($key == $rkey) && ($value == 1)) {
							$allow_rooms[$rvalue['id']] = $rvalue['name'];
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
	 * 配列からXMLを生成
	 * @param unknown $data
	 */
	protected function make_xml(array $data){
		$xml = <<< XML
<?xml version="1.0" encoding="utf-8"?>
<entry xmlns="http://www.w3.org/2005/Atom">
XML;
		foreach ( $data as $key=>$value ){
			$buff = <<< BUFF
<{$key}>{$value}</$key>
BUFF;
			$xml .= $buff;
		}
		$xml .= '</entry>';
		return $xml;
	}

	/**
	 * レスポンスにエラーがあるか判断する
	 * @param unknown $xml //レスポンスデータ
	 * @throws moodle_exception
	 */
	protected function check_error($xml){
		$tmp = simplexml_load_string($xml);

		if(is_object($tmp->error->code)){
			$code = $tmp->error->code->__toString();
			$msg = '';
			foreach ($tmp->error->message as $buff){
				$msg .= $buff->__toString().'<br />';
			}
			$err_msg = $code.'<br />'.$msg;
			throw new moodle_exception($err_msg);
		}
		return $tmp;
	}

/**
 * 会議室予約API
 *
 */

	/**
	 * 会議室予約関数
	 * @param unknown $data
	 */
	function set_new_reserve($data){
		$tmp = array();
		$tmp['room_key'] = $data->roomid;
		$tmp['title']     = $data->name;
		$tmp['place']    = $data->timezone;
		$tmp['starttime']= date('Y-m-d H:i', $data->start_datetime);
		$tmp['endtime']  = date('Y-m-d H:i', $data->end_datetime);
		$tmp['curtaintime'] = date('Y-m-d H:i', $data->curtaintime);
		$tmp['max']         = $data->max;
		//固定値
		$tmp['is_use_local_source'] = 0;
		$tmp['is_auto_rec'] = 0;
		$tmp['video_codec'] = 'Sorenson';
		$tmp['open'] = 0;
		$tmp['is_entry_limit'] = 1;
		$tmp['use_public_chairman_url'] = 1;

		$param = $this->make_xml($tmp);
		$ret_xml = $this->execute_api($param, '/api/atom/reserve', 'POST');
		$xml = $this->check_error($ret_xml); //データ取得＆エラーチェック

		$seminar_key = $xml->seminar_key->__toString();
		return $seminar_key;
	}

	/**
	 * セミナー内容変更
	 * @param unknown $data
	 */
	function update_reverve($data){
		$tmp = array();
		$tmp['room_key'] = $data->roomid;
		$tmp['title']    = $data->name;
		$tmp['place']    = $data->timezone;
		$tmp['starttime']= date('Y-m-d H:i', $data->start_datetime);
		$tmp['endtime']  = date('Y-m-d H:i', $data->end_datetime);
		$tmp['curtaintime'] = date('Y-m-d H:i', $data->curtaintime);
		$tmp['max']         = $data->max;
		//固定値
		$tmp['is_use_local_source'] = 0;
		$tmp['is_auto_rec'] = 0;
		$tmp['video_codec'] = 'Sorenson';
		$tmp['open'] = 0;
		$tmp['is_entry_limit'] = 1;
		$tmp['use_public_chairman_url'] = 1;
		$tmp['tag'] = '';

		$param = $this->make_xml($tmp);
		$ret_xml = $this->execute_api($param, '/api/atom/reserve/'.$data->seminarkey, 'PUT');
		$xml = $this->check_error($ret_xml); //データ取得＆エラーチェック
	}

	 /**
	  * セミナーの削除
	  * @param unknown $data
	  */
	function delete_seminar($data){
		$status = $this->get_seminar_status((array)$data);
		if($status!==0){
			$ret_xml = $this->execute_api(null, '/api/atom/reserve/'.$data->seminarkey, 'DELETE');
			$xml = $this->check_error($ret_xml); //データ取得＆エラーチェック
		}
	}

	/**
	 * セミナーの状況を判断する関数
	 * @param unknown $vcdata
	 * @return number
	 */
	function get_seminar_status($vcdata){
		global $USER;

		$now = date('U');
		if(is_array($vcdata)){
			$starttime = $vcdata['starttime'];
			$endtime = $vcdata['endtime'];
		}else{
			if(isset($vcdata->starttime)){
				$starttime = $vcdata->starttime;
				$endtime = $vcdata->endtime;
			}else{
				$starttime = $vcdata->start_datetime;
				$endtime = $vcdata->end_datetime;
			}
		}

		if(  $now <  $starttime)return 3; //開始前
		if( ($now >= $starttime) && ($now < $endtime) )return 2; //実施中
		if(  $now >= $endtime) return 0; //終了

		return 0;
	}

	/**
	 * セミナー情報取得
	 * @param unknown $seminar_key
	 * @return SimpleXMLElement
	 */
	function get_semianr_info($seminar_key){
		$ret_xml = $this->execute_api('', '/api/atom/reserve/'.$seminar_key, 'GET');
		$xml = $this->check_error($ret_xml); //データ取得＆エラーチェック
		return $xml;
	}


	/**
	 * 指定した日時（とタイムゾーン）に既に予約があるかをチェック
	 * trueの場合、既に予約されている、falseの場合、予約されていない
	 * @param unknown $data
	 * @return boolean
	 */
	function is_set_seminar($data){
		$tmp = array();
		$tmp['room_key'] = $data['roomid'];

		//既にUNIXTIME化されているのでここは変換不要
		$reqstarttime = $data['start_datetime'];
		$reqendtime = $data['end_datetime'];
		$timezoneobj=new TimeZoneCancel();

		$ret_xml = $this->execute_api($tmp, '/api/atom/reserve', 'GET');
		$xml = $this->check_error($ret_xml); //データ取得＆エラーチェック
		$count = $xml->count->__toString();
		if($count==0){
			return false;
		}else{
			for($i = 0; $i<$count; $i++){//時間のチェック
				if (!empty($data['seminarkey'])) {
					//更新時処理
					$seminarkey=$xml->seminar[$i]->seminar_key->__toString();
					if($data['seminarkey']==$seminarkey){
						continue;
					}
				}
				$starttime=$xml->seminar[$i]->starttime->standard_time->__toString();
				$starttime=strtotime($starttime);
				$timezoneobj->unixTimeToServerTime($starttime);
				$endtime=$xml->seminar[$i]->endtime->standard_time->__toString();
				$endtime=strtotime($endtime);
				$timezoneobj->unixTimeToServerTime($endtime);
				if( $starttime <= $reqstarttime && $reqstarttime < $endtime){
					//既にあるミーティングの最中に開始する場合
					return true;
				}
				if($starttime < $reqendtime && $reqendtime <= $endtime){
					//既にあるミーティングの最中に終了する場合
					return true;
				}
				if($reqstarttime <  $starttime && $endtime <  $reqendtime){
					//既にあるミーティングの時間中かぶってる場合
					return true;
				}
				if($reqstarttime == $starttime && $reqendtime == $endtime){
					//開始と終了のが被っている場合
					return true;
				}

			}
		}
		return false;
	}

/**
 * 会議参加
 */
	/**
	 * セミナー参加用のURLの取得
	 * @param unknown $vcdata
	 * @param number $teacher_flag 0:生徒　1:先生
	 */
	function get_seminar_url($vcdata, $teacher_flag = 0){
		global $DB, $USER;
		$url = '';
		if ( $teacher_flag == 0 ){
			//生徒
			$ret_xml = $this->get_student_url($vcdata);
			$url = $ret_xml->participant->url->__toString();
			$invitainkey = $ret_xml->participant->invitation_key->__toString();
			//DBへ保存
			$DB->insert_record('vcubeseminarlog', array('instanceid' => $vcdata->id, 'userid' => $USER->id, 'invitationkey' => $invitainkey));
		}else{
			//教師
			$ret_xml = $this->get_semianr_info($vcdata->seminarkey);
			if( $ret_xml->count->__toString() != 0 ){
				//DBへの保存
				$url = $ret_xml->seminar->chairman_url->__toString();
				$DB->update_record('vcubeseminar', array('id' => $vcdata->id, 'charmanurl'=>$url));
			}else{
				$url = $DB->get_field('vcubeseminar', 'charmanurl', array('id' => $vcdata->id));
			}
		}
		return $url;
	}

	/**
	 * 生徒用URLの取得
	 * @param unknown $vcdata
	 */
	protected function get_student_url($vcdata){
		$tmp = array();
		$tmp['seminar_key'] = $vcdata->seminarkey;
		$tmp['num'] = 1;
		$param = $this->make_xml($tmp);

		$ret_xml = $this->execute_api($param, '/api/atom/participant', 'POST');
		$xml = $this->check_error($ret_xml); //データ取得＆エラーチェック
		return $xml;
	}

	/**
	 * オンデマンドパスワード変更
	 * @param unknown $vcdata
	 * @param unknown $password
	 */
	function set_password($vcdata, $password){
		$tmp = array();
		$tmp['password'] = $password;
		$tmp['public'] = 1;
		$param = $this->make_xml($tmp);
		$ret_xml = $this->execute_api($param, '/api/atom/ondemand/'.$vcdata->seminarkey, 'PUT');
		$xml = $this->check_error($ret_xml); //データ取得＆エラーチェック
	}

	/**
	 * 議事録URLの取得
	 * @param unknown $vcdata
	 * @return Ambigous <multitype:number , multitype:NULL Ambigous <boolean, number> >|Ambigous <mixed, stdClass, false, boolean>
	 */
	function get_minutes($vcdata){
		global $DB;
		$ret = $DB->get_record('vcubeseminarurl', array('instanceid' => $vcdata->id));
		if($ret === false){
			//URLが取得可能か判断
			$ret_xml = $this->execute_api('', '/api/atom/ondemand/'.$vcdata->seminarkey, 'GET');
			$xml = $this->check_error($ret_xml); //データ取得＆エラーチェック
			if( $xml->count == 0) return false; //準備できてない

			$tmp = array();
			//オンデマンド情報の公開設定
			$tmp['public'] = 1;
			$param = $this->make_xml($tmp);
			$ret_xml = $this->execute_api($param, '/api/atom/ondemand/'.$vcdata->seminarkey, 'PUT');
			$xml = $this->check_error($ret_xml); //データ取得＆エラーチェック
			//オンデマンドURLの取得
			$ret_xml = $this->execute_api('', '/api/atom/ondemand/'.$vcdata->seminarkey, 'GET');
			$xml = $this->check_error($ret_xml); //データ取得＆エラーチェック
			if( $xml->count != 0 ){
				//DBへの保存
				$tmp = array();
				$tmp['instanceid'] = $vcdata->id;
				$tmp['ondemandurl'] = $xml->ondemand->ondemand_url->__toString();
				$tmp['whiteboardurl'] = $xml->ondemand->whiteboard_url->__toString();
				$tmp['mobileurl'] = $xml->ondemand->mobile_url->__toString();

				$id = $DB->insert_record('vcubeseminarurl', $tmp);
				$tmp['id'] = $id;

				return $tmp;
			}else{
				return false;
			}
		}else{
			return (Array)$ret;
		}
	}


	/**
	 * 取得した会議記録リストの削除
	 */
	function minutes_list_reset($vcdata){
		global $DB;
		$ret = $DB->count_records('vcubeseminarurl', array('instanceid' => $vcdata->id));
		if($ret !== 0){
			//レコード削除
			$DB->delete_records('vcubeseminarurl', array('instanceid' => $vcdata->id));
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

		$label=get_string('resetbtn','vcubeseminar');
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
 * 入室ログ
 */
	/**
	 * ログの取得
	 * @param unknown $vcdata
	 * @param number $tflag
	 * @param number $start
	 * @param number $pagelimit
	 * @param number $page_flag 0:実際のログ 1:ログの総数
	 * @return SimpleXMLElement
	 */
	function get_entering_log($vcdata, $tflag = 0, $start = 0, $pagelimit = 10, $page_flag = 0){
		$tmp = array();
		$tmp['seminar_key'] = $vcdata->seminarkey;
		if($page_flag == 0){
			$tmp['limit'] = $pagelimit;
			$tmp['offset'] = ($start * $pagelimit);
		}else{
			$tmp['limit'] = 2000;
			$tmp['offset'] = 0;
		}
		$param = $vcdata->seminarkey.'?limit='.$tmp['limit'].'&offset='.$tmp['offset'];
		$ret_xml = $this->execute_api('', '/api/atom/audiencelog/'.$param, 'GET');
		$xml = $this->check_error($ret_xml); //データ取得＆エラーチェック
		if($page_flag == 0){
			return $xml;
		}else{
			return $xml->count->__toString();
		}
	}

	/**
	 * テーブル表示用データの作成
	 * @param unknown $vcdata
	 * @param unknown $logdata
	 * @param number $tflag
	 * @return multitype:multitype:NULL
	 */
	function make_tabledata($vcdata, $logdata, $tflag = 0){
		global $DB, $USER;

		$num = $logdata->count->__toString();
		$tabledata = array();
		$sql = <<< SQL
SELECT u.id, u.username, u.lastname, u.firstname, u.email FROM {vcubeseminarlog} AS vcl
INNER JOIN {user} AS u ON u.id = vcl.userid
WHERE vcl.invitationkey = ?
SQL;
		foreach($logdata->audiencelog as $work){
			$ivk = $work->invitation_key->__toString();
			$buff = array();
			if($work->is_lecturer->__toString() == 0){ //生徒はDBに名前参照する
				$ret = $DB->get_record_sql($sql, array($ivk));
				if( $ret === false) continue;
				$buff['username'] = $ret->username;
				$buff['lastname'] = $ret->lastname;
				$buff['firstname'] = $ret->firstname;
				$buff['email'] = $ret->email;
			}else{
				if($tflag == 1){ //教師フラグが付いているとき
					$buff['username'] = '';
					$buff['lastname'] = get_string('teacher', 'vcubeseminar');
					$buff['firstname'] = '';
					$buff['email'] = '';
				}else{
					continue;
				}
 			}
 			/*
 			$buff['enter'] = $this->timestamp2unixtime($work->entertime->standard_time->__toString())
 							+ $this->get_user_timezone($USER->timezone)*3600;
 			$buff['leave'] = $this->timestamp2unixtime($work->leavetime->standard_time->__toString())
 							+ $this->get_user_timezone($USER->timezone)*3600;
 			*/
 			$buff['enter'] = strtotime($work->entertime->standard_time->__toString());
 			$buff['leave'] = strtotime($work->leavetime->standard_time->__toString());
 			//生徒の場合はテーブルに自分の入室データ以外を入れない
 			if($tflag==2){
 				if($ret->id==$USER->id){
 					$tabledata[] = $buff;
 				}
 			}else{
 				$tabledata[] = $buff;
 			}

		}
		return $tabledata;
	}

	/**
	 * タイムスタンプからunixtimeに変換
	 * @param string $timestamp
	 * @return number
	 */
	protected function timestamp2unixtime($timestamp){
		$date_time = explode(' ', $timestamp);
		$date = explode('-', $date_time[0]);
		$time = explode(':', $date_time[1]);

		return mktime($time[0], $time[1], $time[2], $date[1], $date[2], $date[0]);
	}


		/**
	 * 部屋名、開始日時、終了日時を表示する
	 * @param unknown $roomname
	 * @param string $starttime
	 * @param string $curtaintime
	 * @param string $endtime
	 */
	function outputstatus($roomname,$starttime='',$curtaintime='',$endtime='',$attend_num=null){
		$roomtag=get_string('room', 'vcubeseminar');
		$starttag=get_string('start_datetime', 'vcubeseminar');
		$curtaintag=get_string('curtaintime', 'vcubeseminar');
		$endtag=get_string('end_datetime', 'vcubeseminar');
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
	<tr class='starttimerow'>
			<td class='tag'>{$curtaintag}</td>
			<td>:</td>
			<td class='data'>{$curtaintime}</td>
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
	 * 指定したセミナーが開催済みかを確認する
	 * @param unknown $seminar_key
	 * @return boolean
	 * true : 開催済み　false : 開催中
	 */
	function ishold($seminar){
		global $DB,$CFG;
		$seminar_key=$seminar->seminarkey;
		$tmp = array();
		$tmp['get-history'] =1;
		$tmp['get-hold-status'] = 1;


		$ret_xml = $this->execute_api($tmp, '/api/atom/reserve/'.$seminar_key, 'GET');
		//$ret_xml = $this->execute_api($tmp, '/api/atom/reserve', 'GET');
		$xml = $this->check_error($ret_xml); //データ取得＆エラーチェック
		$ishold=$xml->seminar->is_hold->__toString();
		if($ishold==0){//開催中
			return false;
		}
		$realendtime=$xml->seminar->realendtime->standard_time->__toString();
		if(empty($realendtime))return true;
		//strtotime($realendtime);
		$timezoneobj=new TimeZoneCancel();
		$realendtime=strtotime($realendtime);
		$timezoneobj->unixTimeToServerTime($realendtime);
		$seminar->endtime=$realendtime;
		$data=new stdClass();
		$data->id=$seminar->id;
		$data->endtime=$seminar->endtime;
		$data->timemodified=time();
		$DB->update_record('vcubeseminar', $data);
		return true;
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
		$time = date(FORMAT_SEM,$unixtime);
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


}




















class TimeZoneCancel{

	public $seconds=0;

	function __construct(){

		$this->seconds = date_offset_get(new DateTime); //Server timzone offset

	}


	/**
	 * moodleのDatetimeセレクタから取得した時間をAPIで使うための時間に変更する
	 * ！！参照渡しなので取り扱い注意！！
	 */
	function dateTimeSelectorForAPI(&$data){
		global $CFG,$USER;
		//dateselectorからとったUNIXTIMEがuserのtimezoneでずれている
		//→timezoneとサーバタイムゾーンでリセットして、サーバタイムゾーン換算に変更
		//タイムゾーンのキャンセル

		$usertimezone=vcseminar::get_user_timezone($USER->timezone,false);
		$offset=($usertimezone*3600)-$this->seconds;
		$data->start_datetime+=$offset;
		$data->curtaintime+=$offset;
		$data->end_datetime+=$offset;
		//ここでサーバタイムゾーンで換算したときに、登録時に入力した時刻になる
		//この状態でAPI叩く。

	}


	/**
	 * moodleのDatetimeセレクタから取得した時間をMoodleで使うための時間に変更する
	 * ！！参照渡しなので取り扱い注意！！
	 */
	function dateTimeSelectorForMoodle(&$data){
		global $CFG,$USER;

		//一旦API指定のタイムゾーンのローカルタイムにする
		$this->dateTimeSelectorForAPI($data);

		//その後、サーバタイムゾーン-指定タイムゾーンの差分を足す
		$offset=$this->seconds-($data->timezone*3600);
		$data->start_datetime+=$offset;
		$data->curtaintime+=$offset;
		$data->end_datetime+=$offset;
	}


	/**
	 * 更新時のdatetimeseletorに表示する時間を作成する
	 * Moodleのレコード上はUNIXTIMEとなっており、
	 * セレクター側はユーザのタイムゾーンで自動的にずらす機能があるので、
	 * 最終的にはユーザのタイムゾーン分だけずらした時間をセットする必要がある
	 * @param unknown $vcdata
	 */
	function moodletimeForMoodleSelector(&$vcdata){
		global $CFG,$USER;

		//取ってきたUNIXTIMEをサーバタイムゾーンで初期化後、VCに設定されたタイムゾーンで再計算
		$timezone = $vcdata->timezone;
		//タイムゾーンのキャンセル
		$offset=($timezone*3600)-$this->seconds;
		$vcdata->starttime+=$offset;
		$vcdata->curtaintime+=$offset;
		$vcdata->endtime+=$offset;
		//ここまでで、サーバタイムゾーンで初期化される
		//この後にMoodleのセレクタで”ユーザのタイムゾーン”で補正がかかるので、その逆算を行う
		//この段階では時間がずれているが、datetimeselectorがユーザプロファイルの時間で変換を行うと、
		//予約タイムゾーンの予約日時となる
		$usertimezone=vcseminar::get_user_timezone($USER->timezone,false);
		$offset=$this->seconds-$usertimezone*3600;
		$vcdata->starttime+=$offset;
		$vcdata->curtaintime+=$offset;
		$vcdata->endtime+=$offset;

	}

	/**
	 * moodleのDatetimeセレクタから取得した時間をMoodleで使うための時間に変更する
	 * Validation関数用の変換プログラム
	 * @param unknown $data
	 */
	function dateTimeSelectorForValidation(&$data){
		global $CFG,$USER;

		//タイムゾーンのキャンセル
		//一旦API指定のタイムゾーンのローカルタイムにする
		$usertimezone=vcseminar::get_user_timezone($USER->timezone,false);
		$offset=($usertimezone*3600)-$this->seconds;
		$data['start_datetime']+=$offset;
		$data['curtaintime']+=$offset;
		$data['end_datetime']+=$offset;
		//その後、サーバタイムゾーン-指定タイムゾーンの差分を足す
		@$offset=$this->seconds-($data['timezone']*3600);
		$data['start_datetime']+=$offset;
		$data['curtaintime']+=$offset;
		$data['end_datetime']+=$offset;
	}

	/**
	 *
	 * 渡されたUNIXTIMEにユーザプロファイルタイムゾーン分を加算
	 * @param unknown $time
	 */
	function unixTimeToUserTime(&$time){
		global $USER;
		$usertimezone=vcseminar::get_user_timezone($USER->timezone,false);
		$time+=($usertimezone*3600);
	}

	/**
	 *
	 * 渡されたUNIXTIMEにサーバタイムゾーン分を加算
	 * @param unknown $time
	 */
	function unixTimeToServerTime(&$time){
		$time+=$this->seconds;
	}

	/**
	 * オフセット値を取得
	 */
	function getOffset(){
		global $USER;
		//タイムゾーンのキャンセル
		$usertimezone=vcseminar::get_user_timezone($USER->timezone,false);
		$offset=$this->seconds-($usertimezone*3600);
		return $offset;
	}

}