<?php

/**
 * Student scheduler screen (where students choose appointments).
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/scheduler/studentview.controller.php');

\mod_scheduler\event\booking_form_viewed::create_from_scheduler($scheduler)->trigger();

// Clean all late slots (for everybody).
$scheduler->free_late_unused_slots();

$mygroups = groups_get_all_groups($scheduler->courseid, $USER->id, $cm->groupingid, 'g.id, g.name');

// Print intro.
echo $output->mod_intro($scheduler);

// Get past (attended) slots.

$pastslots = $scheduler->get_attended_slots_for_student($USER->id);

if (count($pastslots) > 0) {
    $slottable = new scheduler_slot_table($scheduler, $scheduler->uses_grades());
    foreach ($pastslots as $pastslot) {
        $appointment = $pastslot->get_student_appointment($USER->id);

        if ($pastslot->is_groupslot() && has_capability('mod/scheduler:seeotherstudentsresults', $context)) {
            $others = new scheduler_student_list($scheduler, $scheduler->uses_grades());
            foreach ($pastslot->get_appointments() as $otherapp) {
                $others->add_student($otherapp, $otherapp->studentid == $USER->id);
            }
        } else {
            $others = null;
        }

        $slottable->add_slot($pastslot, $appointment, $others);
    }

    echo $output->heading(get_string('attendedslots', 'scheduler'), 3);
    echo $output->render($slottable);
}

$upcomingslots = $scheduler->get_upcoming_slots_for_student($USER->id);

if (count($upcomingslots) > 0) {
    $slottable = new scheduler_slot_table($scheduler, $scheduler->uses_grades());
    foreach ($upcomingslots as $slot) {
        $appointment = $slot->get_student_appointment($USER->id);

        if ($slot->is_groupslot() && has_capability('mod/scheduler:seeotherstudentsresults', $context)) {
            $others = new scheduler_student_list($scheduler, $scheduler->uses_grades());
            foreach ($slot->get_appointments() as $otherapp) {
                $others->add_student($otherapp, $otherapp->studentid == $USER->id);
            }
        } else {
            $others = null;
        }

        $slottable->add_slot($slot, $appointment, $others);
    }

    echo $output->heading(get_string('upcomingslots', 'scheduler'), 3);
    echo $output->render($slottable);
}

$bookablecnt = $scheduler->count_bookable_appointments($USER->id, true);
$bookableslots = $scheduler->get_slots_available_to_student($USER->id, true);

if ($bookablecnt == 0) {
    echo html_writer::div(get_string('canbooknofurtherappointments', 'scheduler'), 'studentbookingmessage');

} else if (count($bookableslots) == 0) {

    // No slots are available at this time.
    $noslots = get_string('noslotsavailable', 'scheduler');
    echo html_writer::div($noslots, 'studentbookingmessage');

} else {
    // The student can book further appointments, and slots are available.
    // Show the booking form.

    $actionurl = new moodle_url('/mod/scheduler/view.php', array (
        'what' => 'savechoice',
        'id' => $scheduler->cmid,
        'sesskey' => sesskey()
    ));
    $style = ($scheduler->maxbookings == 1) || ($scheduler->is_group_scheduling_enabled()) ? 'one' : 'multi';
    $booker = new scheduler_slot_booker($scheduler, $USER->id, $actionurl, $style, $bookablecnt);
    $bookedany = false;
    foreach ($bookableslots as $slot) {
        $booked = !is_null($slot->get_student_appointment($USER->id));

        if ($slot->is_groupslot() && has_capability('mod/scheduler:seeotherstudentsbooking', $context)) {
            $others = new scheduler_student_list($scheduler, false);
            foreach ($slot->get_appointments() as $otherapp) {
                $others->add_student($otherapp, $otherapp->studentid == $USER->id);
            }
            $others->expandable = true;
            $others->expanded = false;
        } else {
            $others = null;
        }

        // Check what to print as group information...
        if ($slot->exclusivity == 0) {
            $groupinfo = get_string('yes');
        } else if ($slot->exclusivity == 1) {
            $groupinfo = get_string('no');
        } else {
            $remaining = $slot->count_remaining_appointments();
            if ($remaining > 0) {
                $groupinfo = get_string('limited', 'scheduler', $remaining.'/'.$slot->exclusivity);
            } else { // Group info should not be visible to students.
                $groupinfo = get_string('complete', 'scheduler');
            }
        }

        $booker->add_slot($slot, !$booked, $booked, $groupinfo, $others);
        $bookedany = $bookedany || $booked;
    }
    $booker->candisengage = $bookedany && has_capability('mod/scheduler:disengage', $context);

    if ($scheduler->is_group_scheduling_enabled()) {
        $booker->groupchoice = array();
        foreach ($mygroups as $group) {
            $booker->groupchoice[$group->id] = $group->name;
        }
    }


    $msgkey = scheduler_has_slot($USER->id, $scheduler, true) ? 'welcomebackstudent' : 'welcomenewstudent';
    $bookingmsg1 = get_string($msgkey, 'scheduler');

    $a = $bookablecnt;
    if ($bookablecnt == 1) {
        $msgkey = ($scheduler->schedulermode == 'oneonly') ? 'canbooksingleappointment' : 'canbook1appointment';
    } else if ($bookablecnt > 1) {
        $msgkey = 'canbooknappointments';
    } else {
        $msgkey = 'canbookunlimitedappointments';
    }
    $bookingmsg2 = get_string($msgkey, 'scheduler', $a);

    echo $output->heading(get_string('slots', 'scheduler'), 3);
    echo html_writer::div($bookingmsg1, 'studentbookingmessage');
    echo html_writer::div($bookingmsg2, 'studentbookingmessage');
    echo $output->render($booker);
}

