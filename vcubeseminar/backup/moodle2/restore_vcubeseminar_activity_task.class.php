<?php

/**
 * @package moodlecore
 * @subpackage backup-moodle2
 * @copyright V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/vcubeseminar/backup/moodle2/restore_vcubeseminar_stepslib.php'); // Because it exists (must)

/**
 * vcubeseminar restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_vcubeseminar_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step
        $this->add_step(new restore_vcubeseminar_activity_structure_step('vcubeseminar_structure', 'vcubeseminar.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('vcubeseminar', array('intro'), 'vcubeseminar');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('VCUBESEMINARVIEWBYID', '/mod/vcubeseminar/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('VCUBESEMINARINDEX', '/mod/vcubeseminar/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * vcubeseminar logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('vcubeseminar', 'minute view', 'view.php?id={course_module}', '{vcubeseminar}');
        $rules[] = new restore_log_rule('vcubeseminar', 'mobile view', 'view.php?id={course_module}', '{vcubeseminar}');
        $rules[] = new restore_log_rule('vcubeseminar', 'recoding view', 'view.php?id={course_module}', '{vcubeseminar}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('vcubeseminar', 'view', 'index.php?id={course}', null);

        return $rules;
    }
}
