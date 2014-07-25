<?php

/**
 * Shows a sortable list of appointments
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

include_once $CFG->libdir.'/tablelib.php';


// Print top tabs.

$taburl = new moodle_url('/mod/scheduler/view.php', array('id' => $scheduler->cmid, 'what' => 'datelist'));
echo $output->teacherview_tabs($scheduler, $taburl, 'datelist');

if (has_capability('mod/scheduler:canseeotherteachersbooking', $context)) {
    $teacherid = optional_param('teacherid', $USER->id, PARAM_INT);
    $tutor =  $DB->get_record('user', array('id' => $teacherid));
    $teachers = scheduler_get_attendants ($cm->id);

    $teachermenu = array();
    foreach($teachers as $teacher){
        $teachermenu[$teacher->id] = fullname($teacher);
    }
    ?>
        <form name="teacherform">
        <input type="hidden" name="id" value="<?php p($cm->id) ?>" />
        <input type="hidden" name="what" value="datelist" />
        <?php echo html_writer::select($teachermenu, 'teacherid', $teacherid); ?>
        <input type="submit" value="Go" />
        </form>
        <hr />
        <?php
}
else{
    $teacherid = $USER->id;
}

/// getting date list

$sql = "
    SELECT
    a.id AS id,
    ".user_picture::fields('u1',array('email','department'),'studentid','student').",
    ".$DB->sql_fullname('u1.firstname','u1.lastname')." AS studentfullname,
    a.appointmentnote,
    a.grade,
    sc.name,
    sc.id as schedulerid,
    c.shortname as courseshort,
    c.id as courseid,
    ".user_picture::fields('u2',NULL,'teacherid').",
    s.id as sid,
    s.starttime,
    s.duration,
    s.appointmentlocation,
    s.notes
    FROM
    {course} c,
    {scheduler} sc,
    {scheduler_appointment} a,
    {scheduler_slots} s,
    {user} u1,
    {user} u2
    WHERE
    c.id = sc.course AND
    sc.id = s.schedulerid AND
    a.slotid = s.id AND
    u1.id = a.studentid AND
    u2.id = s.teacherid AND
    u2.id = ?";

$sqlcount = "
    SELECT
    COUNT(*)
    FROM
    {course} as c,
    {scheduler} as sc,
    {scheduler_appointment} a,
    {scheduler_slots} s,
    {user} u1,
    {user} u2
    WHERE
    c.id = sc.course AND
    sc.id = s.schedulerid AND
    a.slotid = s.id AND
    u1.id = a.studentid AND
    u2.id = s.teacherid AND
    u2.id = ?
    ";
$numrecords = $DB->count_records_sql($sqlcount, array($teacherid));


$limit = 30;

if ($numrecords){

    /// make table result

    $coursestr = get_string('course','scheduler');
    $schedulerstr = get_string('scheduler','scheduler');
    $whenstr = get_string('when','scheduler');
    $wherestr = get_string('where','scheduler');
    $whostr = get_string('who','scheduler');
    $wherefromstr = get_string('department','scheduler');
    $whatstr = get_string('what','scheduler');
    $whatresultedstr = get_string('whatresulted','scheduler');
    $whathappenedstr = get_string('whathappened','scheduler');


    $tablecolumns = array('courseshort', 'schedulerid', 'starttime', 'appointmentlocation', 'studentfullname', 'studentdepartment', 'notes', 'grade', 'appointmentnote');
    $tableheaders = array("<strong>$coursestr</strong>", "<strong>$schedulerstr</strong>", "<strong>$whenstr</strong>", "<strong>$wherestr</strong>", "<strong>$whostr</strong>", "<strong>$wherefromstr</strong>", "<strong>$whatstr</strong>", "<strong>$whatresultedstr</strong>", "<strong>$whathappenedstr</strong>");

    $table = new flexible_table('mod-scheduler-datelist');
    $table->define_columns($tablecolumns);
    $table->define_headers($tableheaders);

    $table->define_baseurl($CFG->wwwroot.'/mod/scheduler/view.php?what=datelist&amp;id='.$cm->id.'&amp;teacherid='.$teacherid);

    $table->sortable(true, 'when'); //sorted by date by default
    $table->collapsible(true);
    $table->initialbars(true);

    // allow column hiding
    $table->column_suppress('course');
    $table->column_suppress('starttime');

    $table->set_attribute('cellspacing', '0');
    $table->set_attribute('id', 'dates');
    $table->set_attribute('class', 'datelist');
    $table->set_attribute('width', '100%');

    $table->column_class('course', 'datelist_course');
    $table->column_class('scheduler', 'datelist_scheduler');
    $table->column_class('starttime', 'timelabel');

    $table->setup();

    /// get extra query parameters from flexible_table behaviour
    $where = $table->get_sql_where();
    $sort = $table->get_sql_sort();
    $table->pagesize($limit, count($numrecords));


    if (!empty($sort)){
        $sql .= " ORDER BY $sort";
    }

    $results = $DB->get_records_sql($sql, array($teacherid));


    // display implements a "same value don't appear again" filter
    $coursemem = '';
    $schedulermem = '';
    $whenmem = '';
    $whomem = '';
    $whatmem = '';
    foreach($results as $id => $row){
        $coursedata = ($coursemem != $row->courseshort) ? "<a href=\"{$CFG->wwwroot}/course/view.php?id={$row->courseid}\">".$row->courseshort.'</a>' : '';
        $coursemem = $row->courseshort;
        $schedulerdata = ($schedulermem != $row->name) ? "<a href=\"{$CFG->wwwroot}/mod/scheduler/view.php?a={$row->schedulerid}\">".$row->name.'</a>' : '';
        $schedulermem = $row->name;
        $whendata = ($whenmem != "$row->starttime $row->duration") ? '<strong>'.date("d M Y G:i", $row->starttime).' '.get_string('for','scheduler')." $row->duration ".get_string('mins', 'scheduler').'</strong>' : '';
        $whenmem = "$row->starttime $row->duration";
        $whodata = ($whomem != $row->studentemail) ? "<a href=\"{$CFG->wwwroot}/mod/scheduler/view.php?what=viewstudent&a={$row->schedulerid}&amp;studentid=$row->studentid&amp;course=$row->courseid\">".$row->studentfirstname.' '.$row->studentlastname.'</a>' : '';
        $whomem = $row->studentemail;
        $whatdata = ($whatmem != $row->notes) ? format_string($row->notes) : '';
        $whatmem = $row->notes;
        $dataset = array(
            $coursedata,
            $schedulerdata,
            $whendata,
            format_string($row->appointmentlocation),
            $whodata,
            $row->studentdepartment,
            $whatdata,
            $row->grade,
            $row->appointmentnote);
        $table->add_data($dataset);
    }
    $table->print_html();
    echo $OUTPUT->continue_button($CFG->wwwroot."/mod/scheduler/view.php?id=".$cm->id.'&amp;subpage='.$subpage);
}
else{
    notice(get_string('noresults', 'scheduler'), $CFG->wwwroot."/mod/scheduler/view.php?id=".$cm->id);
}
