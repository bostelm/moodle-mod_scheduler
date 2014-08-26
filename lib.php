<?PHP

/**
 * Library (public API) of the scheduler module
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/// Library of functions and constants for module scheduler
include_once $CFG->dirroot.'/mod/scheduler/locallib.php';
include_once $CFG->dirroot.'/mod/scheduler/mailtemplatelib.php';

define('SCHEDULER_TIMEUNKNOWN', 0);  // This is used for appointments for which no time is entered
define('SCHEDULER_SELF', 0); // Used for setting conflict search scope
define('SCHEDULER_OTHERS', 1); // Used for setting conflict search scope
define('SCHEDULER_ALL', 2); // Used for setting conflict search scope

define ('MEAN_GRADE', 0); // Used for grading strategy
define ('MAX_GRADE', 1); // Used for grading strategy

/**
 * Given an object containing all the necessary data,
 * will create a new instance and return the id number
 * of the new instance.
 * @param object $scheduler the current instance
 * @return int the new instance id
 * @uses $DB
 */
function scheduler_add_instance($scheduler) {
    global $DB;

    $scheduler->timemodified = time();
    $id = $DB->insert_record('scheduler', $scheduler);
    $scheduler->id = $id;

    scheduler_grade_item_update($scheduler);


    return $id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 * @param object $scheduler the current instance
 * @return object the updated instance
 * @uses $DB
 */
function scheduler_update_instance($scheduler) {
    global $DB;

    $scheduler->timemodified = time();
    $scheduler->id = $scheduler->instance;

    $DB->update_record('scheduler', $scheduler);

    // update grade item and grades
    scheduler_update_grades($scheduler);

    return true;
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 * @param int $id the instance to be deleted
 * @return boolean true if success, false otherwise
 * @uses $DB
 */
function scheduler_delete_instance($id) {
    global $DB;

    if (! $DB->record_exists('scheduler', array('id' => $id))) {
        return false;
    }

    $scheduler = scheduler_instance::load_by_id($id);
    $scheduler->delete();

    // Clean up any possibly remaining event records.
    $params = array('modulename' => 'scheduler', 'instance' => $id);
    $DB->delete_records('event', $params);

    return true;
}

/**
 * Return a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 * @param object $course the course instance
 * @param object $user the concerned user instance
 * @param object $mod the current course module instance
 * @param object $scheduler the activity module behind the course module instance
 * @return object an information object as defined above
 */
function scheduler_user_outline($course, $user, $mod, $scheduler) {
    $return = NULL;
    return $return;
}

/**
 * Prints a detailed representation of what a  user has done with
 * a given particular instance of this module, for user activity reports.
 * @param object $course the course instance
 * @param object $user the concerned user instance
 * @param object $mod the current course module instance
 * @param object $scheduler the activity module behind the course module instance
 * @param boolean true if the user completed activity, false otherwise
 */
function scheduler_user_complete($course, $user, $mod, $scheduler) {

    return true;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in scheduler activities and print it out.
 * Return true if there was output, or false is there was none.
 * @param object $course the course instance
 * @param boolean $isteacher true tells a teacher uses the function
 * @param int $timestart a time start timestamp
 * @return boolean true if anything was printed, otherwise false
 */
function scheduler_print_recent_activity($course, $isteacher, $timestart) {

    return false;
}

/**
 * Function to be run periodically according to the moodle
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 * @return boolean always true
 * @uses $CFG
 * @uses $DB
 */
function scheduler_cron () {
    global $CFG, $DB;

    $date = make_timestamp(date('Y'), date('m'), date('d'), date('H'), date('i'));

    // for every appointment in all schedulers
    $select = 'emaildate > 0 AND emaildate <= ? AND starttime > ?';
    $slots = $DB->get_records_select('scheduler_slots', $select, array($date, $date), 'starttime');

    foreach ($slots as $slot) {
        // get teacher
        $teacher = $DB->get_record('user', array('id' => $slot->teacherid));

        // get scheduler, slot and course
        $scheduler = scheduler_instance::load_by_id($slot->schedulerid);
        $slotm = $scheduler->get_slot($slot->id);
        $course = $DB->get_record('course', array('id' => $scheduler->course));

        // get appointed student list
        $appointments = $DB->get_records('scheduler_appointment', array('slotid'=>$slot->id), '', 'id, studentid');

        //if no email previously sent and one is required
        foreach ($appointments as $appointment) {
            $student = $DB->get_record('user', array('id'=>$appointment->studentid));
            cron_setup_user($student, $course);
            $vars = scheduler_get_mail_variables ($scheduler, $slotm, $teacher, $student, $course, $student);
            scheduler_send_email_from_template ($student, $teacher, $course, 'remindtitle', 'reminder', $vars, 'scheduler');
        }
        // mark as sent
        $slot->emaildate = -1;
        $DB->update_record('scheduler_slots', $slot);
    }

    cron_setup_user();

    return true;
}



/**
 * Returns the users with data in one scheduler
 * (users with records in journal_entries, students and teachers)
 * @param int $schedulerid the id of the activity module
 * @uses $CFG
 * @uses $DB
 */
function scheduler_get_participants($schedulerid) {
    global $CFG, $DB;

    //Get students using slots they have
    $sql = '
        SELECT DISTINCT
        u.*
        FROM
        {user} u,
        {scheduler_slots} s,
        {scheduler_appointment} a
        WHERE
        s.schedulerid = ? AND
        s.id = a.slotid AND
        u.id = a.studentid
        ';
    $students = $DB->get_records_sql($sql, array($schedulerid));

    //Get teachers using slots they have
    $sql = '
        SELECT DISTINCT
        u.*
        FROM
        {user} u,
        {scheduler_slots} s
        WHERE
        s.schedulerid = ? AND
        u.id = s.teacherid
        ';
    $teachers = $DB->get_records_sql($sql, array($schedulerid));

    if ($students and $teachers){
        $participants = array_merge(array_values($students), array_values($teachers));
    }
    elseif ($students) {
        $participants = array_values($students);
    }
    elseif ($teachers){
        $participants = array_values($teachers);
    }
    else{
        $participants = array();
    }

    //Return students array (it contains an array of unique users)
    return ($participants);
}

/**
 * This function returns if a scale is being used by one newmodule
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $newmoduleid ID of an instance of this module
 * @return mixed
 * @uses $DB
 **/
function scheduler_scale_used($cmid, $scaleid) {
    global $DB;

    $return = false;

    // note : scales are assigned using negative index in the grade field of the appointment (see mod/assignement/lib.php)
    $rec = $DB->get_record('scheduler', array('id' => $cmid, 'scale' => -$scaleid));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}


/**
 * Checks if scale is being used by any instance of scheduler
 *
 * This is used to find out if scale used anywhere
 * @param $scaleid int
 * @return boolean True if the scale is used by any scheduler
 */
function scheduler_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('scheduler', array('scale'=>-$scaleid))) {
        return true;
    } else {
        return false;
    }
}


/*
 * Course resetting API
 *
 */

/**
 * Called by course/reset.php
 * @param $mform form passed by reference
 */
function scheduler_reset_course_form_definition(&$mform) {
    global $COURSE, $DB;

    $mform->addElement('header', 'schedulerheader', get_string('modulenameplural', 'scheduler'));

    if($DB->record_exists('scheduler', array('course'=>$COURSE->id))){

        $mform->addElement('checkbox', 'reset_scheduler_slots', get_string('resetslots', 'scheduler'));
        $mform->addElement('checkbox', 'reset_scheduler_appointments', get_string('resetappointments', 'scheduler'));
        $mform->disabledIf('reset_scheduler_appointments', 'reset_scheduler_slots', 'checked');
    }
}

/**
 * Default values for the reset form
 */
function scheduler_reset_course_form_defaults($course) {
    return array('reset_scheduler_slots'=>1, 'reset_scheduler_appointments'=>1);
}


/**
 * This function is used by the remove_course_userdata function in moodlelib.
 * If this function exists, remove_course_userdata will execute it.
 * This function will remove all posts from the specified forum.
 * @param data the reset options
 * @return void
 */
function scheduler_reset_userdata($data) {
    global $CFG, $DB;

    $status = array();
    $componentstr = get_string('modulenameplural', 'scheduler');

    $sqlfromslots = 'FROM {scheduler_slots} WHERE schedulerid IN '.
        '(SELECT sc.id FROM {scheduler} sc '.
        ' WHERE sc.course = :course)';

    $params = array('course'=>$data->courseid);

    $strreset = get_string('reset');


    if (!empty($data->reset_scheduler_appointments) || !empty($data->reset_scheduler_slots)) {

        $slots = $DB->get_recordset_sql('SELECT * '.$sqlfromslots, $params);
        $success = true;
        foreach ($slots as $slot) {
            // delete calendar events
            $success = $success && scheduler_delete_calendar_events($slot);

            // delete appointments
            $success = $success && $DB->delete_records('scheduler_appointment', array('slotid'=>$slot->id));
        }
        $slots->close();

        // reset gradebook
        $schedulers = $DB->get_records('scheduler', $params);
        foreach ($schedulers as $scheduler){
            scheduler_grade_item_update($scheduler, 'reset');
        }

        $status[] = array('component' => $componentstr, 'item' => get_string('resetappointments','scheduler'), 'error' => !$success);
    }
    if (!empty($data->reset_scheduler_slots)) {
        if ($DB->execute('DELETE '.$sqlfromslots, $params)) {
            $status[] = array('component' => $componentstr, 'item' => get_string('resetslots','scheduler'), 'error' => false);
        }
    }
    return $status;
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function scheduler_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;

        default: return null;
    }
}

/* Gradebook API */
/*
 * add xxx_update_grades() function into mod/xxx/lib.php
 * add xxx_grade_item_update() function into mod/xxx/lib.php
 * patch xxx_update_instance(), xxx_add_instance() and xxx_delete_instance() to call xxx_grade_item_update()
 * patch all places of code that change grade values to call xxx_update_grades()
 * patch code that displays grades to students to use final grades from the gradebook
 */

/**
 * Update activity grades
 *
 * @param object $scheduler
 * @param int $userid specific user only, 0 means all
 */
function scheduler_update_grades($scheduler, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if ($scheduler->scale == 0) {
        scheduler_grade_item_update($scheduler);

    } else if ($grades = scheduler_get_user_grades($scheduler, $userid)) {
        foreach($grades as $k=>$v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
        scheduler_grade_item_update($scheduler, $grades);

    } else {
        scheduler_grade_item_update($scheduler);
    }
}


/**
 * Create grade item for given scheduler
 *
 * @param object $scheduler object
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function scheduler_grade_item_update($scheduler, $grades=NULL) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($scheduler->courseid)) {
        $scheduler->courseid = $scheduler->course;
    }
    $moduleid = $DB->get_field('modules', 'id', array('name'=>'scheduler'));
    $cmid = $DB->get_field('course_modules', 'id', array('module'=>$moduleid, 'instance'=>$scheduler->id));

    if ($scheduler->scale == 0) {
        // delete any grade item
        scheduler_grade_item_delete($scheduler);
        return 0;
    }
    else {
        $params = array('itemname'=>$scheduler->name, 'idnumber'=>$cmid);

        if ($scheduler->scale > 0) {
            $params['gradetype'] = GRADE_TYPE_VALUE;
            $params['grademax']  = $scheduler->scale;
            $params['grademin']  = 0;

        } else if ($scheduler->scale < 0) {
            $params['gradetype'] = GRADE_TYPE_SCALE;
            $params['scaleid']   = -$scheduler->scale;

        } else {
            $params['gradetype'] = GRADE_TYPE_TEXT; // allow text comments only
        }

        if ($grades  === 'reset') {
            $params['reset'] = true;
            $grades = NULL;
        }

        return grade_update('mod/scheduler', $scheduler->courseid, 'mod', 'scheduler', $scheduler->id, 0, $grades, $params);
    }
}


/**
 * Return grade for given user or all users.
 *
 * @param int $schedulerid id of scheduler
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function scheduler_get_user_grades($scheduler, $userid=0) {
    global $CFG, $DB;

    if ($scheduler->scale == 0) {
        return false;
    }

    $usersql = '';
    $params = array();
    if ($userid) {
        $usersql = ' AND a.studentid = :userid';
        $params['userid'] = $userid;
    }
    $params['sid'] = $scheduler->id;

    $sql = 'SELECT a.id, a.studentid, a.grade '.
        'FROM {scheduler_slots} s JOIN {scheduler_appointment} a ON s.id = a.slotid '.
        'WHERE s.schedulerid = :sid AND a.grade IS NOT NULL'.$usersql;

    $grades = $DB->get_records_sql($sql, $params);
    $finalgrades = array();
    $gradesums = array();

    foreach ($grades as $grade) {
        $gradesums[$grade->studentid] = new stdClass();
        $finalgrades[$grade->studentid] = new stdClass();
        $finalgrades[$grade->studentid]->userid = $grade->studentid;
    }
    if ($scheduler->scale > 0) { // Grading numerically
        foreach ($grades as $aGrade){
            $gradesums[$aGrade->studentid]->sum = @$gradesums[$aGrade->studentid]->sum + $aGrade->grade;
            $gradesums[$aGrade->studentid]->count = @$gradesums[$aGrade->studentid]->count + 1;
            $gradesums[$aGrade->studentid]->max = (@$gradesums[$aGrade->studentid]->max < $aGrade->grade) ? $aGrade->grade : @$gradesums[$aGrade->studentid]->max ;
        }

        /// compute the adequate strategy
        foreach($gradesums as $student => $aGradeSet){
            switch ($scheduler->gradingstrategy) {
                case MAX_GRADE:
                    $finalgrades[$student]->rawgrade = $aGradeSet->max;
                    break;
                case MEAN_GRADE:
                    $finalgrades[$student]->rawgrade = $aGradeSet->sum / $aGradeSet->count ;
                    break;
            }
        }

    } else { // Scales
        $scaleid = - ($scheduler->scale);
        $maxgrade = '';
        if ($scale = $DB->get_record('scale', array('id' => $scaleid))) {
            $scalegrades = make_menu_from_list($scale->scale);
            foreach ($grades as $aGrade) {
                $gradesums[$aGrade->studentid]->sum = @$gradesums[$aGrade->studentid]->sum + $aGrade->grade;
                $gradesums[$aGrade->studentid]->count = @$gradesums[$aGrade->studentid]->count + 1;
                $gradesums[$aGrade->studentid]->max = (@$gradesums[$aGrade->studentid]->max < $aGrade) ? $aGrade->grade : @$gradesums[$aGrade->studentid]->max ;
            }
            $maxgrade = $scale->name;
        }

        /// compute the adequate strategy
        foreach($gradesums as $student => $aGradeSet){
            switch ($scheduler->gradingstrategy){
                case MAX_GRADE:
                    $finalgrades[$student]->rawgrade = $aGradeSet->max;
                    break;
                case MEAN_GRADE:
                    $finalgrades[$student]->rawgrade = $aGradeSet->sum / $aGradeSet->count ;
                    break;
            }
        }

    }
    // include any empty grades
    if ($userid > 0) {
        if (!array_key_exists($userid, $finalgrades)) {
            $finalgrades[$userid] = new stdClass();
            $finalgrades[$userid]->userid = $userid;
            $finalgrades[$userid]->rawgrade = null;
        }
    } else {
        $gradeitem = grade_item::fetch(array('itemtype'=>'mod', 'itemmodule'=>'scheduler', 'iteminstance'=>$scheduler->id, 'courseid'=>$scheduler->course));
        $existinggrades = grade_grade::fetch_all(array('itemid'=>$gradeitem->id));
        if ($existinggrades) {
            foreach ($existinggrades as $grade) {
                $u = $grade->userid;
                if (!array_key_exists($u, $finalgrades)) {
                    $finalgrades[$u] = new stdClass();
                    $finalgrades[$u]->userid = $u;
                    $finalgrades[$u]->rawgrade = null;
                }
            }
        }
    }
    return $finalgrades;

}


/**
 * Update all grades in gradebook.
 */
function scheduler_upgrade_grades() {
    global $DB;

    $sql = "SELECT COUNT('x')
        FROM {scheduler} s, {course_modules} cm, {modules} m
        WHERE m.name='scheduler' AND m.id=cm.module AND cm.instance=s.id";
    $count = $DB->count_records_sql($sql);

    $sql = "SELECT s.*, cm.idnumber AS cmidnumber, s.course AS courseid
        FROM {scheduler} s, {course_modules} cm, {modules} m
        WHERE m.name='scheduler' AND m.id=cm.module AND cm.instance=s.id";
    $rs = $DB->get_recordset_sql($sql);
    if ($rs->valid()) {
        $pbar = new progress_bar('schedulerupgradegrades', 500, true);
        $i=0;
        foreach ($rs as $scheduler) {
            $i++;
            upgrade_set_timeout(60*5); // set up timeout, may also abort execution
            scheduler_update_grades($scheduler);
            $pbar->update($i, $count, "Updating scheduler grades ($i/$count).");
        }
        upgrade_set_timeout(); // reset to default timeout
    }
    $rs->close();
}


/**
 * Delete grade item for given scheduler
 *
 * @param object $scheduler object
 * @return object scheduler
 */
function scheduler_grade_item_delete($scheduler) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($scheduler->courseid)) {
        $scheduler->courseid = $scheduler->course;
    }

    return grade_update('mod/scheduler', $scheduler->courseid, 'mod', 'scheduler', $scheduler->id, 0, NULL, array('deleted'=>1));
}

