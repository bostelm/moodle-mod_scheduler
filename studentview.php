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

$appointgroup = optional_param('appointgroup', 0, PARAM_INT);

\mod_scheduler\event\booking_form_viewed::create_from_scheduler($scheduler)->trigger();

// Clean all late slots (for everybody).
$scheduler->free_late_unused_slots();

$PAGE->set_docs_path('mod/scheduler/studentview');

$urlparas = array(
        'id' => $scheduler->cmid,
        'sesskey' => sesskey()
);
if ($appointgroup) {
    $urlparas['appointgroup'] = $appointgroup;
}
$actionurl = new moodle_url('/mod/scheduler/view.php', $urlparas);


// General permissions check.
require_capability('mod/scheduler:appoint', $context);

if ($scheduler->is_group_scheduling_enabled()) {
    $mygroupsforscheduling = groups_get_all_groups($scheduler->courseid, $USER->id, $scheduler->bookingrouping, 'g.id, g.name');
    if ($appointgroup && !array_key_exists($appointgroup, $mygroupsforscheduling)) {
        throw new moodle_exception('nopermissions');
    }
}

include_once($CFG->dirroot.'/mod/scheduler/studentview.controller.php');

echo $output->header();

// Print intro.
echo $output->mod_intro($scheduler);


$showowngrades = $scheduler->uses_grades();
// Print total grade (if any).
if ($showowngrades) {
    $totalgrade = $scheduler->get_user_grade($USER->id);
    $gradebookinfo = $scheduler->get_gradebook_info($USER->id);

    $showowngrades = !$gradebookinfo->hidden;

    if ($gradebookinfo && !$gradebookinfo->hidden && ($totalgrade || $gradebookinfo->overridden) ) {
        $grademsg = '';
        if ($gradebookinfo->overridden) {
            $grademsg = html_writer::tag('p',
                            get_string('overriddennotice', 'grades'),  array('class' => 'overriddennotice')
                        );
        } else {
            $grademsg = get_string('yourtotalgrade', 'scheduler', $output->format_grade($scheduler, $totalgrade));
        }
        echo html_writer::div($grademsg, 'totalgrade');
    }
}

// Print group selection menu if given.
if ($scheduler->is_group_scheduling_enabled()) {
    $groupchoice = array();
    foreach ($mygroupsforscheduling as $group) {
        $groupchoice[$group->id] = $group->name;
    }
    $select = $output->single_select($actionurl, 'appointgroup', $groupchoice, $appointgroup,
                                     array(0 => get_string('myself', 'scheduler')),
                                     'appointgroupform');
    echo html_writer::div(get_string('appointforgroup', 'scheduler', $select), 'dropdownmenu');
}

// Get past (attended) slots.

$pastslots = $scheduler->get_attended_slots_for_student($USER->id);

if (count($pastslots) > 0) {
    $slottable = new scheduler_slot_table($scheduler, $showowngrades || $scheduler->is_group_scheduling_enabled());
    foreach ($pastslots as $pastslot) {
        $appointment = $pastslot->get_student_appointment($USER->id);

        if ($pastslot->is_groupslot() && has_capability('mod/scheduler:seeotherstudentsresults', $context)) {
            $others = new scheduler_student_list($scheduler, true);
            foreach ($pastslot->get_appointments() as $otherapp) {
                $gradehidden = ($scheduler->get_gradebook_info($otherapp->studentid)->hidden <> 0);
                $others->add_student($otherapp, $otherapp->studentid == $USER->id, false, !$gradehidden);
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
    $slottable = new scheduler_slot_table($scheduler, $showowngrades || $scheduler->is_group_scheduling_enabled(), $actionurl);
    foreach ($upcomingslots as $slot) {
        $appointment = $slot->get_student_appointment($USER->id);

        if ($slot->is_groupslot() && has_capability('mod/scheduler:seeotherstudentsbooking', $context)) {
            $showothergrades = has_capability('mod/scheduler:seeotherstudentsresults', $context);
            $others = new scheduler_student_list($scheduler);
            foreach ($slot->get_appointments() as $otherapp) {
                $gradehidden = !$scheduler->uses_grades() ||
                               ($scheduler->get_gradebook_info($otherapp->studentid)->hidden <> 0) ||
                               (!$showothergrades && $otherapp->studentid <> $USER->id);
                $others->add_student($otherapp, $otherapp->studentid == $USER->id, false, !$gradehidden);
            }
        } else {
            $others = null;
        }

        $slottable->add_slot($slot, $appointment, $others, $slot->is_in_bookable_period());
    }

    echo $output->heading(get_string('upcomingslots', 'scheduler'), 3);
    echo $output->render($slottable);
}

$bookablecnt = $scheduler->count_bookable_appointments($USER->id, false);
$bookableslots = array_values($scheduler->get_slots_available_to_student($USER->id, false));

if ($bookablecnt == 0) {
    echo html_writer::div(get_string('canbooknofurtherappointments', 'scheduler'), 'studentbookingmessage');

} else if (count($bookableslots) == 0) {

    // No slots are available at this time.
    $noslots = get_string('noslotsavailable', 'scheduler');
    echo html_writer::div($noslots, 'studentbookingmessage');

} else {
    // The student can book further appointments, and slots are available.
    // Show the booking form.

    $booker = new scheduler_slot_booker($scheduler, $USER->id, $actionurl, $bookablecnt);

    $pagesize = 25;
    $total = count($bookableslots);
    $start = ($offset >= 0) ? $offset * $pagesize : 0;
    $end = $start + $pagesize;
    if ($end > $total) {
        $end = $total;
    }

    for ($idx = $start; $idx < $end; $idx++) {
        $slot = $bookableslots[$idx];

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

        $booker->add_slot($slot, true, false, $groupinfo, $others);
    }


    $msgkey = $scheduler->has_slots_for_student($USER->id, true, false) ? 'welcomebackstudent' : 'welcomenewstudent';
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

    echo $output->heading(get_string('availableslots', 'scheduler'), 3);
    echo html_writer::div($bookingmsg1, 'studentbookingmessage');
    echo html_writer::div($bookingmsg2, 'studentbookingmessage');
    if ($total > $pagesize) {
        echo $output->paging_bar($total, $offset, $pagesize, $actionurl, 'offset');
    }
    echo $output->render($booker);
    if ($total > $pagesize) {
        echo $output->paging_bar($total, $offset, $pagesize, $actionurl, 'offset');
    }

}

echo $output->footer();