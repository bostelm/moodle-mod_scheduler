<?php

/**
 * Export scheduler data to a file.
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/importform.php');

$PAGE->set_docs_path('mod/scheduler/import');

// Find active group in case that group mode is in use.
$currentgroupid = 0;
$groupmode = groups_get_activity_groupmode($scheduler->cm);
if ($groupmode) {
    $currentgroupid = groups_get_activity_group($scheduler->cm, true);
}

$actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'import', 'id' => $scheduler->cmid));
$returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $scheduler->cmid));
$PAGE->set_url($actionurl);
$mform = new scheduler_import_form($actionurl);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

$data = $mform->get_data();

if (!$data) {
    echo $OUTPUT->header();

    // Print top tabs.
    $taburl = new moodle_url('/mod/scheduler/view.php', array('id' => $scheduler->cmid, 'what' => 'import'));
    echo $output->teacherview_tabs($scheduler, $taburl, 'import');

    /*
    if ($groupmode) {
        groups_print_activity_menu($scheduler->cm, $taburl);
    }
*/
    echo $output->heading(get_string('importhdr', 'scheduler'), 2);

    $mform->display();

    echo $output->footer();
    exit();
}


$import = new scheduler_import($mform->get_csv_reader($returnurl));


// Retrieve them here...