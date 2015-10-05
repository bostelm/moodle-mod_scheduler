<?php

/**
 * This page prints a particular instance of scheduler and handles
 * top level interactions
 *
 * @package    mod
 * @subpackage scheduler
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
// TODO require_capability('mod/scheduler:view', $context);

// Initialize $PAGE, compute blocks.
$PAGE->set_url('/mod/scheduler/view.php', array('id' => $cm->id));

$output = $PAGE->get_renderer('mod_scheduler');

// Print the page header.

$strschedulers = get_string('modulenameplural', 'scheduler');
$strscheduler  = get_string('modulename', 'scheduler');
$strtime = get_string('time');
$strdate = get_string('date', 'scheduler');
$strstart = get_string('start', 'scheduler');
$strend = get_string('end', 'scheduler');
$strname = get_string('name');
$strseen = get_string('seen', 'scheduler');
$strnote = get_string('comments', 'scheduler');
$strgrade = get_string('grade', 'scheduler');
$straction = get_string('action', 'scheduler');
$strduration = get_string('duration', 'scheduler');
$stremail = get_string('email');

$title = $course->shortname . ': ' . format_string($scheduler->name);
$PAGE->set_title($title);
$PAGE->set_heading($course->fullname);


// route to screen

// teacher side
if (has_capability('mod/scheduler:manage', $context)) {
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

    // student side
} else if (has_capability('mod/scheduler:appoint', $context)) {
    include($CFG->dirroot.'/mod/scheduler/studentview.php');

    // for guests
} else {
    echo $OUTPUT->header();
    echo $OUTPUT->box(get_string('guestscantdoanything', 'scheduler'), 'generalbox');
    echo $OUTPUT->footer($course);
}
