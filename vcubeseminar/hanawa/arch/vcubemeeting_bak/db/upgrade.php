<?php

/**
 * Upgrade script for the vcubemeeting module.
*
 * @package    mod
 * @subpackage vcubemeeting
 * @copyright  V-Cube Inc.
*/


defined('MOODLE_INTERNAL') || die();


/**
 * vcubemeeting module upgrade function.
 * @param string $oldversion the version we are upgrading from.
*/
function xmldb_vcubemeeting_upgrade($oldversion) {
	global $CFG, $DB;

	$dbman = $DB->get_manager();
	if ($oldversion < 2014112100) {

		// Define table vcubemeeting_files to be created.
		$table = new xmldb_table('vcubemeeting_files');

		// Adding fields to table vcubemeeting_files.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
		$table->add_field('fileid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		$table->add_field('documentid', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
		$table->add_field('download', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
		$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		$table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

		// Adding keys to table vcubemeeting_files.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Adding indexes to table vcubemeeting_files.
		$table->add_index('fileid_id', XMLDB_INDEX_UNIQUE, array('fileid'));

		// Conditionally launch create table for vcubemeeting_files.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		/*
		// Vcubemeeting savepoint reached.
		upgrade_mod_savepoint(true, 2014112100, 'vcubemeeting');
		*/
	}

	if ($oldversion < 2014112100) {

		// Define table vcubemeeting_status to be created.
		$table = new xmldb_table('vcubemeeting_status');

		// Adding fields to table vcubemeeting_status.
		$table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
		$table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
		$table->add_field('status', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
		$table->add_field('value', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
		$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
		$table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

		// Adding keys to table vcubemeeting_status.
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

		// Conditionally launch create table for vcubemeeting_status.
		if (!$dbman->table_exists($table)) {
			$dbman->create_table($table);
		}

		// Vcubemeeting savepoint reached.
		upgrade_mod_savepoint(true, 2014112100, 'vcubemeeting');
	}

	return true;

}