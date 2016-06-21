<?php

/**
 * This page prints a particular instance of scheduler and handles
 * top level interactions
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/mod/scheduler/lib.php');
require_once($CFG->dirroot.'/mod/scheduler/locallib.php');
require_once($CFG->dirroot.'/mod/scheduler/renderable.php');

// Read common request parameters.
$id = optional_param('id', '', PARAM_INT);    // Course Module ID - if it's not specified, must specify 'a', see below.
$action = optional_param('what', 'view', PARAM_ALPHA);
$subaction = optional_param('subaction', '', PARAM_ALPHA);
$offset = optional_param('offset', -1, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('scheduler', $id, 0, false, MUST_EXIST);
    $scheduler = scheduler_instance::load_by_coursemodule_id($id);
} else {
    $a = required_param('a', PARAM_INT);     // Scheduler ID.
    $scheduler = scheduler_instance::load_by_id($a);
    $cm = $scheduler->get_cm();
}
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

$defaultsubpage = groups_get_activity_groupmode($cm) ? 'myappointments' : 'allappointments';
$subpage = optional_param('subpage', $defaultsubpage, PARAM_ALPHA);

require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);

// Initialize $PAGE, compute blocks.
$PAGE->set_url('/mod/scheduler/view.php', array('id' => $cm->id));

$output = $PAGE->get_renderer('mod_scheduler');

// Print the page header.

$title = $course->shortname . ': ' . format_string($scheduler->name);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);


// Route to screen.

$isteacher = has_capability('mod/scheduler:manage', $context);
$isstudent = has_capability('mod/scheduler:viewslots', $context);
if ($isteacher) {
    // Teacher side.
    if ($action == 'viewstatistics') {
        include($CFG->dirroot.'/mod/scheduler/viewstatistics.php');
    } else if ($action == 'viewstudent') {
        include($CFG->dirroot.'/mod/scheduler/viewstudent.php');
    } else if ($action == 'export') {
        include($CFG->dirroot.'/mod/scheduler/export.php');
    } else if ($action == 'datelist') {
        include($CFG->dirroot.'/mod/scheduler/datelist.php');
    } else {
        include($CFG->dirroot.'/mod/scheduler/teacherview.php');
    }

} else if ($isstudent) {
    // Student side.
    include($CFG->dirroot.'/mod/scheduler/studentview.php');

} else {
    // For guests.
    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('guestscantdoanything', 'scheduler'), 'generalbox');
    echo $OUTPUT->footer($course);
}
