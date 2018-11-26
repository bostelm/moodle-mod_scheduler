<?php

/**
 * Version information for mod/scheduler
 *
 * @package    mod_scheduler
 * @copyright  2018 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * This is the development branch (master) of the scheduler module.
 */

$plugin->component = 'mod_scheduler'; // Full name of the plugin (used for diagnostics).
$plugin->version   = 2018112600;      // The current module version (Date: YYYYMMDDXX).
$plugin->release   = '3.5.0';         // Human-friendly version name.
$plugin->requires  = 2018051700;      // Requires Moodle 3.5.
$plugin->maturity  = MATURITY_STABLE; // Stable release
