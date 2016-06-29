<?php

/**
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright V-Cube Inc.
 */

/**
 * Define all the restore steps that will be used by the restore_vcubeseminar_activity_task
 */

/**
 * Structure step to restore one vcubeseminar activity
 */
class restore_vcubeseminar_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
//         $userinfo = $this->get_setting_value('userinfo');
        $paths[] = new restore_path_element('vcubeseminar', '/activity/vcubeseminar');
        $paths[] = new restore_path_element('vcubeseminarurl', '/activity/vcubeseminar/vcubeseminarurls/vcubeseminarurl');
        $paths[] = new restore_path_element('vcubeseminarlog', '/activity/vcubeseminar/vcubeseminarlogs/vcubeseminarlog');

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_vcubeseminar($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // insert the vcubeseminar record
        $newitemid = $DB->insert_record('vcubeseminar', $data);
        // immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_vcubeseminarurl($data){
    	global $DB;

    	$data = (object)$data;
    	$oldid = $data->id;

    	$data->instanceid = $this->get_new_parentid('vcubeseminar');

    	$newitemid = $DB->insert_record('vcubeseminarurl', $data);
    	$this->set_mapping('vcubeseminarurl', $oldid, $newitemid);
    }

    protected function process_vcubeseminarlog($data){
    	global $DB;

    	$data = (object)$data;
    	$oldid = $data->id;

    	$data->instanceid = $this->get_new_parentid('vcubeseminar');

    	$newitemid = $DB->insert_record('vcubeseminarlog', $data);
    	$this->set_mapping('vcubeseminarlog', $oldid, $newitemid);
    }

    protected function after_execute() {
    	// Add choice related files, no need to match by itemname (just internally handled context)
//     	$this->add_related_files('mod_vcubeseminar', 'intro', null);
//     	$this->add_related_files('mod_vcubeseminar', 'vcubeseminar', null);
    }
}
