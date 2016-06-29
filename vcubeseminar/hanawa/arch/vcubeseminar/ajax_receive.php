<?php
/*
vcubeseminar.jsからAjaxで呼び出され
DBから選択されたセミナールームに合致するセミナー一覧を取得
JSON形式で返す
*/
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/config.php');
defined('MOODLE_INTERNAL') || die();
global $DB,$CFG;
require_once $CFG->dirroot.'/mod/vcubeseminar/locallib.php';
$post_values = explode('_', optional_param('select_room', '0_0', PARAM_TEXT));
$ary = array();
//get ondemand seminar//
if(isset($post_values[0]) && isset($post_values[1]) && $post_values[0] && $post_values[1]){
	$vc = new vcseminar();
	$ary = $vc->get_allow_room_seminar($post_values[0], $post_values[1]);
}
/*
//get live seminar//
if($post_value){
    $rooms = $DB->get_records('vcubeseminar', array('roomid' => $post_value),null);
    foreach($rooms as $room){
        $ary[] = array('name'=>$room->name,'key'=>$room->seminarkey);
    }
}
*/
//echo json_encode($post_values);
echo json_encode($ary);