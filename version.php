<?php

/**
 * Version information for mod/scheduler
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * This is the development branch (master) of the scheduler module.
 */

$plugin->component = 'mod_scheduler'; // Full name of the plugin (used for diagnostics).
$plugin->version   = 2016052000;      // The current module version (Date: YYYYMMDDXX).
$plugin->release   = '3.x dev';       // Human-friendly version name.
$plugin->requires  = 2015042800;      // Requires Moodle 2.9.
$plugin->maturity  = MATURITY_ALPHA;  // Alpha development code - not for production sites!
