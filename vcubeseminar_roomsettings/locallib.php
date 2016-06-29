<?php
/**
 *v-cubeアカウント管理用クラス
 * @package   block_vcubeseminar_roomsettings
 * @copyright V-cube,Inc
 */
class vseminarrs{
	//モジュール限定仕様のセッティング
	private $servicename = 'vcubeseminar';
	private $accountparam=array('domain', 'id', 'password');

	function __construct(){
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
			if($method == 'GET' ) curl_setopt($ch, CURLOPT_HTTPGET, true);
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
	function get_allow_room_list(){
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