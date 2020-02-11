<?php
// This file is part of a 3rd party created module for Moodle - http://moodle.org/
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
 * Services definition.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_scheduler_booking_form_viewed' => [
        'classname' => 'mod_scheduler\\external',
        'methodname' => 'booking_form_viewed',
        'description' => 'Trigger the event reporting that booking for was viewed',
        'type' => 'write',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile']
    ],
    'mod_scheduler_book_slot' => [
        'classname' => 'mod_scheduler\\external',
        'methodname' => 'book_slot',
        'description' => 'Book a slot',
        'type' => 'write',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile']
    ],
    'mod_scheduler_revoke_appointment' => [
        'classname' => 'mod_scheduler\\external',
        'methodname' => 'revoke_appointment',
        'description' => 'Revoke an appointment',
        'type' => 'write',
        'ajax' => true,
    ],
    'mod_scheduler_watch_slot' => [
        'classname' => 'mod_scheduler\\external',
        'methodname' => 'watch_slot',
        'description' => 'Watch a slot',
        'type' => 'write',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile']
    ],
    'mod_scheduler_unwatch_slot' => [
        'classname' => 'mod_scheduler\\external',
        'methodname' => 'unwatch_slot',
        'description' => 'Unwatch a slot',
        'type' => 'write',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE, 'local_mobile']
    ]
];
