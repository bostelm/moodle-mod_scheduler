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

require_once(dirname(__FILE__).'/model/scheduler_instance.php');
require_once(dirname(__FILE__).'/model/scheduler_slot.php');
require_once(dirname(__FILE__).'/model/scheduler_appointment.php');


/**
 * get list of attendants for slot form
 * @param int $cmid the course module
 * @return array of moodle user records
 */
function scheduler_get_attendants($cmid){
    $context = context_module::instance($cmid);
    $attendants = get_users_by_capability ($context, 'mod/scheduler:attend',
        user_picture::fields('u'), 'u.lastname, u.firstname',
        '', '', '', '', false, false, false);
    return $attendants;
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


/// Events related functions

/**
 * Will delete calendar events for a given scheduler slot, and not complain if the record does not exist.
 * The only argument this function requires is the complete database record of a scheduler slot.
 * @param object $slot the slot instance
 * @uses $DB
 * @return boolean true if success, false otherwise
 */
function scheduler_delete_calendar_events($slot) {
    global $DB;

    $scheduler = $DB->get_record('scheduler', array('id' => $slot->schedulerid));

    if (!$scheduler) return false ;

    $teacherEventType = "SSsup:{$slot->id}:{$scheduler->course}";
    $studentEventType = "SSstu:{$slot->id}:{$scheduler->course}";

    $teacherDeletionSuccess = $DB->delete_records('event', array('eventtype'=>$teacherEventType));
    $studentDeletionSuccess = $DB->delete_records('event', array('eventtype'=>$studentEventType));

    return ($teacherDeletionSuccess && $studentDeletionSuccess);
    //this return may not be meaningful if the delete records functions do not return anything meaningful.
}

/**
 * Returns a float or a string which denotes a given user's timezone.
 *
 * A float value means that a simple offset from GMT is used, while a string (it will be the name of a timezone in the database)
 * means that for this timezone there are also DST rules to be taken into account.
 * Checks various settings and picks the most dominant of those which have a value.
 *
 * This is mainly a copy of lib/moodlelib.php, function get_user_timezone(),
 * but with an additional parameter to specify a user (other than the current one)
 * for whom the setting is computed.
 *
 * @param float|int|string $tz timezone to calculate GMT time offset before
 *        calculating user timezone, 99 is default user timezone
 *        {@link http://docs.moodle.org/dev/Time_API#Timezone}
 * @return float|string
 */
function scheduler_get_user_timezone(stdClass $user, $tz = 99) {
    global $CFG;

    $timezones = array(
        $tz,
        isset($CFG->forcetimezone) ? $CFG->forcetimezone : 99,
        isset($user->timezone) ? $user->timezone : 99,
        isset($CFG->timezone) ? $CFG->timezone : 99,
        );

    $tz = 99;

    // Loop while $tz is, empty but not zero, or 99, and there is another timezone is the array.
    while (((empty($tz) && !is_numeric($tz)) || $tz == 99) && $next = each($timezones)) {
        $tz = $next['value'];
    }
    return is_numeric($tz) ? (float) $tz : $tz;
}


/**
 * Construct an array with subtitution rules for mail templates, relating to
 * a single appointment. Any of the parameters can be null.
 * @param scheduler_instance $scheduler The scheduler instance
 * @param scheduler_slot $slot The slot data as an MVC object
 * @param user $attendant A {@link $USER} object describing the attendant (teacher)
 * @param user $attendee A {@link $USER} object describing the attendee (student)
 * @param object $course A course object relating to the ontext of the message
 * @param object $recipient A {@link $USER} object describing the recipient of the message (used for determining the message language)
 * @return array A hash with mail template substitutions
 */
function scheduler_get_mail_variables (scheduler_instance $scheduler, scheduler_slot $slot, $attendant, $attendee, $course, $recipient) {

    global $CFG;

    $lang = scheduler_get_message_language($recipient, $course);
    // Force any string formatting to happen in the target language.
    $oldlang = force_current_language($lang);

    $tz = scheduler_get_user_timezone($recipient);

    $vars = array();

    if ($scheduler) {
        $vars['MODULE']     = $scheduler->name;
        $vars['STAFFROLE']  = $scheduler->get_teacher_name();
    }
    if ($slot) {
        $vars ['DATE']     = userdate($slot->starttime, get_string('strftimedate'), $tz);
        $vars ['TIME']     = userdate($slot->starttime, get_string('strftimetime'), $tz);
        $vars ['ENDTIME']  = userdate($slot->endtime, get_string('strftimetime'), $tz);
        $vars ['LOCATION'] = format_string($slot->appointmentlocation);
    }
    if ($attendant) {
        $vars['ATTENDANT']     = fullname($attendant);
        $vars['ATTENDANT_URL'] = $CFG->wwwroot.'/user/view.php?id='.$attendant->id.'&course='.$scheduler->course;
    }
    if ($attendee) {
        $vars['ATTENDEE']     = fullname($attendee);
        $vars['ATTENDEE_URL'] = $CFG->wwwroot.'/user/view.php?id='.$attendee->id.'&course='.$scheduler->course;
    }

    // Reset language settings.
    force_current_language($oldlang);

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

    global $CFG, $USER, $OUTPUT;

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


function scheduler_has_teachers($context) {
    $teachers = get_users_by_capability ($context, 'mod/scheduler:attend', 'u.id');
    return count($teachers) > 0;
}


