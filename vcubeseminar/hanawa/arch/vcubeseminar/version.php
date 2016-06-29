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

$plugin->release   = 1.3;               // If version == 0 then module will not be installed
$plugin->version   = 2016060100;      // The current module version (Date: YYYYMMDDXX)
$plugin->requires  = 2013111803;      // Requires this Moodle version
$plugin->cron      = 0;               // Period for cron to check this module (secs)
$plugin->component = 'mod_vcubeseminar'; // To check on upgrade, that module sits in correct place
$plugin->maturity = MATURITY_STABLE;
