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
 * Mod Scheduler webservice definitions
 *
 * @package    mod_scheduler
 * @copyright  2022 University of Glasgow
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(

    'mod_scheduler_studentid' => array(
        'classname'   => 'mod_scheduler\external',
        'methodname'  => 'studentid',
        'description' => 'Retrieve the list of potential studentids.',
        'type'        => 'read',
        'ajax'          => true,
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
);
