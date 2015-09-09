<?php

require_once(dirname(__FILE__).'/exportform.php');

/**
 * Export scheduler data to a file.
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$PAGE->set_docs_path('mod/scheduler/export');

// Find active group in case that group mode is in use.
$currentgroupid = 0;
$groupmode = groups_get_activity_groupmode($scheduler->cm);
if ($groupmode) {
    $currentgroupid = groups_get_activity_group($scheduler->cm, true);
}

$actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'export', 'id' => $scheduler->cmid));
$returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $scheduler->cmid));
$mform = new scheduler_export_form($actionurl, $scheduler);

if ($mform->is_cancelled()) {
    redirect($returnurl);
}

$data = $mform->get_data();
if ($data) {
    $availablefields = scheduler_get_export_fields();
    $selectedfields = array();
    foreach ($availablefields as $field) {
        $inputid = 'field-'.$field->get_id();
        if (isset($data->{$inputid}) && $data->{$inputid} == 1) {
            $selectedfields[] = $field;
            $field->set_renderer($output);
        }
    }
    $userid = $USER->id;
    if (isset($data->includewhom) && $data->includewhom == 'all') {
        require_capability('mod/scheduler:canseeotherteachersbooking', $context);
        $userid = 0;
    }
    $pageperteacher = isset($data->paging) && $data->paging == 'perteacher';
    $preview = isset($data->preview);
} else {
    $preview = false;
}

if (!$data || $preview) {
    echo $OUTPUT->header();

    // Print top tabs.
    $taburl = new moodle_url('/mod/scheduler/view.php', array('id' => $scheduler->cmid, 'what' => 'export'));
    echo $output->teacherview_tabs($scheduler, $taburl, 'export');

    if ($groupmode) {
        groups_print_activity_menu($scheduler->cm, $taburl);
    }

    echo $output->heading(get_string('exporthdr', 'scheduler'), 2);

    $mform->display();

    if ($preview) {
        $canvas = new scheduler_html_canvas();
        $export = new scheduler_export($canvas);

        $export->build($scheduler,
                        $selectedfields,
                        $data->content,
                        $userid,
                        $currentgroupid,
                        $data->includeemptyslots,
                        $pageperteacher);

        $limit = 20;
        echo $canvas->as_html($limit, false);

        echo html_writer::div(get_string('previewlimited', 'scheduler', $limit), 'previewlimited');
    }

    echo $output->footer();
    exit();
}

switch ($data->outputformat) {
    case 'csv':
        $canvas = new scheduler_csv_canvas($data->csvseparator); break;
    case 'xls':
        $canvas = new scheduler_excel_canvas(); break;
    case 'ods':
        $canvas = new scheduler_ods_canvas(); break;
    case 'html':
        $canvas = new scheduler_html_canvas($returnurl); break;
    case 'pdf':
        $canvas = new scheduler_pdf_canvas($data->pdforientation); break;
}

$export = new scheduler_export($canvas);

$export->build($scheduler,
               $selectedfields,
               $data->content,
               $userid,
               $currentgroupid,
               $data->includeemptyslots,
               $pageperteacher);

$filename = clean_filename($course->shortname.'_'.format_string($scheduler->name));
$canvas->send($filename);

