<?php
/**
 * Defines the version of vcubeseminar
 *
 * This code fragment is called by moodle_needs_upgrading() and
 * /admin/index.php
 *
 * @package    mod
 * @subpackage vcubeseminar
 * @copyright  V-Cube Inc.
 */

defined('MOODLE_INTERNAL') || die();

$module->release   = 1.3;               // If version == 0 then module will not be installed
$module->version   = 2015091600;      // The current module version (Date: YYYYMMDDXX)
$module->requires  = 2013111803;      // Requires this Moodle version
$module->cron      = 0;               // Period for cron to check this module (secs)
$module->component = 'mod_vcubeseminar'; // To check on upgrade, that module sits in correct place
$module->maturity = MATURITY_STABLE;
