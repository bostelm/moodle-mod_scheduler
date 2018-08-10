<?PHP

/**
 * Library (public API) of the scheduler module
 *
 * @package    mod_scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Library of functions and constants for module Scheduler.

require_once($CFG->dirroot.'/mod/scheduler/locallib.php');
require_once($CFG->dirroot.'/mod/scheduler/mailtemplatelib.php');
require_once($CFG->dirroot.'/mod/scheduler/renderer.php');
require_once($CFG->dirroot.'/mod/scheduler/renderable.php');

define('SCHEDULER_TIMEUNKNOWN', 0);  // This is used for appointments for which no time is entered.
define('SCHEDULER_SELF', 0); // Used for setting conflict search scope.
define('SCHEDULER_OTHERS', 1); // Used for setting conflict search scope.
define('SCHEDULER_ALL', 2); // Used for setting conflict search scope.

define ('SCHEDULER_MEAN_GRADE', 0); // Used for grading strategy.
define ('SCHEDULER_MAX_GRADE', 1);  // Used for grading strategy.

/**
 * Given an object containing all the necessary data,
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $data the current instance
 * @param moodleform $mform the form that the user filled
 * @return int the new instance id
 * @uses $DB
 */
function scheduler_add_instance($data, $mform = null) {
    global $DB;

    $cmid = $data->coursemodule;

    $data->timemodified = time();
    $data->scale = isset($data->grade) ? $data->grade : 0;

    $data->id = $DB->insert_record('scheduler', $data);

    $DB->set_field('course_modules', 'instance', $data->id, array('id' => $cmid));
    $context = context_module::instance($cmid);

    if ($mform) {
        $mform->save_mod_data($data, $context);
    }

    scheduler_grade_item_update($data);

    return $data->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 *
 * @param object $scheduler the current instance
 * @param moodleform $mform the form that the user filled
 * @return object the updated instance
 * @uses $DB
 */
function scheduler_update_instance($data, $mform) {
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    $data->scale = $data->grade;

    $DB->update_record('scheduler', $data);

    $context = context_module::instance($data->coursemodule);
    $mform->save_mod_data($data, $context);

    // Update grade item and grades.
    scheduler_update_grades($data);

    return true;
}


/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id the instance to be deleted
 * @return bool true if success, false otherwise
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
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 * @param object $course the course instance
 * @param object $user the concerned user instance
 * @param object $mod the current course module instance
 * @param object $scheduler the activity module behind the course module instance
 * @return object an information object as defined above
 */
function scheduler_user_outline($course, $user, $mod, $scheduler) {

    $scheduler = scheduler_instance::load_by_coursemodule_id($mod->id);
    $upcoming = count($scheduler->get_upcoming_slots_for_student($user->id));
    $attended = count($scheduler->get_attended_slots_for_student($user->id));

    $text = '';

    if ($attended + $upcoming > 0) {
        $a = array('attended' => $attended, 'upcoming' => $upcoming);
        $text .= get_string('outlineappointments', 'scheduler', $a);
    }

    if ($scheduler->uses_grades()) {
        $grade = $scheduler->get_gradebook_info($user->id);
        if ($grade) {
            $text .= get_string('outlinegrade', 'scheduler', $grade->str_long_grade);
        }
    }

    $return = new stdClass();
    $return->info = $text;
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param object $course the course instance
 * @param object $user the concerned user instance
 * @param object $mod the current course module instance
 * @param object $scheduler the activity module behind the course module instance
 */
function scheduler_user_complete($course, $user, $mod, $scheduler) {

    global $PAGE;

    $scheduler = scheduler_instance::load_by_coursemodule_id($mod->id);
    $output = $PAGE->get_renderer('mod_scheduler', null, RENDERER_TARGET_GENERAL);

    $appointments = $scheduler->get_appointments_for_student($user->id);

    if (count($appointments) > 0) {
        $table = new scheduler_slot_table($scheduler);
        $table->showattended = true;
        foreach ($appointments as $app) {
            $table->add_slot($app->get_slot(), $app, null, false);
        }

        echo $output->render($table);
    } else {
        echo get_string('noappointments', 'scheduler');
    }

    if ($scheduler->uses_grades()) {
        $grade = $scheduler->get_gradebook_info($user->id);
        if ($grade) {
            $info = new scheduler_totalgrade_info($scheduler, $grade);
            echo $output->render($info);
        }
    }

}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in scheduler activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param object $course the course instance
 * @param bool $isteacher true tells a teacher uses the function
 * @param int $timestart a time start timestamp
 * @return bool true if anything was printed, otherwise false
 */
function scheduler_print_recent_activity($course, $isteacher, $timestart) {

    return false;
}


/**
 * This function returns whether a scale is being used by a scheduler.
 *
 * @param int $cmid ID of an instance of this module
 * @param int $casleid the id of the scale in question
 * @return mixed
 * @uses $DB
 **/
function scheduler_scale_used($cmid, $scaleid) {
    global $DB;

    $return = false;

    // Note: scales are assigned using negative index in the grade field of the appointment (see mod/assignement/lib.php).
    $rec = $DB->get_record('scheduler', array('id' => $cmid, 'scale' => -$scaleid));

    if (!empty($rec) && !empty($scaleid)) {
        $return = true;
    }

    return $return;
}


/**
 * Checks if scale is being used by any instance of scheduler
 *
 * @param $scaleid int the id of the scale in question
 * @return bool True if the scale is used by any scheduler
 * @uses $DB
 */
function scheduler_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('scheduler', array('scale' => -$scaleid))) {
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
 *
 * @param $mform form passed by reference
 * @uses $COURSE
 * @uses $DB
 */
function scheduler_reset_course_form_definition(&$mform) {
    global $COURSE, $DB;

    $mform->addElement('header', 'schedulerheader', get_string('modulenameplural', 'scheduler'));

    if ($DB->record_exists('scheduler', array('course' => $COURSE->id))) {

        $mform->addElement('checkbox', 'reset_scheduler_slots', get_string('resetslots', 'scheduler'));
        $mform->addElement('checkbox', 'reset_scheduler_appointments', get_string('resetappointments', 'scheduler'));
        $mform->disabledIf('reset_scheduler_appointments', 'reset_scheduler_slots', 'checked');
    }
}

/**
 * Default values for the reset form
 *
 * @param stdClass $course the course in which the reset takes place
 */
function scheduler_reset_course_form_defaults($course) {
    return array('reset_scheduler_slots' => 1, 'reset_scheduler_appointments' => 1);
}


/**
 * This function is used by the remove_course_userdata function in moodlelib.
 * If this function exists, remove_course_userdata will execute it.
 * This function will remove all slots and appointments from the specified scheduler.
 *
 * @param object $data the reset options
 * @return void
 */
function scheduler_reset_userdata($data) {
    global $CFG, $DB;

    $status = array();
    $componentstr = get_string('modulenameplural', 'scheduler');

    $success = true;

    if (!empty($data->reset_scheduler_appointments) || !empty($data->reset_scheduler_slots)) {

        $schedulers = $DB->get_records('scheduler', ['course' => $data->courseid]);

        foreach ($schedulers as $srec) {
            $scheduler = scheduler_instance::load_by_id($srec->id);

            if (!empty($data->reset_scheduler_slots) ) {
                $scheduler->delete_all_slots();
                $status[] = array('component' => $componentstr, 'item' => get_string('resetslots', 'scheduler'), 'error' => false);
            } else if (!empty($data->reset_scheduler_appointments) ) {
                foreach ($scheduler->get_all_slots() as $slot) {
                    $slot->delete_all_appointments();
                }
                $status[] = array(
                    'component' => $componentstr,
                    'item' => get_string('resetappointments', 'scheduler'),
                    'error' => !$success
                );
            }
        }
    }
    return $status;
}

/**
 * Determine whether a certain feature is supported by Scheduler.
 *
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
 * @param object $schedulerrecord
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone not used
 * @uses $CFG
 * @uses $DB
 */
function scheduler_update_grades($schedulerrecord, $userid=0, $nullifnone=true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    $scheduler = scheduler_instance::load_by_id($schedulerrecord->id);

    if ($scheduler->scale == 0) {
        scheduler_grade_item_update($schedulerrecord);

    } else if ($grades = $scheduler->get_user_grades($userid)) {
        foreach ($grades as $k => $v) {
            if ($v->rawgrade == -1) {
                $grades[$k]->rawgrade = null;
            }
        }
        scheduler_grade_item_update($schedulerrecord, $grades);

    } else {
        scheduler_grade_item_update($schedulerrecord);
    }
}


/**
 * Create grade item for given scheduler
 *
 * @param object $scheduler object
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function scheduler_grade_item_update($scheduler, $grades=null) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if (!isset($scheduler->courseid)) {
        $scheduler->courseid = $scheduler->course;
    }
    $moduleid = $DB->get_field('modules', 'id', array('name' => 'scheduler'));
    $cmid = $DB->get_field('course_modules', 'id', array('module' => $moduleid, 'instance' => $scheduler->id));

    if ($scheduler->scale == 0) {
        // Delete any grade item.
        scheduler_grade_item_delete($scheduler);
        return 0;
    } else {
        $params = array('itemname' => $scheduler->name, 'idnumber' => $cmid);

        if ($scheduler->scale > 0) {
            $params['gradetype'] = GRADE_TYPE_VALUE;
            $params['grademax']  = $scheduler->scale;
            $params['grademin']  = 0;

        } else if ($scheduler->scale < 0) {
            $params['gradetype'] = GRADE_TYPE_SCALE;
            $params['scaleid']   = -$scheduler->scale;

        } else {
            $params['gradetype'] = GRADE_TYPE_TEXT; // Allow text comments only.
        }

        if ($grades === 'reset') {
            $params['reset'] = true;
            $grades = null;
        }

        return grade_update('mod/scheduler', $scheduler->courseid, 'mod', 'scheduler', $scheduler->id, 0, $grades, $params);
    }
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
        $i = 0;
        foreach ($rs as $scheduler) {
            $i++;
            upgrade_set_timeout(60 * 5); // Set up timeout, may also abort execution.
            scheduler_update_grades($scheduler);
            $pbar->update($i, $count, "Updating scheduler grades ($i/$count).");
        }
        upgrade_set_timeout(); // Reset to default timeout.
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

    return grade_update('mod/scheduler', $scheduler->courseid, 'mod', 'scheduler', $scheduler->id, 0, null, array('deleted' => 1));
}


/*
 * File API
 */

/**
 * Lists all browsable file areas
 *
 * @package  mod_scheduler
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClass $context context object
 * @return array
 */
function scheduler_get_file_areas($course, $cm, $context) {
    return array(
            'bookinginstructions' => get_string('bookinginstructions', 'scheduler'),
            'slotnote' => get_string('areaslotnote', 'scheduler'),
            'appointmentnote' => get_string('areaappointmentnote', 'scheduler'),
            'teachernote' => get_string('areateachernote', 'scheduler')
    );
}

/**
 * File browsing support for scheduler module.
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param cm_info $cm
 * @param context $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info_stored file_info_stored instance or null if not found
 */
function scheduler_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    global $CFG, $DB, $USER;

    // Note: 'intro' area is handled in file_browser automatically.

    if (!has_any_capability(array('mod/scheduler:appoint', 'mod/scheduler:attend'), $context)) {
        return null;
    }

    require_once(dirname(__FILE__).'/locallib.php');

    $validareas = array_keys(scheduler_get_file_areas($course, $cm, $context));
    if (!in_array($filearea, $validareas)) {
        return null;
    }

    if (is_null($itemid)) {
        return new scheduler_file_info($browser, $course, $cm, $context, $areas, $filearea);
    }

    try {
        $scheduler = scheduler_instance::load_by_coursemodule_id($cm->id);

        if ($filearea === 'bookinginstructions') {
            $cansee = true;
            $canwrite = has_capability('moodle/course:manageactivities', $context);
            $name = get_string('bookinginstructions', 'scheduler');

        } else if ($filearea === 'slotnote') {
            $slot = $scheduler->get_slot($itemid);

            $cansee = true;
            $canwrite = $USER->id == $slot->teacherid
                        || has_capability('mod/scheduler:manageallappointments', $context);
            $name = get_string('slot', 'scheduler'). ' '.$itemid;

        } else if ($filearea === 'appointmentnote') {
            if (!$scheduler->uses_appointmentnotes()) {
                return null;
            }
            list($slot, $app) = $scheduler->get_slot_appointment($itemid);
            $cansee = $USER->id == $app->studentid || $USER->id == $slot->teacherid
                        || has_capability('mod/scheduler:manageallappointments', $context);
            $canwrite = $USER->id == $slot->teacherid
                        || has_capability('mod/scheduler:manageallappointments', $context);
            $name = get_string('appointment', 'scheduler'). ' '.$itemid;

        } else if ($filearea === 'teachernote') {
            if (!$scheduler->uses_teachernotes()) {
                return null;
            }

            list($slot, $app) = $scheduler->get_slot_appointment($itemid);
            $cansee = $USER->id == $slot->teacherid
                        || has_capability('mod/scheduler:manageallappointments', $context);
            $canwrite = $cansee;
            $name = get_string('appointment', 'scheduler'). ' '.$itemid;
        }

        $fs = get_file_storage();
        $filepath = is_null($filepath) ? '/' : $filepath;
        $filename = is_null($filename) ? '.' : $filename;
        if (!$storedfile = $fs->get_file($context->id, 'mod_scheduler', $filearea, $itemid, $filepath, $filename)) {
            return null;
        }

        $urlbase = $CFG->wwwroot.'/pluginfile.php';
        return new file_info_stored($browser, $context, $storedfile, $urlbase, $name, true, true, $canwrite, false);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Serves the files embedded in various rich text fields, or uploaded by students
 *
 * @package  mod_scheduler
 * @category files
 * @param stdClass $course course object
 * @param stdClass $cm course module object
 * @param stdClsss $context context object
 * @param string $filearea file area
 * @param array $args extra arguments
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function scheduler_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB, $USER;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);
    if (!has_any_capability(array('mod/scheduler:appoint', 'mod/scheduler:attend'), $context)) {
        return false;
    }

    try {
        $scheduler = scheduler_instance::load_by_coursemodule_id($cm->id);

        $entryid = (int)array_shift($args);
        $relativepath = implode('/', $args);

        if ($filearea === 'slotnote') {
            if (!$scheduler->get_slot($entryid)) {
                return false;
            }
            // No further access control required - everyone can see slots notes.

        } else if ($filearea === 'appointmentnote') {
            if (!$scheduler->uses_appointmentnotes()) {
                return false;
            }

            list($slot, $app) = $scheduler->get_slot_appointment($entryid);
            if (!$app) {
                return false;
            }

            if (!($USER->id == $app->studentid || $USER->id == $slot->teacherid)) {
                require_capability('mod/scheduler:manageallappointments', $context);
            }

        } else if ($filearea === 'teachernote') {
            if (!$scheduler->uses_teachernotes()) {
                return false;
            }

            list($slot, $app) = $scheduler->get_slot_appointment($entryid);
            if (!$app) {
                return false;
            }

            if (!($USER->id == $slot->teacherid)) {
                require_capability('mod/scheduler:manageallappointments', $context);
            }

        } else if ($filearea === 'bookinginstructions') {
            $caps = array('moodle/course:manageactivities', 'mod/scheduler:appoint');
            if (!has_any_capability($caps, $context)) {
                return false;
            }

        } else if ($filearea === 'studentfiles') {
            if (!$scheduler->uses_studentfiles()) {
                return false;
            }

            list($slot, $app) = $scheduler->get_slot_appointment($entryid);
            if (!$app) {
                return false;
            }

            if (($USER->id != $slot->teacherid) && ($USER->id != $app->studentid)) {
                require_capability('mod/scheduler:manageallappointments', $context);
            }

        } else {
            // Unknown file area.
            return false;
        }
    } catch (Exception $e) {
        // Typically, records that are not found in the database.
        return false;
    }

    $fullpath = "/$context->id/mod_scheduler/$filearea/$entryid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

