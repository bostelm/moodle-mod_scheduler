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
 * A class for representing a scheduler instance.
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\model;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/grade/lib.php');

use completion_info;

/**
 * A class for representing a scheduler instance, as an MVC model.
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler extends mvc_record_model {

    /**
     * @var \stdClass course module record for this scheduler
     */
    protected $cm = null;

    /**
     * @var \stdClass course record for this scheduler
     */
    protected $courserec = null;

    /**
     * @var \context_module context record of this scheduler
     */
    protected $context = null;

    /**
     * @var int effective group mode of this scheduler
     */
    protected $groupmode;

    /**
     * @var mvc_child_list list of slots in this scheduler
     */
    protected $slots;

    /**
     * get_table
     *
     * @return string
     */
    protected function get_table() {
        return 'scheduler';
    }

    /**
     * scheduler constructor.
     */
    protected function __construct() {
        parent::__construct();
        $this->slots = new mvc_child_list($this, 'scheduler_slots', 'schedulerid', new slot_factory($this));
    }

    /**
     * Create a scheduler instance from the database.
     *
     * @param int $id module id of the scheduler
     * @return scheduler
     */
    public static function load_by_id($id) {
        global $DB;
        $cm = get_coursemodule_from_instance('scheduler', $id, 0, false, MUST_EXIST);
        return self::load_from_record($id, $cm);
    }

    /**
     * Create a scheduler instance from the database.
     *
     * @param int $cmid course module id of the scheduler
     * @return scheduler
     */
    public static function load_by_coursemodule_id($cmid) {
        global $DB;
        $cm = get_coursemodule_from_id('scheduler', $cmid, 0, false, MUST_EXIST);
        return self::load_from_record($cm->instance, $cm);
    }

    /**
     * Create a scheduler instance from an already loaded record.
     *
     * @param int $id the module id of the scheduler
     * @param \stdClass $coursemodule course module record
     * @return scheduler
     */
    protected static function load_from_record($id, \stdClass $coursemodule) {
        $scheduler = new scheduler();
        $scheduler->load($id);
        $scheduler->cm = $coursemodule;
        $scheduler->groupmode = groups_get_activity_groupmode($coursemodule);
        return $scheduler;
    }

    /**
     * Save any changes to the database
     */
    public function save() {
        parent::save();
        $this->slots->save_children();
    }

    /**
     * Delete the scheduler
     */
    public function delete() {
        $this->delete_all_slots();
        scheduler_grade_item_delete($this);
        parent::delete();
    }

    /**
     * Delete all slots from this scheduler.
     */
    public function delete_all_slots() {
        $this->slots->delete_children();
        scheduler_grade_item_update($this, 'reset');
    }

    /**
     * Retrieve the course module id of this scheduler
     *
     * @return int
     */
    public function get_cmid() {
        return $this->cm->id;
    }

    /**
     * Retrieve the course module record of this scheduler
     *
     * @return \stdClass
     */
    public function get_cm() {
        return $this->cm;
    }

    /**
     * Retrieve the course id of this scheduler
     *
     * @return int
     */
    public function get_courseid() {
        return $this->data->course;
    }

    /**
     * Retrieve the course record of this scheduler
     *
     * @return \stdClass
     */
    public function get_courserec() {
        global $DB;
        if (is_null($this->courserec)) {
            $this->courserec = $DB->get_record('course', array('id' => $this->get_courseid()), '*', MUST_EXIST);
        }
        return $this->courserec;
    }

    /**
     * Retrieve the activity module context of this scheduler
     *
     * @return context_module
     */
    public function get_context() {
        if ($this->context == null) {
            $this->context = \context_module::instance($this->get_cmid());
        }
        return $this->context;
    }

    /**
     * Return the last modification date (as stored in database) for this scheduler instance.
     *
     * @return int
     */
    public function get_timemodified() {
        return $this->data->timemodified;
    }

    /**
     * Retrieve the name of this scheduler
     *
     * @param bool $applyfilters whether to apply filters so that the output is printable
     * @return string
     */
    public function get_name($applyfilters = false) {
        $name = $this->data->name;
        if ($applyfilters) {
            $name = format_text($name);
        }
        return $name;
    }

    /**
     * Retrieve the intro of this scheduler
     *
     * @param bool $applyfilters whether to apply filters so that the output is printable
     * @return string
     */
    public function get_intro($applyfilters = false) {
        $intro = $this->data->intro;
        if ($applyfilters) {
            $intro = format_text($intro);
        }
        return $intro;
    }

    /**
     * Retrieve the name for "teacher" in the context of this scheduler
     *
     * TODO: This involves part of the presentation, should it be here?
     *
     * @return string
     */
    public function get_teacher_name() {
        $name = format_string($this->data->staffrolename);
        if (empty($name)) {
            $name = get_string('teacher', 'scheduler');
        }
        return $name;
    }

    /**
     * Retrieve the default duration of a slot, in minutes
     *
     * @return int
     */
    public function get_default_slot_duration() {
        return $this->data->defaultslotduration;
    }

    /**
     * Retrieve whether group scheduling is enabled in this instance
     *
     * @return boolean
     */
    public function is_group_scheduling_enabled() {
        global $CFG;
        $globalenable = (bool) get_config('mod_scheduler', 'groupscheduling');
        $localenable = $this->bookingrouping >= 0;
        return $globalenable && $localenable;
    }

    /**
     * Retrieve whether individual scheduling is enabled in this instance.
     * This is usually the case, but is disabled if the instance uses group scheduling
     * and the configuration setting 'mixindivgroup' is set to inactive.
     *
     * @return boolean
     */
    public function is_individual_scheduling_enabled() {
        if ($this->is_group_scheduling_enabled()) {
            return (bool) get_config('mod_scheduler', 'mixindivgroup');
        } else {
            return true;
        }
    }

    /**
     * get the last location of a certain teacher in this scheduler
     *
     * @param \stdClass $user
     * @uses $DB
     * @return string the last known location for the current user (teacher)
     */
    public function get_last_location($user) {
        global $DB;

        $conds = array('schedulerid' => $this->data->id, 'teacherid' => $user->id);
        $recs = $DB->get_records('scheduler_slots', $conds, 'timemodified DESC', 'id,appointmentlocation', 0, 1);
        $lastlocation = '';
        if ($recs) {
            foreach ($recs as $rec) {
                $lastlocation = $rec->appointmentlocation;
            }
        }
        return $lastlocation;
    }

    /**
     * Whether this scheduler uses "appointment notes" visible to teachers and students
     * @return bool whether appointment notes are used
     */
    public function uses_appointmentnotes() {
        return ($this->data->usenotes % 2 == 1);
    }

    /**
     * Whether this scheduler uses "teacher notes" visible to teachers only
     * @return bool whether appointment notes are used
     */
    public function uses_teachernotes() {
        return (floor($this->data->usenotes / 2) % 2 == 1);
    }

    /**
     * Whether this scheduler uses booking forms at all
     * @return bool whether the booking form is used
     */
    public function uses_bookingform() {
        return $this->data->usebookingform;
    }

    /**
     * Whether this scheduler has booking instructions
     * @return bool whether booking instructions present
     */
    public function has_bookinginstructions() {
        $instr = trim(strip_tags($this->data->bookinginstructions));
        return $this->uses_bookingform() && strlen($instr) > 0;
    }

    /**
     * Whether this scheduler uses "student notes" filled by students at booking time
     * @return bool whether student notes are used
     */
    public function uses_studentnotes() {
        return $this->uses_bookingform() && $this->usestudentnotes > 0;
    }

    /**
     * Whether this scheduler uses student file uploads at booking time
     * @return bool whether student file uploads are used
     */
    public function uses_studentfiles() {
        return $this->uses_bookingform() && $this->uploadmaxfiles > 0;
    }

    /**
     * Whether this scheduler uses any data entered by the student at booking time
     * @return bool whether student data is used
     */
    public function uses_studentdata() {
        return $this->uses_studentnotes() || $this->uses_studentfiles();
    }

    /**
     * Whether this scheduler uses captchas at booking time
     * @return bool whether captchas are used
     */
    public function uses_bookingcaptcha() {
        global $CFG;
        return  $this->uses_bookingform() && $this->data->usecaptcha
                && !empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey);
    }


    /**
     * Checks whether this scheduler allows a student (in principle) to book several slots at a time
     * @return bool whether the student can book multiple appointments
     */
    public function allows_multiple_bookings() {
        return ($this->maxbookings != 1);
    }

    /**
     * Checks whether this scheduler allows unlimited bookings per student.
     * @return bool
     */
    public function allows_unlimited_bookings() {
        return ($this->maxbookings == 0);
    }

    /**
     * Checks whether this scheduler uses grading at all.
     * @return boolean
     */
    public function uses_grades() {
        return ($this->scale != 0);
    }

    /**
     * Return grade for given user.
     * This does not take gradebook data into account.
     *
     * @param int $userid user id
     * @return int grade of this user
     */
    public function get_user_grade($userid) {
        $grades = $this->get_user_grades($userid);
        return $grades[$userid]->rawgrade;
    }

    /**
     * Return grade for given user or all users.
     *
     * @param int $userid optional user id, 0 means all users
     * @return array array of grades, false if none
     */
    public function get_user_grades($userid = 0) {
        global $CFG, $DB;

        if ($this->scale == 0) {
            return false;
        }

        $usersql = '';
        $params = array();
        if ($userid) {
            $usersql = ' AND a.studentid = :userid';
            $params['userid'] = $userid;
        }
        $params['sid'] = $this->id;

        $sql = 'SELECT a.id, a.studentid, a.grade '.
               'FROM {scheduler_slots} s JOIN {scheduler_appointment} a ON s.id = a.slotid '.
               'WHERE s.schedulerid = :sid AND a.grade IS NOT NULL'.$usersql;

        $grades = $DB->get_records_sql($sql, $params);
        $finalgrades = array();
        $gradesums = array();

        foreach ($grades as $grade) {
            $gradesums[$grade->studentid] = new \stdClass();
            $finalgrades[$grade->studentid] = new \stdClass();
            $finalgrades[$grade->studentid]->userid = $grade->studentid;
        }
        if ($this->scale > 0) { // Grading numerically.
            foreach ($grades as $grade) {
                $gradesums[$grade->studentid]->sum = @$gradesums[$grade->studentid]->sum + $grade->grade;
                $gradesums[$grade->studentid]->count = @$gradesums[$grade->studentid]->count + 1;
                $gradesums[$grade->studentid]->max = (@$gradesums[$grade->studentid]->max < $grade->grade) ?
                                                     $grade->grade : @$gradesums[$grade->studentid]->max;
            }

            // Retrieve the adequate strategy.
            foreach ($gradesums as $student => $gradeset) {
                switch ($this->gradingstrategy) {
                    case SCHEDULER_MAX_GRADE:
                        $finalgrades[$student]->rawgrade = $gradeset->max;
                        break;
                    case SCHEDULER_MEAN_GRADE:
                        $finalgrades[$student]->rawgrade = $gradeset->sum / $gradeset->count;
                        break;
                }
            }

        } else { // Grading on scales.
            $scaleid = - ($this->scale);
            $maxgrade = '';
            if ($scale = $DB->get_record('scale', array('id' => $scaleid))) {
                $scalegrades = make_menu_from_list($scale->scale);
                foreach ($grades as $grade) {
                    $gradesums[$grade->studentid]->sum = @$gradesums[$grade->studentid]->sum + $grade->grade;
                    $gradesums[$grade->studentid]->count = @$gradesums[$grade->studentid]->count + 1;
                    $gradesums[$grade->studentid]->max = (@$gradesums[$grade->studentid]->max < $grade) ?
                                                         $grade->grade : @$gradesums[$grade->studentid]->max;
                }
                $maxgrade = $scale->name;
            }

            // Retrieve the adequate strategy.
            foreach ($gradesums as $student => $gradeset) {
                switch ($this->gradingstrategy) {
                    case SCHEDULER_MAX_GRADE:
                        $finalgrades[$student]->rawgrade = $gradeset->max;
                        break;
                    case SCHEDULER_MEAN_GRADE:
                        $finalgrades[$student]->rawgrade = $gradeset->sum / $gradeset->count;
                        break;
                }
            }

        }
        // Include any empty grades.
        if ($userid > 0) {
            if (!array_key_exists($userid, $finalgrades)) {
                $finalgrades[$userid] = new \stdClass();
                $finalgrades[$userid]->userid = $userid;
                $finalgrades[$userid]->rawgrade = null;
            }
        } else {
            $gui = new \graded_users_iterator($this->get_courserec());
            // We must gracefully live through the gradesneedregrading that can be thrown by init().
            try {
                $gui->init();
                while ($userdata = $gui->next_user()) {
                    $uid = $userdata->user->id;
                    if (!array_key_exists($uid, $finalgrades)) {
                        $finalgrades[$uid] = new \stdClass();
                        $finalgrades[$uid]->userid = $uid;
                        $finalgrades[$uid]->rawgrade = null;
                    }
                }
            } catch (\moodle_exception $e) {
                if ($e->errorcode != 'gradesneedregrading') {
                    throw $e;
                }
            }
        }
        return $finalgrades;

    }

    /**
     * Get gradebook information for a particular student.
     * The return value is a grade_grade object.
     *
     * @param int $studentid id number of the student
     * @return \stdClass the gradebook information. May be null if no info is found.
     */
    public function get_gradebook_info($studentid) {

        $gradinginfo = grade_get_grades($this->courseid, 'mod', 'scheduler', $this->id, $studentid);
        if (!empty($gradinginfo->items)) {
            $item = $gradinginfo->items[0];
            if (isset($item->grades[$studentid])) {
                return $item->grades[$studentid];
            }
        }
        return null;
    }

    /**
     * Whether attendance is required for completing this activity.
     *
     * @return bool
     */
    public function completion_requires_attended() {
        return !empty($this->data->completionattended);
    }

    /**
     * Update the completion state of a user, if needed.
     *
     * @param int $userid The user ID.
     * @param bool $hasattended Whether the user just attended a slot.
     * @return void
     */
    public function completion_update_has_attended($userid, $hasattended=false) {
        if (!$this->completion_requires_attended()) {
            return;
        }

        $course = $this->get_courserec();
        $cm = $this->get_cm();
        $completion = new completion_info($course);

        if ($completion->is_enabled($cm)) {
            $state = $hasattended ? COMPLETION_COMPLETE : COMPLETION_UNKNOWN;
            $completion->update_state($cm, $state, $userid);
        }
    }

    /**
     * Whether the user has attended any slot.
     *
     * @param int $userid The user ID.
     * @return bool
     */
    public function has_user_attended_any_slot($userid) {
        global $DB;

        $sql = "SELECT 1
                  FROM {scheduler_appointment} a
                  JOIN {scheduler_slots} s
                    ON a.slotid = s.id
                 WHERE s.schedulerid = :id
                   AND a.studentid = :userid
                   AND a.attended = 1";

        $params = [
            'id' => $this->data->id,
            'userid' => $userid
        ];

        return $DB->record_exists_sql($sql, $params);
    }

    /* *********************** Loading lists of slots *********************** */

    /**
     * Fetch a generic list of slots from the database
     *
     * @param string $wherecond WHERE condition
     * @param string $havingcond HAVING condition
     * @param array $params parameters for DB query
     * @param mixed $limitfrom query limit from here
     * @param mixed $limitnum max number od records to fetch
     * @param string $orderby ORDER BY fields
     * @return slot[]
     */
    protected function fetch_slots($wherecond, $havingcond, array $params, $limitfrom='', $limitnum='', $orderby='') {
        global $DB;
        $select = 'SELECT s.* FROM {scheduler_slots} s';

        $where = 'WHERE schedulerid = :schedulerid';
        if ($wherecond) {
            $where .= ' AND ('.$wherecond.')';
        }
        $params['schedulerid'] = $this->data->id;

        $having = '';
        if ($havingcond) {
            $having = 'HAVING '.$havingcond;
        }

        if ($orderby) {
            $order = "ORDER BY $orderby, s.id";
        } else {
            $order = "ORDER BY s.id";
        }

        $sql = "$select $where $having $order";

        $slotdata = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        $slots = array();
        foreach ($slotdata as $slotrecord) {
            $slot = new slot($this);
            $slot->load_record($slotrecord);
            $slots[] = $slot;
        }
        return $slots;
    }

    /**
     * Count a list of slots (for this scheduler) in the database
     *
     * @param string $wherecond WHERE condition
     * @param array $params parameters for DB query
     * @return int
     */
    protected function count_slots($wherecond, array $params) {
        global $DB;
        $select = 'SELECT COUNT(*) FROM {scheduler_slots} s';

        $where = 'WHERE schedulerid = :schedulerid';
        if ($wherecond) {
            $where .= ' AND ('.$wherecond.')';
        }
        $params['schedulerid'] = $this->data->id;

        $sql = "$select $where";

        return $DB->count_records_sql($sql, $params);
    }


    /**
     * Subquery that counts appointments in the current slot.
     * Only to be used in conjunction with fetch_slots()
     *
     * @return string
     */
    protected function appointment_count_query() {
        return "(SELECT COUNT(a.id) FROM {scheduler_appointment} a WHERE a.slotid = s.id)";
    }

    /**
     * @var int number of student parameters used in queries
     */
    protected $studparno = 0;

    /**
     * Return a WHERE condition relating to sutdents in a slot
     *
     * @param array $params parameters for the query (by reference)
     * @param int $studentid id of student to look for
     * @param bool $mustbeattended include only attended appointments?
     * @param bool $mustbeunattended include only unattended appointments?
     * @return string
     */
    protected function student_in_slot_condition(&$params, $studentid, $mustbeattended, $mustbeunattended) {
        $cond = 'EXISTS (SELECT 1 FROM {scheduler_appointment} a WHERE a.studentid = :studentid'.
                $this->studparno.' and a.slotid=s.id';
        if ($mustbeattended) {
            $cond .= ' AND a.attended = 1';
        }
        if ($mustbeunattended) {
            $cond .= ' AND a.attended = 0';
        }
        $cond .= ')';
        $params['studentid'.$this->studparno] = $studentid;
        $this->studparno++;
        return $cond;
    }


    /**
     * Retrieve a slot by id.
     *
     * @param int $id
     * @return slot
     * @uses $DB
     */
    public function get_slot($id) {

        global $DB;

        $slotdata = $DB->get_record('scheduler_slots', array('id' => $id, 'schedulerid' => $this->id), '*', MUST_EXIST);
        $slot = new slot($this);
        $slot->load_record($slotdata);
        return $slot;
    }

    /**
     * Retrieve a list of all slots in this scheduler
     *
     * @return slot[]
     */
    public function get_slots() {
        return $this->slots->get_children();
    }

    /**
     * Retrieve the number of slots in the scheduler
     *
     * @return int
     */
    public function get_slot_count() {
        return $this->slots->get_child_count();
    }

    /**
     * Load a list of all slots, between certain limits
     *
     * @param string $limitfrom start from this entry
     * @param string $limitnum max number of entries
     * @return slot[]
     */
    public function get_all_slots($limitfrom='', $limitnum='') {
        return $this->fetch_slots('', '', array(), $limitfrom, $limitnum, 's.starttime ASC');
    }

    /**
     * Retrieves attended slots of a student. These will be sorted by start time.
     *
     * @param int $studentid
     * @return slot[]
     */
    public function get_attended_slots_for_student($studentid) {

        $params = array();
        $wherecond = $this->student_in_slot_condition($params, $studentid, true, false);

        $slots = $this->fetch_slots($wherecond, '', $params, '', '', 's.starttime');

        return $slots;
    }

    /**
     * Retrieves upcoming slots booked by a student. These will be sorted by start time.
     * A slot is "upcoming" if it as been booked but is not attended.
     *
     * @param int $studentid
     * @return slot[]
     */
    public function get_upcoming_slots_for_student($studentid) {

        $params = array();
        $wherecond = $this->student_in_slot_condition($params, $studentid, false, true);
        $slots = $this->fetch_slots($wherecond, '', $params, '', '', 's.starttime');

        return $slots;
    }

    /**
     * Retrieves the slots available to a student.
     *
     * Note: this does not check for scheduling conflicts.
     * It does however check for group restrictions if group mode is enabled.
     *
     * @param int $studentid
     * @param bool $includefullybooked include slots that are already fully booked
     * @return slot[]
     */
    public function get_slots_available_to_student($studentid, $includefullybooked = false) {

        global $DB;

        $params = array();
        $wherecond = "(s.starttime > :cutofftime) AND (s.hideuntil < :nowhide)";
        $params['nowhide'] = time();
        $params['cutofftime'] = time() + $this->guardtime;
        $subcond = 'NOT ('.$this->student_in_slot_condition($params, $studentid, false, false).')';
        if (!$includefullybooked) {
            $subcond .= ' AND (s.exclusivity = 0 OR s.exclusivity > '.$this->appointment_count_query().')';
        }
        if ($this->groupmode != NOGROUPS) {
            $groups = groups_get_all_groups($this->cm->course, $studentid, $this->cm->groupingid);
            if ($groups) {
                $groupids = array();
                foreach ($groups as $group) {
                    $groupids[] = $group->id;
                }
                list($sqlin, $paramsin) = $DB->get_in_or_equal($groupids, SQL_PARAMS_NAMED);
                $subquery = "SELECT 1 FROM {groups_members} gm WHERE gm.userid = s.teacherid AND gm.groupid $sqlin";
                $subcond .= " AND EXISTS ($subquery)";
                $params = array_merge($params, $paramsin);
            } else {
                $subcond .= " AND FALSE";
            }
        }
        $wherecond .= " AND ($subcond)";
        $order = 's.starttime ASC, s.duration ASC, s.teacherid';
        $slots = $this->fetch_slots($wherecond, '', $params, '', '', $order);

        return $slots;
    }

    /**
     * Does htis scheduler have a slot where a certain student is booked?
     *
     * @param int $studentid student to look for
     * @param bool $mustbeattended include only attended slots
     * @param bool $mustbeunattended include only unattended slots
     * @return boolean
     */
    public function has_slots_for_student($studentid, $mustbeattended, $mustbeunattended) {
        $params = array();
        $where = $this->student_in_slot_condition($params, $studentid, $mustbeattended, $mustbeunattended);
        $cnt = $this->count_slots($where, $params);
        return $cnt > 0;
    }


    /**
     * Does this scheduler contain any slots where a certain group has booked?
     *
     * @param int $groupid the group to look for
     * @param bool $mustbeattended include only attended slots
     * @param bool $mustbeunattended include only unattended slots
     * @return boolean
     */
    public function has_slots_booked_for_group($groupid, $mustbeattended = false, $mustbeunattended = false) {
        global $DB;
        $attendcond = '';
        if ($mustbeattended) {
            $attendcond .= " AND a.attended = 1";
        }
        if ($mustbeunattended) {
            $attendcond .= " AND a.attended = 0";
        }
        $sql = "SELECT COUNT(*)
                  FROM {scheduler_slots} s
                  JOIN {scheduler_appointment} a ON a.slotid = s.id
                  JOIN {groups_members} gm ON a.studentid = gm.userid
                 WHERE s.schedulerid = :schedulerid
                       AND gm.groupid = :groupid
                       $attendcond";
        $params = array('schedulerid' => $this->id, 'groupid' => $groupid);
        return $DB->count_records_sql($sql, $params) > 0;
    }


    /**
     * retrieves slots without any appointment made
     *
     * @param int $teacherid if given, will return only slots for this teacher
     * @return slot[] list of unused slots
     */
    public function get_slots_without_appointment($teacherid = 0) {
        $wherecond = '('.$this->appointment_count_query().' = 0)';
        $params = array();
        if ($teacherid > 0) {
            list($twhere, $params) = $this->slots_for_teacher_cond($teacherid, 0, false);
            $wherecond .= " AND $twhere";
        }
        $slots = $this->fetch_slots($wherecond, '', $params);
        return $slots;
    }

    /**
     * Retrieve a list of slots for a certain teacher or group of teachers
     * @param int $teacherid id of teacher to look for, can be 0
     * @param int $groupid find only slots with a teacher in this group, can be 0
     * @param bool|int $timerange include only slots in the future/past?
     *            Accepted values are: 0=all, 1=future, 2=past, false=all, true=past
     * @return mixed SQL condition and parameters
     */
    protected function slots_for_teacher_cond($teacherid, $groupid, $timerange) {
        $wheres = array();
        $params = array();
        if ($teacherid > 0) {
            $wheres[] = "teacherid = :tid";
            $params['tid'] = $teacherid;
        }
        if ($groupid > 0) {
            $wheres[] = "EXISTS (SELECT 1 FROM {groups_members} gm WHERE gm.groupid = :gid AND gm.userid = s.teacherid)";
            $params['gid'] = $groupid;
        }
        if ($timerange === true || $timerange == 2) {
            $wheres[] = "s.starttime < ".strtotime('now');
        } else if ($timerange == 1) {
            $wheres[] = "s.starttime >= ".strtotime('now');
        }
        $where = implode(" AND ", $wheres);
        return array($where, $params);
    }

    /**
     * Count the number of slots available to a teacher or group of teachers
     *
     * @param int $teacherid id of teacher to look for, can be 0
     * @param int $groupid find only slots with a teacher in this group, can be 0
     * @param bool $inpast include only slots in the past?
     * @return int
     */
    public function count_slots_for_teacher($teacherid, $groupid = 0, $inpast = false) {
        list($where, $params) = $this->slots_for_teacher_cond($teacherid, $groupid, $inpast);
        return $this->count_slots($where, $params);
    }

    /**
     * Retrieve slots available to a teacher or group of teachers
     *
     * @param int $teacherid id of teacher to look for, can be 0
     * @param int $groupid find only slots with a teacher in this group, can be 0
     * @param mixed $limitfrom start from this entry
     * @param mixed $limitnum max number of entries
     * @param int $timerange whether to include past/future slots (0=all, 1=future, 0=past)
     * @return slot[]
     */
    public function get_slots_for_teacher($teacherid, $groupid = 0, $limitfrom = '', $limitnum = '', $timerange = 0) {
        list($where, $params) = $this->slots_for_teacher_cond($teacherid, $groupid, $timerange);
        return $this->fetch_slots($where, '', $params, $limitfrom, $limitnum, 's.starttime ASC, s.duration ASC, s.teacherid');
    }

    /**
     * Retrieve slots available to a group of teachers
     *
     * @param int $groupid find only slots with a teacher in this group
     * @param mixed $limitfrom start from this entry
     * @param mixed $limitnum max number of entries
     * @param int $timerange whether to include past/future slots (0=all, 1=future, 0=past)
     * @return slot[]
     */
    public function get_slots_for_group($groupid, $limitfrom = '', $limitnum = '', $timerange = 0) {
        list($where, $params) = $this->slots_for_teacher_cond(0, $groupid, $timerange);
        return $this->fetch_slots($where, '', $params, $limitfrom, $limitnum, 's.starttime ASC, s.duration ASC, s.teacherid');
    }


    /* ************** End of slot retrieveal routines ******************** */

    /**
     * Returns an array of slots that would overlap with this one.
     *
     * @param int $starttime the start of time slot as a timestamp
     * @param int $endtime end of time slot as a timestamp
     * @param int $teacher the id of the teacher constraint, or 0 for "all teachers"
     * @param int $student the id of the student constraint, or 0 for "all students"
     * @param int $others selects where to search for conflicts, [SCHEDULER_SELF, SCHEDULER_OTHERS, SCHEDULER_ALL]
     * @param int $excludeslot exclude slot with this id (useful to exclude present slot when saving)
     * @uses $DB
     * @return array conflicting slots
     */
    public function get_conflicts($starttime, $endtime, $teacher = 0, $student = 0,
                           $others = SCHEDULER_SELF, $excludeslot = 0) {
        global $DB;

        $params = array();

        $slotscope = ($excludeslot == 0) ? "" : "sl.id != :excludeslot AND ";
        $params['excludeslot'] = $excludeslot;

        switch ($others) {
            case SCHEDULER_SELF:
                $schedulerscope = "sl.schedulerid = :schedulerid AND ";
                $params['schedulerid'] = $this->id;
                break;
            case SCHEDULER_OTHERS:
                $schedulerscope = "sl.schedulerid != :schedulerid AND ";
                $params['schedulerid'] = $this->id;
                break;
            default:
                $schedulerscope = '';
        }
        if ($teacher != 0) {
            $teacherscope = "sl.teacherid = :teacherid AND ";
            $params['teacherid'] = $teacher;
        } else {
            $teacherscope = "";
        }

        $studentjoin = ($student != 0) ? "JOIN {scheduler_appointment} a ON a.slotid = sl.id AND a.studentid = :studentid " : '';
        $params['studentid'] = $student;

        $timeclause = "( (sl.starttime <= :starttime1 AND sl.starttime + sl.duration * 60 > :starttime2) OR
                         (sl.starttime < :endtime1 AND sl.starttime + sl.duration * 60 >= :endtime2) OR
                         (sl.starttime >= :starttime3 AND sl.starttime + sl.duration * 60 <= :endtime3) )
                       AND sl.starttime + sl.duration * 60 > :nowtime";
        $params['starttime1'] = $starttime;
        $params['starttime2'] = $starttime;
        $params['starttime3'] = $starttime;
        $params['endtime1'] = $endtime;
        $params['endtime2'] = $endtime;
        $params['endtime3'] = $endtime;
        $params['nowtime'] = time();

        $sql = "SELECT sl.*,
                       s.name AS schedulername,
                       (CASE WHEN (s.id = :thisid) THEN 1 ELSE 0 END) AS isself,
                       c.id AS courseid, c.shortname AS courseshortname, c.fullname AS coursefullname,
                       (SELECT COUNT(*) FROM {scheduler_appointment} ac WHERE sl.id = ac.slotid) AS numstudents
                  FROM {scheduler_slots} sl
                       $studentjoin
                  JOIN {scheduler} s ON sl.schedulerid = s.id
                  JOIN {course} c ON c.id = s.course
                 WHERE $slotscope $schedulerscope $teacherscope $timeclause
              ORDER BY sl.starttime ASC, sl.duration ASC";

        $params['thisid'] = $this->id;

        $conflicting = $DB->get_records_sql($sql, $params);

        return $conflicting;
    }

    /**
     * retrieves an appointment and the corresponding slot
     *
     * @param mixed $appointmentid
     * @return mixed List of (slot, scheduler_appointment)
     */
    public function get_slot_appointment($appointmentid) {
        global $DB;

        $slotid = $DB->get_field('scheduler_appointment', 'slotid', array('id' => $appointmentid));
        $slot = $this->get_slot($slotid);
        $app = $slot->get_appointment($appointmentid);

        return array($slot, $app);
    }

    /**
     * Retrieves all appointments of a student. These will be sorted by start time.
     *
     * @param int $studentid
     * @return array of appointment objects
     */
    public function get_appointments_for_student($studentid) {

        global $DB;

        $sql = "SELECT s.*, a.id as appointmentid
                  FROM {scheduler_slots} s, {scheduler_appointment} a
                 WHERE s.schedulerid = :schedulerid
                       AND s.id = a.slotid
                       AND a.studentid = :studid
              ORDER BY s.starttime";
        $params = array('schedulerid' => $this->id, 'studid' => $studentid);

        $slotrecs = $DB->get_records_sql($sql, $params);

        $appointments = array();
        foreach ($slotrecs as $rec) {
            $slot = new slot($this);
            $slot->load_record($rec);
            $appointrec = $DB->get_record('scheduler_appointment', array('id' => $rec->appointmentid), '*', MUST_EXIST);
            $appointment = new appointment($slot);
            $appointment->load_record($appointrec);
            $appointments[] = $appointment;
        }

        return $appointments;
    }

    /**
     * Create a new slot relating to this scheduler.
     *
     * @return slot
     */
    public function create_slot() {
        return $this->slots->create_child();
    }

    /**
     * Computes how many appointments a student can still book.
     *
     * @param int $studentid
     * @param bool $includechangeable include appointments that are booked but can still be changed?
     * @return int the number of bookable or changeable appointments, possibly 0; returns -1 if unlimited.
     */
    public function count_bookable_appointments($studentid, $includechangeable = true) {
        global $DB;

        // Find how many slots have already been booked.
        $sql = 'SELECT COUNT(*) FROM {scheduler_slots} s'
              .' JOIN {scheduler_appointment} a ON s.id = a.slotid'
              .' WHERE s.schedulerid = :schedulerid AND a.studentid=:studentid';
        if ($this->schedulermode == 'onetime') {
            if ($includechangeable) {
                $sql .= ' AND s.starttime <= :cutofftime';
            }
            $sql .= ' AND a.attended = 0';
        } else if ($includechangeable) {
            $sql .= ' AND (s.starttime <= :cutofftime OR a.attended = 1)';
        }
        $params = array('schedulerid' => $this->id, 'studentid' => $studentid, 'cutofftime' => time() + $this->guardtime);

        $booked = $DB->count_records_sql($sql, $params);
        $allowed = $this->maxbookings;

        if ($allowed == 0) {
            return -1;
        } else if ($booked >= $allowed) {
            return 0;
        } else {
            return $allowed - $booked;
        }

    }

    /**
     * Get list of teachers that have slots in this scheduler
     *
     * @return \stdClass[]
     */
    public function get_teachers() {
        global $DB;
        $sql = "SELECT DISTINCT u.*
                  FROM {scheduler_slots} s, {user} u
                 WHERE s.teacherid = u.id
                       AND schedulerid = ?";
        $teachers = $DB->get_records_sql($sql, array($this->id));
        return $teachers;
    }

    /**
     * Get list of available users with a certain capability.
     *
     * @param string $capability the capabilty to look for
     * @param int|array $groupids - group id or array of group ids; if set, will only return users who are in these groups.
     *                             (for legacy processing, allow also group objects and arrays of these)
     * @return \stdClass[] array of moodle user records
     */
    protected function get_available_users($capability, $groupids = 0) {

        // If full group objects are given, reduce the array to only group ids.
        if (is_array($groupids) && is_object(array_values($groupids)[0])) {
            $groupids = array_keys($groupids);
        } else if (is_object($groupids)) {
            $groupids = $groupids->id;
        }

        // Legacy: empty string amounts to no group filter.
        if ($groupids === '') {
            $groupids = 0;
        }

        $users = array();
        if (is_integer($groupids)) {
            $users = get_enrolled_users($this->get_context(), $capability, $groupids, 'u.*', null, 0, 0, true);

        } else if (is_array($groupids)) {
            foreach ($groupids as $groupid) {
                $groupusers = get_enrolled_users($this->get_context(), 'mod/scheduler:appoint', $groupid,
                                                 'u.*', null, 0, 0, true);
                foreach ($groupusers as $user) {
                    if (!array_key_exists($user->id, $users)) {
                        $users[$user->id] = $user;
                    }
                }
            }
        }

        $modinfo = get_fast_modinfo($this->courseid);
        $info = new \core_availability\info_module($modinfo->get_cm($this->cmid));
        $users = $info->filter_user_list($users);

        return $users;
    }

    /**
     * Get list of available students (i.e., users that can book slots)
     *
     * @param mixed $groupids - group id or array of group ids; if set, will only return users who are in these groups.
     * @return \stdClass[] array of moodle user records
     */
    public function get_available_students($groupids = 0) {

        return $this->get_available_users('mod/scheduler:appoint', $groupids);
    }

    /**
     * Get list of available teachers (i.e., users that can offer slots)
     *
     * @param mixed $groupids - only return users who are in this group.
     * @return \stdClass array of moodle user records
     */
    public function get_available_teachers($groupids = 0) {

        return $this->get_available_users('mod/scheduler:attend', $groupids);
    }

    /**
     * Checks whether there are any possible teachers for the scheduler
     *
     * @return bool whether teachers are present
     */
    public function has_available_teachers() {
        $teachers = $this->get_available_teachers();
        return count($teachers) > 0;
    }

    /**
     * Get a list of students that can still make an appointment.
     *
     * @param mixed $groups single group or array of groups - only return
     *            users who are in one of these group(s).
     * @param int $cutoff if the number of students in the course is more than this limit,
     *            the routine will return the number of students rather than a list
     *            (this is for performance reasons).
     * @param bool $onlymandatory include only students who _must_ (rather than _can_) make
     *            another appointment. This matters onyl in schedulers where students can make
     *            unlimited appointments.
     * @return int|array of moodle user records; or int 0 if there are no students in the course;
     *            or the number of students if there are too many students. Array keys are student ids.
     */
    public function get_students_for_scheduling($groups = '', $cutoff = 0, $onlymandatory = false) {
        $studs = $this->get_available_students($groups);
        if (($cutoff > 0 && count($studs) > $cutoff) || count($studs) == 0) {
            return count($studs);
        }
        $schedstuds = array();
        foreach ($studs as $stud) {
            $include = false;
            if ($this->allows_unlimited_bookings()) {
                $include = !$onlymandatory || !$this->has_slots_for_student($stud->id, false, false);
            } else {
                $include = ($this->count_bookable_appointments($stud->id, false) != 0);
            }
            if ($include) {
                $schedstuds[$stud->id] = $stud;
            }
        }
        return $schedstuds;
    }


    /**
     * Delete an appointment, and do whatever is needed
     *
     * @param int $appointmentid
     * @uses $DB
     */
    public function delete_appointment($appointmentid) {
        global $DB;

        if (!$oldrecord = $DB->get_record('scheduler_appointment', array('id' => $appointmentid))) {
            return;
        }

        $slot = $this->get_slot($oldrecord->slotid);
        $appointment = $slot->get_appointment($appointmentid);

        // Delete the appointment.
        $slot->remove_appointment($appointment);
        $slot->save();
    }

    /**
     * Frees all empty slots that are in the past, hence no longer bookable.
     * This applies to all schedulers in the system.
     *
     * @uses $CFG
     * @uses $DB
     */
    public static function free_late_unused_slots() {
        global $DB;

        $sql = "SELECT DISTINCT s.id
                           FROM {scheduler_slots} s
                LEFT OUTER JOIN {scheduler_appointment} a ON s.id = a.slotid
                          WHERE a.studentid IS NULL
                            AND starttime < ?";
        $now = time();
        $todelete = $DB->get_records_sql($sql, array($now), 0, 1000);
        if ($todelete) {
            list($usql, $params) = $DB->get_in_or_equal(array_keys($todelete));
            $DB->delete_records_select('scheduler_slots', " id $usql ", $params);
        }
    }

}
