<?php

/**
 * Prints the screen that displays a single student to a teacher.
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/scheduler/locallib.php');

if (!has_capability('mod/scheduler:manage', $context)) {
    require_capability('mod/scheduler:manageallappointments', $context);
}

$appointmentid = required_param('appointmentid', PARAM_INT);
list($slot, $appointment) = $scheduler->get_slot_appointment($appointmentid);
$studentid = $appointment->studentid;

$sql = "
SELECT
s.*,
a.id as appid,
a.studentid,
a.attended,
a.appointmentnote,
a.grade,
a.timemodified as apptimemodified
FROM
{scheduler_slots} s,
{scheduler_appointment} a
WHERE
s.id = a.slotid AND
schedulerid = ? AND
studentid = ?
ORDER BY
starttime ASC
";

$slots = $DB->get_records_sql($sql, array($scheduler->id, $studentid));

scheduler_print_user($DB->get_record('user', array('id' => $appointment->studentid)), $course);

$params = array(
                'startdate' => scheduler_userdate($slot->starttime, 1),
                'starttime' => scheduler_usertime($slot->starttime, 1),
                'endtime' => scheduler_usertime($slot->endtime, 1),
                'teacher' => fullname($slot->get_teacher())
                );
echo html_writer::tag('p', get_string('appointmentsummary', 'scheduler', $params));

// Print tabs.
$tabrows = array();
$row  = array();
$urlparas = array('what' => 'viewstudent',
                  'id' => $scheduler->cmid,
                  'appointmentid' => $appointmentid,
                  'course' => $scheduler->courseid);
$taburl = new moodle_url('/mod/scheduler/view.php', $urlparas);

$pages = array('thisappointment');
if ($slot->get_appointment_count() > 1) {
    $pages[] = 'otherstudents';
}
if (count($slots) > 1) {
    $pages[] = 'otherappointments';
}

if (!in_array($subpage, $pages) ) {
    $subpage = 'thisappointment';
}

if (count($pages) > 1) {
    foreach ($pages as $tabpage) {
        $tabname = get_string('tab-'.$tabpage, 'scheduler');
        $row[] = new tabobject($tabpage, new moodle_url($taburl, array('subpage' => $tabpage)), $tabname);
    }
    $tabrows[] = $row;
    print_tabs($tabrows, $subpage);
}

if ($subpage == 'thisappointment') {
    // Print editable appointment description.
    require_once($CFG->dirroot.'/mod/scheduler/appointmentforms.php');

    $actionurl = new moodle_url($taburl, array('page' => 'thisappointment'));
    $returnurl = new moodle_url($taburl, array('page' => 'thisappointment'));

    $distribute = ($slot->get_appointment_count() > 1);
    $gradeedit = ($slot->teacherid == $USER->id) || $CFG->scheduler_allteachersgrading;
    $mform = new scheduler_editappointment_form($appointment, $actionurl, $gradeedit, $distribute);
    $mform->set_data($mform->prepare_appointment_data($appointment));

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        $data = $mform->extract_appointment_data($formdata);
        if ($distribute && isset($formdata->distribute)) {
            foreach ($slot->get_appointments() as $otherapp) {
                $otherapp->set_data($data);
            }
            $slot->save();
        } else {
            $appointment->set_data($data);
            $appointment->save();
        }
        redirect($returnurl);
    } else {
        $mform->display();
    }

} else if ($subpage == 'otherappointments') {
    // Print table of other appointments of the same student.

    $table = new html_table();

    $table->head  = array ($strdate, $strstart, $strend, $strseen, $strnote, $strgrade, s($scheduler->get_teacher_name()));
    $table->align = array ('LEFT', 'LEFT', 'CENTER', 'CENTER', 'LEFT', 'CENTER', 'CENTER');

    foreach ($slots as $otherslot) {
        $startdate = scheduler_userdate($otherslot->starttime, 1);
        $studenturl = new moodle_url($taburl, array('appointmentid' => $otherslot->appid, 'page' => 'thisappointment'));
        $datelink = $OUTPUT->action_link($studenturl, $startdate);
        $starttime = scheduler_usertime($otherslot->starttime, 1);
        $endtime = scheduler_usertime($otherslot->starttime + ($otherslot->duration * 60), 1);
        $iconid = $otherslot->attended ? 'ticked' : 'unticked';
        $iconhelp = $otherslot->attended ? 'seen' : 'notseen';
        $attendedpix = $OUTPUT->pix_icon($iconid, get_string($iconhelp, 'scheduler'), 'mod_scheduler');

        $otherslot->appointmentnote .= "<br/><span class=\"timelabel\">[".userdate($otherslot->apptimemodified)."]</span>";
        $grade = $output->format_grade($scheduler, $otherslot->grade);
        $teacher = $DB->get_record('user', array('id' => $otherslot->teacherid));
        $table->data[] = array ($datelink, $starttime, $endtime, $attendedpix,
                                 $otherslot->appointmentnote, $grade, fullname($teacher));
    }
    echo html_writer::table($table);

} else if ($subpage == 'otherstudents') {
    // Print table of other students in the same slot.

    $table = new html_table();

    $table->head  = array($strname, $strseen, $strnote, $strgrade);
    $table->align = array('LEFT', 'CENTER', 'LEFT', 'CENTER');

    foreach ($slot->get_appointments() as $otherappointment) {
        $studentname = fullname($otherappointment->student);
        $studenturl = new moodle_url($taburl, array('appointmentid' => $otherappointment->id, 'page' => 'thisappointment'));
        $studentlink = $OUTPUT->action_link($studenturl, $studentname);
        $grade = $output->format_grade($scheduler, $otherappointment->grade);
        $iconid = $otherappointment->attended ? 'ticked' : 'unticked';
        $iconhelp = $otherappointment->attended ? 'seen' : 'notseen';
        $icon = $OUTPUT->pix_icon($iconid, get_string($iconhelp, 'scheduler'), 'mod_scheduler');
        $note = $otherappointment->appointmentnote;
        if ($note) {
            $note .= '<br/><span class="timelabel">['.userdate($otherappointment->timemodified).']</span>';
        }
        $table->data[] = array ($studentlink, $icon, $note, $grade);
    }
    echo html_writer::table($table);
}

echo $OUTPUT->continue_button(new moodle_url('/mod/scheduler/view.php', array('id' => $scheduler->cmid)));
echo $OUTPUT->footer($course);
exit;
