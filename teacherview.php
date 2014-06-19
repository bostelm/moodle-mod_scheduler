<?php

/**
 * Contains various sub-screens that a teacher can see.
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function scheduler_prepare_formdata(scheduler_slot $slot) {

    $data = $slot->get_data();
    $data->notes = array();
    $data->notes['text'] = $slot->notes;
    $data->notes['format'] = $slot->notesformat;
    if ($slot->emaildate < 0) {
        $data->emaildate = 0;
    }

    $i = 0;
    foreach ($slot->get_appointments() as $appointment) {
        $data->studentid[$i] = $appointment->studentid;
        $data->attended[$i] = $appointment->attended;
        $data->appointmentnote[$i]['text'] = $appointment->appointmentnote;
        $data->appointmentnote[$i]['format'] = $appointment->appointmentnoteformat;
        $data->grade[$i] = $appointment->grade;
        $i++;
    }
    return $data;
}

function scheduler_save_slotform(scheduler_instance $scheduler, $course, $slotid, $data) {

    global $DB;

    if ($slotid) {
        $slot = scheduler_slot::load_by_id($slotid, $scheduler);
    } else {
        $slot = new scheduler_slot($scheduler);
    }

    // Set data fields from input form.
    $slot->starttime = $data->starttime;
    $slot->duration = $data->duration;
    $slot->exclusivity = $data->exclusivity;
    $slot->teacherid = $data->teacherid;
    $slot->notes = $data->notes['text'];
    $slot->notesformat = $data->notes['format'];
    $slot->appointmentlocation = $data->appointmentlocation;
    $slot->hideuntil = $data->hideuntil;
    $slot->emaildate = $data->emaildate;
    $slot->timemodified = time();

    $currentapps = $slot->get_appointments();
    $processedstuds = array();
    for ($i = 0; $i < $data->appointment_repeats; $i++) {
        if ($data->studentid[$i] > 0) {
            $app = null;
            foreach ($currentapps as $currentapp) {
                if ($currentapp->studentid == $data->studentid[$i]) {
                    $app = $currentapp;
                    $processedstuds[] = $currentapp->studentid;
                }
            }
            if ($app == null) {
                $app = $slot->create_appointment();
                $app->studentid = $data->studentid[$i];
            }
            $app->attended = isset($data->attended[$i]);
            $app->appointmentnote = $data->appointmentnote[$i]['text'];
            $app->appointmentnoteformat = $data->appointmentnote[$i]['format'];
            if (isset($data->grade)) {
                $selgrade = $data->grade[$i];
                $app->grade = ($selgrade >= 0) ? $selgrade : null;
            }
        }
    }
    foreach ($currentapps as $currentapp) {
        if (!in_array($currentapp->studentid, $processedstuds)) {
            $slot->remove_appointment($currentapp);
        }
    }

    $slot->save();
}


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

// load group restrictions
$modinfo = get_fast_modinfo($course);

$usergroups = '';
if ($cm->groupmode > 0) {
    $groups = groups_get_all_groups($COURSE->id, 0, $cm->groupingid);
    $usergroups = array_keys($groups);
}

if ($action != 'view') {
    include_once($CFG->dirroot.'/mod/scheduler/slotforms.php');
    include($CFG->dirroot.'/mod/scheduler/teacherview.controller.php');
}


/************************************ View : New single slot form ****************************************/
if ($action == 'addslot') {
    $actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'addslot', 'id' => $cm->id));
    $returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $cm->id));

    if (!scheduler_has_teachers($context)) {
        print_error('needteachers', 'scheduler', $returnurl);
    }

    $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $usergroups);

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        scheduler_save_slotform ($scheduler, $course, 0, $formdata);
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
    $data = scheduler_prepare_formdata($slot);

    $actionurl = new moodle_url('/mod/scheduler/view.php',
                    array('what' => 'updateslot', 'id' => $cm->id, 'slotid' => $slotid, 'subpage' => $subpage, 'offset' => $offset));
    $returnurl = new moodle_url('/mod/scheduler/view.php',
                    array('what' => 'view', 'id' => $cm->id, 'subpage' => $subpage, 'offset' => $offset));

    $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $usergroups, array('slotid' => $slotid));
    $mform->set_data($data);

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        scheduler_save_slotform ($scheduler, $course, $slotid, $formdata);
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

    if (!scheduler_has_teachers($context)) {
        print_error('needteachers', 'scheduler', $returnurl);
    }

    $mform = new scheduler_addsession_form($actionurl, $scheduler, $cm, $usergroups);

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        scheduler_action_doaddsession($scheduler, $formdata);
    } else {
        echo $output->heading(get_string('addsession', 'scheduler'));
        $mform->display();
        echo $output->footer($course);
        die;
    }
}

/************************************ Schedule a student form ***********************************************/
if ($action == 'schedule') {
    if ($subaction == 'dochooseslot') {
        $slotid = required_param('slotid', PARAM_INT);
        $studentid = required_param('studentid', PARAM_INT);

        $actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'updateslot', 'id' => $cm->id, 'slotid' => $slotid));
        $returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $cm->id));

        $data = scheduler_prepare_formdata($scheduler->get_slot($slotid));
        $i = 0;
        while (isset($data->studentid[$i])) {
            $i++;
        }
        $data->studentid[$i] = $studentid;
        $i++;

        $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $usergroups, array('slotid' => $slotid, 'repeats' => $i));
        $mform->set_data($data);

        echo $output->heading(get_string('updatesingleslot', 'scheduler'), 2);
        $mform->display();

    } else if (empty($subaction)) {
        $studentid = required_param('studentid', PARAM_INT);
        $student = $DB->get_record('user', array('id' => $studentid), '*', MUST_EXIST);

        $actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'addslot', 'id' => $cm->id));
        $returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $cm->id));

        $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $usergroups);

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

    // return code for include
    return -1;
}
/************************************ Schedule a whole group in form ***********************************************/
if ($action == 'schedulegroup') {

    $groupid = required_param('groupid', PARAM_INT);
    $group = $DB->get_record('groups', array('id' => $groupid), '*', MUST_EXIST);
    $members = groups_get_members($groupid);

    if ($subaction == 'dochooseslot') {

        $slotid = required_param('slotid', PARAM_INT);
        $groupid = required_param('groupid', PARAM_INT);

        $actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'updateslot', 'id' => $cm->id, 'slotid' => $slotid));
        $returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $cm->id));

        $data = scheduler_prepare_formdata($scheduler->get_slot($slotid));
        $i = 0;
        while (isset($data->studentid[$i])) {
            $i++;
        }
        foreach ($members as $member) {
            $data->studentid[$i] = $member->id;
            $i++;
        }

        $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $usergroups,
                        array('slotid' => $slotid, 'repeats' => $i));
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

        $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $usergroups, array('repeats' => $i));
        $mform->set_data($data);

        echo $output->heading(get_string('scheduleappointment', 'scheduler', $group->name));

        scheduler_print_schedulebox($scheduler, 0, $groupid);

        echo $output->box_start();
        echo $output->heading(get_string('scheduleinnew', 'scheduler'), 3);
        $mform->display();
        echo $output->box_end();

    }
    // return code for include
    return -1;
}
//****************** Standard view ***********************************************//

// Clean all late slots (for everybody).
$scheduler->free_late_unused_slots();

// Trigger view event.
\mod_scheduler\event\appointment_list_viewed::create_from_scheduler($scheduler)->trigger();


// Print top tabs.

$taburl = new moodle_url('/mod/scheduler/view.php', array('id' => $scheduler->cmid, 'what' => 'view'));
$actionurl = new moodle_url($taburl, array('offset' => $offset, 'sesskey' => sesskey()));

$inactive = array();
if ($DB->count_records('scheduler_slots', array('schedulerid' => $scheduler->id)) <= $DB->count_records('scheduler_slots', array('schedulerid' => $scheduler->id, 'teacherid' => $USER->id))) {
    // We are alone in this scheduler.
    $inactive[] = 'allappointments';
    if ($subpage = 'allappointments') {
        $subpage = 'myappointments';
    }
}

echo $output->teacherview_tabs($scheduler, $taburl, $subpage, $inactive);

// Print intro.
echo $output->mod_intro($scheduler);

if ($subpage == 'allappointments') {
    $select = "schedulerid = '". $scheduler->id ."'";
} else {
    $select = "schedulerid = '". $scheduler->id ."' AND teacherid = '{$USER->id}'";
    $subpage = 'myappointments';
}
$sqlcount = $DB->count_records_select('scheduler_slots', $select);

if ($offset == -1) {
    if ($sqlcount > 25) {
        $offsetcount = $DB->count_records_select('scheduler_slots', $select." AND starttime < '".strtotime('now')."'");
        $offset = floor($offsetcount / 25);
    } else {
        $offset = 0;
    }
}

$slots = $DB->get_records_select('scheduler_slots', $select, null, 'starttime', '*', $offset * 25, 25);
if ($slots) {
    foreach (array_keys($slots) as $slotid) {
        $slots[$slotid]->isappointed = $DB->count_records('scheduler_appointment', array('slotid' => $slotid));
        $slots[$slotid]->isattended = $DB->record_exists('scheduler_appointment', array('slotid' => $slotid, 'attended' => 1));
    }
}

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
    $delselected = $commandbar->action_link($delselectedurl, 'deleteselection', 't/delete', 'confirmdelete', 'delselected');
    $delselected->formid = 'delselected';
    $delbuttons[] = $delselected;

    if (has_capability('mod/scheduler:manageallappointments', $context) && $subpage == 'allappointments') {
        $delbuttons[] = $commandbar->action_link(
                        new moodle_url($actionurl, array('what' => 'deleteall')),
                        'deleteallslots', 't/delete', 'confirmdelete');
        $delbuttons[] = $commandbar->action_link(
                        new moodle_url($actionurl, array('what' => 'deleteallunused')),
                        'deleteallunusedslots', 't/delete', 'confirmdelete');
    }
    $delbuttons[] = $commandbar->action_link(
                    new moodle_url($actionurl, array('what' => 'deleteunused')),
                    'deleteunusedslots', 't/delete', 'confirmdelete');
    $delbuttons[] = $commandbar->action_link(
                    new moodle_url($actionurl, array('what' => 'deleteonlymine')),
                    'deletemyslots', 't/delete', 'confirmdelete');

    $commandbar->add_group(get_string('deletecommands', 'scheduler'), $delbuttons);
}

echo $output->render($commandbar);


// Some slots already exist - prepare the table of slots.
if ($slots) {

    $slotman = new scheduler_slot_manager($scheduler, $actionurl);
    $slotman->showteacher = ($subpage == 'allappointments');

    foreach ($slots as $rawslot) {

        $slot = $scheduler->get_slot($rawslot->id); /* TODO load objects in the first place */

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
            $studlist->add_student($app, false, $app->is_attended());
        }

        $slotman->add_slot($slot, $studlist, $editable);
    }

    echo $output->render($slotman);

    if ($sqlcount > 25) {
        echo $output->paging_bar($sqlcount, $offset, 25, $actionurl, 'offset');
    }

    // Instruction for teacher to click Seen box after appointment.
    echo html_writer::div(get_string('markseen', 'scheduler'));

}


$students = $scheduler->get_students_for_scheduling($usergroups, $CFG->scheduler_maxstudentlistsize);
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

    $maillist = array();
    foreach ($students as $student) {
        $maillist[] = trim($student->email);
    }

    $mailto = 'mailto:'.s(implode($maillist, ', '));

    $subject = get_string('invitation', 'scheduler'). ': ' . $scheduler->name;
    $body = $subject."\n\n";
    $body .= get_string('invitationtext', 'scheduler');
    $body .= "\n\n{$CFG->wwwroot}/mod/scheduler/view.php?id={$cm->id}";
    $invitationurl = new moodle_url($mailto, array('subject' => $subject, 'body' => $body));

    $subject = get_string('reminder', 'scheduler'). ': ' . $scheduler->name;
    $body = $subject."\n\n";
    $body .= get_string('remindertext', 'scheduler');
    $body .= "\n\n{$CFG->wwwroot}/mod/scheduler/view.php?id={$cm->id}";
    $reminderurl = new moodle_url($mailto, array('subject' => $subject, 'body' => $body));

    $maildisplay = '';
    if ($CFG->scheduler_showemailplain) {
        $maildisplay .= html_writer::div(implode(', ', $maillist));
    }
    $maildisplay .= get_string('composeemail', 'scheduler').' ';
    $maildisplay .= html_writer::link($invitationurl, get_string('invitation', 'scheduler'));
    $maildisplay .= ' &mdash; ';
    $maildisplay .= html_writer::link($reminderurl, get_string('reminder', 'scheduler'));

    echo $output->box_start('maildisplay');
    // Print number of students who still have to make an appointment.
    echo $output->heading(get_string('missingstudents', 'scheduler', count($students)), 3);
    // Print e-mail addresses and mailto links.
    echo $maildisplay;
    echo $output->box_end();


    $userfields = scheduler_get_user_fields(null);
    $fieldtitles = array();
    foreach ($userfields as $f) {
        $fieldtitles[] = $f->title;
    }
    $studtable = new scheduler_scheduling_list($scheduler, $fieldtitles);

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

        $userfields = scheduler_get_user_fields($student);
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

        if (empty($groups)) {
            echo $output->notification(get_string('nogroups', 'scheduler'));
        } else {
            $grouptable = new scheduler_scheduling_list($scheduler, array());

            $groupcnt = 0;
            foreach ($groups as $group) {
                $members = groups_get_members($group->id, user_picture::fields('u'), 'u.lastname, u.firstname');
                if (empty($members)) {
                    continue;
                }
                // TODO refactor query
                if (!scheduler_has_slot(implode(',', array_keys($members)), $scheduler, true, $scheduler->schedulermode == 'onetime')) {

                    $picture = print_group_picture($group, $course->id, false, true, true);
                    $name = $groups[$group->id]->name;
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
    echo $output->notification(get_string('nostudents', 'scheduler'));
}
