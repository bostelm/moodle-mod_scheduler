<?php

/**
 * Controller for student view
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2015 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/scheduler/mailtemplatelib.php');

$returnurl = new moodle_url('/mod/scheduler/view.php', array('id' => $cm->id));

/************************************************ Book a slot  ************************************************/

if ($action == 'bookslot') {

    require_sesskey();

    // Get the request parameters.
    $slotid = required_param('slotid', PARAM_INT);
    $slot = $scheduler->get_slot($slotid);
    if (!$slot) {
        throw new moodle_exception('error');
    }

    if (!$slot->is_in_bookable_period()) {
        throw new moodle_exception('nopermissions');
    }

    $requiredcapacity = 1;
    $userstobook = array($USER->id);
    if ($appointgroup) {
        $groupmembers = $scheduler->get_possible_attendees(array($appointgroup));
        $requiredcapacity = count($groupmembers);
        $userstobook = array_keys($groupmembers);
    }

    $errormessage = '';

    $bookinglimit = $scheduler->count_bookable_appointments($USER->id, false);
    if ($bookinglimit == 0) {
        $errormessage = get_string('selectedtoomany', 'scheduler', $bookinglimit);
    }
    if (!$errormessage) {
        // Validate our user ids.
        $existingstudents = array();
        foreach ($slot->get_appointments() as $app) {
            $existingstudents[] = $app->studentid;
        }
        $userstobook = array_diff($userstobook, $existingstudents);

        $remaining = $slot->count_remaining_appointments();
        // If the slot is already overcrowded...
        if ($remaining >= 0 && $remaining < $requiredcapacity) {
            if ($requiredcapacity > 1) {
                $errormessage = get_string('notenoughplaces', 'scheduler');
            } else {
                $errormessage = get_string('slot_is_just_in_use', 'scheduler');
            }
        }
    }

    if ($errormessage) {
        echo $output->header();
        echo $output->box($errormessage, 'error');
        echo $output->continue_button($returnurl);
        echo $output->footer();
        exit();
    }

    // Create new appointment and add it for each member of the group.
    foreach ($userstobook as $studentid) {
        $appointment = $slot->create_appointment();
        $appointment->studentid = $studentid;
        $appointment->attended = 0;
        $appointment->timecreated = time();
        $appointment->timemodified = time();

        \mod_scheduler\event\booking_added::create_from_slot($slot)->trigger();

        // Notify the teacher.
        if ($scheduler->allownotifications) {
            $student = $DB->get_record('user', array('id' => $appointment->studentid));
            $teacher = $DB->get_record('user', array('id' => $slot->teacherid));
            $vars = scheduler_get_mail_variables($scheduler, $slot, $teacher, $student, $course, $teacher);
            scheduler_send_email_from_template($teacher, $student, $course, 'newappointment', 'applied', $vars, 'scheduler');
        }
    }
    $slot->save();
    redirect($returnurl);
}


/******************************** Cancel a booking (for the current student or a group) ******************************/

if ($action == 'cancelbooking') {

    require_sesskey();

    // Get the request parameters.
    $slotid = required_param('slotid', PARAM_INT);
    $slot = $scheduler->get_slot($slotid);
    if (!$slot) {
        throw new moodle_exception('error');
    }

    if (!$slot->is_in_bookable_period()) {
        throw new moodle_exception('nopermissions');
    }

    require_capability('mod/scheduler:appoint', $context);

    $userstocancel = array($USER->id);
    if ($appointgroup) {
        $userstocancel = array_keys($scheduler->get_possible_attendees(array($appointgroup)));
    }

    foreach ($userstocancel as $userid) {
        if ($appointment = $slot->get_student_appointment($userid)) {
            $scheduler->delete_appointment($appointment->id);

            // Notify the teacher.
            if ($scheduler->allownotifications) {
                $student = $DB->get_record('user', array('id' => $USER->id));
                $teacher = $DB->get_record('user', array('id' => $slot->teacherid));
                $vars = scheduler_get_mail_variables($scheduler, $slot, $teacher, $student, $course, $teacher);
                scheduler_send_email_from_template($teacher, $student, $COURSE,
                                                   'cancelledbystudent', 'cancelled', $vars, 'scheduler');
            }
            \mod_scheduler\event\booking_removed::create_from_slot($slot)->trigger();
        }
    }
    redirect($returnurl);

}
