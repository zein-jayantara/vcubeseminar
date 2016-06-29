<?php

/**
 *
 * @package   block_vcubeseminar_roomsettings
 * @copyright V-cube,Inc
 */
global $CFG;
//include 'lib.php';
include $CFG->dirroot.'/blocks/vcubeseminar_roomsettings/locallib.php';

class block_vcubeseminar_roomsettings extends block_base {
    function init() {
        global $DB;
      $this->title =get_string('pluginname', 'block_vcubeseminar_roomsettings');
    }

    function has_config() {
      return true;
    }

    // ブロックの設置場所の有効範囲
    function applicable_formats() {
    	return array('site-index' => false,
    			'course-view' => true,
    			'course-view-social' => true
    	);
    }
    // 複数個設置の許可
    public function instance_allow_multiple() {
    	return false;
    }


    /**
     * アンインストール時の処理
     * @see block_base::instance_delete()
     */
    function instance_delete() {
      global $DB;
      return true;
    }

  /**
   * ブロックの中身描画
   * (non-PHPdoc)
   * @see block_base::get_content()
   */
    function get_content() {
        global $USER, $CFG, $DB, $OUTPUT,$context,$COURSE;

        $vcube_obj=new vseminarrs();
        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';
        if($context==null){
        	$context = context_course::instance($COURSE->id);
        }
        //APIパラメータチェック
        if(!$vcube_obj->accountcheck()){
          //セットアップ未完了
          $this->content->text .= '<div class="info">';
          $this->content->text=get_string('no_account_setup','block_vcubeseminar_roomsettings');
          $this->content->text .= '</div>';
        }else if (!has_capability('block/vcubeseminar_roomsettings:view', $context)){
        	$this->content->text='';
        }else {
	        //セットアップ済み。部屋の制限実施中なら制限している部屋一覧を表示する。
	        $obj = new vseminarrs();
	        $rooms = $obj->get_allow_room_list();
	        $html = html_writer::div(get_string('allow_rooms_list', 'block_vcubeseminar_roomsettings'), 'info');
	        if ( $rooms !== false ) {
	        	$html .= html_writer::start_tag('ul');
	        	foreach ($rooms as $tmp){
	        		$html .= html_writer::tag('li', $tmp);
	        	}
	        	$html .= html_writer::end_tag('ul');
	        }
	        $this->content->text = $html;
        }
        return $this->content;
    }
}


