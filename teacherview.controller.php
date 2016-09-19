<?php

/**
 * Controller for all teacher-related views.
 *
 * @package    mod_scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


function scheduler_action_doaddsession($scheduler, $formdata) {

    global $DB, $output;

    $data = (object) $formdata;

    $fordays = 0;
    if ($data->rangeend > 0) {
        $fordays = ($data->rangeend - $data->rangestart) / DAYSECS;
    }

    // Create as many slots of $duration as will fit between $starttime and $endtime and that do not conflict.
    $countslots = 0;
    $couldnotcreateslots = '';
    $startfrom = $data->rangestart + ($data->starthour * 60 + $data->startminute) * 60;
    $endat = $data->rangestart + ($data->endhour * 60 + $data->endminute) * 60;
    $slot = new stdClass();
    $slot->schedulerid = $scheduler->id;
    $slot->teacherid = $data->teacherid;
    $slot->appointmentlocation = $data->appointmentlocation;
    $slot->exclusivity = $data->exclusivityenable ? $data->exclusivity : 0;
    if ($data->divide) {
        $slot->duration = $data->duration;
    } else {
        $slot->duration = $data->endhour * 60 + $data->endminute - $data->starthour * 60 - $data->startminute;
    };
    $slot->notes = '';
    $slot->notesformat = FORMAT_HTML;
    $slot->timemodified = time();

    for ($d = 0; $d <= $fordays; $d ++) {
        $starttime = $startfrom + ($d * DAYSECS);
        $eventdate = usergetdate($starttime);
        $dayofweek = $eventdate['wday'];
        if ((($dayofweek == 1) && ($data->monday == 1)) ||
        (($dayofweek == 2) && ($data->tuesday == 1)) ||
        (($dayofweek == 3) && ($data->wednesday == 1)) ||
        (($dayofweek == 4) && ($data->thursday == 1)) ||
        (($dayofweek == 5) && ($data->friday == 1)) ||
        (($dayofweek == 6) && ($data->saturday == 1)) ||
        (($dayofweek == 0) && ($data->sunday == 1))) {
            $slot->starttime = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'],
                                              $data->starthour, $data->startminute);
            $data->timestart = $slot->starttime;
            $data->timeend = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'],
                                            $data->endhour, $data->endminute);

            // This corrects around midnight bug.
            if ($data->timestart > $data->timeend) {
                $data->timeend += DAYSECS;
            }
            if ($data->hideuntilrel == 0) {
                $slot->hideuntil = time();
            } else {
                $slot->hideuntil = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'], 6, 0) -
                                    $data->hideuntilrel;
            }
            if ($data->emaildaterel == -1) {
                $slot->emaildate = 0;
            } else {
                $slot->emaildate = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'], 0, 0) -
                                    $data->emaildaterel;
            }
            while ($slot->starttime <= $data->timeend - $slot->duration * 60) {
                $conflicts = $scheduler->get_conflicts($data->timestart, $data->timestart + $slot->duration * 60,
                                                       $data->teacherid, 0, SCHEDULER_ALL);
                $resolvable = (boolean) $data->forcewhenoverlap;
                foreach ($conflicts as $conflict) {
                    $resolvable = $resolvable
                                     && $conflict->isself == 1       // Do not delete slots outside the current scheduler.
                                     && $conflict->numstudents == 0; // Do not delete slots with bookings.
                }

                if ($conflicts) {
                    $cl = new scheduler_conflict_list();
                    $cl->add_conflicts($conflicts);
                    if (!$resolvable) {
                        print_string('conflictingslots', 'scheduler', userdate($data->timestart));
                        echo $output->doc_link('mod/scheduler/conflict', '', true);
                        echo $output->render($cl);
                    } else { // We force, so delete all conflicting before inserting.
                        foreach ($conflicts as $conflict) {
                            $cslot = $scheduler->get_slot($conflict->id);
                            \mod_scheduler\event\slot_deleted::create_from_slot($cslot, 'addsession-conflict')->trigger();
                            $cslot->delete();
                        }
                        print_string('deletedconflictingslots', 'scheduler', userdate($data->timestart));
                        echo $output->doc_link('mod/scheduler/conflict', '', true);
                        echo $output->render($cl);
                    }
                }
                if (!$conflicts || $resolvable) {
                    $slotid = $DB->insert_record('scheduler_slots', $slot, true, true);
                    $slotobj = $scheduler->get_slot($slotid);
                    \mod_scheduler\event\slot_added::create_from_slot($slotobj)->trigger();
                    $countslots++;
                }
                $slot->starttime += ($slot->duration + $data->break) * 60;
                $data->timestart += ($slot->duration + $data->break) * 60;
            }
        }
    }
    echo $output->action_message(get_string('slotsadded', 'scheduler', $countslots));
}

function scheduler_action_dosendmessage($scheduler, $formdata) {

    global $DB, $USER, $output;

    $data = (object) $formdata;

    $recipients = $data->recipient;
    if ($data->copytomyself) {
        $recipients[$USER->id] = 1;
    }
    $rawmessage = $data->body['text'];
    $format = $data->body['format'];
    $textmessage = format_text_email($rawmessage, $format);
    $htmlmessage = null;
    if ($format == FORMAT_HTML) {
        $htmlmessage = $rawmessage;
    }

    $cnt = 0;
    foreach ($recipients as $recipientid => $value) {
        if ($value) {
            $message = new \core\message\message();
            $message->component = 'mod_scheduler';
            $message->name = 'invitation';
            $message->userfrom = $USER;
            $message->userto = $recipientid;
            $message->subject = $data->subject;
            $message->fullmessage = $textmessage;
            $message->fullmessageformat = $format;
            if ($htmlmessage) {
                $message->fullmessagehtml = $htmlmessage;
            }
            $message->notification = '0';

            message_send($message);
            $cnt++;
        }
    }

    echo $output->action_message(get_string('messagesent', 'scheduler', $cnt));
}

function scheduler_delete_slots_from_ui(array $slots, $action) {
    global $output;

    $cnt = 0;
    foreach ($slots as $slot) {
        \mod_scheduler\event\slot_deleted::create_from_slot($slot, $action)->trigger();
        $slot->delete();
        $cnt++;
    }

    if ($cnt == 1) {
        $msg = get_string('oneslotdeleted', 'scheduler');
    } else {
        $msg = get_string('slotsdeleted', 'scheduler', $cnt);
    }
    echo $output->action_message($msg);
}

// Require valid session key for all actions.
require_sesskey();

// We first have to check whether some action needs to be performed.
switch ($action) {
    /************************************ Deleting a slot ***********************************************/
    case 'deleteslot': {
        $slotid = required_param('slotid', PARAM_INT);
        $slot = $scheduler->get_slot($slotid);
        scheduler_delete_slots_from_ui(array($slot), $action);
        break;
    }
    /************************************ Deleting multiple slots ***********************************************/
    case 'deleteslots': {
        $slotids = required_param('items', PARAM_SEQUENCE);
        $slotids = explode(",", $slotids);
        $slots = array();
        foreach ($slotids as $slotid) {
            $slots[] = $scheduler->get_slot($slotid);
        }
        scheduler_delete_slots_from_ui($slots, $action);
        break;
    }
    /************************************ Students were seen ***************************************************/
    case 'saveseen': {
        $slotid = required_param('slotid', PARAM_INT);
        $slot = $scheduler->get_slot($slotid);
        $seen = optional_param_array('seen', array(), PARAM_INT);

        if (is_array($seen)) {
            foreach ($slot->get_appointments() as $app) {
                $app->attended = (in_array($app->id, $seen)) ? 1 : 0;
                $app->timemodified = time();
            }
        }
        $slot->save();
        break;
    }
    /************************************ Revoking all appointments to a slot ***************************************/
    case 'revokeall': {
        $slotid = required_param('slotid', PARAM_INT);
        $slot = $scheduler->get_slot($slotid);

        $oldstudents = array();
        foreach ($slot->get_appointments() as $app) {
            $oldstudents[] = $app->studentid;
            $slot->remove_appointment($app);
        }
        // Notify the student.
        if ($scheduler->allownotifications) {
            foreach ($oldstudents as $oldstudent) {
                include_once($CFG->dirroot.'/mod/scheduler/mailtemplatelib.php');

                $student = $DB->get_record('user', array('id' => $oldstudent));
                $teacher = $DB->get_record('user', array('id' => $slot->teacherid));

                scheduler_messenger::send_slot_notification($slot, 'bookingnotification', 'teachercancelled',
                                        $teacher, $student, $teacher, $student, $COURSE);
            }
        }

        $slot->save();
        break;
    }

    /************************************ Toggling to unlimited group ***************************************/
    case 'allowgroup':{
        $slotid = required_param('slotid', PARAM_INT);
        $slot = new stdClass();
        $slot->id = $slotid;
        $slot->exclusivity = 0;
        $DB->update_record('scheduler_slots', $slot);
        break;
    }

    /************************************ Toggling to single student ******************************************/
    case 'forbidgroup':{
        $slotid = required_param('slotid', PARAM_INT);
        $slot = new stdClass();
        $slot->id = $slotid;
        $slot->exclusivity = 1;
        $DB->update_record('scheduler_slots', $slot);
        break;
    }

    /************************************ Deleting all slots ***************************************************/
    case 'deleteall':{
        require_capability('mod/scheduler:manageallappointments', $context);
        $slots = $scheduler->get_all_slots();
        scheduler_delete_slots_from_ui($slots, $action);
        break;
    }
    /************************************ Deleting unused slots *************************************************/
    case 'deleteunused':{
        $slots = $scheduler->get_slots_without_appointment($USER->id);
        scheduler_delete_slots_from_ui($slots, $action);
        break;
    }
    /************************************ Deleting unused slots (all teachers) ************************************/
    case 'deleteallunused': {
        require_capability('mod/scheduler:manageallappointments', $context);
        $slots = $scheduler->get_slots_without_appointment();
        scheduler_delete_slots_from_ui($slots, $action);
        break;
    }
    /************************************ Deleting current teacher's slots ***************************************/
    case 'deleteonlymine': {
        $slots = $scheduler->get_slots_for_teacher($USER->id);
        scheduler_delete_slots_from_ui($slots, $action);
        break;
    }
    /************************************ Mark as seen now *******************************************************/
    case 'markasseennow': {
        $slot = new stdClass();
        $slot->schedulerid = $scheduler->id;
        $slot->teacherid = $USER->id;
        $slot->starttime = time();
        $slot->duration = $scheduler->defaultslotduration;
        $slot->exclusivity = 1;
        $slot->notes = '';
        $slot->notesformat = FORMAT_HTML;
        $slot->hideuntil = time();
        $slot->appointmentlocation = '';
        $slot->emaildate = 0;
        $slot->timemodified = time();
        $slotid = $DB->insert_record('scheduler_slots', $slot);

        $appointment = new stdClass();
        $appointment->slotid = $slotid;
        $appointment->studentid = required_param('studentid', PARAM_INT);
        $appointment->attended = 1;
        $appointment->appointmentnote = '';
        $appointment->appointmentnoteformat = FORMAT_HTML;
        $appointment->teachernote = '';
        $appointment->teachernoteformat = FORMAT_HTML;
        $appointment->timecreated = time();
        $appointment->timemodified = time();
        $DB->insert_record('scheduler_appointment', $appointment);

        $slot = $scheduler->get_slot($slotid);
        \mod_scheduler\event\slot_added::create_from_slot($slot)->trigger();

        break;
    }
}

/*************************************************************************************************************/
