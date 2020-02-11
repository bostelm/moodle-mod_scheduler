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
 * Mobile renderer.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\output;
defined('MOODLE_INTERNAL') || die();

use moodle_exception;
use mod_scheduler_renderer as renderer;
use mod_scheduler\external;
use mod_scheduler\model\scheduler;
use mod_scheduler\permission\scheduler_permissions;

/**
 * Mobile renderer.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    protected static function pre($args) {
        global $PAGE, $USER;

        $args = (object) $args;
        $renderer = $PAGE->get_renderer('mod_scheduler');

        $scheduler = scheduler::load_by_coursemodule_id($args->cmid);
        require_login($scheduler->get_courseid(), true, $scheduler->get_cm());
        $permissions = new scheduler_permissions($scheduler->get_context(), $USER->id);

        return (object) ['scheduler' => $scheduler, 'permissions' => $permissions, 'renderer' => $renderer];
    }

    /**
     * Get common template data.
     *
     * @param scheduler $scheduler The scheduler.
     * @return array
     */
    protected static function get_common_data(scheduler $scheduler) {
        $module = (object) external::serialize_scheduler($scheduler);
        return [
            'cmid' => $scheduler->get_cmid(),
            'courseid' => $scheduler->get_courseid(),
            'scheduler' => $module,
        ];
    }

    public static function book_slot($args) {
        global $USER;

        $args = (object) $args;
        $slotid = (int) $args->id;

        $pre = static::pre($args);
        $permissions = $pre->permissions;
        $scheduler = $pre->scheduler;
        $renderer = $pre->renderer;
        $context = $scheduler->get_context();
        $userid = $USER->id;

        require_capability('mod/scheduler:appoint', $context);
        $slot = $scheduler->get_slot($slotid);
        $data = static::get_common_data($scheduler);

        if (!$data['scheduler']->isinappbookingsupported) {
            throw new moodle_exception('bookingnotsupported', 'mod_scheduler');
        } else if (!$slot->is_in_bookable_period()) {
            throw new moodle_exception('error');
        }

        $groups = [];
        if ($scheduler->is_group_scheduling_enabled()) {
            $groups = groups_get_all_groups($scheduler->courseid, $userid, $scheduler->bookingrouping, 'g.id, g.name');
        }

        $data = array_merge($data, [
            'slot' => external::serialize_slot($slot),
            'isstudentnotesrequired' => $scheduler->is_studentnotes_required(),
            'hasgroups' => !empty($groups),
            'groups' => array_values($groups),
        ]);

        return [
            'templates' => [
                [
                    'id' => 'book_slot',
                    'html' => $renderer->render_from_template('mod_scheduler/mobile_book_slot', $data)
                ]
            ],
            'javascript' => '',
            'otherdata' => '',
            'files' => [],
        ];
    }

    public static function landing_page($args) {
        $args = (object) $args;

        $pre = static::pre($args);
        $permissions = $pre->permissions;
        $scheduler = $pre->scheduler;
        $renderer = $pre->renderer;

        if ($permissions->is_teacher()) {
        } else if ($permissions->is_student()) {
            return static::student_landing_page($args, $renderer, $scheduler, $permissions);
        }

        return [
            'templates' => [
                [
                    'id' => 'noguests',
                    'html' => $renderer->render_from_template('mod_scheduler/mobile_noguests', [])
                ]
            ],
            'javascript' => '',
            'otherdata' => '',
            'files' => [],
        ];
    }

    public static function slot($args) {
        global $USER;

        $args = (object) $args;
        $slotid = (int) $args->id;

        $pre = static::pre($args);
        $permissions = $pre->permissions;
        $scheduler = $pre->scheduler;
        $renderer = $pre->renderer;
        $context = $scheduler->get_context();
        $userid = $USER->id;

        $isstudent = $permissions->is_student();
        $isteacher = $permissions->is_teacher();
        $permissions->ensure($isstudent || $isteacher);

        $slot = $scheduler->get_slot($slotid);
        $appointment = null;
        if ($isstudent) {
            $appointment = $slot->get_student_appointment($userid);
        }

        $data = array_merge(static::get_common_data($scheduler), [
            'slot' => external::serialize_slot($slot),
            'appointment' => $appointment ? external::serialize_appointment($appointment) : null,
            'isstudent' => $isstudent,
            'isteacher' => $isteacher,
        ]);

        return [
            'templates' => [
                [
                    'id' => 'slot',
                    'html' => $renderer->render_from_template('mod_scheduler/mobile_slot', $data)
                ]
            ],
            'javascript' => '',
            'otherdata' => '',
            'files' => [],
        ];
    }

    /**
     * Student bookable slots.
     *
     * @param object $args Contains cmid and optionally page.
     * @return array
     */
    public static function student_bookable_slots($args) {
        $args = (object) $args;

        $pre = static::pre($args);
        $permissions = $pre->permissions;
        $scheduler = $pre->scheduler;
        $renderer = $pre->renderer;
        $context = $scheduler->get_context();

        $permissions->ensure($permissions->is_student());
        require_capability('mod/scheduler:viewslots', $context);
        require_capability('mod/scheduler:appoint', $context);
        $canwatch = has_capability('mod/scheduler:watchslots', $context) && $scheduler->is_watching_enabled();

        $page = isset($args->page) ? (int) $args->page : 1;
        $result = (object) external::get_available_slots($scheduler->get_cmid(), $page);

        $data = array_merge(static::get_common_data($scheduler), [
            'prevpage' => max($result->page - 1, 0),
            'hasnext' => $result->hasnextpage,
            'hasprev' => $result->page > 1,
            'nextpage' => $result->page + 1,
            'prevpage' => max($result->page - 1, 0),
            'slots' => $result->slots,
        ]);

        return [
            'templates' => [
                [
                    'id' => 'student_bookable_slots',
                    'html' => $renderer->render_from_template('mod_scheduler/mobile_student_bookable_slots', $data)
                ]
            ],
            'javascript' => '',
            'otherdata' => '',
            'files' => [],
        ];
    }

    /**
     * Student landing page.
     *
     * @param object $args The original arguments.
     * @param renderer_base $renderer The renderer.
     * @param scheduler $scheduler The scheduler.
     * @param scheduler_permissions $permissions The permissions.
     * @return array
     */
    public static function student_landing_page($args, $renderer, scheduler $scheduler, scheduler_permissions $permissions) {
        global $USER;

        $userid = $USER->id;
        $context = $scheduler->get_context();

        require_capability('mod/scheduler:viewslots', $context);

        // Find attended slots.
        $pastslotsraw = $scheduler->get_attended_slots_for_student($userid);
        $pastslots = array_map(function($slot) {
            return external::serialize_slot($slot);
        }, $pastslotsraw);

        // Find the upcoming slots.
        $upcomingslotsraw = $scheduler->get_upcoming_slots_for_student($userid);
        $upcomingslots = array_map(function($slot) use ($context, $scheduler, $userid) {
            // $appointment = $slot->get_student_appointment($userid);
            // $cancancel = $slot->is_in_bookable_period();
            // $canedit = $cancancel && $scheduler->uses_studentdata();
            // $canview = !$cancancel && $scheduler->uses_studentdata();
            // if ($scheduler->is_group_scheduling_enabled()) {
            //     $cancancel = $cancancel && ($appointgroup >= 0);
            // }
            return external::serialize_slot($slot);
        }, $upcomingslotsraw);

        // Display the bookable slots.
        $result = (object) external::get_available_slots($scheduler->get_cmid(), 1, 10);
        $nobookingsremaining = $result->bookingsremaining;
        $bookableslots = $result->slots;
        $totalbookableslots = $result->total;

        $nobookingmessage = '';
        if (!$nobookingsremaining) {
            $nobookingmessage = get_string('canbooknofurtherappointments', 'mod_scheduler');
        } else if (!count($bookableslots)) {
            $nobookingmessage = get_string('noslotsavailable', 'mod_scheduler');
        }

        $bookingmessage = '';
        if ($nobookingsremaining == 1) {
            $msgkey = ($scheduler->schedulermode == 'oneonly') ? 'canbooksingleappointment' : 'canbook1appointment';
            $bookingmessage = get_string($msgkey, 'mod_scheduler');
        } else if ($nobookingsremaining > 1) {
            $bookingmessage = get_string('canbooknappointments', 'mod_scheduler');
        } else if ($nobookingsremaining < 0) {
            $bookingmessage = get_string('canbookunlimitedappointments', 'mod_scheduler');
        }

        $data = array_merge(static::get_common_data($scheduler), [
            'haspastslots' => !empty($pastslots),
            'pastslots' => $pastslots,

            'hasupcomingslots' => !empty($upcomingslots),
            'upcomingslots' => $upcomingslots,

            'hasbookableslots' => !empty($bookableslots),
            'hasmorebookableslots' => count($bookableslots) < $totalbookableslots,
            'bookableslots' => $bookableslots,
            'bookingmessage' => $bookingmessage,
            'nobookingmessage' => $nobookingmessage,
        ]);

        return [
            'templates' => [
                [
                    'id' => 'student_landing_page',
                    'html' => $renderer->render_from_template('mod_scheduler/mobile_student_landing_page', $data)
                ]
            ],
            'javascript' => '',
            'otherdata' => '',
            'files' => [],
        ];
    }

    /**
     * Watch a slot.
     *
     * @param array $args The arguments.
     * @return array
     */
    public static function watch_slot($args) {
        global $USER;

        $args = (object) $args;
        $slotid = (int) $args->id;

        $pre = static::pre($args);

        external::watch_slot($args->cmid, $slotid);

        return static::slot($args);
    }

    /**
     * Unwatch a slot.
     *
     * @param array $args The arguments.
     * @return array
     */
    public static function unwatch_slot($args) {
        global $USER;

        $args = (object) $args;
        $slotid = (int) $args->id;

        $pre = static::pre($args);
        external::unwatch_slot($args->cmid, $slotid);
        return static::slot($args);
    }
}
