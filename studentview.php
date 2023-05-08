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
 * Student scheduler screen (where students choose appointments).
 *
 * @package    mod_scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$appointgroup = optional_param('appointgroup', -1, PARAM_INT);

\mod_scheduler\event\booking_form_viewed::create_from_scheduler($scheduler)->trigger();

$PAGE->set_docs_path('mod/scheduler/studentview');

$urlparas = array(
        'id' => $scheduler->cmid,
        'sesskey' => sesskey()
);
if ($appointgroup >= 0) {
    $urlparas['appointgroup'] = $appointgroup;
}
$actionurl = new moodle_url('/mod/scheduler/view.php', $urlparas);


// General permissions check.
require_capability('mod/scheduler:viewslots', $context);
$canbook = has_capability('mod/scheduler:appoint', $context);
$canseefull = has_capability('mod/scheduler:viewfullslots', $context);
$canwatch = has_capability('mod/scheduler:watchslots', $context);

if ($scheduler->is_group_scheduling_enabled()) {
    $mygroupsforscheduling = groups_get_all_groups($scheduler->courseid, $USER->id, $scheduler->bookingrouping, 'g.id, g.name');
    if ($appointgroup > 0 && !array_key_exists($appointgroup, $mygroupsforscheduling)) {
        throw new moodle_exception('nopermissions');
    }
}

if ($scheduler->is_group_scheduling_enabled()) {
    $canbook = $canbook && ($appointgroup >= 0);
} else {
    $appointgroup = 0;
}

if (!$scheduler->is_watching_enabled()) {
    $canwatch = false;
}

require_once($CFG->dirroot.'/mod/scheduler/studentview.controller.php');

echo $output->header();


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
    if ($scheduler->is_individual_scheduling_enabled()) {
        $groupchoice[0] = get_string('myself', 'scheduler');
    }
    foreach ($mygroupsforscheduling as $group) {
        $groupchoice[$group->id] = $group->name;
    }
    $select = $output->single_select($actionurl, 'appointgroup', $groupchoice, $appointgroup,
                                     array(-1 => 'choosedots'), 'appointgroupform');
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
                $othermark = $scheduler->get_gradebook_info($otherapp->studentid);
                $gradehidden = !is_null($othermark) && ($othermark->hidden <> 0);
                $others->add_student($otherapp, $otherapp->studentid == $USER->id, false, !$gradehidden);
            }
        } else {
            $others = null;
        }
        $hasdetails = $scheduler->uses_studentdata();
        $slottable->add_slot($pastslot, $appointment, $others, false, false, $hasdetails);
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

        $cancancel = $slot->is_in_bookable_period();
        $canedit = $cancancel && $scheduler->uses_studentdata();
        $canview = !$cancancel && $scheduler->uses_studentdata();
        if ($scheduler->is_group_scheduling_enabled()) {
            $cancancel = $cancancel && ($appointgroup >= 0);
        }
        $slottable->add_slot($slot, $appointment, $others, $cancancel, $canedit, $canview);
    }

    echo $output->heading(get_string('upcomingslots', 'scheduler'), 3);
    echo $output->render($slottable);
}

$bookablecnt = $scheduler->count_bookable_appointments($USER->id, false);
$canbookslots = $canbook && $bookablecnt != 0;
$canwatchslots = $canwatch && $canbookslots && !$appointgroup;
$bookableslots = array_values($scheduler->get_slots_available_to_student($USER->id, $canseefull || $canwatchslots));

if (!$canseefull && $bookablecnt == 0) {
    echo html_writer::div(get_string('canbooknofurtherappointments', 'scheduler'), 'studentbookingmessage');

} else if (count($bookableslots) == 0) {

    // No slots are available at this time.
    $noslots = get_string('noslotsavailable', 'scheduler');
    echo html_writer::div($noslots, 'studentbookingmessage');

} else {
    // The student can book (or see) further appointments, and slots are available.
    // Show the booking form.

    $booker = new scheduler_slot_booker($scheduler, $USER->id, $actionurl, $bookablecnt);
    $haswatchableslots = false;

    $pagesize = 25;
    $total = count($bookableslots);
    $start = ($offset >= 0) ? $offset * $pagesize : 0;
    $end = $start + $pagesize;
    if ($end > $total) {
        $end = $total;
    }

    for ($idx = $start; $idx < $end; $idx++) {
        $slot = $bookableslots[$idx];
        $canbookthisslot = $canbookslots;

        if (has_capability('mod/scheduler:seeotherstudentsbooking', $context)) {
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
        $remaining = $slot->count_remaining_appointments();
        if ($slot->exclusivity == 0) {
            $groupinfo = get_string('yes');
        } else if ($slot->exclusivity == 1 && $remaining == 1) {
            $groupinfo = get_string('no');
        } else {
            if ($remaining > 0) {
                $groupinfo = get_string('limited', 'scheduler', $remaining.'/'.$slot->exclusivity);
            } else { // Group info should not be visible to students.
                $groupinfo = get_string('complete', 'scheduler');
                $canbookthisslot = false;
            }
        }

        $canwatchthisslot = $canwatchslots && $slot->is_watchable_by_student($USER->id);
        $iswatching = $canwatchthisslot && $slot->is_watched_by_student($USER->id);
        $haswatchableslots = $haswatchableslots || $canwatchthisslot;
        $booker->add_slot($slot, $canbookthisslot, false, $groupinfo, $others, $canwatchthisslot, $iswatching);
    }


    $msgkey = $scheduler->has_slots_for_student($USER->id, true, false) ? 'welcomebackstudent' : 'welcomenewstudent';
    $bookingmsg1 = get_string($msgkey, 'scheduler');

    $a = $bookablecnt;
    if ($bookablecnt == 0) {
        $msgkey = 'canbooknofurtherappointments';
    } else if ($bookablecnt == 1) {
        $msgkey = ($scheduler->schedulermode == 'oneonly') ? 'canbooksingleappointment' : 'canbook1appointment';
    } else if ($bookablecnt > 1) {
        $msgkey = 'canbooknappointments';
    } else {
        $msgkey = 'canbookunlimitedappointments';
    }
    $bookingmsg2 = get_string($msgkey, 'scheduler', $a);

    echo $output->heading(get_string('availableslots', 'scheduler'), 3);
    if ($canbook) {
        echo html_writer::div($bookingmsg1, 'studentbookingmessage');
        echo html_writer::div($bookingmsg2, 'studentbookingmessage');
    }
    if ($total > $pagesize) {
        echo $output->paging_bar($total, $offset, $pagesize, $actionurl, 'offset');
    }
    echo $output->render($booker);
    if ($total > $pagesize) {
        echo $output->paging_bar($total, $offset, $pagesize, $actionurl, 'offset');
    }

    if ($canwatchslots) {
        echo html_writer::tag('p', get_string('watchslotsintro', 'mod_scheduler'));
    }

}

echo $output->footer();
