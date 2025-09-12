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
 * Shows a sortable list of appointments
 *
 * @package    mod_scheduler
 * @copyright  2015 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_scheduler\local\systemreports\images_list;

$PAGE->set_docs_path('mod/scheduler/manageimages');

$scopecontext = context_course::instance($scheduler->courseid);

// TODO: test capability

$taburl = new moodle_url('/mod/scheduler/view.php',
                array('id' => $scheduler->cmid, 'what' => 'manageimages'));
$returnurl = new moodle_url('/mod/scheduler/view.php', array('id' => $scheduler->cmid));

$PAGE->set_url($taburl);

$PAGE->requires->js_call_amd('mod_scheduler/images_list', 'init');

echo $output->header();

// Print top tabs.

echo $output->teacherview_tabs($scheduler, $permissions, $taburl, 'manageimages');

// Find active group in case that group mode is in use.
$currentgroupid = 0;
$groupmode = groups_get_activity_groupmode($scheduler->cm);
if ($groupmode) {
    $currentgroupid = groups_get_activity_group($scheduler->cm, true);

    echo html_writer::start_div('dropdownmenu');
    groups_print_activity_menu($scheduler->cm, $taburl);
    echo html_writer::end_div();
}

$report = core_reportbuilder\system_report_factory::create(images_list::class, context_module::instance($scheduler->cmid), 'mod_scheduler', 'message', $scheduler->cmid);
echo $report->output();

echo $output->footer();
