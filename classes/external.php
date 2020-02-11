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
 * External API.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler;
defined('MOODLE_INTERNAL') || die();

use core_user;
use external_api;
use external_function_parameters;
use extenral_multiple_structure;
use external_single_structure;
use external_value;
use moodle_exception;
use mod_scheduler\model\appointment;
use mod_scheduler\model\scheduler;
use mod_scheduler\model\slot;
use mod_scheduler\permission\scheduler_permissions;
use mod_scheduler_renderer as renderer;
use scheduler_messenger;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/scheduler/mailtemplatelib.php');

/**
 * External API.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function booking_form_viewed_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Trigger booking form viewed event.
     *
     * @param int $cmid The cmid.
     * @return null
     */
    public static function booking_form_viewed($cmid) {
        global $USER;

        $params = self::validate_parameters(self::booking_form_viewed_parameters(), ['cmid' => $cmid]);
        $cmid = $params['cmid'];

        $scheduler = scheduler::load_by_coursemodule_id($cmid);
        self::validate_context($scheduler->get_context());
        $permissions = new scheduler_permissions($scheduler->get_context(), $USER->id);

        $permissions->ensure($permissions->is_student());
        \mod_scheduler\event\booking_form_viewed::create_from_scheduler($scheduler)->trigger();

        return null;
    }

    /**
     * External function return structure.
     *
     * @return external_value
     */
    public static function booking_form_viewed_returns() {
        return new external_value(null);
    }

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function book_slot_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT),
            'slotid' => new external_value(PARAM_INT),
            'groupid' => new external_value(PARAM_INT, '', VALUE_DEFAULT, 0),
            'bookingdata' => new external_single_structure([
                'studentnote' => new external_value(PARAM_RAW, '', VALUE_DEFAULT, ''),
                'studentnoteformat' => new external_value(PARAM_INT, '', VALUE_DEFAULT, FORMAT_PLAIN),
            ], '', VALUE_DEFAULT, [])
        ]);
    }

    /**
     * Trigger booking form viewed event.
     *
     * @param int $cmid The cmid.
     * @param int $slotid The slot ID.
     * @param int $groupid The group ID, if any.
     * @param array|null $bookingdata The booking data.
     * @return null
     */
    public static function book_slot($cmid, $slotid, $groupid = 0, $bookingdata = []) {
        global $USER;

        $params = self::validate_parameters(self::book_slot_parameters(), ['cmid' => $cmid, 'slotid' => $slotid,
            'groupid' => $groupid, 'bookingdata' => $bookingdata]);
        $cmid = $params['cmid'];
        $slotid = $params['slotid'];
        $groupid = $params['groupid'];
        $bookingdata = $params['bookingdata'];

        $scheduler = scheduler::load_by_coursemodule_id($cmid);
        $context = $scheduler->get_context();
        self::validate_context($context);
        $permissions = new scheduler_permissions($context, $USER->id);

        $permissions->ensure($permissions->is_student());
        require_capability('mod/scheduler:appoint', $context);
        $slot = $scheduler->get_slot($slotid);

        if (!static::is_in_app_booking_supported($scheduler)) {
            throw new moodle_exception('bookingnotsupported', 'mod_scheduler');
        } else if ($slot->is_booked_by_student($USER->id)) {
            throw new moodle_exception('alreadybookedbyyou', 'mod_scheduler');
        }

        if (empty($bookingdata)) {
            $bookingdata = ['studentnote' => '', 'studentnoteformat' => FORMAT_PLAIN];
        }
        $bookingdata = (object) $bookingdata;

        if ($scheduler->uses_bookingform()) {
            if ($scheduler->is_studentnotes_required() && static::is_empty($bookingdata->studentnote)) {
                throw new moodle_exception('studentnotemissing', 'mod_scheduler');
            }
        }

        $formdata = (object) [];
        if ($scheduler->uses_studentnotes()) {
            $formdata->studentnote_editor = [
                'text' => $bookingdata->studentnote,
                'format' => $bookingdata->studentnoteformat ?: FORMAT_PLAIN,
            ];
        }
        mod_scheduler_book_slot($scheduler, $slotid, $USER->id, $groupid, $formdata);

        return self::serialize_appointment($scheduler->get_slot($slotid)->get_student_appointment($USER->id));
    }

    /**
     * External function return structure.
     *
     * @return external_value
     */
    public static function book_slot_returns() {
        return static::appointment_structure();
    }

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function get_available_slots_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT),
            'page' => new external_value(PARAM_INT, '', VALUE_DEFAULT, 1),
            'perpage' => new external_value(PARAM_INT, '', VALUE_DEFAULT, 25),
        ]);
    }

    /**
     * Get available slots.
     *
     * When the student can watch slots, this will also return slots that are fully booked but
     * can be watched in order to be notified when they open up.
     *
     * @param int $cmid The cmid.
     * @param int $page The page number.
     * @param int $perpage The number of items per page.
     * @return array
     */
    public static function get_available_slots($cmid, $page = 1, $perpage = 25) {
        global $USER;

        $params = self::validate_parameters(self::get_available_slots_parameters(), ['cmid' => $cmid, 'page' => $page]);
        $cmid = $params['cmid'];
        $page = max($params['page'], 1);
        $offset = $page - 1 * $perpage;

        $userid = $USER->id;
        $scheduler = scheduler::load_by_coursemodule_id($cmid);
        $context = $scheduler->get_context();
        self::validate_context($context);

        require_capability('mod/scheduler:viewslots', $context);
        require_capability('mod/scheduler:appoint', $context);
        $canbook = has_capability('mod/scheduler:appoint', $context);
        $canwatch = has_capability('mod/scheduler:watchslots', $context) && $scheduler->is_watching_enabled();
        $canseeothers = has_capability('mod/scheduler:seeotherstudentsbooking', $context);

        $nobookingsremaining = $scheduler->count_bookable_appointments($userid, false);
        $canbookslots = $canbook && $nobookingsremaining != 0;
        $canwatchslots = $canwatch && $canbookslots;
        $bookableslots = [];
        $totalbookableslots = 0;

        if ($canbookslots) {
            $bookableslotsraw = array_values($scheduler->get_slots_available_to_student($userid, $canwatchslots));
            $totalbookableslots = count($bookableslotsraw);
            $bookableslots = array_map(function($slot) use ($canbookslots, $canwatchslots, $canseeothers) {
                return static::serialize_slot($slot, $canbookslots, $canwatchslots, $canseeothers);
            }, array_slice($bookableslotsraw, ($page - 1) * $perpage, $perpage));
        }

        $hasnextpage = $page * $perpage < $totalbookableslots;

        return [
            'bookingsremaining' => $nobookingsremaining,
            'canbookslots' => $canbookslots,
            'canwatchslots' => $canwatchslots,
            'hasnextpage' => $hasnextpage,
            'page' => $page,
            'slots' => $bookableslots,
            'total' => $totalbookableslots,
        ];
    }

    /**
     * External function return structure.
     *
     * @return external_value
     */
    public static function get_available_slots_returns() {
        return new external_single_structure([
            'bookingsremaining' => new external_value(PARAM_INT, 'The number of bookings remaining, -1 means unlimited.'),
            'canbookslots' => new external_value(PARAM_BOOL, 'Whether the user can book additional slots.'),
            'canwatchslots' => new external_value(PARAM_BOOL, 'Whether the user can watch additional slots.'),
            'hasnextpage' => new external_value(PARAM_BOOL, 'Whether we can browse another page.'),
            'page' => new external_value(PARAM_INT, 'The current page number.'),
            'slots' => new extenral_multiple_structure(static::slot_structure(), 'The slots'),
            'total' => new external_value(PARAM_INT, 'The total number of slots in the set.'),
        ]);
    }

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function revoke_appointment_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT),
            'appointmentid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Revoke appointment.
     *
     * @param int $cmid The cmid.
     * @param int $appointmentid The appointment ID.
     * @return null
     */
    public static function revoke_appointment($cmid, $appointmentid) {
        global $USER;

        $params = self::validate_parameters(self::revoke_appointment_parameters(),
            ['cmid' => $cmid, 'appointmentid' => $appointmentid]);

        $cmid = $params['cmid'];
        $appointmentid = $params['appointmentid'];

        $scheduler = scheduler::load_by_coursemodule_id($cmid);
        self::validate_context($scheduler->get_context());
        $permissions = new scheduler_permissions($scheduler->get_context(), $USER->id);

        list($slot, $app) = $scheduler->get_slot_appointment($appointmentid);
        $permissions->ensure($permissions->can_edit_slot($slot));
        $slot->remove_appointment($app);

        // Notify the student.
        if ($scheduler->allownotifications) {
            $student = core_user::get_user($app->studentid, '*', MUST_EXIST);
            $teacher = core_user::get_user($slot->teacherid, '*', MUST_EXIST);
            scheduler_messenger::send_slot_notification($slot, 'bookingnotification', 'teachercancelled',
                $teacher, $student, $teacher, $student, $scheduler->get_courserec());
        }

        $slot->save();

        return true;
    }

    /**
     * External function return structure.
     *
     * @return external_value
     */
    public static function revoke_appointment_returns() {
        return new external_value(PARAM_BOOL);
    }

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function watch_slot_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT),
            'slotid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Watch a slot.
     *
     * @param int $cmid The cmid.
     * @param int $slotid The slot ID..
     * @return true
     */
    public static function watch_slot($cmid, $slotid) {
        global $USER;

        $params = self::validate_parameters(self::watch_slot_parameters(), ['cmid' => $cmid, 'slotid' => $slotid]);
        $cmid = $params['cmid'];
        $slotid = $params['slotid'];

        $scheduler = scheduler::load_by_coursemodule_id($cmid);
        $context = $scheduler->get_context();
        self::validate_context($context);
        $permissions = new scheduler_permissions($context, $USER->id);

        $permissions->ensure($permissions->is_student());
        require_capability('mod/scheduler:watchslots', $context);

        if (!$scheduler->is_watching_enabled()) {
            throw new moodle_exception('error');
        }

        $slot = $scheduler->get_slot($slotid);
        if (!$slot) {
            throw new moodle_exception('error');
        } else if (!$slot->is_watchable_by_student($USER->id)) {
            throw new moodle_exception('nopermissions');
        }

        $watcher = $slot->add_watcher($USER->id);
        if ($watcher) {
            \mod_scheduler\event\slot_watched::create_from_watcher($watcher)->trigger();
        }

        return true;
    }

    /**
     * External function return structure.
     *
     * @return external_value
     */
    public static function watch_slot_returns() {
        return new external_value(PARAM_BOOL);
    }

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public static function unwatch_slot_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT),
            'slotid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Watch a slot.
     *
     * @param int $cmid The cmid.
     * @param int $slotid The slot ID..
     * @return true
     */
    public static function unwatch_slot($cmid, $slotid) {
        global $USER;

        $params = self::validate_parameters(self::unwatch_slot_parameters(), ['cmid' => $cmid, 'slotid' => $slotid]);
        $cmid = $params['cmid'];
        $slotid = $params['slotid'];

        $scheduler = scheduler::load_by_coursemodule_id($cmid);
        $context = $scheduler->get_context();
        self::validate_context($context);
        $permissions = new scheduler_permissions($context, $USER->id);

        $permissions->ensure($permissions->is_student());
        require_capability('mod/scheduler:watchslots', $context);

        $slot = $scheduler->get_slot($slotid);
        if (!$slot) {
            throw new moodle_exception('error');
        }

        $watcher = $slot->remove_watcher($USER->id);
        if ($watcher) {
            \mod_scheduler\event\slot_unwatched::create_from_watcher($watcher)->trigger();
        }

        return true;
    }

    /**
     * External function return structure.
     *
     * @return external_value
     */
    public static function unwatch_slot_returns() {
        return new external_value(PARAM_BOOL);
    }

    /**
     * Serialize an appointment.
     *
     * @param appointment $app The appointment.
     * @param bool $includeteachernote Whether to include the teacher's note.
     * @return array
     */
    public static function serialize_appointment(appointment $app, $includeteachernote = false, $renderer = null) {
        global $PAGE;

        $context = $app->get_scheduler()->get_context();
        $renderer = $renderer ?: $PAGE->get_renderer('mod_scheduler');

        $teachernote = null;
        $teachernoteformat = null;
        $teachernoteformatted = null;

        if ($includeteachernote) {
            $teachernote = $app->teachernote;
            $teachernoteformat = $app->teachernoteformat;
            $teachernoteformatted = external_format_text($app->appointmentnote, $app->appointmentnoteformat, $context->id,
                'mod_scheduler', 'teachernote', $app->id)[0];
        }

        return [
            'id' => $app->id,

            'appointmentnote' => $app->appointmentnote,
            'appointmentnoteformat' => $app->appointmentnoteformat,
            'appointmentnoteformatted' => external_format_text($app->appointmentnote, $app->appointmentnoteformat, $context->id,
                'mod_scheduler', 'appointmentnote', $app->id)[0],
            'hasappointmentnote' => !static::is_empty($app->appointmentnote),

            'gradeformatted' => $renderer->format_grade($app->get_scheduler(), $app->grade),
            'hasgrade' => $app->grade !== null,
            'isattended' => $app->attended,

            'studentnote' => $app->studentnote,
            'studentnoteformat' => $app->studentnoteformat,
            'studentnoteformatted' => external_format_text($app->studentnote, $app->studentnoteformat, $context->id)[0],
            'hasstudentnote' => !static::is_empty($app->studentnote),

            'teachernote' => $teachernote,
            'teachernoteformat' => $teachernoteformat,
            'teachernoteformatted' => $teachernoteformatted,
            'hasteachernote' => !static::is_empty($teachernote),

            'student' => static::serialize_user($app->student)
        ];
    }

    /**
     * Get appointment structure.
     *
     * @return external_value
     */
    protected static function appointment_structure() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT),

            'appointmentnote' => new external_value(PARAM_RAW, 'Notes for the student'),
            'appointmentnoteformat' => new external_value(PARAM_INT),
            'appointmentnoteformatted' => new external_value(PARAM_RAW),
            'hasappointmentnote' => new external_value(PARAM_BOOL),

            'gradeformatted' => new external_value(PARAM_RAW, 'The grade formatted'),
            'hasgrade' => new external_value(PARAM_BOOL),
            'isattended' => new external_value(PARAM_BOOL),

            'studentnote' => new external_value(PARAM_RAW, 'Notes by the student.'),
            'studentnoteformat' => new external_value(PARAM_INT),
            'studentnoteformatted' => new external_value(PARAM_RAW),
            'hasstudentnote' => new external_value(PARAM_BOOL),

            'teachernote' => new external_value(PARAM_RAW, 'Notes for the teacher, hidden to student', VALUE_DEFAULT, null),
            'teachernoteformat' => new external_value(PARAM_INT, '', VALUE_DEFAULT, null),
            'teachernoteformatted' => new external_value(PARAM_RAW, '', VALUE_DEFAULT, null),
            'hasteachernote' => new external_value(PARAM_BOOL),

            'student' => static::user_structure(),
        ]);
    }

    /**
     * Serialize a scheduler.
     *
     * @param scheduler $scheduler The scheduler.
     * @return array
     */
    public static function serialize_scheduler(scheduler $scheduler) {
        $context = $scheduler->get_context();

        $data = (object) [
            'id' => $scheduler->get_id(),
            'courseid' => $scheduler->get_courseid(),
            'cmid' => $scheduler->get_cmid(),
            'name' => external_format_string($scheduler->name, $context),

            'bookinginstructions' => $scheduler->bookinginstructions,
            'bookinginstructionsformat' => $scheduler->bookinginstructionsformat,
            'hasbookinginstructions' => $scheduler->has_bookinginstructions(),

            'intro' => $scheduler->intro,
            'introformat' => $scheduler->introformat,
            'hasintro' => !static::is_empty($scheduler->intro),

            'isinappbookingsupported' => static::is_in_app_booking_supported($scheduler),
            'isindividualbookingenabled' => $scheduler->is_individual_scheduling_enabled(),
            'isgroupbookingenabled' => $scheduler->is_group_scheduling_enabled(),

            'teachername' => $scheduler->get_teacher_name(),
            'usesbookingform' => $scheduler->uses_bookingform(),
            'usesgrades' => $scheduler->uses_grades(),
            'usesstudentnotes' => $scheduler->uses_studentnotes(),
        ];

        list($data->introformatted, $unused) = external_format_text($scheduler->intro, $scheduler->introformat,
            $context->id, 'mod_scheduler', 'intro');

        list($data->bookinginstructionsformatted, $unused) = external_format_text($scheduler->bookinginstructions,
            $scheduler->bookinginstructionsformat, $context->id, 'mod_scheduler', 'bookinginstructions', 0);

        return (array) $data;
    }

    /**
     * Serialize a slot.
     *
     * @param slot $slot The slot.
     * @return array
     */
    public static function serialize_slot(slot $slot) {
        global $USER;

        $context = $slot->get_scheduler()->get_context();
        $nremaining = $slot->count_remaining_appointments();

        $canbookslots = has_capability('mod/scheduler:appoint', $context);
        $canwatchslots = has_capability('mod/scheduler:appoint', $context) && $slot->get_scheduler()->is_watching_enabled();
        $canseeothers = has_capability('mod/scheduler:seeotherstudentsbooking', $context);

        $canbookslot = $canbookslots && $nremaining != 0 && $slot->is_in_bookable_period();
        $canwatchslot = $canwatchslots && $slot->is_watchable_by_student($USER->id);
        $iswatching = $canwatchslot && $slot->is_watched_by_student($USER->id);

        return [
            'id' => $slot->id,

            'starttime' => $slot->starttime,
            'duration' => $slot->duration,
            'timeformatted' => renderer::slotdatetime($slot->starttime, $slot->duration),

            'notes' => $slot->notes,
            'notesformat' => $slot->notesformat,
            'notesformatted' => external_format_text($slot->notes, $slot->notesformat, $context->id,
                'mod_scheduler', 'slotnote', $slot->id)[0],
            'hasnotes' => !static::is_empty($slot->notes),

            'appointments' => array_map(function($app) {
                return static::serialize_appointment($app);
            }, $slot->get_appointments($canseeothers ? [$USER->id] : null)),
            'appointmentlocation' => $slot->appointmentlocation,
            'hasappointmentlocation' => !static::is_empty($slot->appointmentlocation),

            'canbookslot' => $canbookslot,
            'canwatchslot' => $canwatchslot,
            'iswatching' => $iswatching,

            'isunlimited' => $slot->exclusivity == 0,
            'isexclusive' => $slot->exclusivity == 1,
            'isgroupallowed' => !$slot->exclusivity || $slot->exclusivity >= 1,
            'isfull' => $nremaining == 0,

            'nremaining' => $nremaining,
            'ntaken' => $slot->exclusivity > 0 ? $slot->exclusivity - $nremaining : 0,
            'maxappointments' => $slot->exclusivity,

            'teacher' => static::serialize_user($slot->teacher)
        ];
    }

    /**
     * Get slot structure.
     *
     * @return external_value
     */
    protected static function slot_structure() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT),

            'starttime' => new external_value(PARAM_INT),
            'duration' => new external_value(PARAM_INT),
            'timeformatted' => new external_single_structure([
                'date' => new external_value(PARAM_RAW),
                'starttime' => new external_value(PARAM_RAW),
                'shortdatetime' => new external_value(PARAM_RAW),
                'endtime' => new external_value(PARAM_RAW),
            ]),

            'notes' => new external_value(PARAM_RAW),
            'notesformat' => new external_value(PARAM_INT),
            'notesformatted' => new external_value(PARAM_HTML),
            'hasnotes' => new external_value(PARAM_BOOL),

            'appointments' => new extenral_multiple_structure(
                static::appointment_structure()
            ),
            'appointmentlocation' => new external_value(PARAM_RAW),
            'hasappointmentlocation' => new external_value(PARAM_BOOL),

            'canbookslot' => new external_value(PARAM_BOOL),
            'canwatchslot' => new external_value(PARAM_BOOL),
            'iswatching' => new external_value(PARAM_BOOL),
            'isunlimited' => new external_value(PARAM_BOOL),
            'isexclusive' => new external_value(PARAM_BOOL),
            'isgroupallowed' => new external_value(PARAM_BOOL),
            'isfull' => new external_value(PARAM_BOOL),

            'nremaining' => new external_value(PARAM_INT),
            'ntaken' => new external_value(PARAM_INT, 'The number of seats taken when not unlimited.'),
            'maxappointments' => new external_value(PARAM_INT),

            'teacher' => static::user_structure(),
        ]);
    }

    /**
     * Serialize a user.
     *
     * @param object $user The user.
     * @return array
     */
    public static function serialize_user($user) {
        global $PAGE;
        $userpicture = new \user_picture($user);
        $userpicture->size = 1;
        $profileimageurl = $userpicture->get_url($PAGE)->out(false);
        return [
            'id' => $user->id,
            'fullname' => fullname($user),
            'profileimageurl' => $profileimageurl,
        ];
    }

    /**
     * Get the user structure.
     *
     * @return external_value
     */
    protected static function user_structure() {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT),
            'fullname' => new external_value(PARAM_RAW),
            'profileimageurl' => new external_value(PARAM_URL)
        ]);
    }

    /**
     * Check whether a value is empty.
     *
     * @param mixed $value The value.
     * @return bool
     */
    public static function is_empty($value) {
        if (is_string($value)) {
            $value = trim(strip_tags($value));
        }
        return empty($value);
    }

    /**
     * Whether in-app booking is supported.
     *
     * @param scheduler $scheduler The scheduler.
     * @return bool
     */
    public static function is_in_app_booking_supported(scheduler $scheduler) {
        $bookingsupported = true;
        if ($scheduler->uses_bookingform()) {
            if ($scheduler->uses_bookingcaptcha()) {
                $bookingsupported = false;
            } else if ($scheduler->is_studentfiles_required()) {
                $bookingsupported = false;
            }
        }
        return $bookingsupported;
    }
}
