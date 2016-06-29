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
$post_value = optional_param('select_room', '0', PARAM_INT);
$ary = array();
//get ondemand seminar//
if($post_value){
	$vc = new vcseminar();
	$ary = $vc->get_allow_room_seminar($post_value);
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
echo json_encode($ary);