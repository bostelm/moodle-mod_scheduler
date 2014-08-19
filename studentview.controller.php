<?php

/**
 * Controller for student view
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/scheduler/mailtemplatelib.php');


/************************************************ Saving choice ************************************************/
if ($action == 'savechoice') {
    require_sesskey();
    require_capability( 'mod/scheduler:appoint', $context);

    // Get the request parameters.
    $slotids = array();
    $slotidsraw = optional_param_array('slotcheck', '', PARAM_INT);
    if (empty($slotidsraw)) {
        $slotid = optional_param('slotid', -1, PARAM_INT);
        if ($slotid >= 0) {
            $slotids[] = $slotid;
        }
    } else {
        foreach ($slotidsraw as $k => $v) {
            if (!empty($v)) {
                $slotids[] = (int) $v;
            }
        }
    }

    $appointgroup = optional_param('appointgroup', 0, PARAM_INT);

    $requiredcapacity = 1;
    if ($appointgroup) {
        $groupmembers = groups_get_members($appointgroup);
        $requiredcapacity = count($groupmembers);
    }

    $errormessage = '';

    $bookinglimit = $scheduler->count_bookable_appointments($USER->id);
    if ($bookinglimit >= 0 && count($slotids) > $bookinglimit) {
        $errormessage = get_string('selectedtoomany', 'scheduler', $bookinglimit);
    }

    if (!$errormessage) {
        // Validate our slot ids.
        $slotidsvalidated = array();
        $slotidstoadd = array();
        foreach ($slotids as $index => $slotid) {
            $slot = $scheduler->get_slot($slotid);

            $available = $slot->get_appointments();
            $consumed = $slot->get_appointment_count();

            $usersforslot = scheduler_get_appointed($slotid);
            $alreadysignedup = (isset($usersforslot[$USER->id]));

            if (!$alreadysignedup) {
                $remaining = $slot->count_remaining_appointments();
                // If the slot is already overcrowded...
                if ($remaining >= 0 && $remaining < $requiredcapacity) {
                    if ($updating = $DB->count_records('scheduler_appointment', array('slotid' => $slot->id, 'studentid' => $USER->id))) {
                        $errormessage = get_string('alreadyappointed', 'scheduler');
                    } else if ($requiredcapacity > 1) {
                        $errormessage = get_string('notenoughplaces', 'scheduler');
                    } else {
                        $errormessage = get_string('slot_is_just_in_use', 'scheduler');
                    }
                    break;
                }
                $slotidstoadd[$index] = $slotid;
            }
            $slotidsvalidated[$index] = $slotid;
        }
    }

    if ($errormessage) {
        echo $output->box($errormessage, 'error');
        $returnurl = new moodle_url('/mod/scheduler/view.php', array('id' => $cm->id));
        echo $output->continue_button($returnurl);
        echo $output->footer($course);
        exit();
    }

    // If we are scheduling a full group we must discard all pending appointments of other participants of the scheduled group.
    // Just add the list of other students for searching slots to delete.
    $oldslotowners = array();
    if ($appointgroup) {
        $oldslotownersarray = $groupmembers;
        foreach ($oldslotownersarray as $oldslotownermember) {
            if (has_capability('mod/scheduler:appoint', $context, $oldslotownermember->id)) {
                $oldslotowners[] = $oldslotownermember->id;
            }
        }
    } else {
        // Single user appointment: Get current user.
        $oldslotowners[] = $USER->id;
    }
    $oldslotownerlist = implode("','", $oldslotowners);

    if ($oldslotowners) {
        // Cleans up old slots if not attended and within rebookable time limits
        $sql = 'SELECT a.id as appointmentid, s.* '.
                        'FROM {scheduler_slots} AS s, {scheduler_appointment} AS a '.
                        'WHERE s.id = a.slotid AND s.schedulerid = :schedulerid '.
                        "AND a.studentid IN ('$oldslotownerlist') ".
                        'AND a.attended = 0 '.
                        'AND s.starttime > :cutoff';
        $paras = array('schedulerid' => $scheduler->id, 'cutoff' => time() + $scheduler->guardtime);
        if ($slotidsvalidated) {
             list($extrasql, $extraparas) = $DB->get_in_or_equal($slotidsvalidated, SQL_PARAMS_NAMED, 'param', false);
             $sql .= ' AND a.slotid '.$extrasql;
             $paras = array_merge($paras, $extraparas);
        }
        if ($oldappointments = $DB->get_records_sql($sql, $paras)) {

            foreach ($oldappointments as $oldappointment) {

                $oldappid  = $oldappointment->appointmentid;
                $oldslotid = $oldappointment->id;
                $oldslot = scheduler_slot::load_by_id($oldslotid, $scheduler);

                // Prepare notification e-mail first
                if ($scheduler->allownotifications) {
                    $student = $DB->get_record('user', array('id' => $USER->id));
                    $teacher = $DB->get_record('user', array('id' => $oldslot->teacherid));
                    $vars = scheduler_get_mail_variables($scheduler, $oldslot, $teacher, $student, $course, $teacher);
                }

                \mod_scheduler\event\booking_removed::create_from_slot($oldslot)->trigger();

                // Delete the appointment (and possibly the slot).
                $scheduler->delete_appointment($oldappid);

                // Notify the teacher.
                if ($scheduler->allownotifications) {
                    scheduler_send_email_from_template($teacher, $student, $course, 'cancelledbystudent', 'cancelled', $vars, 'scheduler');
                }
            }
        }
    }

    foreach ($slotidstoadd as $slotid) {
        $newslot = $scheduler->get_slot($slotid);

        // Create new appointment and add it for each member of the group.
        foreach ($oldslotowners as $astudentid) {
            $appointment = $newslot->create_appointment();
            $appointment->studentid = $astudentid;
            $appointment->attended = 0;
            $appointment->timecreated = time();
            $appointment->timemodified = time();
            scheduler_update_grades($scheduler, $astudentid);

            \mod_scheduler\event\booking_added::create_from_slot($newslot)->trigger();

            // Notify the teacher.
            if ($scheduler->allownotifications) {
                $student = $DB->get_record('user', array('id' => $appointment->studentid));
                $teacher = $DB->get_record('user', array('id' => $slot->teacherid));
                $vars = scheduler_get_mail_variables($scheduler, $newslot, $teacher, $student, $course, $teacher);
                scheduler_send_email_from_template($teacher, $student, $course, 'newappointment', 'applied', $vars, 'scheduler');
            }
        }
        $newslot->save();
    }
}

// *********************************** Disengage from the slot (only the current student) ******************************/
if ($action == 'disengage') {
    require_sesskey();
    require_capability('mod/scheduler:disengage', $context);
    $where = 'studentid = :studentid AND attended = 0 AND ' .
        'EXISTS(SELECT 1 FROM {scheduler_slots} sl WHERE sl.id = slotid AND sl.schedulerid = :scheduler AND sl.starttime > :cutoff)';
    $params = array('scheduler' => $scheduler->id, 'studentid' => $USER->id, 'cutoff' => time() + $scheduler->guardtime);
    $appointments = $DB->get_records_select('scheduler_appointment', $where, $params);
    if ($appointments) {
        foreach ($appointments as $appointment) {

            $oldslot = $scheduler->get_slot($appointment->slotid);

            \mod_scheduler\event\booking_removed::create_from_slot($oldslot)->trigger();

            $scheduler->delete_appointment($appointment->id);

            // Notify the teacher.
            if ($scheduler->allownotifications) {
                $student = $DB->get_record('user', array('id' => $USER->id));
                $teacher = $DB->get_record('user', array('id' => $oldslot->teacherid));
                $vars = scheduler_get_mail_variables($scheduler, $oldslot, $teacher, $student, $course, $teacher);
                scheduler_send_email_from_template($teacher, $student, $COURSE, 'cancelledbystudent', 'cancelled', $vars, 'scheduler');
            }
        }
    }
}
