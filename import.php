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
 * Import page.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/csvlib.class.php');

$iid = optional_param('iid', null, PARAM_INT);

$baseurl = new moodle_url('/mod/scheduler/view.php', ['what' => 'import', 'id' => $scheduler->cmid]);
$returnurl = new moodle_url($baseurl, ['what' => 'view', 'subaction' => '']);

$PAGE->set_url($baseurl);
$PAGE->set_docs_path('mod/scheduler/import');

// Check permissions and whether we have teachers.
$permissions->ensure($permissions->can_edit_own_slots());
if (!$scheduler->has_available_teachers()) {
    print_error('needteachers', 'scheduler', $returnurl);
}

// While we don't yet have a valid file.
if (empty($iid)) {
    $csvform = new mod_scheduler\output\import_csv_form($baseurl->out(false));
    if ($csvform->is_cancelled()) {
        redirect($returnurl);
    }

    $formdata = $csvform->get_data();
    if ($formdata) {
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);

        $iid = csv_import_reader::get_new_iid('importslots');
        $cir = new csv_import_reader($iid, 'importslots');

        $content = $csvform->get_file_content('file');
        $cir->load_csv_content($content, $formdata->encoding, $formdata->delimname);
        $importer = new mod_scheduler\csv_slots_importer($scheduler, $permissions, $cir);

        $errors = $importer->validate_csv();
        if (!empty($errors)) {
            $errorkey = array_keys($errors)[0];
            $error = reset($errors);
            print_error($errorkey, '', $baseurl, $error);
        }

        $table = new flexible_table('import-slot-preview');
        $table->define_baseurl($baseurl);
        $table->define_columns(['indicator', 'lineno', 'starttime', 'duration', 'teacher', 'errors']);
        $table->define_headers([
            '',
            get_string('csvline', 'mod_scheduler'),
            get_string('field-starttime', 'mod_scheduler'),
            get_string('duration', 'mod_scheduler'),
            get_string('teacher', 'mod_scheduler'),
            get_string('errors', 'mod_scheduler'),
        ]);

        echo $OUTPUT->header();
        echo $output->teacherview_tabs($scheduler, $permissions, $baseurl, 'import');
        echo $output->heading(get_string('importslots', 'mod_scheduler'), 2);
        echo $output->heading(get_string('preview'), 3);
        $table->setup();

        $i = 0;
        foreach ($importer as $lineno => $info) {
            if ($i++ >= $formdata->previewrows) {
                break;
            }

            $valid = empty($info->errors);
            $data = $info->data;
            $table->add_data([
                $OUTPUT->pix_icon($valid ? 'i/valid' : 'i/invalid', ''),
                $info->lineno,
                userdate($data->starttime->getTimestamp(), get_string('strftimedatetime', 'langconfig')),
                get_string('numminutes', 'core', $data->duration),
                $data->teacher ? fullname($data->teacher) : '',
                !$valid ? implode(' ', $info->errors) : ''
            ]);
        }

        $table->finish_output();

        $optsform = new mod_scheduler\output\import_csv_options_form($baseurl->out(false));
        $optsform->set_data((object) ['iid' => $iid]);

        echo $OUTPUT->heading(get_string('confirm'), 3);
        $optsform->display();
        echo $OUTPUT->footer();
        exit();
    }

    if (!$formdata) {
        echo $OUTPUT->header();
        echo $output->teacherview_tabs($scheduler, $permissions, $baseurl, 'import');
        echo $OUTPUT->heading_with_help(get_string('importslots', 'mod_scheduler'), 'importslots', 'mod_scheduler');
        echo html_writer::tag('div', markdown_to_html(get_string('importslotsintro', 'mod_scheduler', [
            'exampleurl' => (new moodle_url('/mod/scheduler/tests/fixtures/slots.csv'))->out(false)
        ])));
        $csvform->display();
        echo $output->footer();
        exit();
    }

} else {

    core_php_time_limit::raise();
    raise_memory_limit(MEMORY_EXTRA);
    $cir = new csv_import_reader($iid, 'importslots');

    // We've got an import ID.
    $optsform = new mod_scheduler\output\import_csv_options_form($baseurl->out(false));
    if ($data = $optsform->get_data()) {

        $importer = new mod_scheduler\csv_slots_importer($scheduler, $permissions, $cir);
        $errors = $importer->validate_csv();
        if (!empty($errors)) {
            $errorkey = array_keys($errors)[0];
            $error = reset($errors);
            print_error($errorkey, '', $baseurl, $error);
        }

        $imported = 0;
        $errors = [];

        foreach ($importer as $info) {
            $valid = empty($info->errors);
            if (!$valid) {
                $errors[$info->lineno] = implode(' ', $info->errors);
                continue;
            }
            try {
                $slot = $importer->make_slot_from_processed_line($info);
                $slot->save();
            } catch (moodle_exception $e) {
                $errors[$info->lineno] = $e->getMessage();
                continue;
            }

            $imported++;

            // Avoid error event from being bothered by these missing properties. In the future,
            // we should make it so that the slot factory adds all the required properties on
            // the object when creating a blank one. We could not do this by fetching the record
            // from the database, but that seems to be an unecessary performance drain.
            $slot->reuse = 0;
            $slot->emaildate = 0;
            \mod_scheduler\event\slot_added::create_from_slot($slot)->trigger();
        }

        echo $OUTPUT->header();
        echo $output->teacherview_tabs($scheduler, $permissions, $baseurl, 'import');
        echo $output->heading(get_string('importslots', 'mod_scheduler'), 2);
        echo $output->heading(get_string('results', 'mod_scheduler'), 3);

        echo html_writer::start_tag('ul');
        echo html_writer::tag('li', get_string('nslotsimported', 'mod_scheduler', $imported));
        echo html_writer::tag('li', get_string('nslotswitherror', 'mod_scheduler', count($errors)));
        echo html_writer::end_tag('ul');

        if (!empty($errors)) {
            $table = new flexible_table('import-slots-errors');
            $table->define_baseurl($baseurl);
            $table->define_columns(['indicator', 'lineno', 'errors']);
            $table->define_headers([
                '',
                get_string('csvline', 'mod_scheduler'),
                get_string('errors', 'mod_scheduler'),
            ]);
            $table->setup();

            echo $OUTPUT->heading(get_string('errors', 'mod_scheduler'), 3);
            foreach ($errors as $lineno => $error) {
                $table->add_data([$OUTPUT->pix_icon('i/invalid', ''), $lineno, $error]);
            }
            $table->finish_output();
        }

        echo $OUTPUT->single_button($returnurl, get_string('continue'), 'get');
        echo $OUTPUT->footer();

        $cir->cleanup();
        exit();
    }

    // Right now we should not get here unless the form was cancelled.
    $cir->cleanup();
    redirect($baseurl);
}

