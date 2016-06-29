<?php


/**
 * Define all the backup steps that will be used by the backup_vcubeseminar_activity_task
 *
 * @package    mod
 * @subpackage vcubeseminar
 * @copyright  V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete vcubeseminar structure for backup, with file and id annotations
 */
class backup_vcubeseminar_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated

        $vcubeseminar = new backup_nested_element('vcubeseminar', array('id'), array('course',
        'name', 'intro', 'introformat', 'showdescription','roomid', 'starttime',
        'curtaintime', 'endtime', 'timezone', 'seminarkey', 'charmanurl', 'timecreated', 'timemodified'));

        $vcubeseminarurls = new backup_nested_element('vcubeseminarurls');
        $vcubeseminarurl  = new backup_nested_element('vcubeseminarurl', array('id'),array('instanceid',
        'ondemandurl', 'whiteboardurl', 'mobileurl'));

        $vcubeseminarlogs = new backup_nested_element('vcubeseminarlogs');
        $vcubeseminarlog  = new backup_nested_element('vcubeseminarlog', array('id'), array('instanceid',
        'userid', 'invitationkey' ));

        // Build the tree
		$vcubeseminar->add_child($vcubeseminarurls);
		$vcubeseminarurls->add_child($vcubeseminarurl);
		$vcubeseminar->add_child($vcubeseminarlogs);
		$vcubeseminarlogs->add_child($vcubeseminarlog);

        // Define sources
        $vcubeseminar->set_source_table('vcubeseminar', array('id' => backup::VAR_ACTIVITYID));
        $sql = <<< SQL
SELECT * FROM {vcubeseminarurl}
WHERE instanceid = ?
SQL;
        $vcubeseminarurl->set_source_sql($sql, array(backup::VAR_PARENTID));
        $sql = <<< SQL
SELECT * FROM {vcubeseminarlog}
WHERE instanceid = ?
SQL;
        $vcubeseminarlog->set_source_sql($sql, array(backup::VAR_PARENTID));

        // Define id annotations

        // Define file annotations
//         global $CFG;
//         $vcubeseminar->annotate_files('mod_vcubeseminar', 'intro', null);
//         $vcubeseminar->annotate_files('mod_vcubeseminar', 'vcubeseminar', null);

        // Return the root element (vcubeseminar), wrapped into standard activity structure
        return $this->prepare_activity_structure($vcubeseminar);
    }

}
