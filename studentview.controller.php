<?php

/**
 * Controller for student view
 * 
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/scheduler/mailtemplatelib.php');


/************************************************ Saving choice ************************************************/
if ($action == 'savechoice') {
    // get parameters
    $slotid = optional_param('slotid', '', PARAM_INT);
    $appointgroup = optional_param('appointgroup', 0, PARAM_INT);
    // $notes = optional_param('notes', '', PARAM_TEXT);
    
    if (!$slotid) {
        notice(get_string('notselected', 'scheduler'), "view.php?id={$cm->id}");
    }
    
    if (!$slot = $DB->get_record('scheduler_slots', array('id' => $slotid))) {
        print_error('errorinvalidslot', 'scheduler');
    }
    
    
    $available = scheduler_get_appointments($slotid);
    $consumed = ($available) ? count($available) : 0 ;
    
    // if slot is already overcrowded
    if ($slot->exclusivity > 0 && $slot->exclusivity <= $consumed) {
        if ($updating = $DB->count_records('scheduler_appointment', array('slotid' => $slot->id, 'studentid' => $USER->id))) {
            $message = get_string('alreadyappointed', 'scheduler');
        } else {
            $message = get_string('slot_is_just_in_use', 'scheduler');
        }
        echo $OUTPUT->box_start('error');
        echo $message;
        echo $OUTPUT->continue_button("{$CFG->wwwroot}/mod/scheduler/view.php?id={$cm->id}");
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer($course);
        exit();
    }
    
    /// If we are scheduling a full group we must discard all pending appointments of other participants of the scheduled group
    /// just add the list of other students for searching slots to delete
    if ($appointgroup){
        if (!function_exists('build_navigation')){
            // we are still in 1.8
            $oldslotownersarray = groups_get_members($appointgroup, 'student');
        } else {
            // we are still in 1.8
            $oldslotownersarray = groups_get_members($appointgroup);
        }
        // special hack for 1.8 / 1.9 compatibility for groups_get_members()
        foreach($oldslotownersarray as $oldslotownermember){
            if (is_numeric($oldslotownermember)){
                // we are in 1.8
                if (has_capability("mod/scheduler:appoint", $context, $oldslotownermember)){
                    $oldslotowners[] = $oldslotownermember;
                }
            } else {
                // we are in 1.9
                if (has_capability("mod/scheduler:appoint", $context, $oldslotownermember->id)){
                    $oldslotowners[] = $oldslotownermember->id;
                }
            }
        }
    } else {
        // single user appointment : get current user in
        $oldslotowners[] = $USER->id;
    }
    $oldslotownerlist = implode("','", $oldslotowners);
    
    /// cleans up old slots if not attended (attended are definitive results, with grades)
    $sql = "
        SELECT 
        s.*,
        a.id as appointmentid
        FROM 
        {scheduler_slots} AS s,
        {scheduler_appointment} AS a 
        WHERE 
        s.id = a.slotid AND
        s.schedulerid = '{$slot->schedulerid}' AND 
        a.studentid IN ('$oldslotownerlist') AND
        a.attended = 0
        ";
    if ($scheduler->schedulermode == 'onetime'){
        $sql .= " AND s.starttime > ".time();
    }
    if ($oldappointments = $DB->get_records_sql($sql)){
        foreach($oldappointments as $oldappointment){
            
            $oldappid  = $oldappointment->appointmentid;
            $oldslotid = $oldappointment->id;
            
            // prepare notification e-mail first - slot might be deleted if it's volatile 
            if ($scheduler->allownotifications) {
                $student = $DB->get_record('user', array('id'=>$USER->id));
                $teacher = $DB->get_record('user', array('id'=>$oldappointment->teacherid));
                $vars = scheduler_get_mail_variables($scheduler,$oldappointment,$teacher,$student);
            }
            
            // reload old slot
            $oldslot = $DB->get_record('scheduler_slots', array('id'=>$oldslotid));
            // delete the appointment (and possibly the slot)
            scheduler_delete_appointment($oldappid, $oldslot, $scheduler);
            
            // notify teacher
            if ($scheduler->allownotifications){
                scheduler_send_email_from_template($teacher, $student, $course, 'cancelledbystudent', 'cancelled', $vars, 'scheduler');
            }
            
            // delete all calendar events for that slot
            scheduler_delete_calendar_events($oldappointment);
            // renew all calendar events as some appointments may be left for other students
            scheduler_add_update_calendar_events($oldappointment, $course);
        }
    }
    
    
    $newslot = $DB->get_record('scheduler_slots', array('id'=>$slotid));
    
    /// create new appointment and add it for each member of the group
    foreach($oldslotowners as $astudentid){
    	$appointment = new stdClass();
        $appointment->slotid = $slotid;
        // $appointment->notes = $notes;
        $appointment->studentid = $astudentid;
        $appointment->attended = 0;
        $appointment->timecreated = time();
        $appointment->timemodified = time();
        $DB->insert_record('scheduler_appointment', $appointment);
        scheduler_update_grades($scheduler, $astudentid);
        scheduler_events_update($newslot, $course);
        
        // notify teacher
        if ($scheduler->allownotifications){
            $student = $DB->get_record('user', array('id' => $appointment->studentid));
            $teacher = $DB->get_record('user', array('id' => $slot->teacherid));
            $vars = scheduler_get_mail_variables($scheduler,$newslot,$teacher,$student);
            scheduler_send_email_from_template($teacher, $student, $course, 'newappointment', 'applied', $vars, 'scheduler');
        }
    }
}
// *********************************** Disengage alone from the slot ******************************/
if ($action == 'disengage') {
    $where = 'studentid = :studentid AND attended = 0 AND ' .
             'EXISTS(SELECT 1 FROM {scheduler_slots} sl WHERE sl.id = slotid AND sl.schedulerid = :scheduler )';
    $params = array('scheduler'=>$scheduler->id, 'studentid'=>$USER->id);
    $appointments = $DB->get_records_select('scheduler_appointment', $where, $params);
    if ($appointments){
        foreach($appointments as $appointment){
            $oldslot = $DB->get_record('scheduler_slots', array('id' => $appointment->slotid));
            scheduler_delete_appointment($appointment->id, $oldslot, $scheduler);
            
            // notify teacher
            if ($scheduler->allownotifications){
                $student = $DB->get_record('user', array('id' => $USER->id));
                $teacher = $DB->get_record('user', array('id' => $oldslot->teacherid));
                $vars = scheduler_get_mail_variables($scheduler,$oldslot,$teacher,$student);
                scheduler_send_email_from_template($teacher, $student, $COURSE, 'cancelledbystudent', 'cancelled', $vars, 'scheduler');
            }                    
        }
        
        // delete calendar events for that slot
        scheduler_delete_calendar_events($oldslot);  
        // renew all calendar events as some appointments may be left for other students
        scheduler_add_update_calendar_events($oldslot, $course);
    }
}

?>