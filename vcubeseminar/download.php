<?php
/**
 * Moodleファイルダウンローダー
 */
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);

$cmid=getCmid();
if($cmid==0)error('Error:unknown course module id detected');


if ($id) {
	$cm         = get_coursemodule_from_id('vcubeseminar', $cmid, 0, false, MUST_EXIST);
	$course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
} else {
	error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$file=$DB->get_record('files',array('id' => $id));

if(permitDownload($file, $course->id,$cm->id)){
	//ダウンロード処理

	//ファイルのパス
	$contenthash=$file->contenthash;
	// $contenthashの頭2文字ずつを区切ったディレクトリ内に$contenthashのファイルが保存されている。
	$filepath=$CFG->dataroot.'/filedir/'.substr($contenthash, 0,2).'/'.substr($contenthash, 2,2).'/'.$contenthash;

	header('Content-Type: application/force-download');
	header('Content-Length: '.filesize($filepath));
	header('Content-disposition: attachment; filename="'.$file->filename.'"');
	readfile($filepath);




}else{
	error();
}


/**
 * 指定したファイルIDのダウンロードが許可できるか
 * @param unknown $fileid
 */
function permitDownload($file,$courseid,$cmid){
	global $DB;

	$context=$DB->get_record('context', array('contextlevel'=>70,'instanceid'=>$cmid));
	if($file->contextid===$context->id && $file->filearea){
		return true;
	}
	return false;
}


/**
 * リファラからcmidを取得し、返す
 *
 */
function getCmid(){
	$referer=$_SERVER["HTTP_REFERER"];
	$getparam=array();
	//リファラの?以降の文字を取得（GETパラメータを取得）
	$opt=strstr($referer, "?");
	//？　を削除
	$opt=substr($opt,1);
	//GETパラメータごとに分割
	$optar=explode('&',$opt);
	foreach ($optar AS $key=>$value){
		$tmp=explode('=', $value);
		$getparam[$tmp[0]]=$tmp[1];
	}
	if(isset($getparam['id'])){
		return $getparam['id'];
	}
	return 0;

}