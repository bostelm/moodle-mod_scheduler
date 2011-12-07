<?php

/**
 * Prints the screen that displays a single student to a teacher.
 * 
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once $CFG->dirroot.'/mod/scheduler/locallib.php';


$studentid = required_param('studentid', PARAM_INT);
$order = optional_param('order','ASC',PARAM_ALPHA);
if (!in_array($order,array('ASC','DESC'))) {
    $order='ASC';
}

$usehtmleditor = can_use_html_editor();

if ($subaction != ''){
    include $CFG->dirroot.'/mod/scheduler/viewstudent.controller.php'; 
}

scheduler_print_user($DB->get_record('user', array('id' => $studentid)), $course);

//print tabs
$tabrows = array();
$row  = array();
if($page == 'appointments'){
    $currenttab = get_string('appointments', 'scheduler');
} else {
    $currenttab = get_string('notes', 'scheduler');
}
$tabname = get_string('appointments', 'scheduler');
$row[] = new tabobject($tabname, "view.php?what=viewstudent&amp;id={$cm->id}&amp;studentid={$studentid}&amp;course={$scheduler->course}&amp;order={$order}&amp;page=appointments", $tabname);
$tabname = get_string('comments', 'scheduler');
$row[] = new tabobject($tabname, "view.php?what=viewstudent&amp;id={$cm->id}&amp;studentid={$studentid}&amp;course={$scheduler->course}&amp;order={$order}&amp;page=notes", $tabname);
$tabrows[] = $row;
print_tabs($tabrows, $currenttab);

/// if slots have been booked
$sql = "
    SELECT
    s.*,
    a.id as appid,
    a.studentid,
    a.attended,
    a.appointmentnote,
    a.grade,
    a.timemodified as apptimemodified
    FROM
    {scheduler_slots} s,
    {scheduler_appointment} a
    WHERE
    s.id = a.slotid AND
    schedulerid = ? AND
    studentid = ?
    ORDER BY
    starttime $order
    ";
if ($slots = $DB->get_records_sql($sql, array($scheduler->id, $studentid, $order))) {
    /// provide link to sort in the opposite direction
    if($order == 'DESC'){
        $orderlink = "<a href=\"view.php?what=viewstudent&amp;id=$cm->id&amp;studentid=".$studentid."&amp;course=$scheduler->course&amp;order=ASC&amp;page=$page\">";
    } else {
        $orderlink = "<a href=\"view.php?what=viewstudent&amp;id=$cm->id&amp;studentid=".$studentid."&amp;course=$scheduler->course&amp;order=DESC&amp;page=$page\">";
    }
    
    $table = new html_table();
    /// print page header and prepare table headers
    if ($page == 'appointments'){
        echo $OUTPUT->heading(get_string('slots' ,'scheduler'));
        $table->head  = array ($strdate, $strstart, $strend, $strseen, $strnote, $strgrade, s(scheduler_get_teacher_name($scheduler)));
        $table->align = array ('LEFT', 'LEFT', 'CENTER', 'CENTER', 'LEFT', 'CENTER', 'CENTER');
        $table->width = '80%';
    } else {
        echo $OUTPUT->heading(get_string('comments' ,'scheduler'));
        $table->head  = array (get_string('studentcomments', 'scheduler'), get_string('comments', 'scheduler'), $straction);
        $table->align = array ('LEFT', 'LEFT');
        $table->width = '80%';
    }
    foreach($slots as $slot) {
        $startdate = scheduler_userdate($slot->starttime,1);
        $starttime = scheduler_usertime($slot->starttime,1);
        $endtime = scheduler_usertime($slot->starttime + ($slot->duration * 60),1);
        $distributecheck = '';
        if ($page == 'appointments'){
            if ($DB->count_records('scheduler_appointment', array('slotid' => $slot->id)) > 1){
                $distributecheck = "<br/><input type=\"checkbox\" name=\"distribute{$slot->appid}\" value=\"1\" /> ".get_string('distributetoslot', 'scheduler')."\n";
            }
            //display appointments
            if ($slot->attended == 0){
            	$teacher = $DB->get_record('user', array('id'=>$slot->teacherid));
                $table->data[] = array ($startdate, $starttime, $endtime, "<img src=\"pix/unticked.gif\" border=\"0\" />", $slot->appointmentnote, scheduler_format_grade($scheduler,$slot->grade), fullname($teacher));
            }
            else {
                $slot->appointmentnote .= "<br/><span class=\"timelabel\">[".userdate($slot->apptimemodified)."]</span>";
                if (($scheduler->scale !=0 ) && (($slot->teacherid == $USER->id) || $CFG->scheduler_allteachersgrading)){
                    $grade = scheduler_make_grading_menu($scheduler, 'gr'.$slot->appid, $slot->grade, true);
                }
                else{
                    $grade = scheduler_format_grade($scheduler,$slot->grade);
                }
                
                $teacher = $DB->get_record('user', array('id'=>$slot->teacherid));
                $table->data[] = array ($startdate, $starttime, $endtime, "<img src=\"pix/ticked.gif\" border=\"0\" />", $slot->appointmentnote, $grade.$distributecheck, fullname($teacher));
            }
        } else {
            if ($DB->count_records('scheduler_appointment', array('slotid' => $slot->id)) > 1){
                $distributecheck = "<input type=\"checkbox\" name=\"distribute\" value=\"1\" /> ".get_string('distributetoslot', 'scheduler')."\n";
            }
            //display notes
            $actions = "<a href=\"javascript:document.forms['updatenote{$slot->id}'].submit()\">".get_string('savecomment', 'scheduler').'</a>';
            $commenteditor = "<form name=\"updatenote{$slot->id}\" action=\"view.php\" method=\"post\">\n";
            $commenteditor .= "<input type=\"hidden\" name=\"what\" value=\"viewstudent\" />\n";
            $commenteditor .= "<input type=\"hidden\" name=\"subaction\" value=\"updatenote\" />\n";
            $commenteditor .= "<input type=\"hidden\" name=\"page\" value=\"appointments\" />\n";
            $commenteditor .= "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />\n";
            $commenteditor .= "<input type=\"hidden\" name=\"studentid\" value=\"{$studentid}\" />\n";
            $commenteditor .= "<input type=\"hidden\" name=\"appid\" value=\"{$slot->appid}\" />\n";
            $commenteditor .= print_textarea($usehtmleditor, 20, 60, 400, 200, 'appointmentnote', $slot->appointmentnote, $COURSE->id, true);
            if ($usehtmleditor) {
                $commenteditor .= "<input type=\"hidden\" name=\"format\" value=\"FORMAT_HTML\" />\n";
            } 
            else {
                $commenteditor .= '<p align="right">';
                $commenteditor .= $OUTPUT->help_icon('textformat', get_string('formattexttype'), 'moodle', true, false, '', true);
                $commenteditor .= get_string('formattexttype');
                $commenteditor .= ':&nbsp;';
                if (!$form->format) {
                    $form->format = 'MOODLE';
                }
                $commenteditor .= html_writer::select(format_text_menu(), 'format', $form->format); 
                $commenteditor .= '</p>';
            }
            $commenteditor .= $distributecheck;
            $commenteditor .= "</form>";
            $table->data[] = array ($slot->notes.'<br/><font size=-2>'.$startdate.' '.$starttime.' to '.$endtime.'</font>', $commenteditor, $actions);
        }
    }
    // print slots table
    if ($page == 'appointments'){
        echo '<form name="studentform" action="view.php" method="post">';
        echo "<input type=\"hidden\" name=\"id\" value=\"{$cm->id}\" />\n";
        echo "<input type=\"hidden\" name=\"subaction\" value=\"updategrades\" />\n";
        echo "<input type=\"hidden\" name=\"what\" value=\"viewstudent\" />\n";
        echo "<input type=\"hidden\" name=\"page\" value=\"appointments\" />\n";
        echo "<input type=\"hidden\" name=\"studentid\" value=\"{$studentid}\" />\n";
    }
    echo html_writer::table($table);
    if ($page == 'appointments'){
        if ($scheduler->scale != 0) {
            echo "<p><center><input type=\"submit\" name=\"go_btn\" value=\"".get_string('updategrades', 'scheduler')."\" />";
        }
        echo '</form>';
    }
}
echo $OUTPUT->continue_button($CFG->wwwroot.'/mod/scheduler/view.php?id='.$cm->id);

return;
/// Finish the page
echo $OUTPUT->footer($course);
exit;
?>