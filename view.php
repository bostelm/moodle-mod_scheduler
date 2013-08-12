<?PHP  

/**
 * This page prints a particular instance of scheduler and handles
 * top level interactions
 * 
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->dirroot.'/mod/scheduler/lib.php');
require_once($CFG->dirroot.'/mod/scheduler/locallib.php');

// common parameters
$id = optional_param('id', '', PARAM_INT);    // Course Module ID, or
$a = optional_param('a', '', PARAM_INT);     // scheduler ID
$action = optional_param('what', 'view', PARAM_CLEAN); 
$subaction = optional_param('subaction', '', PARAM_CLEAN);
$page = optional_param('page', 'allappointments', PARAM_CLEAN);
$offset = optional_param('offset', '', PARAM_CLEAN);
$usehtmleditor = false;
$editorfields = '';

if ($id) {
    if (! $cm = get_coursemodule_from_id('scheduler', $id)) {
        print_error('invalidcoursemodule');
    }
    
    if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
        print_error('coursemisconf');
    }
    
    if (! $scheduler = $DB->get_record('scheduler', array('id' => $cm->instance))) {
        print_error('invalidcoursemodule');
    }
    
} else {
    if (! $scheduler = $DB->get_record('scheduler', array('id' => $a))) {
        print_error('invalidcoursemodule');
    }
    if (! $course = $DB->get_record('course', array('id' => $scheduler->course))) {
        print_error('coursemisconf');
    }
    if (! $cm = get_coursemodule_from_instance('scheduler', $scheduler->id, $course->id)) {
        print_error('invalidcoursemodule');
    }
}


require_login($course->id, false, $cm);
$context = context_module::instance($cm->id);
// TODO require_capability('mod/scheduler:view', $context);

add_to_log($course->id, 'scheduler', $action, "view.php?id={$cm->id}", $scheduler->id, $cm->id);

$groupmode = groupmode($course, $cm);

// Initialize $PAGE, compute blocks
$PAGE->set_url('/mod/scheduler/view.php', array('id' => $cm->id));


/// This is a pre-header selector for downloded documents generation

    if (has_capability('mod/scheduler:manage', $context) || has_capability('mod/scheduler:attend', $context)) {
        if (preg_match('/downloadexcel|^downloadcsv|downloadods/', $action)){
            include($CFG->dirroot.'/mod/scheduler/downloads.php');
        }
    }

/// Print the page header

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

echo $OUTPUT->header();

/// route to screen

// teacher side
if (has_capability('mod/scheduler:manage', $context)) {
    if ($action == 'viewstatistics'){
        include $CFG->dirroot.'/mod/scheduler/viewstatistics.php';
    }
    elseif ($action == 'viewstudent'){
        include $CFG->dirroot.'/mod/scheduler/viewstudent.php';
    }
    elseif ($action == 'downloads' || $action == 'dodownloadcsv'){
        include $CFG->dirroot.'/mod/scheduler/downloads.php';
    }
    elseif ($action == 'datelist'){
        include $CFG->dirroot.'/mod/scheduler/datelist.php';
    }
    else{
        include $CFG->dirroot.'/mod/scheduler/teacherview.php';
    }
}

// student side
elseif (has_capability('mod/scheduler:appoint', $context)) { 
    include $CFG->dirroot.'/mod/scheduler/studentview.php';
}
// for guests
else {
    echo $OUTPUT->box(get_string('guestscantdoanything', 'scheduler'), 'center', '70%');
}    

echo $OUTPUT->footer($course);

?>