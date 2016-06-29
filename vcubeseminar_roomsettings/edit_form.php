<?php
require_once 'locallib.php';
/**
 *
 * @package   block_vcubeseminar_roomsettings
 * @copyright V-cube,Inc
 */


class block_vcubeseminar_roomsettings_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
		$obj = new vseminarrs();
		$rooms = $obj->get_room_list();

        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block'));

        $mform->setDefault('config_title', $this->block->title);
        $mform->setType('config_title', PARAM_TEXT);

        $mform->addElement('static', 'desc', get_string('allow_rooms', 'block_vcubeseminar_roomsettings'));
        //部屋の一覧の描画
        foreach ($rooms AS $room) {
        	$name = 'config_'.$room['id'];
        	$mform->addElement('advcheckbox', $name, '', $room['name'], array('group' => 1));
        }
        $this->add_checkbox_controller(1);
    }

    function set_data($defaults) {
    	if(!$this->is_submitted()) {
    		//set value for room checked
	    	$data = unserialize(base64_decode($defaults->configdata));
			if(!empty($data)){
		    	foreach ($data as $key=>$value){
		    		$key = 'config_'.$key;
		    		$this->_form->_constantValues[$key] = $value;
		    	}
			}
	    	parent::set_data($defaults);
    	}
    }
}
