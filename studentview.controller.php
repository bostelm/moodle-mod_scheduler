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
 * Controller for student view
 *
 * @package    mod_scheduler
 * @copyright  2015 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/scheduler/mailtemplatelib.php');

/**
 * scheduler_book_slot
 *
 * @param scheduler_instance $scheduler
 * @param int $slotid
 * @param int $userid
 * @param int $groupid
 * @param scheduler_booking_form $mform
 * @param mixed $formdata
 * @param mixed $returnurl
 * @throws mixed moodle_exception
 */
function scheduler_book_slot($scheduler, $slotid, $userid, $groupid, $mform, $formdata, $returnurl) {
    try {
        mod_scheduler_book_slot($scheduler, $slotid, $userid, $groupid, $formdata);
    } catch (moodle_exception $e) {
        \core\notification::error($e->getMessage());
    }
    redirect($returnurl);
}

$returnurlparas = array('id' => $cm->id);
if ($scheduler->is_group_scheduling_enabled()) {
    $returnurlparas['appointgroup'] = $appointgroup;
}
$returnurl = new moodle_url('/mod/scheduler/view.php', $returnurlparas);


/******************************************** Show the booking form *******************************************/

if ($action == 'bookingform') {
    require_once($CFG->dirroot.'/mod/scheduler/bookingform.php');

    require_sesskey();
    require_capability('mod/scheduler:appoint', $context);

    $slotid = required_param('slotid', PARAM_INT);
    $slot = $scheduler->get_slot($slotid);

    $actionurl = new moodle_url($returnurl, array('what' => 'bookingform', 'slotid' => $slotid));

    $mform = new scheduler_booking_form($slot, $actionurl);

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if (($formdata = $mform->get_data()) || $appointgroup < 0) {
        // Workaround - call scheduler_book_slot also if no group was selected, to show an error message.
        scheduler_book_slot($scheduler, $slotid, $USER->id, $appointgroup, $mform, $formdata, $returnurl);
        redirect($returnurl);
    } else {
        $groupinfo = null;
        if ($scheduler->is_group_scheduling_enabled() && $appointgroup == 0) {
            $groupinfo = get_string('myself', 'scheduler');
        } else if ($appointgroup > 0) {
            $groupinfo = $mygroupsforscheduling[$appointgroup]->name;
        }

        echo $output->header();
        echo $output->heading(get_string('bookaslot', 'scheduler'));

        $info = scheduler_appointment_info::make_from_slot($slot, true, true, $groupinfo);
        echo $output->render($info);
        $mform->display();
        echo $output->footer();
        exit();
    }

}

/************************************************ Book a slot  ************************************************/

if ($action == 'bookslot') {

    require_sesskey();
    require_capability('mod/scheduler:appoint', $context);

    // Reject this request if the user is required to go through a booking form.
    if ($scheduler->uses_bookingform()) {
        throw new moodle_exception('error');
    }

    // Get the request parameters.
    $slotid = required_param('slotid', PARAM_INT);

    scheduler_book_slot($scheduler, $slotid, $USER->id, $appointgroup, null, null, $returnurl);
}

/************************************************ Watching slots ************************************************/

if ($action == 'watchslot') {
    require_sesskey();
    require_capability('mod/scheduler:watchslots', $context);

    if (!$scheduler->is_watching_enabled()) {
        throw new moodle_exception('error');
    }

    $slotid = required_param('slotid', PARAM_INT);
    $slot = $scheduler->get_slot($slotid);
    if (!$slot) {
        throw new moodle_exception('error');
    } else if (!$slot->is_watchable_by_student($USER->id)) {
        throw new moodle_exception('nopermissions');
    }

    $watcher = $slot->add_watcher($USER->id);
    \mod_scheduler\event\slot_watched::create_from_watcher($watcher)->trigger();
    redirect($returnurl);
}

if ($action == 'unwatchslot') {
    require_sesskey();
    require_capability('mod/scheduler:watchslots', $context);
    $slotid = required_param('slotid', PARAM_INT);

    $slot = $scheduler->get_slot($slotid);
    if (!$slot) {
        throw new moodle_exception('error');
    }

    $watcher = $slot->remove_watcher($USER->id);
    if ($watcher) {
        \mod_scheduler\event\slot_unwatched::create_from_watcher($watcher)->trigger();
    }
    redirect($returnurl);
}

/******************************************** Show details of booking *******************************************/

if ($action == 'viewbooking') {
    require_once($CFG->dirroot.'/mod/scheduler/bookingform.php');

    require_sesskey();
    require_capability('mod/scheduler:appoint', $context);

    $appointmentid = required_param('appointmentid', PARAM_INT);
    list($slot, $appointment) = $scheduler->get_slot_appointment($appointmentid);

    if ($appointment->studentid != $USER->id) {
        throw new moodle_exception('nopermissions');
    }

    echo $output->header();
    echo $output->heading(get_string('bookingdetails', 'scheduler'));
    $info = scheduler_appointment_info::make_from_appointment($slot, $appointment);
    echo $output->render($info);

    echo $output->continue_button($returnurl);
    echo $output->footer();
    exit();

}

/******************************************** Edit a booking *******************************************/

if ($action == 'editbooking') {
    require_once($CFG->dirroot.'/mod/scheduler/bookingform.php');

    require_sesskey();
    require_capability('mod/scheduler:appoint', $context);

    if (!$scheduler->uses_studentdata()) {
        throw new moodle_exception('error');
    }

    $appointmentid = required_param('appointmentid', PARAM_INT);
    list($slot, $appointment) = $scheduler->get_slot_appointment($appointmentid);

    if ($appointment->studentid != $USER->id) {
        throw new moodle_exception('nopermissions');
    }
    if (!$slot->is_in_bookable_period()) {
        throw new moodle_exception('nopermissions');
    }

    $actionurl = new moodle_url($returnurl, array('what' => 'editbooking', 'appointmentid' => $appointmentid));

    $mform = new scheduler_booking_form($slot, $actionurl, true);
    $mform->set_data($mform->prepare_booking_data($appointment));

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        mod_scheduler_save_booking_data($appointment, $formdata);
        redirect($returnurl);
    } else {
        echo $output->header();
        echo $output->heading(get_string('editbooking', 'scheduler'));
        echo $output->box(format_text($scheduler->intro, $scheduler->introformat));
        $info = scheduler_appointment_info::make_from_slot($slot);
        echo $output->render($info);
        $mform->display();
        echo $output->footer();
        exit();
    }

}


/******************************** Cancel a booking (for the current student or a group) ******************************/

if ($action == 'cancelbooking') {

    require_sesskey();
    require_capability('mod/scheduler:appoint', $context);

    // Get the request parameters.
    $slotid = required_param('slotid', PARAM_INT);
    $slot = $scheduler->get_slot($slotid);
    if (!$slot) {
        throw new moodle_exception('error');
    }

    if (!$slot->is_in_bookable_period()) {
        throw new moodle_exception('nopermissions');
    }

    $userstocancel = array($USER->id);
    if ($appointgroup) {
        $userstocancel = array_keys($scheduler->get_available_students($appointgroup));
    }

    foreach ($userstocancel as $userid) {
        if ($appointment = $slot->get_student_appointment($userid)) {
            $scheduler->delete_appointment($appointment->id);

            // Notify the teacher.
            if ($scheduler->allownotifications) {
                $student = $DB->get_record('user', array('id' => $USER->id));
                $teacher = $DB->get_record('user', array('id' => $slot->teacherid));
                scheduler_messenger::send_slot_notification($slot, 'bookingnotification', 'cancelled',
                                                            $student, $teacher, $teacher, $student, $COURSE);
            }
            \mod_scheduler\event\booking_removed::create_from_slot($slot)->trigger();
        }
    }
    redirect($returnurl);

}
