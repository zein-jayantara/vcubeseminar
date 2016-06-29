<?php
/**
 *v-cubeアカウント管理用クラス
 * @package   block_vcubemeeting_roomsettings
 * @copyright V-cube,Inc
 */
class vcmeetingrs{
	private $session;
	private $apiurl;

	//モジュール限定仕様のセッティング
	private $servicename = 'vcubemeeting';
	private $accountparam=array('domain', 'id', 'password', 'adminpassword');

	function __construct(){
		$this->session = '';
		$this->apiurl = '';
	}


	/**
	 * APIにログインする。
	 * @throws moodle_exception エラーの時
	 */
	 private function login(){
		try{
			$param=$this->get_vcubeaccount();
			if( $param === false) return false;
			$this->apiurl=$param->domain.'/api/v1/user/';
			$post=array('action_login'=>'', 'id'=>$param->id, 'pw'=>$param->password);
			//セッション変数からAPIセッションを取得
			if(isset($_SESSION['vcmeetingrs_login'])) { //すでにログイン済みかチェック
				$tmp = $_SESSION['vcmeetingrs_login'];
				if ( (date('U') - $tmp['createdatetime']) <= 86100 ){ // 24h - 5m = 86100s経過していないか
					$this->session = $tmp['session'];
					return;
				}
			}
			unset($_SESSION['vcmeetingrs_login']);
			//APIでログイン処理
			$ret = $this->execute_api($post);

			if($ret['status'] == 1){ //成功
				//save the data to session
				$this->session = $ret['data']['session'];
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
	 * @return boolean|Ambigous <boolean, multitype:string >
	 */
	function get_allow_room_list(){
		global $DB, $COURSE;
		$sql = <<< SQL
SELECT configdata FROM mdl_block_instances AS bi
INNER JOIN mdl_context AS c ON
bi.parentcontextid = c.id
AND c.contextlevel = 50
AND c.instanceid = ?
AND bi.blockname = 'vcubemeeting_roomsettings'
SQL;
		$ret = $DB->get_record_sql($sql, array($COURSE->id));
		if ($ret === false){
			return false;
		}else{
			$configdata = (array)unserialize(base64_decode($ret->configdata));
		}
		//部屋名の一覧の取得
		$rooms = $this->get_room_list();
		$allow_rooms = array();
		foreach ($configdata as $key=>$value){
			reset($rooms);
			while(list($rkey, $rvalue) = each($rooms)) {//利用を許可された部屋の一覧
				if( ($key == $rkey) && ($value == 1)) {
					$allow_rooms[] = $rvalue['name'];
				}
			}
		}
		return (count($allow_rooms) == 0)? false:$allow_rooms;

	}

}