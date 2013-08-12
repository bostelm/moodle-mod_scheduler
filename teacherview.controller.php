<?php

/**
 * Controller for all teacher-related views.
 * 
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


// We first have to check whether some action needs to be performed
switch ($action) {
/************************************ creates or updates a slot ***********************************************/
 /*
  * If fails, should reenter within the form signalling error cause
  */
    case 'doaddupdateslot':{
        // get expected parameters
        $slotid = optional_param('slotid', '', PARAM_INT);
        
        // get standard slot parms
        $data = new stdClass();
        get_slot_data($data);
        $appointments = unserialize(stripslashes(optional_param('appointments', '', PARAM_RAW)));
        
        $errors = array();
        
        //  in the "schedule as seen" workflow, do not check for conflicting slots etc.
        $force = optional_param('seen', 0, PARAM_BOOL);
        if (!$force) {
            
            // Avoid slots starting in the past (too far)
            if ($data->starttime < (time() - DAYSECS * 10)) {
                $erroritem = new stdClass();
                $erroritem->message = get_string('startpast', 'scheduler');
                $erroritem->on = 'rangestart';
                $errors[] = $erroritem;
            }
            
            if ($data->exclusivity > 0 and count($appointments) > $data->exclusivity){
                $erroritem = new stdClass();
                $erroritem->message = get_string('exclusivityoverload', 'scheduler');
                $erroritem->on = 'exclusivity';
                $errors[] = $erroritem;
            }
            
            if ($data->teacherid == 0){
                $erroritem = new stdClass();
                $erroritem->message = get_string('noteacherforslot', 'scheduler');
                $erroritem->on = 'teacherid';
                $errors[] = $erroritem;
            }
            
            if (count($errors)){
                $action = 'addslot';
                return;
            }
            
            // Avoid overlapping slots, by asking the user if they'd like to overwrite the existing ones...
            // for other scheduler, we check independently of exclusivity. Any slot here conflicts
            // for this scheduler, we check against exclusivity. Any complete slot here conflicts
            $conflictsRemote = scheduler_get_conflicts($scheduler->id, $data->starttime, $data->starttime + $data->duration * 60, $data->teacherid, 0, SCHEDULER_OTHERS, false);
            $conflictsLocal = scheduler_get_conflicts($scheduler->id, $data->starttime, $data->starttime + $data->duration * 60, $data->teacherid, 0, SCHEDULER_SELF, true);
            if (!$conflictsRemote) $conflictsRemote = array();
            if (!$conflictsLocal) $conflictsLocal = array();
            $conflicts = $conflictsRemote + $conflictsLocal;
            
            // remove itself from conflicts when updating
            if (!empty($slotid) and array_key_exists($slotid, $conflicts)){
                unset($conflicts[$slotid]);
            }
            
            if (count($conflicts)) {
                if ($subaction == 'confirmdelete' && confirm_sesskey()) {
                    foreach ($conflicts as $conflict) {
                        if ($conflict->id != @$slotid) {
                            $DB->delete_records('scheduler_slots', array('id' => $conflict->id));
                            $DB->delete_records('scheduler_appointment', array('slotid' => $conflict->id));
                            scheduler_delete_calendar_events($conflict);
                        }
                    }
                } 
                else { 
                    echo "<br/><br/>";
                    echo $OUTPUT->box_start('center', '', '');
                    echo get_string('slotwarning', 'scheduler').'<br/><br/>';
                    foreach ($conflicts as $conflict) {
                        $students = scheduler_get_appointed($conflict->id);
                        
                        echo (!empty($students)) ? '<b>' : '' ;
                        echo userdate($conflict->starttime);
                        echo ' [';
                        echo $conflict->duration.' '.get_string('minutes');
                        echo ']<br/>';
                        
                        if ($students){
                            $appointed = array();
                            foreach($students as $aStudent){
                                $appointed[] = fullname($aStudent);
                            }
                            if (count ($appointed)){
                                echo '<span style="font-size : smaller">';
                                echo implode(', ', $appointed);
                                echo '</span>';
                            }
                            unset ($appointed);
                            echo '<br/>';
                        }
                        echo (!empty($students)) ? '</b>' : '' ;
                    }
                    
                    $options = array();
                    $options['what'] = 'addslot';
                    $options['id'] = $cm->id;
                    $options['page'] = $page;
                    $options['slotid'] = $slotid;
                    echo $OUTPUT->single_button(new moodle_url('view.php',$options), get_string('cancel'));
                    
                    $options['what'] = 'doaddupdateslot';
                    $options['subaction'] = 'confirmdelete';
                    $options['sesskey'] = sesskey();
                    $options['year'] = $data->year;
                    $options['month'] = $data->month;
                    $options['day'] = $data->day;
                    $options['hour'] = $data->hour;
                    $options['minute'] = $data->minute;
                    $options['displayyear'] = $data->displayyear;
                    $options['displaymonth'] = $data->displaymonth;
                    $options['displayday'] = $data->displayday;
                    $options['duration'] = $data->duration;
                    $options['teacherid'] = $data->teacherid;
                    $options['exclusivity'] = $data->exclusivity;
                    $options['appointments'] = serialize($appointments);
                    $options['notes'] = $data->notes;
                    $options['reuse'] = $data->reuse;
                    $options['appointmentlocation'] = $data->appointmentlocation;
                    echo $OUTPUT->single_button(new moodle_url('view.php',$options), get_string('deletetheseslots', 'scheduler'));
                    echo $OUTPUT->box_end(); 
                    echo $OUTPUT->footer($course);
                    die();  
                }
            }
            
        } 
        
        // make new slot record
        $slot = new stdClass();
        $slot->schedulerid = $scheduler->id;
        $slot->starttime = $data->starttime;
        $slot->duration = $data->duration;
        if (!empty($data->slotid)){
            $appointed = count(scheduler_get_appointments($data->slotid));
            if ($data->exclusivity > 0 and $appointed > $data->exclusivity){
                unset($erroritem);
                $erroritem->message = get_string('exclusivityoverload', 'scheduler');
                $erroritem->on = 'exclusivity';
                $errors[] = $erroritem;
                return;
            }
            $slot->exclusivity = max($data->exclusivity, $appointed);
        }
        else{
            $slot->exclusivity = $data->exclusivity;
        }
        $slot->timemodified = time();
        if (!empty($data->teacherid)) $slot->teacherid = $data->teacherid;
        $slot->notes = $data->notes;
        $slot->appointmentlocation = $data->appointmentlocation;
        $slot->hideuntil = $data->hideuntil;
        $slot->reuse = $data->reuse;
        $slot->emaildate = 0;
        if (!$slotid){ // add it
            $slot->id = $DB->insert_record('scheduler_slots', $slot);
            echo $OUTPUT->heading(get_string('oneslotadded','scheduler'));
        }
        else{ // update it
            $slot->id = $slotid;
            $DB->update_record('scheduler_slots', $slot);
            echo $OUTPUT->heading(get_string('slotupdated','scheduler'));
        }
        
        $DB->delete_records('scheduler_appointment', array('slotid'=>$slot->id)); // cleanup old appointments
        if($appointments){
            foreach ($appointments as $appointment){ // insert updated
                $appointment->slotid = $slot->id; // now we know !!
                $DB->insert_record('scheduler_appointment', $appointment);
		        scheduler_update_grades($scheduler, $appointment->studentid);
            }
        }
        
        scheduler_events_update($slot, $course);
        break;
    }
    /************************************ Saving a session with slots *************************************/
    case 'doaddsession':{
        // This creates sessions using the data submitted by the user via the form on add.html
        get_session_data($data);
        
        $fordays = (($data->rangeend - $data->rangestart) / DAYSECS);
        
        $errors = array();
        
        /// range is negative
        if ($fordays < 0){
            $erroritem->message = get_string('negativerange', 'scheduler');
            $erroritem->on = 'rangeend';
            $errors[] = $erroritem;
        }
        
        if ($data->teacherid == 0){
            unset($erroritem);
            $erroritem->message = get_string('noteacherforslot', 'scheduler');
            $erroritem->on = 'teacherid';
            $errors[] = $erroritem;
        }
        
        /// first slot is in the past
        if ($data->rangestart < time() - DAYSECS) {
            unset($erroritem);
            $erroritem->message = get_string('startpast', 'scheduler');
            $erroritem->on = 'rangestart';
            $errors[] = $erroritem;
        }
        
        // first error trap. Ask to correct that first
        if (count($errors)){
            $action = 'addsession';
            break;
        }
        
        
        /// make a base slot for generating
        $slot = new stdClass();
        $slot->appointmentlocation = $data->appointmentlocation;
        $slot->exclusivity = $data->exclusivity;
        $slot->reuse = $data->reuse;
        $slot->duration = $data->duration;
        $slot->schedulerid = $scheduler->id;
        $slot->timemodified = time();
        $slot->teacherid = $data->teacherid;
        
        /// check if overlaps. Check also if some slots are in allowed day range
        $startfrom = $data->rangestart;
        $noslotsallowed = true;
        for ($d = 0; $d <= $fordays; $d ++){
            $starttime = $startfrom + ($d * DAYSECS);
            $eventdate = usergetdate($starttime);
            $dayofweek = $eventdate['wday'];
            if ((($dayofweek == 1) && ($data->monday == 1)) ||
                    (($dayofweek == 2) && ($data->tuesday == 1)) || 
                    (($dayofweek == 3) && ($data->wednesday == 1)) ||
                    (($dayofweek == 4) && ($data->thursday == 1)) || 
                    (($dayofweek == 5) && ($data->friday == 1)) ||
                    (($dayofweek == 6) && ($data->saturday == 1)) ||
                    (($dayofweek == 0) && ($data->sunday == 1))){
                $noslotsallowed = false;
                $data->starttime = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'], $data->starthour, $data->startminute);
                $conflicts = scheduler_get_conflicts($scheduler->id, $data->starttime, $data->starttime + $data->duration * 60, $data->teacherid, 0, SCHEDULER_ALL, false);
                if (!$data->forcewhenoverlap){
                    if ($conflicts){
                        unset($erroritem);
                        $erroritem->message = get_string('overlappings', 'scheduler');
                        $erroritem->on = 'range';
                        $errors[] = $erroritem;
                    }
                }
            }
        }
        
        /// Finally check if some slots are allowed (an error is thrown to ask care to this situation)
        if ($noslotsallowed){
            unset($erroritem);
            $erroritem->message = get_string('allslotsincloseddays', 'scheduler');
            $erroritem->on = 'days';
            $errors[] = $erroritem;
        }
        
        // second error trap. For last error cases.
        if (count($errors)){
            $action = 'addsession';
            break;
        }
        
        /// Now create as many slots of $duration as will fit between $starttime and $endtime and that do not conflicts
        $countslots = 0;
        $couldnotcreateslots = '';
        $startfrom = $data->timestart;
        for ($d = 0; $d <= $fordays; $d ++){
            $starttime = $startfrom + ($d * DAYSECS);
            $eventdate = usergetdate($starttime);
            $dayofweek = $eventdate['wday'];
            if ((($dayofweek == 1) && ($data->monday == 1)) ||
                    (($dayofweek == 2) && ($data->tuesday == 1)) ||
                    (($dayofweek == 3) && ($data->wednesday == 1)) || 
                    (($dayofweek == 4) && ($data->thursday == 1)) ||
                    (($dayofweek == 5) && ($data->friday == 1)) ||
                    (($dayofweek == 6) && ($data->saturday == 1)) ||
                    (($dayofweek == 0) && ($data->sunday == 1))){
                $slot->starttime = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'], $data->starthour, $data->startminute);
                $data->timestart = $slot->starttime;
                $data->timeend = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'], $data->endhour, $data->endminute);
                
                // this corrects around midnight bug
                if ($data->timestart > $data->timeend){
                    $data->timeend += DAYSECS;
                }
                if ($data->displayfrom == 'now'){
                    $slot->hideuntil = time();
                } 
                else {
                    $slot->hideuntil = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'], 6, 0) - $data->displayfrom;
                }
                if ($data->emailfrom == 'never'){
                    $slot->emaildate = 0;
                } 
                else {
                    $slot->emaildate = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'], 0, 0) - $data->emailfrom;
                }
                // echo " generating from " .userdate($slot->starttime)." till ".userdate($data->timeend). " ";
                // echo " generating on " . ($data->timeend - $slot->starttime) / 60;
                while ($slot->starttime <= $data->timeend - $data->duration * 60) {
                    $conflicts = scheduler_get_conflicts($scheduler->id, $data->timestart, $data->timestart + $data->duration * 60, $data->teacherid, 0, SCHEDULER_ALL, false);
                    if ($conflicts) {
                        if (!$data->forcewhenoverlap){
                            print_string('conflictingslots', 'scheduler');
                            echo '<ul>';
                            foreach ($conflicts as $aConflict){
                                $sql = "
                                    SELECT
                                    c.fullname,
                                    c.shortname,
                                    sl.starttime
                                    FROM
                                    {course} c,
                                    {scheduler} s,
                                    {scheduler_slots} sl
                                    WHERE
                                    s.course = c.id AND
                                    sl.schedulerid = s.id AND
                                    sl.id = {$aConflict->id}
                                    ";
                                $conflictinfo = $DB->get_record_sql($sql);
                                echo '<li> ' . userdate($conflictinfo->starttime) . ' ' . usertime($conflictinfo->starttime) . ' ' . get_string('incourse', 'scheduler') . ': ' . $conflictinfo->shortname . ' - ' . $conflictinfo->fullname . "</li>\n";
                            }
                            echo '</ul><br/>';
                        }
                        else{ // we force, so delete all conflicting before inserting
                            foreach($conflicts as $conflict){
                                scheduler_delete_slot($conflict->id);
                            }
                        }
                    } 
                    else {
                        $DB->insert_record('scheduler_slots', $slot, false);
                        $countslots++;
                    }
                    $slot->starttime += $data->duration * 60;
                    $data->timestart += $data->duration * 60;
                }
            }
        }
        echo $OUTPUT->heading(get_string('slotsadded', 'scheduler', $countslots));
        break;
    }
    /************************************ Deleting a slot ***********************************************/
    case 'deleteslot': {
        $slotid = required_param('slotid', PARAM_INT);
        
        scheduler_delete_slot($slotid, $scheduler);
        break;
    }
    /************************************ Deleting multiple slots ***********************************************/
    case 'deleteslots': {
        $slotids = required_param('items', PARAM_RAW);
        $slots = explode(",", $slotids);
        foreach($slots as $aSlotId){
            scheduler_delete_slot($aSlotId, $scheduler);
        }
        break;
    }
    /************************************ Students were seen ***************************************************/
    case 'saveseen':{
        // get required param
        $slotid = required_param('slotid', PARAM_INT);
        $seen = optional_param_array('seen', array(), PARAM_INT);
        
        $appointments = scheduler_get_appointments($slotid);
        if (is_array($seen)){
            foreach($appointments as $anAppointment){
                $anAppointment->attended = (in_array($anAppointment->id, $seen)) ? 1 : 0 ;
                $anAppointment->timemodified = time();
                $anAppointment->appointmentnote = $anAppointment->appointmentnote;
                $DB->update_record('scheduler_appointment', $anAppointment);
            }
        }
        
        $slot = $DB->get_record('scheduler_slots', array('id'=>$slotid));
        scheduler_events_update($slot, $course);
        break;
    }
    /************************************ Revoking all appointments to a slot ***************************************/
    case 'revokeall': {
        $slotid = required_param('slotid', PARAM_INT);
        
        if ($slot = $DB->get_record('scheduler_slots', array('id' => $slotid))){
            // unassign student to the slot
            $oldstudents = $DB->get_records('scheduler_appointment', array('slotid' => $slot->id), '', 'id,studentid');
            
            if ($oldstudents){            
                foreach($oldstudents as $oldstudent){
                    scheduler_delete_appointment($oldstudent->id, $slot, $scheduler);
                }
            }
            
            // delete subsequent event
            scheduler_delete_calendar_events($slot);
            
            // notify student
            if ($scheduler->allownotifications && $oldstudents){
                foreach($oldstudents as $oldstudent){
                    include_once($CFG->dirroot.'/mod/scheduler/mailtemplatelib.php');
                    
                    $student = $DB->get_record('user', array('id'=>$oldstudent->studentid));
                    $teacher = $DB->get_record('user', array('id'=>$slot->teacherid));
                    
                    $vars = scheduler_get_mail_variables($scheduler, $slot, $teacher, $student);
                    scheduler_send_email_from_template($student, $teacher, $COURSE, 'cancelledbyteacher', 'teachercancelled', $vars, 'scheduler');
                }
            }
            
            if (!$slot->reuse and $slot->starttime > time() - $scheduler->reuseguardtime){
                $DB->delete_records('scheduler_slots', array('id'=>$slot->id));
            }
        }
        break;
    }
    
    /************************************ Toggling to unlimited group ***************************************/
    case 'allowgroup':{
        $slotid = required_param('slotid', PARAM_INT);
        $slot = new stdClass();
        $slot->id = $slotid;
        $slot->exclusivity = 0;
        $DB->update_record('scheduler_slots', $slot);
        break;
    }
    
    /************************************ Toggling to single student ******************************************/
    case 'forbidgroup':{
        $slotid = required_param('slotid', PARAM_INT);
        $slot = new stdClass();
        $slot->id = $slotid;
        $slot->exclusivity = 1;
        $DB->update_record('scheduler_slots', $slot);
        break;
    }
    
    /************************************ Toggling reuse on ***************************************/
    case 'reuse':{
        $slotid = required_param('slotid', PARAM_INT);
        $slot = new stdClass();
        $slot->id = $slotid;
        $slot->reuse = 1;
        $DB->update_record('scheduler_slots', $slot);
        break;
    }
    
    /************************************ Toggling reuse off ***************************************/
    case 'unreuse':{
        $slotid = required_param('slotid', PARAM_INT);
        $slot = new stdClass();
        $slot->id = $slotid;
        $slot->reuse = 0;
        $DB->update_record('scheduler_slots', $slot);
        break;
    }
    
    /************************************ Deleting all slots ***************************************************/
    case 'deleteall':{
        if ($slots = $DB->get_records('scheduler_slots', array('schedulerid' => $cm->instance))){
            foreach($slots as $aSlot){
                scheduler_delete_calendar_events($aSlot);
            }
            list($usql, $params) = $DB->get_in_or_equal(array_keys($slots));
            $DB->delete_records_select('scheduler_appointment', " slotid $usql ", $params);
            $DB->delete_records('scheduler_slots', array('schedulerid' => $cm->instance));
            unset($slots);
            scheduler_update_grades($scheduler);            
        }       
        break;
    }
    /************************************ Deleting unused slots *************************************************/
    // MUST STAY HERE, JUST BEFORE deleteallunused
    case 'deleteunused':{
        $teacherClause = " AND s.teacherid = {$USER->id} ";
    }
    /************************************ Deleting unused slots (all teachers) ************************************/
    case 'deleteallunused': {
        if (!isset($teacherClause)) $teacherClause = '';
        if (has_capability('mod/scheduler:manageallappointments', $context)){
            $sql = "
                SELECT
                s.id,
                s.id
                FROM
                {scheduler_slots} s
                LEFT JOIN
                {scheduler_appointment} a
                ON
                s.id = a.slotid
                WHERE
                s.schedulerid = ? AND a.studentid IS NULL
                {$teacherClause}
                ";
            if ($unappointed = $DB->get_records_sql($sql, array($scheduler->id))) {
                list($usql, $params) = $DB->get_in_or_equal(array_keys($unappointed));
                $DB->delete_records_select('scheduler_slots', "schedulerid = $cm->instance AND id $usql ", $params);
            }
        }
        break;
    }
    /************************************ Deleting current teacher's slots ***************************************/
    case 'deleteonlymine': {
        if ($slots = $DB->get_records_select('scheduler_slots', "schedulerid = {$cm->instance} AND teacherid = {$USER->id}", null, '', 'id,id')) {
            foreach($slots as $aSlot){
                scheduler_delete_calendar_events($aSlot);
            }
            $DB->delete_records('scheduler_slots', array('schedulerid'=>$cm->instance, 'teacherid'=>$USER->id));
            $slotList = implode(',', array_keys($slots));
            $DB->delete_records_select('scheduler_appointment', "slotid IN ($slotList)");
            unset($slots);
            scheduler_update_grades($scheduler);
        }
        break;
    }
}

/*************************************************************************************************************/
?>