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

require_once($CFG->libdir.'/tablelib.php');

$PAGE->set_docs_path('mod/scheduler/datelist');

$scope = optional_param('scope', 'activity', PARAM_TEXT);
if (!in_array($scope, array('activity', 'course', 'site'))) {
    $scope = 'activity';
}
$teacherid = optional_param('teacherid', 0, PARAM_INT);

if ($scope == 'site') {
    $scopecontext = context_system::instance();
} else if ($scope == 'course') {
    $scopecontext = context_course::instance($scheduler->courseid);
} else {
    $scopecontext = $context;
}

if (!has_capability('mod/scheduler:seeoverviewoutsideactivity', $context)) {
    $scope = 'activity';
}
if (!has_capability('mod/scheduler:canseeotherteachersbooking', $scopecontext)) {
    $teacherid = 0;
}

$taburl = new moodle_url('/mod/scheduler/view.php',
                array('id' => $scheduler->cmid, 'what' => 'datelist', 'scope' => $scope, 'teacherid' => $teacherid));
$returnurl = new moodle_url('/mod/scheduler/view.php', array('id' => $scheduler->cmid));

$PAGE->set_url($taburl);

echo $output->header();

// Print top tabs.

echo $output->teacherview_tabs($scheduler, $permissions, $taburl, 'datelist');


// Find active group in case that group mode is in use.
$currentgroupid = 0;
$groupmode = groups_get_activity_groupmode($scheduler->cm);
if ($groupmode) {
    $currentgroupid = groups_get_activity_group($scheduler->cm, true);

    echo html_writer::start_div('dropdownmenu');
    groups_print_activity_menu($scheduler->cm, $taburl);
    echo html_writer::end_div();
}

$scopemenukey = 'scopemenuself';
if (has_capability('mod/scheduler:canseeotherteachersbooking', $scopecontext)) {
    $teachers = $scheduler->get_available_teachers($currentgroupid);
    $teachermenu = array();
    foreach ($teachers as $teacher) {
        $teachermenu[$teacher->id] = fullname($teacher);
    }
    $select = $output->single_select($taburl, 'teacherid', $teachermenu, $teacherid,
                    array(0 => get_string('myself', 'scheduler')), 'teacheridform');
    echo html_writer::div(get_string('teachersmenu', 'scheduler', $select), 'dropdownmenu');
    $scopemenukey = 'scopemenu';
}
if (has_capability('mod/scheduler:seeoverviewoutsideactivity', $context)) {
    $scopemenu = array('activity' => get_string('thisscheduler', 'scheduler'),
                    'course' => get_string('thiscourse', 'scheduler'),
                    'site' => get_string('thissite', 'scheduler'));
    $select = $output->single_select($taburl, 'scope', $scopemenu, $scope, null, 'scopeform');
    echo html_writer::div(get_string($scopemenukey, 'scheduler', $select), 'dropdownmenu');
}

// Getting date list.

$params = array();
$params['teacherid']   = $teacherid == 0 ? $USER->id : $teacherid;
$params['courseid']    = $scheduler->courseid;
$params['schedulerid'] = $scheduler->id;

$scopecond = '';
if ($scope == 'activity') {
    $scopecond = ' AND sc.id = :schedulerid';
} else if ($scope == 'course') {
    $scopecond = ' AND c.id = :courseid';
}

$sql = "SELECT a.id AS id, ".
               user_picture::fields('u1', array('email', 'department'), 'studentid', 'student').", ".
               $DB->sql_fullname('u1.firstname', 'u1.lastname')." AS studentfullname,
               a.appointmentnote,
               a.appointmentnoteformat,
               a.teachernote,
               a.teachernoteformat,
               a.grade,
               sc.name,
               sc.id AS schedulerid,
               sc.scale,
               c.shortname AS courseshort,
               c.id AS courseid, ".
               user_picture::fields('u2', null, 'teacherid').",
               s.id AS sid,
               s.starttime,
               s.duration,
               s.appointmentlocation,
               s.notes,
               s.notesformat
          FROM {course} c,
               {scheduler} sc,
               {scheduler_appointment} a,
               {scheduler_slots} s,
               {user} u1,
               {user} u2
         WHERE c.id = sc.course AND
               sc.id = s.schedulerid AND
               a.slotid = s.id AND
               u1.id = a.studentid AND
               u2.id = s.teacherid AND
               s.teacherid = :teacherid ".
               $scopecond;

$sqlcount =
       "SELECT COUNT(*)
          FROM {course} c,
               {scheduler} sc,
               {scheduler_appointment} a,
               {scheduler_slots} s
         WHERE c.id = sc.course AND
               sc.id = s.schedulerid AND
               a.slotid = s.id AND
               s.teacherid = :teacherid ".
               $scopecond;

$numrecords = $DB->count_records_sql($sqlcount, $params);


$limit = 30;

if ($numrecords) {

    // Make the table of results.

    $coursestr = get_string('course', 'scheduler');
    $schedulerstr = get_string('scheduler', 'scheduler');
    $whenstr = get_string('when', 'scheduler');
    $wherestr = get_string('where', 'scheduler');
    $whostr = get_string('who', 'scheduler');
    $wherefromstr = get_string('department', 'scheduler');
    $whatstr = get_string('what', 'scheduler');
    $whatresultedstr = get_string('whatresulted', 'scheduler');
    $whathappenedstr = get_string('whathappened', 'scheduler');

    $tablecolumns = array('courseshort', 'schedulerid', 'starttime', 'appointmentlocation',
                          'studentfullname', 'studentdepartment', 'notes', 'grade', 'appointmentnote');
    $tableheaders = array($coursestr, $schedulerstr, $whenstr, $wherestr,
                          $whostr, $wherefromstr, $whatstr, $whatresultedstr, $whathappenedstr);

    $table = new flexible_table('mod-scheduler-datelist');
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);

    $table->define_baseurl($taburl);

    $table->sortable(true, 'when'); // Sorted by date by default.
    $table->collapsible(true);      // Allow column hiding.
    $table->initialbars(true);

    $table->column_suppress('courseshort');
    $table->column_suppress('schedulerid');
    $table->column_suppress('starttime');
    $table->column_suppress('studentfullname');
    $table->column_suppress('notes');

    $table->set_attribute('id', 'dates');
    $table->set_attribute('class', 'datelist');

    $table->column_class('course', 'datelist_course');
    $table->column_class('scheduler', 'datelist_scheduler');

    $table->setup();

    // Get extra query parameters from flexible_table behaviour.
    $where = $table->get_sql_where();
    $sort = $table->get_sql_sort();
    $table->pagesize($limit, $numrecords);

    if (!empty($sort)) {
        $sql .= " ORDER BY $sort";
    }

    $results = $DB->get_records_sql($sql, $params);

    foreach ($results as $id => $row) {
        $courseurl = new moodle_url('/course/view.php', array('id' => $row->courseid));
        $coursedata = html_writer::link($courseurl, format_string($row->courseshort));
        $schedulerurl = new moodle_url('/mod/scheduler/view.php', array('a' => $row->schedulerid));
        $schedulerdata = html_writer::link($schedulerurl, format_string($row->name));
        $a = mod_scheduler_renderer::slotdatetime($row->starttime, $row->duration);
        $whendata = get_string('slotdatetime', 'scheduler', $a);
        $whourl = new moodle_url('/mod/scheduler/view.php',
                        array('what' => 'viewstudent', 'a' => $row->schedulerid, 'appointmentid' => $row->id));
        $whodata = html_writer::link($whourl, $row->studentfullname);
        $whatdata = $output->format_notes($row->notes, $row->notesformat, $context, 'slotnote', $row->sid);
        $gradedata = $row->scale == 0 ? '' : $output->format_grade($row->scale, $row->grade);

        $dataset = array(
                        $coursedata,
                        $schedulerdata,
                        $whendata,
                        format_string($row->appointmentlocation),
                        $whodata,
                        $row->studentdepartment,
                        $whatdata,
                        $gradedata,
                        $output->format_appointment_notes($scheduler, $row) );
        $table->add_data($dataset);
    }
    $table->print_html();
    echo $output->continue_button($returnurl);
} else {
    notice(get_string('noresults', 'scheduler'), $returnurl);
}

echo $output->footer();