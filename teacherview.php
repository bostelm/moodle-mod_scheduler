<?php

/**
 * Contains various sub-screens that a teacher can see.
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Print a selection box of existing slots to be scheduler in
 *
 * @param scheduler_instance $scheduler
 * @param int $studentid student to schedule
 * @param int $groupid group to schedule
 */
function scheduler_print_schedulebox(scheduler_instance $scheduler, $studentid, $groupid = 0) {
    global $output;

    $availableslots = $scheduler->get_slots_available_to_student($studentid);

    $startdatemem = '';
    $starttimemem = '';
    $availableslotsmenu = array();
    foreach ($availableslots as $slot) {
        $startdatecnv = $output->userdate($slot->starttime);
        $starttimecnv = $output->usertime($slot->starttime);

        $startdatestr = ($startdatemem != '' and $startdatemem == $startdatecnv) ? "-----------------" : $startdatecnv;
        $starttimestr = ($starttimemem != '' and $starttimemem == $starttimecnv) ? '' : $starttimecnv;

        $startdatemem = $startdatecnv;
        $starttimemem = $starttimecnv;

        $url = new moodle_url('/mod/scheduler/view.php',
                        array('id' => $scheduler->cmid, 'slotid' => $slot->id, 'sesskey' => sesskey()));
        if ($groupid) {
            $url->param('what', 'schedulegroup');
            $url->param('subaction', 'dochooseslot');
            $url->param('groupid', $groupid);
        } else {
            $url->param('what', 'schedule');
            $url->param('subaction', 'dochooseslot');
            $url->param('studentid', $studentid);
        }
        $availableslotsmenu[$url->out()] = "$startdatestr $starttimestr";
    }

    $chooser = new url_select($availableslotsmenu);

    if ($availableslots) {
        echo $output->box_start();
        echo $output->heading(get_string('chooseexisting', 'scheduler'), 3);
        echo $output->render($chooser);
        echo $output->box_end();
    }
}

// Load group restrictions.
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = false;
if ($groupmode) {
    $currentgroup = groups_get_activity_group($cm, true);
}

// All group arrays in the following are in the format used by groups_get_all_groups.
// The special value '' (empty string) is used to signal "all groups" (no restrictions).

// Find groups which the current teacher can see ($groupsicansee, $groupsicurrentlysee).
// $groupsicansee contains all groups that a teacher potentially has access to.
// $groupsicurrentlysee may be restricted by the user to one group, using the drop-down box.
$userfilter = $USER->id;
if (has_capability('moodle/site:accessallgroups', $context)) {
    $userfilter = 0;
}
$groupsicansee = '';
$groupsicurrentlysee = '';
if ($groupmode) {
    if ($userfilter) {
        $groupsicansee = groups_get_all_groups($COURSE->id, $userfilter, $cm->groupingid);
    }
    $groupsicurrentlysee = $groupsicansee;
    if ($currentgroup) {
        if ($userfilter && !groups_is_member($currentgroup, $userfilter)) {
            $groupsicurrentlysee = array();
        } else {
            $cgobj = groups_get_group($currentgroup);
            $groupsicurrentlysee = array($currentgroup => $cgobj);
        }
    }
}

// Find groups which the current teacher can schedule as a group ($groupsicanschedule).
$groupsicanschedule = array();
if ($scheduler->is_group_scheduling_enabled()) {
    $groupsicanschedule = groups_get_all_groups($COURSE->id, $userfilter, $scheduler->bookingrouping);
}

// Find groups which can book an appointment with the current teacher ($groupsthatcanseeme).

$groupsthatcanseeme = '';
if ($groupmode) {
    $groupsthatcanseeme = groups_get_all_groups($COURSE->id, $USER->id, $cm->groupingid);
}


$taburl = new moodle_url('/mod/scheduler/view.php', array('id' => $scheduler->cmid, 'what' => 'view', 'subpage' => $subpage));
$PAGE->set_url($taburl);

echo $output->header();

if ($action != 'view') {
    require_once($CFG->dirroot.'/mod/scheduler/slotforms.php');
    include($CFG->dirroot.'/mod/scheduler/teacherview.controller.php');
}

/************************************ View : New single slot form ****************************************/
if ($action == 'addslot') {
    $actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'addslot', 'subpage' => $subpage, 'id' => $cm->id));
    $returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'subpage' => $subpage, 'id' => $cm->id));

    if (!$scheduler->has_available_teachers()) {
        print_error('needteachers', 'scheduler', $returnurl);
    }

    $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $groupsicansee);

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        $slot = $mform->save_slot(0, $formdata);
        \mod_scheduler\event\slot_added::create_from_slot($slot)->trigger();
        echo $output->action_message(get_string('oneslotadded', 'scheduler'));
    } else {
        echo $output->heading(get_string('addsingleslot', 'scheduler'));
        $mform->display();
        echo $output->footer($course);
        die;
    }
}
/************************************ View : Update single slot form ****************************************/
if ($action == 'updateslot') {

    $slotid = required_param('slotid', PARAM_INT);
    $slot = $scheduler->get_slot($slotid);
    if ($slot->starttime % 300 !== 0 || $slot->duration % 5 !== 0) {
        $timeoptions = array('step' => 1, 'optional' => false);
    } else {
        $timeoptions = array('step' => 5, 'optional' => false);
    }

    $actionurl = new moodle_url('/mod/scheduler/view.php',
                    array('what' => 'updateslot', 'id' => $cm->id, 'slotid' => $slotid,
                          'subpage' => $subpage, 'offset' => $offset));
    $returnurl = new moodle_url('/mod/scheduler/view.php',
                    array('what' => 'view', 'id' => $cm->id, 'subpage' => $subpage, 'offset' => $offset));

    $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $groupsicansee, array(
            'slotid' => $slotid,
            'timeoptions' => $timeoptions)
        );
    $data = $mform->prepare_formdata($slot);
    $mform->set_data($data);

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        $mform->save_slot($slotid, $formdata);
        echo $output->action_message(get_string('slotupdated', 'scheduler'));
    } else {
        echo $output->heading(get_string('updatesingleslot', 'scheduler'));
        $mform->display();
        echo $output->footer($course);
        die;
    }

}
/************************************ Add session multiple slots form ****************************************/
if ($action == 'addsession') {

    $actionurl = new moodle_url('/mod/scheduler/view.php',
                    array('what' => 'addsession', 'id' => $cm->id, 'subpage' => $subpage));
    $returnurl = new moodle_url('/mod/scheduler/view.php',
                    array('what' => 'view', 'id' => $cm->id, 'subpage' => $subpage));

    if (!$scheduler->has_available_teachers()) {
        print_error('needteachers', 'scheduler', $returnurl);
    }

    $mform = new scheduler_addsession_form($actionurl, $scheduler, $cm, $groupsicansee);

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        scheduler_action_doaddsession($scheduler, $formdata);
    } else {
        echo $output->heading(get_string('addsession', 'scheduler'));
        $mform->display();
        echo $output->footer();
        die;
    }
}

/************************************ Schedule a student form ***********************************************/
if ($action == 'schedule') {
    if ($subaction == 'dochooseslot') {
        $slotid = required_param('slotid', PARAM_INT);
        $slot = $scheduler->get_slot($slotid);
        $studentid = required_param('studentid', PARAM_INT);

        $actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'updateslot', 'id' => $cm->id, 'slotid' => $slotid));
        $returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $cm->id));


        $repeats = $slot->get_appointment_count() + 1;
        $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $groupsicansee,
                                             array('slotid' => $slotid, 'repeats' => $repeats));
        $data = $mform->prepare_formdata($slot);
        $data->studentid[] = $studentid;
        $mform->set_data($data);

        echo $output->heading(get_string('updatesingleslot', 'scheduler'), 2);
        $mform->display();

    } else if (empty($subaction)) {
        $studentid = required_param('studentid', PARAM_INT);
        $student = $DB->get_record('user', array('id' => $studentid), '*', MUST_EXIST);

        $actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'addslot', 'id' => $cm->id));
        $returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $cm->id));

        $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $groupsicansee);

        $data = array();
        $data['studentid'][0] = $studentid;
        $mform->set_data($data);
        echo $output->heading(get_string('scheduleappointment', 'scheduler', fullname($student)));

        scheduler_print_schedulebox($scheduler, $studentid);

        echo $output->box_start();
        echo $output->heading(get_string('scheduleinnew', 'scheduler'), 3);
        $mform->display();
        echo $output->box_end();
    }

    echo $output->footer();
    die();
}
/************************************ Schedule a whole group in form ***********************************************/
if ($action == 'schedulegroup') {

    $groupid = required_param('groupid', PARAM_INT);
    $group = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);
    $members = groups_get_members($groupid);

    if ($subaction == 'dochooseslot') {

        $slotid = required_param('slotid', PARAM_INT);
        $groupid = required_param('groupid', PARAM_INT);
        $slot = $scheduler->get_slot($slotid);

        $actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'updateslot', 'id' => $cm->id, 'slotid' => $slotid));
        $returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $cm->id));

        $repeats = $slot->get_appointment_count() + count($members);
        $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $groupsicansee,
                                             array('slotid' => $slotid, 'repeats' => $repeats));
        $data = $mform->prepare_formdata($slot);
        foreach ($members as $member) {
            $data->studentid[] = $member->id;
        }
        $mform->set_data($data);

        echo $output->heading(get_string('updatesingleslot', 'scheduler'), 3);
        $mform->display();

    } else if (empty($subaction)) {

        $actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'addslot', 'id' => $cm->id));
        $returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $cm->id));

        $data = array();
        $i = 0;
        foreach ($members as $member) {
            $data['studentid'][$i] = $member->id;
            $i++;
        }
        $data['exclusivity'] = $i;

        $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $groupsicansee, array('repeats' => $i));
        $mform->set_data($data);

        echo $output->heading(get_string('scheduleappointment', 'scheduler', $group->name));

        scheduler_print_schedulebox($scheduler, 0, $groupid);

        echo $output->box_start();
        echo $output->heading(get_string('scheduleinnew', 'scheduler'), 3);
        $mform->display();
        echo $output->box_end();

    }
    echo $output->footer();
    die();
}

/************************************ Send message to students ****************************************/
if ($action == 'sendmessage') {
    require_once($CFG->dirroot.'/mod/scheduler/message_form.php');

    $template = optional_param('template', 'none', PARAM_ALPHA);
    $recipientids = required_param('recipients', PARAM_SEQUENCE);

    $actionurl = new moodle_url('/mod/scheduler/view.php',
            array('what' => 'sendmessage', 'id' => $cm->id, 'subpage' => $subpage,
                  'template' => $template, 'recipients' => $recipientids));
    $returnurl = new moodle_url('/mod/scheduler/view.php',
            array('what' => 'view', 'id' => $cm->id, 'subpage' => $subpage));

    $templatedata = array();
    if ($template != 'none') {
        $vars = scheduler_messenger::get_scheduler_variables($scheduler, null, $USER, null, $COURSE, null);
        $templatedata['subject'] = scheduler_messenger::compile_mail_template($template, 'subject', $vars);
        $templatedata['body'] = scheduler_messenger::compile_mail_template($template, 'html', $vars);
    }
    $templatedata['recipients'] = $DB->get_records_list('user', 'id', explode(',', $recipientids), 'lastname,firstname');

    $mform = new scheduler_message_form($actionurl, $scheduler, $templatedata);

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        scheduler_action_dosendmessage($scheduler, $formdata);
    } else {
        echo $output->heading(get_string('sendmessage', 'scheduler'));
        $mform->display();
        echo $output->footer();
        die;
    }
}


/****************** Standard view ***********************************************/


// Trigger view event.
\mod_scheduler\event\appointment_list_viewed::create_from_scheduler($scheduler)->trigger();


// Print top tabs.

$actionurl = new moodle_url($taburl, array('offset' => $offset, 'sesskey' => sesskey()));

$inactive = array();
if ($DB->count_records('scheduler_slots', array('schedulerid' => $scheduler->id)) <=
         $DB->count_records('scheduler_slots', array('schedulerid' => $scheduler->id, 'teacherid' => $USER->id)) ) {
    // We are alone in this scheduler.
    $inactive[] = 'allappointments';
    if ($subpage = 'allappointments') {
        $subpage = 'myappointments';
    }
}

echo $output->teacherview_tabs($scheduler, $taburl, $subpage, $inactive);
if ($groupmode) {
    if ($subpage == 'allappointments') {
        groups_print_activity_menu($cm, $taburl);
    } else {
        $a = new stdClass();
        $a->groupmode = get_string($groupmode == VISIBLEGROUPS ? 'groupsvisible' : 'groupsseparate');
        $groupnames = array();
        foreach ($groupsthatcanseeme as $id => $group) {
            $groupnames[] = $group->name;
        }
        $a->grouplist = implode(', ', $groupnames);
        $messagekey = $groupsthatcanseeme ? 'groupmodeyourgroups' : 'groupmodeyourgroupsempty';
        $message = get_string($messagekey, 'scheduler', $a);
        echo html_writer::div($message, 'groupmodeyourgroups');
    }
}

// Print intro.
echo $output->mod_intro($scheduler);


if ($subpage == 'allappointments') {
    $teacherid = 0;
} else {
    $teacherid = $USER->id;
    $subpage = 'myappointments';
}
$sqlcount = $scheduler->count_slots_for_teacher($teacherid, $currentgroup);

$pagesize = 25;
if ($offset == -1) {
    if ($sqlcount > $pagesize) {
        $offsetcount = $scheduler->count_slots_for_teacher($teacherid, $currentgroup, true);
        $offset = floor($offsetcount / $pagesize);
    } else {
        $offset = 0;
    }
}
if ($offset * $pagesize >= $sqlcount && $sqlcount > 0) {
    $offset = floor(($sqlcount-1) / $pagesize);
}

$slots = $scheduler->get_slots_for_teacher($teacherid, $currentgroup, $offset * $pagesize, $pagesize);

echo $output->heading(get_string('slots', 'scheduler'));

// Print instructions and button for creating slots.
$key = ($slots) ? 'addslot' : 'welcomenewteacher';
echo html_writer::div(get_string($key, 'scheduler'));


$commandbar = new scheduler_command_bar();
$commandbar->title = get_string('actions', 'scheduler');

$addbuttons = array();
$addbuttons[] = $commandbar->action_link(new moodle_url($actionurl, array('what' => 'addsession')), 'addsession', 't/add');
$addbuttons[] = $commandbar->action_link(new moodle_url($actionurl, array('what' => 'addslot')), 'addsingleslot', 't/add');
$commandbar->add_group(get_string('addcommands', 'scheduler'), $addbuttons);

// If slots already exist, also show delete buttons.
if ($slots) {
    $delbuttons = array();

    $delselectedurl = new moodle_url($actionurl, array('what' => 'deleteslots'));
    $PAGE->requires->yui_module('moodle-mod_scheduler-delselected', 'M.mod_scheduler.delselected.init',
                                array($delselectedurl->out(false)) );
    $delselected = $commandbar->action_link($delselectedurl, 'deleteselection', 't/delete',
                                            'confirmdelete-selected', 'delselected');
    $delselected->formid = 'delselected';
    $delbuttons[] = $delselected;

    if (has_capability('mod/scheduler:manageallappointments', $context) && $subpage == 'allappointments') {
        $delbuttons[] = $commandbar->action_link(
                        new moodle_url($actionurl, array('what' => 'deleteall')),
                        'deleteallslots', 't/delete', 'confirmdelete-all');
        $delbuttons[] = $commandbar->action_link(
                        new moodle_url($actionurl, array('what' => 'deleteallunused')),
                        'deleteallunusedslots', 't/delete', 'confirmdelete-unused');
    }
    $delbuttons[] = $commandbar->action_link(
                    new moodle_url($actionurl, array('what' => 'deleteunused')),
                    'deleteunusedslots', 't/delete', 'confirmdelete-myunused');
    $delbuttons[] = $commandbar->action_link(
                    new moodle_url($actionurl, array('what' => 'deleteonlymine')),
                    'deletemyslots', 't/delete', 'confirmdelete-mine');

    $commandbar->add_group(get_string('deletecommands', 'scheduler'), $delbuttons);
}

echo $output->render($commandbar);


// Some slots already exist - prepare the table of slots.
if ($slots) {

    $slotman = new scheduler_slot_manager($scheduler, $actionurl);
    $slotman->showteacher = ($subpage == 'allappointments');

    foreach ($slots as $slot) {

        $editable = ($USER->id == $slot->teacherid || has_capability('mod/scheduler:manageallappointments', $context));

        $studlist = new scheduler_student_list($slotman->scheduler);
        $studlist->expandable = false;
        $studlist->expanded = true;
        $studlist->editable = $editable;
        $studlist->linkappointment = true;
        $studlist->checkboxname = 'seen[]';
        $studlist->buttontext = get_string('saveseen', 'scheduler');
        $studlist->actionurl = new moodle_url($actionurl, array('what' => 'saveseen', 'slotid' => $slot->id));
        foreach ($slot->get_appointments() as $app) {
            $studlist->add_student($app, false, $app->is_attended(), true, $scheduler->uses_studentdata());
        }

        $slotman->add_slot($slot, $studlist, $editable);
    }

    echo $output->render($slotman);

    if ($sqlcount > $pagesize) {
        echo $output->paging_bar($sqlcount, $offset, $pagesize, $actionurl, 'offset');
    }

    // Instruction for teacher to click Seen box after appointment.
    echo html_writer::div(get_string('markseen', 'scheduler'));

}

$groupfilter = ($subpage == 'myappointments') ? $groupsthatcanseeme : $groupsicurrentlysee;
$maxlistsize = get_config('mod_scheduler', 'maxstudentlistsize');
$students = array();
$reminderstudents = array();
if ($groupfilter === '') {
    $students = $scheduler->get_students_for_scheduling('', $maxlistsize);
    if ($scheduler->allows_unlimited_bookings()) {
        $reminderstudents  = $scheduler->get_students_for_scheduling('', $maxlistsize, true);
    } else {
        $reminderstudents = $students;
    }
} else if (count($groupfilter) > 0) {
    $students = $scheduler->get_students_for_scheduling(array_keys($groupfilter), $maxlistsize);
    if ($scheduler->allows_unlimited_bookings()) {
        $reminderstudents = $scheduler->get_students_for_scheduling(array_keys($groupfilter), $maxlistsize, true);
    } else {
        $reminderstudents = $students;
    }
}

if ($students === 0) {
    $nostudentstr = get_string('noexistingstudents', 'scheduler');
    if ($COURSE->id == SITEID) {
        $nostudentstr .= '<br/>'.get_string('howtoaddstudents', 'scheduler');
    }
    echo $output->notification($nostudentstr, 'notifyproblem');
} else if (is_integer($students)) {
    // There are too many students who still have to make appointments, don't display a list.
    $toomanystr = get_string('missingstudentsmany', 'scheduler', $students);
    echo $output->notification($toomanystr, 'notifymessage');

} else if (count($students) > 0) {

    if (count($reminderstudents) > 0) {
        $studids = implode(',', array_keys($reminderstudents));

        $messageurl = new moodle_url($actionurl, array('what' => 'sendmessage', 'recipients' => $studids));
        $invitationurl = new moodle_url($messageurl, array('template' => 'invite'));
        $reminderurl = new moodle_url($messageurl, array('template' => 'invitereminder'));

        $maildisplay = '';
        $maildisplay .= html_writer::link($invitationurl, get_string('sendinvitation', 'scheduler'));
        $maildisplay .= ' &mdash; ';
        $maildisplay .= html_writer::link($reminderurl, get_string('sendreminder', 'scheduler'));

        echo $output->box_start('maildisplay');
        // Print number of students who still have to make an appointment.
        echo $output->heading(get_string('missingstudents', 'scheduler', count($reminderstudents)), 3);
        // Print e-mail addresses and mailto links.
        echo $maildisplay;
        echo $output->box_end();
    }

    $userfields = scheduler_get_user_fields(null, $context);
    $fieldtitles = array();
    foreach ($userfields as $f) {
        $fieldtitles[] = $f->title;
    }
    $studtable = new scheduler_scheduling_list($scheduler, $fieldtitles);
    $studtable->id = 'studentstoschedule';

    foreach ($students as $student) {
        $picture = $output->user_picture($student);
        $name = $output->user_profile_link($scheduler, $student);
        $actions = array();
        $actions[] = new action_menu_link_secondary(
                        new moodle_url($actionurl, array('what' => 'schedule', 'studentid' => $student->id)),
                        new pix_icon('e/insert_date', '', 'moodle'),
                        get_string('scheduleinslot', 'scheduler') );
        $actions[] = new action_menu_link_secondary(
                        new moodle_url($actionurl, array('what' => 'markasseennow', 'studentid' => $student->id)),
                        new pix_icon('t/approve', '', 'moodle'),
                        get_string('markasseennow', 'scheduler') );

        $userfields = scheduler_get_user_fields($student, $context);
        $fieldvals = array();
        foreach ($userfields as $f) {
            $fieldvals[] = $f->value;
        }
        $studtable->add_line($picture, $name, $fieldvals, $actions);
    }

    $divclass = 'schedulelist '.($scheduler->is_group_scheduling_enabled() ? 'halfsize' : 'fullsize');
    echo html_writer::start_div($divclass);
    echo $output->heading(get_string('schedulestudents', 'scheduler'), 3);

    // Print table of students who still have to make appointments.
    echo $output->render($studtable);
    echo html_writer::end_div();

    if ($scheduler->is_group_scheduling_enabled()) {

        // Print list of groups that can be scheduled.

        echo html_writer::start_div('schedulelist halfsize');
        echo $output->heading(get_string('schedulegroups', 'scheduler'), 3);

        if (empty($groupsicanschedule)) {
            echo $output->notification(get_string('nogroups', 'scheduler'));
        } else {
            $grouptable = new scheduler_scheduling_list($scheduler, array());
            $grouptable->id = 'groupstoschedule';

            $groupcnt = 0;
            foreach ($groupsicanschedule as $group) {
                $members = groups_get_members($group->id, user_picture::fields('u'), 'u.lastname, u.firstname');
                if (empty($members)) {
                    continue;
                }
                if (!$scheduler->has_slots_booked_for_group($group->id, false, $scheduler->schedulermode == 'onetime')) {

                    $picture = print_group_picture($group, $course->id, false, true, true);
                    $name = $group->name;
                    $groupmembers = array();
                    foreach ($members as $member) {
                        $groupmembers[] = fullname($member);
                    }
                    $name .= ' ['. implode(', ', $groupmembers) . ']';
                    $actions = array();
                    $actions[] = new action_menu_link_secondary(
                                    new moodle_url($actionurl, array('what' => 'schedulegroup', 'groupid' => $group->id)),
                                    new pix_icon('e/insert_date', '', 'moodle'),
                                    get_string('scheduleinslot', 'scheduler') );

                    $grouptable->add_line($picture, $name, array(), $actions);
                    $groupcnt++;
                }
            }
            // Print table of groups that still need to make appointments.
            if ($groupcnt > 0) {
                echo $output->render($grouptable);
            } else {
                echo $output->notification(get_string('nogroups', 'scheduler'));
            }
        }
        echo html_writer::end_div();
    }

} else {
    echo $output->notification(get_string('noexistingstudents', 'scheduler'));
}
echo $output->footer();