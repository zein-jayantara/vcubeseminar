<?php

/**
 * Capability definitions for the newmodule module
 *
 * The capabilities are loaded into the database table when the module is
 * installed or updated. Whenever the capability definitions are updated,
 * the module version number should be bumped up.
 *
 * The system has four possible values for a capability:
 * CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
 *
 * It is important that capability names are unique. The naming convention
 * for capabilities that are specific to modules and blocks is as follows:
 *   [mod/block]/<plugin_name>:<capabilityname>
 *
 * component_name should be the same as the directory name of the mod or block.
 *
 * Core moodle capabilities are defined thus:
 *    moodle/<capabilityclass>:<capabilityname>
 *
 * Examples: mod/forum:viewpost
 *           block/recent_activity:view
 *           moodle/site:deleteuser
 *
 * The variable name for the capability definitions array is $capabilities
 *
 * @package    mod
 * @subpackage vcubeseminar
 * @copyright  V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
	'mod/vcubeseminar:addinstance' => array(
		'captype' => 'write',
		'contextlevel' => CONTEXT_MODULE,
		'archetypes' => array(
				'editingteacher' => CAP_ALLOW,
				'manager' => CAP_ALLOW
		)
	)
);