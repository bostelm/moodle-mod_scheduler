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
 * Version information for mod/scheduler
 *
 * @package    mod_scheduler
 * @copyright  2018 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * This is the 4.0 branch of the scheduler module, intended for Moodle 4.0 and later.
 */

$plugin->component = 'mod_scheduler'; // Full name of the plugin (used for diagnostics).
$plugin->version   = 2023052300;      // The current module version (Date: YYYYMMDDXX).
$plugin->release   = '4.0.0';       // Human-friendly version name.
$plugin->requires  = 2022041900;      // Requires Moodle 4.0.
$plugin->maturity  = MATURITY_STABLE;  // Stable version.
