<?php

/**
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright V-Cube Inc.
 */

/**
 * Define all the restore steps that will be used by the restore_vcubemeeting_activity_task
 */

/**
 * Structure step to restore one vcubemeeting activity
 */
class restore_vcubemeeting_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
//         $userinfo = $this->get_setting_value('userinfo');
        $paths[] = new restore_path_element('vcubemeeting', '/activity/vcubemeeting');
        $paths[] = new restore_path_element('vcubemeetingurl', '/activity/vcubemeeting/vcubemeetingurls/vcubemeetingurl');
        $paths[] = new restore_path_element('vcubemeeting_files', '/activity/vcubemeeting/vcubemeeting_files');
        $paths[] = new restore_path_element('vcubemeeting_status', '/activity/vcubemeeting/vcubemeeting_status');
        $paths[] = new restore_path_element('log', '/activity/vcubemeeting/log');
        $paths[] = new restore_path_element('logstore_standard_log', '/activity/vcubemeeting/logstore_standard_log');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_vcubemeeting($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the vcubemeeting record
        $newitemid = $DB->insert_record('vcubemeeting', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_vcubemeetingurl($data){
    	global $DB;

    	$data = (object)$data;
    	$oldid = $data->id;

    	$data->instanceid = $this->get_new_parentid('vcubemeeting');

    	$newitemid = $DB->insert_record('vcubemeetingurl', $data);
    	$this->set_mapping('vcubemeetingurl', $oldid, $newitemid);
    }


    protected function process_vcubemeeting_files($data){
    	global $DB;

    	$data = (object)$data;
    	$oldid = $data->id;

    	$data->instanceid = $this->get_new_parentid('vcubemeeting');
    	$data->timecreated = $this->apply_date_offset($data->timecreated);
    	$data->timemodified = $this->apply_date_offset($data->timemodified);

    	$newitemid = $DB->insert_record('vcubemeeting_files', $data);
    	$this->set_mapping('vcubemeeting_files', $oldid, $newitemid);
    }

    protected function process_vcubemeeting_status($data){
    	global $DB;

    	$data = (object)$data;
    	$oldid = $data->id;

    	$data->instanceid = $this->get_new_parentid('vcubemeeting');
    	$data->timecreated = $this->apply_date_offset($data->timecreated);
    	$data->timemodified = $this->apply_date_offset($data->timemodified);

    	$newitemid = $DB->insert_record('vcubemeeting_status', $data);
    	$this->set_mapping('vcubemeeting_status', $oldid, $newitemid);
    }


    protected function process_log($data){
    	global $DB;
    	$ver = $DB->get_field('config', 'value', array('name'=>'version'));
    	$data = (object)$data;
    	$oldid = $data->id;

    	if($ver < 2014051200){//moodle2.6まで
	    	$data->userid = $this->get_mappingid('user', $data->userid);
	    	$data->cmid = $this->get_mappingid('course_module', $data->cmid);
	    	$data->course = $this->get_courseid();
	    	$newitemid = $DB->insert_record('log', $data);
	    	$this->set_mapping('log', $oldid, $newitemid);
    	}else{//moodle2.7から
    		$newdata=new stdClass();
    		$newdata->id=$data->id;
    		$newdata->eventname='\mod_vcubemeeting\event\vcubemeeting_room_entering';
    		$newdata->component='mod_vcubemeeting';
    		$newdata->action='entering';
    		$newdata->target='vcubemeeting_room';
    		$newdata->objecttable='vcubemeeting';
    		$newdata->objectid=$this->get_mappingid('course_module', $data->cmid);
    		$newdata->crud='c';
    		$newdata->edulevel=0;
    		$newdata->contextid=0;
    		$newdata->contextlevel=70;
    		$newdata->contextinstanceid=$newdata->objectid;
    		$newdata->timecreated=$data->time;
    		$newdata->ip=$data->ip;
    		$newdata->userid = $this->get_mappingid('user', $data->userid);
    		$newdata->courseid = $this->get_courseid();
    		$newitemid = $DB->insert_record('logstore_standard_log', $newdata);
    		$this->set_mapping('logstore_standard_log', $oldid, $newitemid);
    	}
    }


    protected function process_logstore_standard_log($data){
    	global $DB;
    	$ver = $DB->get_field('config', 'value', array('name'=>'version'));
    	$data = (object)$data;
    	$oldid = $data->id;

    	if($ver < 2014051200){//moodle2.6まで

    		//下位バージョンへのリストアは不可
    	}else{//moodle2.7から

    		$data->userid = $this->get_mappingid('user', $data->userid);
    		$data->objectid = $this->get_mappingid('course_module', $data->objectid);
    		$data->contextinstanceid= $data->objectid;
    		$data->courseid = $this->get_courseid();
    		$data->contextid= $this->get_mappingid('contextid', $data->contextid);

    		$newitemid = $DB->insert_record('logstore_standard_log', $data);
    		$this->set_mapping('logstore_standard_log', $oldid, $newitemid);
    	}
    }


    protected function after_execute() {
    	// Add choice related files, no need to match by itemname (just internally handled context)
     	$this->add_related_files('mod_vcubemeeting', 'intro', null);
     	$this->add_related_files('mod_vcubemeeting', 'content', null);
    }
}
