<?php

/**
 * General library for the scheduler module.
 * 
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/customlib.php');


/**
 * Parameter $local added by power-web.at
 * When local Date is needed the $local Param must be set to 1 
 * @param int $date a timestamp
 * @param int $local
 * @todo check consistence
 * @return string printable date
 */
function scheduler_userdate($date, $local=0) {
    if ($date == 0) {
        return '';
    } else {
        return userdate($date, get_string('strftimedaydate'));
    }
}

/**
 * Parameter $local added by power-web.at 
 * When local Time is needed the $local Param must be set to 1 
 * @param int $date a timestamp
 * @param int $local
 * @todo check consistence
 * @return string printable time
 */
function scheduler_usertime($date, $local=0) {
    if ($date == 0) {
        return '';
    } else {
        $timeformat = get_user_preferences('calendar_timeformat');//get user config
        if(empty($timeformat)){
            $timeformat = get_config(NULL,'calendar_site_timeformat');//get calendar config	if above not exist
        }
        if(empty($timeformat)){
            $timeformat = get_string('strftimetime');//get locale default format if both above not exist
        }
        return userdate($date, $timeformat);    
    }
}

/**
 * get list of attendants for slot form
 * @param int $cmid the course module
 * @return array of moodle user records
 */
function scheduler_get_attendants($cmid){
    $context = context_module::instance($cmid);
    $attendants = get_users_by_capability ($context, 'mod/scheduler:attend', 'u.id,lastname,firstname,email,picture', 'lastname, firstname', '', '', '', '', false, false, false);
    return $attendants;
}

/**
 * get list of possible attendees (i.e., users that can make an appointment)
 * @param object $cm the course module
 * @param $groups - single group or array of groups - only return
 *                  users who are in one of these group(s).
 * @return array of moodle user records
 */
function scheduler_get_possible_attendees($cm, $groups=''){

    $context = context_module::instance($cm->id);
    $attendees = get_users_by_capability($context, 'mod/scheduler:appoint', '', 'lastname, firstname', '', '', $groups, '', false, false, false);

    return $attendees;
}

/**
 * Returns an array of slots that would overlap with this one.
 * @param int $schedulerid the current activity module id
 * @param int $starttimethe start of time slot as a timestamp
 * @param int $endtime end of time slot as a timestamp
 * @param int $teacher if not null, the id of the teacher constraint, 0 otherwise standas for "all teachers"
 * @param int $others selects where to search for conflicts, [SCHEDULER_SELF, SCHEDULER_OTHERS, SCHEDULER_ALL]
 * @param boolean $careexclusive if false, conflict will consider all slots wether exlusive or not. Use it for testing if user is appointed in the given scope.
 * @uses $CFG
 * @uses $DB
 * @return array array of conflicting slots
 */
function scheduler_get_conflicts($schedulerid, $starttime, $endtime, $teacher=0, $student=0, $others=SCHEDULER_SELF, $careexclusive=true) {
    global $CFG, $DB;
    
    switch ($others){
        case SCHEDULER_SELF:
            $schedulerScope = "s.schedulerid = {$schedulerid} AND ";
            break;
        case SCHEDULER_OTHERS:
            $schedulerScope = "s.schedulerid != {$schedulerid} AND ";
            break;
        default:
            $schedulerScope = '';
    }
    $teacherScope = ($teacher != 0) ? "s.teacherid = {$teacher} AND " : '' ;
    $studentJoin = ($student != 0) ? "JOIN {scheduler_appointment} a ON a.slotid = s.id AND a.studentid = {$student} " : '' ;
    $exclusiveClause = ($careexclusive) ? "exclusivity != 0 AND " : '' ;
	$timeClause = "( (s.starttime <= {$starttime} AND s.starttime + s.duration * 60 > {$starttime}) OR ".
        		  "  (s.starttime < {$endtime} AND s.starttime + s.duration * 60 >= {$endtime}) OR ".
        		  "  (s.starttime >= {$starttime} AND s.starttime + s.duration * 60 <= {$endtime}) ) ";

    $sql = 'SELECT s.* from {scheduler_slots} s '.$studentJoin.' WHERE '.
    		 $schedulerScope.$teacherScope.$exclusiveClause.$timeClause;
        
    $conflicting = $DB->get_records_sql($sql);
    
    return $conflicting;
}

/**
 * Returns count of slots that would overlap with this
 * use it as a test function before toggling to exclusive
 * @param int $schedulerid the actual scheduler instance
 * @param int $starttime the starttime identifying the slot
 * @param int $endtime the endtime of the period
 * @param int $teacher the teacher constraint, if null stands for "all teachers"
 * @return int the number of compatible slots
 * @uses $CFG
 * @uses $DB
 */
function scheduler_get_consumed($schedulerid, $starttime, $endtime, $teacherid=0) {
    global $CFG, $DB;
    
    $teacherScope = ($teacherid != 0) ? " teacherid = '{$teacherid}' AND " : '' ;
    $sql = "
        SELECT
        COUNT(*)
        FROM
        {scheduler_slots} s,
        {scheduler_appointment} a
        WHERE
        a.slotid = s.id AND
        schedulerid = {$schedulerid} AND
        {$teacherScope}
        ( (s.starttime <= {$starttime} AND
        {$starttime} < s.starttime + s.duration * 60) OR
        (s.starttime < {$endtime} AND
        {$endtime} <= s.starttime + s.duration * 60) OR
        (s.starttime >= {$starttime} AND
        s.starttime + s.duration * 60 <= {$endtime}) )
        ";
    $count = $DB->count_records_sql($sql, NULL);
    return $count;
}

/**
 * Returns the known exclusivity at that time
 * @param int $schedulerid the actual scheduler instance
 * @param int $starttime the starttime identifying the slot
 * @return int the exclusivity value
 * @uses $CFG
 * @uses $DB
 */
function scheduler_get_exclusivity($schedulerid, $starttime) {
    global $CFG, $DB;
    
    $sql = '
        SELECT
        exclusivity
        FROM
        {scheduler_slots} s
        WHERE
        schedulerid = ? AND
        s.starttime <= ? AND
        ? <= s.starttime + s.duration * 60
        ';
    return $DB->get_field_sql($sql, array($schedulerid, $starttime, $starttime));
}

/**
 * retreives the unappointed slots
 * @param int $schedulerid
 * @uses $CFG
 * @uses $DB
 */
function scheduler_get_unappointed_slots($schedulerid){
    global $CFG, $DB;
    
    $sql = '
        SELECT
        s.*,
        MAX(a.studentid) AS appointed
        FROM
        {scheduler_slots} s
        LEFT JOIN
        {scheduler_appointment} a
        ON
        a.slotid = s.id
        WHERE
        s.schedulerid = ?
        GROUP BY
        s.id
        HAVING
        appointed = 0 OR appointed IS NULL
        ORDER BY
        s.starttime ASC
        ';
    $recs = $DB->get_records_sql($sql, array($schedulerid));
    return $recs;
}

/**
 * retreives the available slots in several situations with a complex query
 * @param int $studentid
 * @param int $schedulerid
 * @param boolean $studentside changes query if we are getting slots in student context
 * @uses $CFG
 * @uses $DB
 */
function scheduler_get_available_slots($studentid, $schedulerid, $studentside=false){
    global $CFG, $DB;
    
    // more compatible tryout
    $slots = $DB->get_records('scheduler_slots', array('schedulerid' => $schedulerid), 'starttime');
    $retainedslots = array();
    if ($slots){
        foreach($slots as $slot){
            $slot->population = $DB->count_records('scheduler_appointment', array('slotid' => $slot->id));
            $slot->appointed = ($slot->population > 0);
            $slot->attended = $DB->record_exists('scheduler_appointment', array('slotid' => $slot->id, 'attended' => 1));
            if ($studentside){
                $slot->appointedbyme = $DB->record_exists('scheduler_appointment', array('slotid' => $slot->id, 'studentid' => $studentid));
                if ($slot->appointedbyme) {
                    $retainedslots[] = $slot;
                    continue;
                }
            }
            // both side, slot is not complete
            if ($slot->exclusivity == 0 or ($slot->exclusivity > 0 and $slot->population < $slot->exclusivity)){
                $retainedslots[] = $slot;
                continue;
            }
        }
    }
    
    return $retainedslots;
}

/**
 * checks if user has an appointment in this scheduler
 * @param object $userlist
 * @param object $scheduler
 * @param boolean $student, if true, is a student, a teacher otherwise
 * @param boolean $unattended, if true, only checks for unattended slots
 * @param string $otherthan giving a slotid, excludes this slot from the search
 * @uses $CFG
 * @uses $DB
 * @return the count of records
 */
function scheduler_has_slot($userlist, &$scheduler, $student=true, $unattended = false, $otherthan = 0){
    global $CFG, $DB;
    
    $userlist = str_replace(',', "','", $userlist);
    
    $unattendedClause = ($unattended) ? ' AND a.attended = 0 ' : '' ;
    $otherthanClause = ($otherthan) ? " AND a.slotid != $otherthan " : '' ;
    
    if ($student){
        $sql = "
            SELECT
            COUNT(*)
            FROM
            {scheduler_slots} s,
            {scheduler_appointment} a
            WHERE
            a.slotid = s.id AND
            s.schedulerid = ? AND
            a.studentid IN ('{$userlist}')
            $unattendedClause
            $otherthanClause
            ";
        return $DB->count_records_sql($sql, array($scheduler->id));
    } else {
        return $DB->count_records('scheduler_slots', array('teacherid' => $userlist, 'schedulerid' => $scheduler->id));
    }
}

/**
 * returns an array of appointed user records for a certain slot.
 * @param int $slotid
 * @uses $CFG
 * @uses $DB
 * @return an array of users
 */
function scheduler_get_appointed($slotid){
    global $CFG, $DB;
    
    $sql = "
        SELECT
        u.*
        FROM
        {user} u,
        {scheduler_appointment} a
        WHERE
        u.id = a.studentid AND
        a.slotid = ?
        ";
    return $DB->get_records_sql($sql, array($slotid));
}

/**
 * fully deletes a slot with all dependancies
 * @param int slotid
 * @param stdClass $scheduler (optional)
 * @uses $DB
 */
function scheduler_delete_slot($slotid, $scheduler=null){
    global $DB;
    
    if ($slot = $DB->get_record('scheduler_slots', array('id' => $slotid))) {
        scheduler_delete_calendar_events($slot);
    }
    $DB->delete_records('scheduler_slots', array('id' => $slotid));
    $DB->delete_records('scheduler_appointment', array('slotid' => $slotid));
    
    if ($slot) {
    	if (!$scheduler){ // fetch optimization
	        $scheduler = $DB->get_record('scheduler', array('id' => $slot->schedulerid));
    	}
    	scheduler_update_grades($scheduler); // generous, but works
    }
    
}


/**
 * get appointment records for a slot
 * @param int $slotid
 * @return an array of appointments
 * @uses $CFG
 * @uses $DB
 */
function scheduler_get_appointments($slotid){
    global $CFG, $DB;
    
    $apps = $DB->get_records('scheduler_appointment', array('slotid' => $slotid));
    
    return $apps;
}

/**
 * a high level api function for deleting an appointement, and do
 * what ever is needed
 * @param int $appointmentid
 * @param object $slot
 * @param object $scheduler
 * @uses $DB
 */
function scheduler_delete_appointment($appointmentid, $slot=null, $scheduler=null){
    global $DB;
    
    if (!$oldrecord = $DB->get_record('scheduler_appointment', array('id' => $appointmentid))) return ;
    
    if (!$slot){ // fetch optimization
        $slot = $DB->get_record('scheduler_slots', array('id' => $oldrecord->slotid));
    }
    if($slot){
        // delete appointment
        if (!$DB->delete_records('scheduler_appointment', array('id' => $appointmentid))) {
            print_error('Couldn\'t delete old choice from database');
        }
        if (!$scheduler){ // fetch optimization
            $scheduler = $DB->get_record('scheduler', array('id' => $slot->schedulerid));
        }
        scheduler_update_grades($scheduler, $oldrecord->studentid);        
        // not reusable slot. Delete it if slot is too near and has no more appointments.
        if ($slot->reuse == 0) {
            $consumed = scheduler_get_consumed($slot->schedulerid, $slot->starttime, $slot->starttime + $slot->duration * 60);
            if (!$consumed){
                if (time() > 0 ) { //  ULPGC ecastro, volatiles are deleted always   $slot->starttime - $scheduler->reuseguardtime * 3600){
                    if (!$DB->delete_records('scheduler_slots', array('id' => $slot->id))) {
                        print_error('Couldn\'t delete old choice from database');
                    }
                }
            }
        }
    }
}

/**
 * get the last considered location in this scheduler
 * @param reference $scheduler
 * @uses $USER
 * @uses $DB
 * @return the last known location for the current user (teacher)
 */
function scheduler_get_last_location(&$scheduler){
    global $USER, $DB;
    
    $lastlocation = '';
    $select = 'SELECT appointmentlocation FROM {scheduler_slots} WHERE schedulerid = ? AND teacherid = ? ORDER BY timemodified DESC';
    $lastlocation = $DB->get_field_sql($select, array($scheduler->id, $USER->id), IGNORE_MULTIPLE);
    return $lastlocation;
}

/**
 * frees all slots unapppointed that are in the past
 * @param int $schedulerid
 * @param int $now give a date reference for measuring the "past" ! If 0, uses current time
 * @uses $CFG
 * @uses $DB
 * @return void
 */
function scheduler_free_late_unused_slots($schedulerid, $now=0){
    global $CFG, $DB;
    
    if(!$now) {
        $now = time();
    }
    $sql = '
        SELECT DISTINCT
        s.id
        FROM
        {scheduler_slots} s
        LEFT JOIN
        {scheduler_appointment} a
        ON
        s.id = a.slotid
        WHERE
        a.studentid IS NULL AND
        s.schedulerid = ? AND
        starttime < ?
        ';
    $to_delete = $DB->get_records_sql($sql, array($schedulerid, $now));
    if ($to_delete){
        list($usql, $params) = $DB->get_in_or_equal(array_keys($to_delete));
        $DB->delete_records_select('scheduler_slots', " id $usql ", $params);
    }
}


/// Events related functions


// TODO: The following is not yet converted
 /**
  * Updates events in the calendar to the information provided.
  * If the events do not yet exist it creates them.
  * The only argument this function requires is the complete database record of a scheduler slot.
  * The course parameter should be the full record of the course for this scheduler so the 
  * teacher-title and student-title can be determined.
  * @param object $slot the slot instance
  * @param object $course the actual course
  */
function scheduler_add_update_calendar_events($slot, $course) {    
    
    global $DB;
    
    //firstly, collect up the information we'll need no matter what.
    $eventDuration = ($slot->duration) * 60;
    $eventStartTime = $slot->starttime;
    
    // get all students attached to that slot
    $appointments = $DB->get_records('scheduler_appointment', array('slotid'=>$slot->id), '', 'studentid,studentid');
    
    // nothing to do
    if (!$appointments) return;
    
    $studentids = array_keys($appointments);
    
    $teacher = $DB->get_record('user', array('id'=>$slot->teacherid));
    $students = $DB->get_records_list('user', 'id', $studentids);
    
    $schedulerDescription = $DB->get_field('scheduler', 'intro', array('id' => $slot->schedulerid));
    $schedulerName = $DB->get_field('scheduler', 'name', array('id' => $slot->schedulerid));
    $teacherEventDescription = "$schedulerName<br/><br/>$schedulerDescription";
    
    $studentEventDescription = $teacherEventDescription;
    
    //the eventtype field stores a code that is used to relate calendar events with the slots that 'own' them.
    //the code is SSstu (for a student event) or SSsup (for a teacher event).
    //then, the id of the scheduler slot that it belongs to.
    //finally, the courseID. I can't remember why, TODO: remember the good reason.
    //all in a colon delimited string. This will run into problems when the IDs of slots and courses are bigger than 7 digits in length...    
    $teacherEventType = "SSsup:{$slot->id}:{$course->id}";
    $studentEventType = "SSstu:{$slot->id}:{$course->id}";
    
    $studentNames = array();
    
    foreach($students as $student){
        $studentNames[] = fullname($student);
        $studentEventName = get_string('meetingwith', 'scheduler').' '.get_string('teacher','scheduler').', '.fullname($teacher);
        $studentEventName = shorten_text($studentEventName, 200);
        
        //firstly, deal with the student's event
        //if it exists, update it, else create a new one.

		$studentEvent = scheduler_get_student_event($slot, $student->id);
        
        if ($studentEvent) {
            $studentEvent->name = $studentEventName;
            $studentEvent->description = $studentEventDescription;
            $studentEvent->format = 1;
            $studentEvent->userid = $student->id;
            $studentEvent->timemodified = time();
            // $studentEvent->modulename = 'scheduler'; // Issue on delete/edit link
            $studentEvent->instance = $slot->schedulerid;
            $studentEvent->timestart = $eventStartTime;
            $studentEvent->timeduration = $eventDuration;
            $studentEvent->visible = 1;
            $studentEvent->eventtype = $studentEventType;
            $DB->update_record('event', $studentEvent);
        } else {
            $studentEvent = new stdClass();
            $studentEvent->name = $studentEventName;
            $studentEvent->description = $studentEventDescription;
            $studentEvent->format = 1;
            $studentEvent->userid = $student->id;
            $studentEvent->timemodified = time();
            // $studentEvent->modulename = 'scheduler';
            $studentEvent->instance = $slot->schedulerid;
            $studentEvent->timestart = $eventStartTime;
            $studentEvent->timeduration = $eventDuration;
            $studentEvent->visible = 1;
            $studentEvent->id = null;
            $studentEvent->eventtype = $studentEventType;
            // This should be changed to use add_event()
            $DB->insert_record('event', $studentEvent);
        }
        
    }
    
    if (count($studentNames) > 1){
        $teacherEventName = get_string('meetingwithplural', 'scheduler').' '.get_string('students', 'scheduler').', '.implode(', ', $studentNames);
    } else {
        $teacherEventName = get_string('meetingwith', 'scheduler').' '.get_string('student', 'scheduler').', '.$studentNames[0];
    }
    $teacherEventName = shorten_text($teacherEventName, 200);
	$teacherEvent = scheduler_get_teacher_event($slot);
    if ($teacherEvent) {
        $teacherEvent->name = $teacherEventName;
        $teacherEvent->description = $teacherEventDescription;
        $teacherEvent->format = 1;
        $teacherEvent->userid = $slot->teacherid;
        $teacherEvent->timemodified = time();
        // $teacherEvent->modulename = 'scheduler';
        $teacherEvent->instance = $slot->schedulerid;
        $teacherEvent->timestart = $eventStartTime;
        $teacherEvent->timeduration = $eventDuration;
        $teacherEvent->visible = 1;
        $teacherEvent->eventtype = $teacherEventType;
        $DB->update_record('event', $teacherEvent);
    } else {
        $teacherEvent = new stdClass();
        $teacherEvent->name = $teacherEventName;
        $teacherEvent->description = $teacherEventDescription;
        $teacherEvent->format = 1;
        $teacherEvent->userid = $slot->teacherid;
        $teacherEvent->instance = $slot->schedulerid;
        $teacherEvent->timemodified = time();
        // $teacherEvent->modulename = 'scheduler';
        $teacherEvent->timestart = $eventStartTime;
        $teacherEvent->timeduration = $eventDuration;
        $teacherEvent->visible = 1;
        $teacherEvent->id = null;
        $teacherEvent->eventtype = $teacherEventType;
        $DB->insert_record('event', $teacherEvent);
    }
}


/**
 * Will delete calendar events for a given scheduler slot, and not complain if the record does not exist.
 * The only argument this function requires is the complete database record of a scheduler slot.
 * @param object $slot the slot instance
 * @uses $DB 
 * @return boolean true if success, false otherwise
 */
function scheduler_delete_calendar_events($slot) {
    global $DB;
    
    $scheduler = $DB->get_record('scheduler', array('id'=>$slot->schedulerid));
    
    if (!$scheduler) return false ;
    
    $teacherEventType = "SSsup:{$slot->id}:{$scheduler->course}";
    $studentEventType = "SSstu:{$slot->id}:{$scheduler->course}";
    
    $teacherDeletionSuccess = $DB->delete_records('event', array('eventtype'=>$teacherEventType));
    $studentDeletionSuccess = $DB->delete_records('event', array('eventtype'=>$studentEventType));
    
    return ($teacherDeletionSuccess && $studentDeletionSuccess);
    //this return may not be meaningful if the delete records functions do not return anything meaningful.
}

/**
 * This function decides if a slot should have calendar events associated with it,
 * and calls the update/delete functions if neccessary.
 * it must be passed the complete scheduler_slots record to function correctly.
 * The course parameter should be the record that belongs to the course for this scheduler.
 * @param object $slot the slot instance
 * @param object $course the actual course
 * @uses $DB
 */
function scheduler_events_update($slot, $course) {
    global $DB;
    
    $slotDoesntHaveAStudent = !$DB->count_records('scheduler_appointment', array('slotid' => $slot->id));
    $slotWasAttended = $DB->count_records('scheduler_appointment', array('slotid' => $slot->id, 'attended' => 1));
    
    if ($slotDoesntHaveAStudent || $slotWasAttended) {
        scheduler_delete_calendar_events($slot);
    }
    else {
        scheduler_add_update_calendar_events($slot, $course);
    }
}

/**
 * This function gets the calendar entry of the teacher relating to a slot.
 * If none is found, the return value is false.
 * 
 * @param object $slot the slot instance
 * @uses $DB
 * @return stdClass the calendar event of the teacher
 */
function scheduler_get_teacher_event($slot) {
    global $DB;
    
    //first we need to know the course that the scheduler belongs to...
    $courseid = $DB->get_field('scheduler', 'course', array('id' => $slot->schedulerid), MUST_EXIST);
    
    //now try to fetch the event records...
    $teacherEventType = "SSsup:{$slot->id}:{$courseid}";
    
    $event = $DB->get_record('event', array('eventtype' => $teacherEventType), '*', IGNORE_MISSING);
    
	return $event; 
}

/**
 * This function gets the calendar entry of a student relating to a slot.
 * If none is found, the return value is false.
 * 
 * @param object $slot the slot instance
 * @param int $studentid the id number of the student record
 * @uses $DB
 * @return stdClass the calendar event of the student
 */
function scheduler_get_student_event($slot, $studentid) {
    global $DB;
    
    //first we need to know the course that the scheduler belongs to...
    $courseid = $DB->get_field('scheduler', 'course', array('id' => $slot->schedulerid), MUST_EXIST);
    
    //now try to fetch the event records...
    $studentEventType = "SSstu:{$slot->id}:{$courseid}";
    
    $event = $DB->get_record('event', array('eventtype' => $studentEventType, 'userid'=>$studentid), '*', IGNORE_MISSING);
	return $event; 
}


/**
 * a utility function for formatting grades for display
 * @param reference $scheduler
 * @param string $grade the grade to be displayed
 * @param boolean $short formats the grade in short form (result empty if grading is
 * not used, or no grade is available; parantheses are put around the grade if it is present)
 * @return string the formatted grade
 */
function scheduler_format_grade(&$scheduler, $grade, $short=false){
    
    global $DB;
    
    $result = '';
    if ($scheduler->scale == 0 || is_null($grade) ){
        // scheduler doesn't allow grading, or no grade entered
        if (!$short) {
            $result = get_string('nograde');
        }
    }
    else {
        if ($scheduler->scale > 0) {
            // numeric grades
            $result .= $grade;
            if (strlen($grade)>0){
                $result .=  '/' . $scheduler->scale;
            }
        }
        else{
            // grade on scale
            if ($grade > 0) {
                $scaleid = - ($scheduler->scale);
                if ($scale = $DB->get_record('scale', array('id'=>$scaleid))) {
                    $levels = explode(',',$scale->scale);
                    if ($grade <= count($levels)) {
                    	$result .= $levels[$grade-1];
                    }
                }
            }
        }
        if ($short && (strlen($result)>0)) {
            $result = '('.$result.')';
        }
    }
    return $result;
}


/**
 * a utility function for producing grading lists (for use in formslib)
 * @param reference $scheduler
 * @return the html selection element for a grading list
 */
function scheduler_get_grading_choices(&$scheduler) {
	global $DB;
    if ($scheduler->scale > 0){
        $scalegrades = array();
        for($i = 0 ; $i <= $scheduler->scale ; $i++) {
            $scalegrades[$i] = $i; 
        }
    }
    else {
        $scaleid = - ($scheduler->scale);
        if ($scale = $DB->get_record('scale', array('id'=>$scaleid))) {
            $scalegrades = make_menu_from_list($scale->scale);
        }
    }
    return $scalegrades;
}


/**
 * a utility function for making grading lists
 * @param reference $scheduler
 * @param string $id the form field id
 * @param string $selected the selected value
 * @return the html selection element for a grading list
 */
function scheduler_make_grading_menu(&$scheduler, $id, $selected = '') {
	global $DB;
    $scalegrades = scheduler_get_grading_choices($scheduler);
    $menu = html_writer::select($scalegrades, $id, $selected);
    return $menu;
}


/**
 * Construct an array with subtitution rules for mail templates, relating to 
 * a single appointment. Any of the parameters can be null.
 * @param object $scheduler The scheduler instance
 * @param object $slot The slot data, obtained with get_record().
 * @param user $attendant A {@link $USER} object describing the attendant (teacher)
 * @param user $attendee A {@link $USER} object describing the attendee (student)
 * @return array A hash with mail template substitutions 
 */
function scheduler_get_mail_variables ($scheduler, $slot, $attendant, $attendee) {
    
    global $CFG;
    
    $vars = array();
    
    if ($scheduler) {
        $vars['MODULE']     = $scheduler->name;
        $vars['STAFFROLE']  = scheduler_get_teacher_name($scheduler);
    }
    if ($slot) {
        $vars ['DATE']     = userdate($slot->starttime,get_string('strftimedate'));
        $vars ['TIME']     = userdate($slot->starttime,get_string('strftimetime'));
        $vars ['ENDTIME']  = userdate($slot->starttime+$slot->duration*60, get_string('strftimetime'));
        $vars ['LOCATION'] = $slot->appointmentlocation;
    }
    if ($attendant) {
        $vars['ATTENDANT']     = fullname($attendant);
        $vars['ATTENDANT_URL'] = $CFG->wwwroot.'/user/view.php?id='.$attendant->id.'&course='.$scheduler->course;
    }
    if ($attendee) {
        $vars['ATTENDEE']     = fullname($attendee);
        $vars['ATTENDEE_URL'] = $CFG->wwwroot.'/user/view.php?id='.$attendee->id.'&course='.$scheduler->course;
    }
    
    return $vars;
    
}

/**
 * Prints a summary of a user in a nice little box.
 *
 * @uses $CFG
 * @uses $USER
 * @param user $user A {@link $USER} object representing a user
 * @param course $course A {@link $COURSE} object representing a course
 */
function scheduler_print_user($user, $course, $messageselect=false, $return=false) {
    
    global $CFG, $USER, $OUTPUT ;
    
    $output = '';
    
    static $string;
    static $datestring;
    static $countries;
    
    $context = context_course::instance($course->id);
    if (isset($user->context->id)) {
        $usercontext = $user->context;
    } else {
        $usercontext = context_user::instance($user->id);
    }
    
    if (empty($string)) {     // Cache all the strings for the rest of the page

        $string = new stdClass();        
        $string->email       = get_string('email');
        $string->lastaccess  = get_string('lastaccess');
        $string->activity    = get_string('activity');
        $string->loginas     = get_string('loginas');
        $string->fullprofile = get_string('fullprofile');
        $string->role        = get_string('role');
        $string->name        = get_string('name');
        $string->never       = get_string('never');
        
        $datestring = new stdClass();        
        $datestring->day     = get_string('day');
        $datestring->days    = get_string('days');
        $datestring->hour    = get_string('hour');
        $datestring->hours   = get_string('hours');
        $datestring->min     = get_string('min');
        $datestring->mins    = get_string('mins');
        $datestring->sec     = get_string('sec');
        $datestring->secs    = get_string('secs');
        $datestring->year    = get_string('year');
        $datestring->years   = get_string('years');
        
    }
    
    /// Get the hidden field list
    if (has_capability('moodle/course:viewhiddenuserfields', $context)) {
        $hiddenfields = array();
    } else {
        $hiddenfields = array_flip(explode(',', $CFG->hiddenuserfields));
    }
    
    $output .= '<table class="userinfobox">';
    $output .= '<tr>';
    $output .= '<td class="left side">';
    $output .= $OUTPUT->user_picture($user, array('size'=>100));
    $output .= '</td>';
    $output .= '<td class="content">';
    $output .= '<div class="username">'.fullname($user, has_capability('moodle/site:viewfullnames', $context)).'</div>';
    $output .= '<div class="info">';
    if (!empty($user->role) and ($user->role <> $course->teacher)) {
        $output .= $string->role .': '. $user->role .'<br />';
    }

	$extrafields = scheduler_get_user_fields($user);
	foreach ($extrafields as $field) {
        $output .= $field->title . ': ' . $field->value . '<br />';	    
	}
	
    
    if (!isset($hiddenfields['lastaccess'])) {
        if ($user->lastaccess) {
            $output .= $string->lastaccess .': '. userdate($user->lastaccess);
            $output .= '&nbsp; ('. format_time(time() - $user->lastaccess, $datestring) .')';
        } else {
            $output .= $string->lastaccess .': '. $string->never;
        }
    }
    $output .= '</div></td><td class="links">';
    //link to blogs
    if ($CFG->bloglevel > 0) {
        $output .= '<a href="'.$CFG->wwwroot.'/blog/index.php?userid='.$user->id.'">'.get_string('blogs','blog').'</a><br />';
    }
    //link to notes
    if (!empty($CFG->enablenotes) and (has_capability('moodle/notes:manage', $context) || has_capability('moodle/notes:view', $context))) {
        $output .= '<a href="'.$CFG->wwwroot.'/notes/index.php?course=' . $course->id. '&amp;user='.$user->id.'">'.get_string('notes','notes').'</a><br />';
    }
    
    if (has_capability('moodle/site:viewreports', $context) or has_capability('moodle/user:viewuseractivitiesreport', $usercontext)) {
        $output .= '<a href="'. $CFG->wwwroot .'/course/user.php?id='. $course->id .'&amp;user='. $user->id .'">'. $string->activity .'</a><br />';
    }
    $output .= '<a href="'. $CFG->wwwroot .'/user/profile.php?id='. $user->id .'">'. $string->fullprofile .'...</a>';
    
    if (!empty($messageselect)) {
        $output .= '<br /><input type="checkbox" name="user'.$user->id.'" /> ';
    }
    
    $output .= '</td></tr></table>';
    
    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}

function scheduler_get_teacher_name($scheduler) {
    $name = $scheduler->staffrolename;
    if (empty($name)) {
        $name = get_string('teacher', 'scheduler');
    }
    return $name;
}

function scheduler_group_scheduling_enabled($course, $cm) {
	global $CFG;
    $globalenable = (bool) $CFG->scheduler_groupscheduling;
    $localenable = (groupmode($course, $cm) > 0);
    return $globalenable && $localenable;
}


/**
 * adds an error css marker in case of matching error
 * @param array $errors the current error set
 * @param string $errorkey 
 */
if (!function_exists('print_error_class')){
    function print_error_class($errors, $errorkeylist){
        if ($errors){
            foreach($errors as $anError){
                if ($anError->on == '') continue;
                if (preg_match("/\\b{$anError->on}\\b/" ,$errorkeylist)){
                    echo " class=\"formerror\" ";
                    return;
                }
            }        
        }
    }
}
?>