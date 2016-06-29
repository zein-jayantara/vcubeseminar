<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
/**
 * V-CUBE Seminar module upgrade code
 *
 * @package    mod_vcubeseminar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * V-CUBE Seminar module upgrade task
 *
 * @param int $oldversion the version we are upgrading from
 * @return vcubeseminar always true
 */
function xmldb_vcubeseminar_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2015081003) {

        //add field 'seminar_type'
        $table = new xmldb_table('vcubeseminar');
        $field = new xmldb_field('seminar_type', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        //rename 'charmanurl' to 'chairmanurl'
        $table = new xmldb_table('vcubeseminar');
        $field = new xmldb_field('charmanurl', XMLDB_TYPE_CHAR, '1333', null, XMLDB_NOTNULL, null, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table,$field,'chairmanurl');
        }

    }


    if ($oldversion < 2015082500) {

        //create table 'vcubeseminar_ondemandlog'
        $table = new xmldb_table('vcubeseminar_ondemandlog');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('starttime', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if(!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

    }


    if ($oldversion < 2015090901) {
    	// Define table vcubeseminar_files to be created.
    	$table = new xmldb_table('vcubeseminar_files');

    	// Adding fields to table vcubeseminar_files.
    	$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
    	$table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    	$table->add_field('fileid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    	$table->add_field('documentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
    	$table->add_field('type', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
    	$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
    	$table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

    	// Adding keys to table vcubeseminar_files.
    	$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

    	// Adding indexes to table vcubeseminar_files.
    	$table->add_index('fileid_id', XMLDB_INDEX_UNIQUE, array('fileid'));

    	// Conditionally launch create table for vcubeseminar_files.
    	if (!$dbman->table_exists($table)) {
    		$dbman->create_table($table);
    	}
    }

    if ($oldversion < 2015091600) {
        //add to 4 field to table 'vcubeseminar'
    	$dbman = $DB->get_manager();
    	$table = new xmldb_table('vcubeseminar');

    	//add is_animation
    	$field = new xmldb_field('is_animation', XMLDB_TYPE_INTEGER,'1',null,null,null,null);
    	if (!$dbman->field_exists($table, $field)) $dbman->add_field($table, $field);

    	//add download_whiteboard
    	$field = new xmldb_field('download_whiteboard', XMLDB_TYPE_INTEGER,'1',null,null,null,null);
    	if (!$dbman->field_exists($table, $field)) $dbman->add_field($table, $field);

    	//add download_filecabinet
    	$field = new xmldb_field('download_filecabinet', XMLDB_TYPE_INTEGER,'1',null,null,null,null);
    	if (!$dbman->field_exists($table, $field)) $dbman->add_field($table, $field);

    	//add link_url
    	$field = new xmldb_field('link_url', XMLDB_TYPE_CHAR,'1333',null,null,null,null);
    	if (!$dbman->field_exists($table, $field)) $dbman->add_field($table, $field);

    }

    return true;
}
