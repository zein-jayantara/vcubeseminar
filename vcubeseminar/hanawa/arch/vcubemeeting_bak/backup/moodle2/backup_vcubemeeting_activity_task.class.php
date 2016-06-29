<?php
/**
 * Defines backup_vcubemeeting_activity_task class
 *
 * @package     mod_vcubemeeting
 * @category    backup
 * @copyright   V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/vcubemeeting/backup/moodle2/backup_vcubemeeting_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the vcubemeeting instance
 */
class backup_vcubemeeting_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the vcubemeeting.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_vcubemeeting_activity_structure_step('vcubemeeting_structure', 'vcubemeeting.xml'));
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

        //Link to the list of vcubemeetings
        $search="/(".$base."\/mod\/vcubemeeting\/index.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@vcubemeetingINDEX*$2@$', $content);

        // Link to vcubemeeting view by moduleid
        $search="/(".$base."\/mod\/vcubemeeting\/view.php\?id\=)([0-9]+)/";
        $content= preg_replace($search, '$@vcubemeetingVIEWBYID*$2@$', $content);

        return $content;
    }
}
