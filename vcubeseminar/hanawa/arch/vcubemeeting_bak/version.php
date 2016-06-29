<?php
/**
 * Defines the version of vcubemeeting
 *
 * This code fragment is called by moodle_needs_upgrading() and
 * /admin/index.php
 *
 * @package    mod
 * @subpackage vcubemeeting
 * @copyright  V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die();

$module->release   = 2.1;               // If version == 0 then module will not be installed
$module->version   = 2014121900;      // The current module version (Date: YYYYMMDDXX)
$module->requires  = 2013111800;      // Requires this Moodle version
$module->cron      = 0;               // Period for cron to check this module (secs)
$module->component = 'mod_vcubemeeting'; // To check on upgrade, that module sits in correct place
$module->maturity = MATURITY_STABLE;
