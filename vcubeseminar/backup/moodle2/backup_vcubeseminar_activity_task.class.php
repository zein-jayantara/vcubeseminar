<?php
/**
 * Defines backup_vcubeseminar_activity_task class
 *
 * @package     mod_vcubeseminar
 * @category    backup
 * @copyright   V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/vcubeseminar/backup/moodle2/backup_vcubeseminar_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the vcubeseminar instance
 */
class backup_vcubeseminar_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the vcubeseminar.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_vcubeseminar_activity_structure_step('vcubeseminar_structure', 'vcubeseminar.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot,"/");

        //Link to the list of vcubeseminars
        $search="/(".$base."\/mod\/vcubeseminar\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@vcubeseminarINDEX*$2@$', $content);

        // Link to vcubeseminar view by moduleid
        $search="/(".$base."\/mod\/vcubeseminar\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@vcubeseminarVIEWBYID*$2@$', $content);

        return $content;
    }
}
