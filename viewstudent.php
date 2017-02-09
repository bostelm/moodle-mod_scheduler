<?php

/**
 * Prints the screen that displays a single student to a teacher.
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
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

$urlparas = array('what' => 'viewstudent',
    'id' => $scheduler->cmid,
    'appointmentid' => $appointmentid,
    'course' => $scheduler->courseid);
$taburl = new moodle_url('/mod/scheduler/view.php', $urlparas);
$PAGE->set_url($taburl);

$appts = $scheduler->get_appointments_for_student($studentid);

$pages = array('thisappointment');
if ($slot->get_appointment_count() > 1) {
    $pages[] = 'otherstudents';
}
if (count($appts) > 1) {
    $pages[] = 'otherappointments';
}

if (!in_array($subpage, $pages) ) {
    $subpage = 'thisappointment';
}

// Process edit form before page output starts.
if ($subpage == 'thisappointment') {
    require_once($CFG->dirroot.'/mod/scheduler/appointmentforms.php');

    $actionurl = new moodle_url($taburl, array('page' => 'thisappointment'));
    $returnurl = new moodle_url($taburl, array('page' => 'thisappointment'));

    $distribute = ($slot->get_appointment_count() > 1);
    $gradeedit = ($slot->teacherid == $USER->id) || get_config('mod_scheduler', 'allteachersgrading');
    $mform = new scheduler_editappointment_form($appointment, $actionurl, $gradeedit, $distribute);
    $mform->set_data($mform->prepare_appointment_data($appointment));

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        $mform->save_appointment_data($formdata, $appointment);
        redirect($returnurl);
    }
}

echo $output->header();

// Print user summary.

scheduler_print_user($DB->get_record('user', array('id' => $appointment->studentid)), $course);

// Print tabs.
$tabrows = array();
$row  = array();

if (count($pages) > 1) {
    foreach ($pages as $tabpage) {
        $tabname = get_string('tab-'.$tabpage, 'scheduler');
        $row[] = new tabobject($tabpage, new moodle_url($taburl, array('subpage' => $tabpage)), $tabname);
    }
    $tabrows[] = $row;
    print_tabs($tabrows, $subpage);
}

$totalgradeinfo = new scheduler_totalgrade_info($scheduler, $scheduler->get_gradebook_info($appointment->studentid));

if ($subpage == 'thisappointment') {

    $ai = scheduler_appointment_info::make_for_teacher($slot, $appointment);
    echo $output->render($ai);

    $mform->display();

    if ($scheduler->uses_grades()) {
        echo $output->render($totalgradeinfo);
    }

} else if ($subpage == 'otherappointments') {
    // Print table of other appointments of the same student.

    $studenturl = new moodle_url($taburl, array('page' => 'thisappointment'));
    $table = new scheduler_slot_table($scheduler, true, $studenturl);
    $table->showattended = true;
    $table->showteachernotes = true;
    $table->showeditlink = true;
    $table->showlocation = false;

    foreach ($appts as $appt) {
        $table->add_slot($appt->get_slot(), $appt, null, false);
    }

    echo $output->render($table);

    if ($scheduler->uses_grades()) {
        $totalgradeinfo->showtotalgrade = true;
        $totalgradeinfo->totalgrade = $scheduler->get_user_grade($appointment->studentid);
        echo $output->render($totalgradeinfo);
    }

} else if ($subpage == 'otherstudents') {
    // Print table of other students in the same slot.

    $ai = scheduler_appointment_info::make_from_slot($slot, false);
    echo $output->render($ai);

    $studenturl = new moodle_url($taburl, array('page' => 'thisappointment'));
    $table = new scheduler_slot_table($scheduler, true, $studenturl);
    $table->showattended = true;
    $table->showslot = false;
    $table->showstudent = true;
    $table->showteachernotes = true;
    $table->showeditlink = true;

    foreach ($slot->get_appointments() as $otherappointment) {
        $table->add_slot($otherappointment->get_slot(), $otherappointment, null, false);
    }

    echo $output->render($table);
}

echo $output->continue_button(new moodle_url('/mod/scheduler/view.php', array('id' => $scheduler->cmid)));
echo $output->footer($course);
exit;
