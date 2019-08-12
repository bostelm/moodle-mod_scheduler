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
$plugin->version   = 2019081200;      // The current module version (Date: YYYYMMDDXX).
$plugin->release   = '3.x dev';       // Human-friendly version name.
$plugin->requires  = 2019052000;      // requires Moodle 3.7.
$plugin->maturity  = MATURITY_ALPHA;  // Development release - not for production use.
