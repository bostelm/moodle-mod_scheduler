<?php

/**
 * Contains various sub-screens that a teacher can see.
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

function scheduler_prepare_formdata($scheduler, $slot) {
    global $DB;

    $data = clone (object) $slot;
    $data->notes = array();
    $data->notes['text'] = $slot->notes;
    $data->notes['format'] = $slot->notesformat;
    if ($slot->emaildate < 0){
        $data->emaildate = 0;
    }
    
    $appointments = $DB->get_records('scheduler_appointment', array('slotid' => $data->id));
    $i = 0;
    foreach ($appointments as $appointment) {
        $data->studentid[$i] = $appointment->studentid;
        $data->attended[$i] = $appointment->attended;
        $data->appointmentnote[$i]['text'] = $appointment->appointmentnote;
        $data->appointmentnote[$i]['format'] = $appointment->appointmentnoteformat;
        $data->grade[$i] = $appointment->grade;
        $i++;
    }
    return $data;
}

function scheduler_save_slotform($scheduler, $course, $slotid, $data) {

    global $DB, $OUTPUT;

    // make new slot record
    $slot = new stdClass();
    $slot->schedulerid = $scheduler->id;
    $slot->starttime = $data->starttime;
    $slot->duration = $data->duration;
    $slot->exclusivity = $data->exclusivity;
    $slot->teacherid = $data->teacherid;
    $slot->notes = $data->notes['text'];
    $slot->notesformat = $data->notes['format'];
    $slot->appointmentlocation = $data->appointmentlocation;
    $slot->hideuntil = $data->hideuntil;
    $slot->reuse = $data->reuse;
    $slot->emaildate = $data->emaildate;
    $slot->timemodified = time();
    if (!$slotid) {
        // add it
        $slot->id = $DB->insert_record('scheduler_slots', $slot);
    } else {
        // update it
        $slot->id = $slotid;
        $DB->update_record('scheduler_slots', $slot);
    }

    $DB->delete_records('scheduler_appointment', array('slotid'=>$slot->id)); // cleanup old appointments
    for ($i = 0; $i < $data->appointment_repeats; $i++) {
        if ($data->studentid[$i] > 0) {
            $appointment = new stdClass();
            $appointment->slotid = $slot->id;
            $appointment->studentid = $data->studentid[$i];
            $appointment->attended = isset($data->attended[$i]);
            $appointment->appointmentnote = $data->appointmentnote[$i]['text'];
            $appointment->appointmentnoteformat = $data->appointmentnote[$i]['format'];
            if (isset($data->grade)) {
                $appointment->grade = $data->grade[$i];
            }
            $DB->insert_record('scheduler_appointment', $appointment);
            scheduler_update_grades($scheduler, $appointment->studentid);
        }
    }

    scheduler_events_update($slot, $course);
}


function scheduler_print_schedulebox($scheduler, $cm, $studentid, $groupid = 0) {
    global $CFG, $OUTPUT;

    $availableslots = scheduler_get_available_slots($studentid, $scheduler->id);

    $startdatemem = '';
    $starttimemem = '';
    $availableslotsmenu = array();
    foreach ($availableslots as $slot) {
        $startdatecnv = scheduler_userdate($slot->starttime, 1);
        $starttimecnv = scheduler_usertime($slot->starttime, 1);

        $startdatestr = ($startdatemem != '' and $startdatemem == $startdatecnv) ? "-----------------" : $startdatecnv;
        $starttimestr = ($starttimemem != '' and $starttimemem == $starttimecnv) ? '' : $starttimecnv;

        $startdatemem = $startdatecnv;
        $starttimemem = $starttimecnv;

        $url = $CFG->wwwroot.'/mod/scheduler/view.php?id='.$cm->id.'&slotid='.$slot->id;
        if ($groupid) {
            $url .= '&what=schedulegroup&subaction=dochooseslot&groupid='.$groupid;
        } else {
            $url .= '&what=schedule&subaction=dochooseslot&studentid='.$studentid;
        }
        $availableslotsmenu[$url] = "$startdatestr $starttimestr";
    }

    $chooser = new url_select($availableslotsmenu);
    //    $formoptions = array('action' => $CFG->wwwroot.'/mod/scheduler/view.php?what=schedule&subaction=dochooseslot&studentid='.$studentid);
    //  $form = html_writer::tag('form', $chooser, $formoptions);

    if ($availableslots) {
        echo $OUTPUT->box_start();
        echo $OUTPUT->heading(get_string('chooseexisting', 'scheduler'), 2);
        echo $OUTPUT->render($chooser);
        echo $OUTPUT->box_end();
    }
}

// load group restrictions
$modinfo = get_fast_modinfo($course);

$usergroups = '';
if ($cm->groupmode > 0) {
    $groups = groups_get_all_groups($COURSE->id, 0, $cm->groupingid);
    $usergroups = array_keys($groups);
}

if ($action) {
    include_once($CFG->dirroot.'/mod/scheduler/slotforms.php');
    include($CFG->dirroot.'/mod/scheduler/teacherview.controller.php');
}


/************************************ View : New single slot form ****************************************/
if ($action == 'addslot') {
    $actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'addslot', 'id' => $cm->id));
    $returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $cm->id));

    $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $usergroups);

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        scheduler_save_slotform ($scheduler, $course, 0, $formdata);
        redirect($returnurl);
        echo $OUTPUT->heading(get_string('oneslotadded', 'scheduler'));
    } else {
        echo $OUTPUT->heading(get_string('addsingleslot', 'scheduler'));
        $mform->display();
        echo $OUTPUT->footer($course);
        die;
    }
    // return code for include
    return -1;
}
/************************************ View : Update single slot form ****************************************/
if ($action == 'updateslot') {

    $slotid = required_param('slotid', PARAM_INT);
    $slot = $DB->get_record('scheduler_slots', array('id' => $slotid));
    $data = scheduler_prepare_formdata($scheduler, $slot);

    $actionurl = new moodle_url('/mod/scheduler/view.php',
                    array('what' => 'updateslot', 'id' => $cm->id, 'slotid' => $slotid, 'page' => $page, 'offset' => $offset));
    $returnurl = new moodle_url('/mod/scheduler/view.php',
                    array('what' => 'view', 'id' => $cm->id, 'page' => $page, 'offset' => $offset));

    $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $usergroups, array('slotid' => $slotid));
    $mform->set_data($data);

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        scheduler_save_slotform ($scheduler, $course, $slotid, $formdata);
        echo $OUTPUT->heading(get_string('slotupdated', 'scheduler'));
    } else {
        echo $OUTPUT->heading(get_string('updatesingleslot', 'scheduler'));
        $mform->display();
        echo $OUTPUT->footer($course);
        die;
    }

}
/************************************ Add session multiple slots form ****************************************/
if ($action == 'addsession') {

    $actionurl = new moodle_url('/mod/scheduler/view.php',
                    array('what' => 'addsession', 'id' => $cm->id, 'page' => $page));
    $returnurl = new moodle_url('/mod/scheduler/view.php',
                    array('what' => 'view', 'id' => $cm->id, 'page' => $page));

    $mform = new scheduler_addsession_form($actionurl, $scheduler, $cm, $usergroups);

    if ($mform->is_cancelled()) {
        redirect($returnurl);
    } else if ($formdata = $mform->get_data()) {
        scheduler_action_doaddsession($scheduler, $formdata);
    } else {
        echo $OUTPUT->heading(get_string('addsession', 'scheduler'));
        $mform->display();
        echo $OUTPUT->footer($course);
        die;
    }
}
/************************************ Schedule a student form ***********************************************/
if ($action == 'schedule') {
    if ($subaction == 'dochooseslot') {
        $slotid = required_param('slotid', PARAM_INT);
        $studentid = required_param('studentid', PARAM_INT);
        $slot = $DB->get_record('scheduler_slots', array('id' => $slotid), '*', MUST_EXIST);

        $actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'updateslot', 'id' => $cm->id, 'slotid' => $slotid));
        $returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $cm->id));

        $data = scheduler_prepare_formdata($scheduler, $slot);
        $i = 0;
        while (isset($data->studentid[$i])) {
            $i++;
        }
        $data->studentid[$i] = $studentid;
        $i++;

        $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $usergroups, array('slotid' => $slotid, 'repeats' => $i));
        $mform->set_data($data);

        echo $OUTPUT->heading(get_string('updatesingleslot', 'scheduler'), 2);
        $mform->display();

    } else if (empty($subaction)) {
        $studentid = required_param('studentid', PARAM_INT);
        $student = $DB->get_record('user', array('id'=>$studentid), '*', MUST_EXIST);

        $actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'addslot', 'id' => $cm->id));
        $returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $cm->id));

        $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $usergroups);

        $data = array();
        $data['studentid'][0] = $studentid;
        $mform->set_data($data);
        echo $OUTPUT->heading(get_string('scheduleappointment', 'scheduler', fullname($student)));

        scheduler_print_schedulebox($scheduler, $cm, $studentid);

        echo $OUTPUT->box_start();
        echo $OUTPUT->heading(get_string('scheduleinnew', 'scheduler'), 2);
        $mform->display();
        echo $OUTPUT->box_end();
    }

    // return code for include
    return -1;
}
/************************************ Schedule a whole group in form ***********************************************/
if ($action == 'schedulegroup') {

    $groupid = required_param('groupid', PARAM_INT);
    $group = $DB->get_record('groups', array('id'=>$groupid), '*', MUST_EXIST);
    $members = groups_get_members($groupid);

    if ($subaction == 'dochooseslot') {

        $slotid = required_param('slotid', PARAM_INT);
        $groupid = required_param('groupid', PARAM_INT);
        $slot = $DB->get_record('scheduler_slots', array('id' => $slotid), '*', MUST_EXIST);

        $actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'updateslot', 'id' => $cm->id, 'slotid' => $slotid));
        $returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $cm->id));

        $data = scheduler_prepare_formdata($scheduler, $slot);
        $i = 0;
        while (isset($data->studentid[$i])) {
            $i++;
        }
        foreach ($members as $member) {
            $data->studentid[$i] = $member->id;
            $i++;
        }

        $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $usergroups,
                        array('slotid' => $slotid, 'repeats' => $i));
        $mform->set_data($data);

        echo $OUTPUT->heading(get_string('updatesingleslot', 'scheduler'), 2);
        $mform->display();

    } else if (empty($subaction)) {

        $actionurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'addslot', 'id' => $cm->id));
        $returnurl = new moodle_url('/mod/scheduler/view.php', array('what' => 'view', 'id' => $cm->id));

        $data = array();
        $i = 0;
        foreach ($members as $member) {
            $data['studentid'][$i] = $member->id;
            $i++;
        }
        $data['exclusivity'] = $i;

        $mform = new scheduler_editslot_form($actionurl, $scheduler, $cm, $usergroups, array('repeats' => $i));
        $mform->set_data($data);

        echo $OUTPUT->heading(get_string('scheduleappointment', 'scheduler', $group->name));

        scheduler_print_schedulebox($scheduler, $cm, 0, $groupid);

        echo $OUTPUT->box_start();
        echo $OUTPUT->heading(get_string('scheduleinnew', 'scheduler'), 2);
        $mform->display();
        echo $OUTPUT->box_end();

    }
    // return code for include
    return -1;
}
//****************** Standard view ***********************************************//


/// print top tabs

$tabrows = array();
$row  = array();

switch ($action){
    case 'viewstatistics':{
        $currenttab = get_string('statistics', 'scheduler');
        break;
    }
    case 'datelist':{
        $currenttab = get_string('datelist', 'scheduler');
        break;
    }
    case 'viewstudent':{
        $currenttab = get_string('studentdetails', 'scheduler');
        $row[] = new tabobject($currenttab, '', $currenttab);
        break;
    }
    case 'downloads':{
        $currenttab = get_string('downloads', 'scheduler');
        break;
    }
    default: {
        $currenttab = get_string($page, 'scheduler');
    }
}

$tabname = get_string('myappointments', 'scheduler');
$row[] = new tabobject($tabname, "view.php?id={$cm->id}&amp;page=myappointments", $tabname);
if ($DB->count_records('scheduler_slots', array('schedulerid'=>$scheduler->id)) > $DB->count_records('scheduler_slots', array('schedulerid'=>$scheduler->id, 'teacherid'=>$USER->id))) {
    $tabname = get_string('allappointments', 'scheduler');
    $row[] = new tabobject($tabname, "view.php?id={$cm->id}&amp;page=allappointments", $tabname);
} else {
    // we are alone in this scheduler
    if ($page == 'allappointements') {
        $currenttab = get_string('myappointments', 'scheduler');
    }
}
$tabname = get_string('datelist', 'scheduler');
$row[] = new tabobject($tabname, "view.php?id={$cm->id}&amp;what=datelist", $tabname);
$tabname = get_string('statistics', 'scheduler');
$row[] = new tabobject($tabname, "view.php?what=viewstatistics&amp;id={$cm->id}&amp;course={$scheduler->course}&amp;page=overall", $tabname);
$tabname = get_string('downloads', 'scheduler');
$row[] = new tabobject($tabname, "view.php?what=downloads&amp;id={$cm->id}&amp;course={$scheduler->course}", $tabname);
$tabrows[] = $row;
print_tabs($tabrows, $currenttab);

/// print heading
echo $OUTPUT->heading(format_string($scheduler->name), 2);

/// print page
if (trim(strip_tags($scheduler->intro))) {
    echo $OUTPUT->box_start('mod_introbox');
    echo format_module_intro('scheduler', $scheduler, $cm->id);
    echo $OUTPUT->box_end();
}

if ($page == 'allappointments'){
    $select = "schedulerid = '". $scheduler->id ."'";
} else {
    $select = "schedulerid = '". $scheduler->id ."' AND teacherid = '{$USER->id}'";
    $page = 'myappointments';
}
$sqlcount = $DB->count_records_select('scheduler_slots',$select);

if (($offset == '') && ($sqlcount > 25)){
    $offsetcount = $DB->count_records_select('scheduler_slots', $select." AND starttime < '".strtotime('now')."'");
    $offset = floor($offsetcount/25);
}


$slots = $DB->get_records_select('scheduler_slots', $select, null, 'starttime', '*', $offset * 25, 25);
if ($slots){
    foreach(array_keys($slots) as $slotid){
        $slots[$slotid]->isappointed = $DB->count_records('scheduler_appointment', array('slotid'=>$slotid));
        $slots[$slotid]->isattended = $DB->record_exists('scheduler_appointment', array('slotid'=>$slotid, 'attended'=> 1));
    }
}

$straddsession = get_string('addsession', 'scheduler');
$straddsingleslot = get_string('addsingleslot', 'scheduler');
$strdownloadexcel = get_string('downloadexcel', 'scheduler');

/// some slots already exist
if ($slots){
    // print instructions and button for creating slots
    echo $OUTPUT->box_start('center', '', '');
    print_string('addslot', 'scheduler');

    // print add session button
    $strdeleteallslots = get_string('deleteallslots', 'scheduler');
    $strdeleteallunusedslots = get_string('deleteallunusedslots', 'scheduler');
    $strdeleteunusedslots = get_string('deleteunusedslots', 'scheduler');
    $strdeletemyslots = get_string('deletemyslots', 'scheduler');
    $strstudents = get_string('students', 'scheduler');
    $displaydeletebuttons = 1;
    echo '<center>';
    include $CFG->dirroot.'/mod/scheduler/commands.html';
    echo '</center>';
    echo $OUTPUT->box_end();

    // prepare slots table
    $table = new html_table();
    if ($page == 'myappointments'){
        $table->head  = array ('', $strdate, $strstart, $strend, $strstudents, $straction);
        $table->align = array ('CENTER', 'LEFT', 'LEFT', 'CENTER', 'CENTER', 'CENTER', 'LEFT', 'CENTER');
    } else {
        $table->head  = array ('', $strdate, $strstart, $strend, $strstudents, s(scheduler_get_teacher_name($scheduler)), $straction);
        $table->align = array ('CENTER', 'LEFT', 'LEFT', 'CENTER', 'CENTER', 'CENTER', 'LEFT', 'LEFT', 'CENTER');
    }
    $table->width = '90%';
    $offsetdatemem = '';
    foreach($slots as $slot) {
        if (!$slot->isappointed && $slot->starttime + (60 * $slot->duration) < time()) {
            // This slot is in the past and has not been chosen by any student, so delete
            $DB->delete_records('scheduler_slots', array('id'=>$slot->id));
            continue;
        }

        /// Parameter $local in scheduler_userdate and scheduler_usertime added by power-web.at
        /// When local Time or Date is needed the $local Param must be set to 1
        $offsetdate = scheduler_userdate($slot->starttime,1);
        $offsettime = scheduler_usertime($slot->starttime,1);
        $endtime = scheduler_usertime($slot->starttime + ($slot->duration * 60),1);

        /// make a slot select box
        if ($USER->id == $slot->teacherid || has_capability('mod/scheduler:manageallappointments', $context)){
            $selectcheck = "<input type=\"checkbox\" id=\"sel_{$slot->id}\" name=\"sel_{$slot->id}\" onclick=\"document.forms['deleteslotsform'].items.value = toggleListState(document.forms['deleteslotsform'].items.value, 'sel_{$slot->id}', '{$slot->id}');\" />";
        } else {
            $selectcheck = '';
        }

        // slot is appointed
        $studentArray = array();
        if ($slot->isappointed) {
            $appointedstudents = $DB->get_records('scheduler_appointment', array('slotid'=>$slot->id));
            $studentArray[] = "<form name=\"appointementseen_{$slot->id}\" method=\"post\" action=\"view.php\">";
            $studentArray[] = "<input type=\"hidden\" name=\"id\" value=\"".$cm->id."\" />";
            $studentArray[] = "<input type=\"hidden\" name=\"slotid\" value=\"".$slot->id."\" />";
            $studentArray[] = "<input type=\"hidden\" name=\"what\" value=\"saveseen\" />";
            $studentArray[] = "<input type=\"hidden\" name=\"page\" value=\"".$page."\" />";
            foreach($appointedstudents as $appstudent){
                $student = $DB->get_record('user', array('id'=>$appstudent->studentid));
                if ($student) {
                    $picture = $OUTPUT->user_picture($student);
                    $name = "<a href=\"view.php?what=viewstudent&amp;id={$cm->id}&amp;studentid={$student->id}&amp;course={$scheduler->course}&amp;order=DESC\">".fullname($student).'</a>';
                }


                /// formatting grade
                $grade=scheduler_format_grade($scheduler,$appstudent->grade,true);

                if ($USER->id == $slot->teacherid || has_capability('mod/scheduler:manageallappointments', $context)){
                    $checked = ($appstudent->attended) ? 'checked="checked"' : '' ;
                    $checkbox = "<input type=\"checkbox\" name=\"seen[]\" value=\"{$appstudent->id}\" {$checked} />";
                } else {
                    // same thing but no link
                    if ($appstudent->attended == 1) {
                        $checkbox = '<img src="pix/ticked.gif" border="0">';
                    } else {
                        $checkbox = '<img src="pix/unticked.gif" border="0">';
                    }
                }
                $studentArray[] = "$checkbox $picture $name $grade<br/>";
            }
            $studentArray[] = "<a href=\"javascript:document.forms['appointementseen_{$slot->id}'].submit();\">".get_string('saveseen','scheduler').'</a>';
            $studentArray[] = "</form>";
        } else {
            // slot is free
            $picture = '';
            $name = '';
            $checkbox = '';
        }

        $actions = '<span style="font-size: x-small;">';
        if ($USER->id == $slot->teacherid || has_capability('mod/scheduler:manageallappointments', $context)){

            $strdelete = get_string('delete');
            $stredit = get_string('move','scheduler');
            $strattended = get_string('attended','scheduler');
            $strnonexclusive = get_string('isnonexclusive', 'scheduler');
            $strallowgroup = get_string('allowgroup', 'scheduler');
            $strforbidgroup = get_string('forbidgroup', 'scheduler');
            $strrevoke = get_string('revoke', 'scheduler');
            $strreused = get_string('setreused', 'scheduler');
            $strunreused = get_string('setunreused', 'scheduler');

            $actions .= "<a href=\"view.php?what=deleteslot&amp;id={$cm->id}&amp;slotid={$slot->id}&amp;page={$page}\" title=\"{$strdelete}\"><img src=\"{$CFG->wwwroot}/pix/t/delete.png\" alt=\"{$strdelete}\" /></a>";
            $actions .= "&nbsp;<a href=\"view.php?what=updateslot&amp;id={$cm->id}&amp;slotid={$slot->id}&amp;page={$page}\" title=\"{$stredit}\"><img src=\"{$CFG->wwwroot}/pix/t/edit.png\" alt=\"{$stredit}\" /></a>";
            if ($slot->isattended){
                $actions .= "&nbsp;<img src=\"{$CFG->wwwroot}/pix/i/groupevent.png\" title=\"{$strattended}\" />";
            } else {
                if ($slot->isappointed > 1){
                    $actions .= "&nbsp;<img src=\"{$CFG->wwwroot}/pix/i/groupevent.png\" title=\"{$strnonexclusive}\" />";
                } else {
                    if ($slot->exclusivity == 1){
                        $actions .= "&nbsp;<a href=\"view.php?what=allowgroup&amp;id={$cm->id}&amp;slotid={$slot->id}&amp;page={$page}\" title=\"{$strallowgroup}\"><img src=\"{$CFG->wwwroot}/pix/t/groupn.png\" alt=\"{$strallowgroup}\" /></a>";
                    } else {
                        $actions .= "&nbsp;<a href=\"view.php?what=forbidgroup&amp;id={$cm->id}&amp;slotid={$slot->id}&amp;page={$page}\" title=\"{$strforbidgroup}\"><img src=\"{$CFG->wwwroot}/pix/t/groupv.png\" alt=\"{$strforbidgroup}\" /></a>";
                    }
                }
            }
            if ($slot->isappointed){
                $actions .= "&nbsp;<a href=\"view.php?what=revokeall&amp;id={$cm->id}&amp;slotid={$slot->id}&amp;page={$page}\" title=\"{$strrevoke}\"><img src=\"{$CFG->wwwroot}/pix/s/no.gif\" alt=\"{$strrevoke}\" /></a>";
            }
        } else {
            // just signal group status
            if ($slot->isattended){
                $actions .= "&nbsp;<img src=\"{$CFG->wwwroot}/pix/i/groupevent.png\" title=\"{$strattended}\" />";
            } else {
                if ($slot->isappointed > 1){
                    $actions .= "&nbsp;<img src=\"{$CFG->wwwroot}/pix/i/groupevent.png\" title=\"{$strnonexclusive}\" />";
                } else {
                    if ($slot->exclusivity == 1){
                        $actions .= "&nbsp;<img src=\"{$CFG->wwwroot}/pix/t/groupn.png\" title=\"{$strallowgroup}\" />";
                    } else {
                        $actions .= "&nbsp;<img src=\"{$CFG->wwwroot}/pix/t/groupv.png\" title=\"{$strforbidgroup}\" />";
                    }
                }
            }
        }
        if ($slot->exclusivity > 1){
            $actions .= ' ('.$slot->exclusivity.')';
        }
        if ($slot->reuse){
            $actions .= "&nbsp;<a href=\"view.php?what=unreuse&amp;id={$cm->id}&amp;slotid={$slot->id}&amp;page={$page}\" title=\"{$strunreused}\" ><img src=\"pix/volatile_shadow.gif\" alt=\"{$strunreused}\" border=\"0\" /></a>";
        } else {
            $actions .= "&nbsp;<a href=\"view.php?what=reuse&amp;id={$cm->id}&amp;slotid={$slot->id}&amp;page={$page}\" title=\"{$strreused}\" ><img src=\"pix/volatile.gif\" alt=\"{$strreused}\" border=\"0\" /></a>";
        }
        $actions .= '</span>';
        if($page == 'myappointments'){
            $table->data[] = array ($selectcheck, ($offsetdate == $offsetdatemem) ? '' : $offsetdate, $offsettime, $endtime, implode("\n",$studentArray), $actions);
        } else {
            $teacherlink = "<a href=\"$CFG->wwwroot/user/view.php?id={$slot->teacherid}\">".fullname($DB->get_record('user', array('id'=> $slot->teacherid)))."</a>";
            $table->data[] = array ($selectcheck, ($offsetdate == $offsetdatemem) ? '' : $offsetdate, $offsettime, $endtime, implode("\n",$studentArray), $teacherlink, $actions);
        }
        $offsetdatemem = $offsetdate;
    }

    // print slots table
    echo $OUTPUT->heading(get_string('slots' ,'scheduler'));
    echo html_writer::table($table);
    ?>
<center>
	<table width="90%">
		<tr>
			<td align="left"><script
					src="<?php echo "{$CFG->wwwroot}/mod/scheduler/scripts/listlib.js" ?>"></script>
				<form name="deleteslotsform" style="display: inline">
					<input type="hidden" name="id" value="<?php p($cm->id) ?>" /> <input
						type="hidden" name="page" value="<?php echo $page ?>" /> <input
						type="hidden" name="what" value="deleteslots" /> <input
						type="hidden" name="items" value="" />
				</form> <a
				href="javascript:document.forms['deleteslotsform'].submit()"><?php print_string('deleteselection','scheduler') ?>
			</a> <br />
			</td>
		</tr>
	</table>

	<?php
	if ($sqlcount > 25){
    echo "Page : ";
    $pagescount = ceil($sqlcount/25);
    for ($n = 0; $n < $pagescount; $n ++){
        if ($n == $offset){
            echo ($n+1).' ';
        } else {
            echo "<a href=view.php?id={$cm->id}&amp;page={$page}&amp;offset={$n}>".($n+1)."</a> ";
        }
    }
}

echo '</center>';

// Instruction for teacher to click Seen box after appointment
echo '<br /><center>' . get_string('markseen', 'scheduler') . '</center>';

} else if ($action != 'addsession') {
    /// There are no slots, should the teacher be asked to make some
    echo $OUTPUT->box_start('center', '', '');
    print_string('welcomenewteacher', 'scheduler');
    echo '<center>';
    $displaydeletebuttons = 0;
    include "commands.html";
    echo '</center>';
    echo $OUTPUT->box_end();
}

/// print table of students needing an appointment
?>
	<center>
		<table width="90%">
			<tr valign="top">
				<td width="50%">
<?php
echo $OUTPUT->heading(get_string('schedulestudents', 'scheduler'));

$students = scheduler_get_possible_attendees($cm, $usergroups);
if (!$students) {
    $nostudentstr = get_string('noexistingstudents','scheduler');
    if ($COURSE->id == SITEID){
        $nostudentstr .= '<br/>'.get_string('howtoaddstudents','scheduler');
    }
    echo $OUTPUT->notification($nostudentstr, 'notifyproblem');

} else if (count($students) > $CFG->scheduler_maxstudentlistsize) {

    // There are too many students who still have to make appointments, don't display a list
    $toomanystr = get_string('missingstudentsmany', 'scheduler', count($students));
    echo $OUTPUT->notification($toomanystr, 'notifymessage');

} else {
    $mtable = new html_table();

    // build table header
    $mtable->head  = array ('', $strname);
    $mtable->align = array ('CENTER','LEFT');
    $extrafields = scheduler_get_user_fields(null);
    foreach ($extrafields as $field) {
    	$mtable->head[] = $field->title;
    	$mtable->align[] = 'LEFT';
	}
	$mtable->head[] = $strseen;
	$mtable->align[] = 'CENTER';
	$mtable->head[] = $straction;
	$mtable->align[] = 'CENTER';
	// end table header

	$mtable->data = array();
	// In $mailto the mailing list for reminder emails is built up
	$mailto = '<a href="mailto:';
	// $maillist will hold a list of email addresses for people who prefer to cut
	// and paste into their To field rather than using the mailto link
	$maillist = array();
	$date = usergetdate(time());
	foreach ($students as $student) {
        if (!scheduler_has_slot($student->id, $scheduler, true, $scheduler->schedulermode == 'onetime')) {
            $picture = $OUTPUT->user_picture($student);
            $name = "<a href=\"../../user/view.php?id={$student->id}&amp;course={$scheduler->course}\">";
            $name .= fullname($student);
            $name .= '</a>';
            if (scheduler_has_slot($student->id, $scheduler, true, false) == 0){
                // student has never scheduled
                $mailto .= $student->email.', ';
                $maillist[] = $student->email; // constructing list of email addresses to be shown later
            }


            $checkbox = "<a href=\"view.php?what=schedule&amp;id={$cm->id}&amp;studentid={$student->id}&amp;page={$page}&amp;seen=1\">";
            $checkbox .= '<img src="pix/unticked.gif" border="0" />';
            $checkbox .= '</a>';

            $args['what'] = 'schedule';
            $args['id'] = $cm->id;
            $args['studentid'] = $student->id;
            $args['page'] = $page;
            $url = new moodle_url('view.php',$args);
            $actions = $OUTPUT->single_button($url, get_string('schedule','scheduler'));

            $args['what'] = 'markasseennow';
            $args['id'] = $cm->id;
            $args['studentid'] = $student->id;
            $url = new moodle_url('view.php', $args);
            $actions .= $OUTPUT->single_button($url, get_string('markasseennow','scheduler'));

            $newdata = array($picture, $name);
            $extrafields = scheduler_get_user_fields($student);
            foreach ($extrafields as $field) {
                $newdata[] = $field->value;
            }
            $newdata[] = $checkbox;
            $newdata[] = $actions;
            $mtable->data[] = $newdata;
        }
    }

    $num = count($mtable->data);

    // dont print if allowed to book multiple appointments
    if ($num > 0) {
        // There are students who still have to make appointments

        // Print number of students who still have to make an appointment
        echo $OUTPUT->heading(get_string('missingstudents', 'scheduler', $num), 3);

        // Print links to print invitation or reminder emails
        $strinvitation = get_string('invitation', 'scheduler');
        $strreminder = get_string('reminder', 'scheduler');
        $mailto = rtrim($mailto, ', ');

        $subject = $strinvitation . ': ' . $scheduler->name;
        $body = $strinvitation . ': ' . $scheduler->name . "\n\n";
        $body .= get_string('invitationtext', 'scheduler');
        $body .= "{$CFG->wwwroot}/mod/scheduler/view.php?id={$cm->id}";
        $maildisplay = '<center>';
        if ($CFG->scheduler_showemailplain) {
        	$maildisplay .= '<p>'.implode(', ', $maillist).'</p>';
        }
        $maildisplay .= get_string('composeemail', 'scheduler').
        $mailto.'?subject='.htmlentities(rawurlencode($subject)).
        '&amp;body='.htmlentities(rawurlencode($body)).
        '"> '.$strinvitation.'</a> ';
        $maildisplay .= ' &mdash; ';

        $subject = $strreminder . ': ' . $scheduler->name;
        $body = $strreminder . ': ' . $scheduler->name . "\n\n";
        $body .= get_string('remindertext', 'scheduler');
        $body .= "{$CFG->wwwroot}/mod/scheduler/view.php?id={$cm->id}";
        $maildisplay .= $mailto.'?subject='.htmlentities(rawurlencode($subject)).
        '&amp;body='.htmlentities(rawurlencode($body)).
        '"> '.$strreminder.'</a></center>';
        echo $OUTPUT->box($maildisplay);

        // print table of students who still have to make appointments
        echo html_writer::table($mtable);
    } else {
        echo $OUTPUT->notification(get_string('nostudents', 'scheduler'));
    }
}
?>
				</td>
				<?php
				if (scheduler_group_scheduling_enabled($course, $cm)){
    ?>
				<td width="50%"><?php

				/// print table of outstanding appointer (groups)

				echo $OUTPUT->heading(get_string('schedulegroups', 'scheduler'));

				if (empty($groups)){
    echo $OUTPUT->notification(get_string('nogroups', 'scheduler'));
} else {
	$mtable = new html_table();
	$mtable->head  = array ('', $strname, $straction);
	$mtable->align = array ('CENTER', 'LEFT', 'CENTER');
	foreach($groups as $group) {
        $members = groups_get_members($group->id, 'u.id, lastname, firstname, email, picture', 'lastname, firstname');
        if (empty($members)) continue;
        if (!scheduler_has_slot(implode(',', array_keys($members)), $scheduler, true, $scheduler->schedulermode == 'onetime')) {
            $actions = '';
            $actions .= "<a href=\"view.php?what=schedulegroup&amp;id={$cm->id}&amp;groupid={$group->id}&amp;page={$page}\">";
            $actions .= get_string('schedule', 'scheduler');
            $actions .= '</a>';
            $groupmembers = array();
            foreach($members as $member){
                $groupmembers[] = fullname($member);
            }
            $groupcrew = '['. implode(", ", $groupmembers) . ']';
            $mtable->data[] = array('', $groups[$group->id]->name.' '.$groupcrew, $actions);
        }
    }
    // print table of students who still have to make appointments
    if (!empty($mtable->data)){
        echo html_writer::table($mtable);
    } else {
        echo $OUTPUT->notification(get_string('nogroups', 'scheduler'));
    }
}
?>
				</td>
				<?php
}
?>
			</tr>
		</table>
	</center>