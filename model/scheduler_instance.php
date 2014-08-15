<?php

/**
 * A class for representing a scheduler instance.
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once('modellib.php');

class scheduler_instance extends mvc_record_model {

    protected $cm = null;
    protected $context = null;
    protected $groupmode;
    protected $slots;
    protected $scalecache = null;

    protected function get_table() {
        return 'scheduler';
    }

    protected function __construct() {
        parent::__construct();
        $this->slots = new mvc_child_list($this, 'scheduler_slots', 'schedulerid',
                        new scheduler_slot_factory($this));
    }

    /**
     * Create a scheduler instance from the database.
     */
    public static function load_by_id($id) {
        global $DB;
        $cm = get_coursemodule_from_instance('scheduler', $id, 0, false, MUST_EXIST);
        return self::load_from_record($id, $cm);
    }

    /**
     * Create a scheduler instance from the database.
     */
    public static function load_by_coursemodule_id($cmid) {
        global $DB;
        $cm = get_coursemodule_from_id('scheduler', $cmid, 0, false, MUST_EXIST);
        return self::load_from_record($cm->instance, $cm);
    }

    /**
     * Create a scheduler instance from the database.
     */
    protected static function load_from_record($id, stdClass $coursemodule) {
        $scheduler = new scheduler_instance();
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
        $this->slots->delete_children();
        scheduler_grade_item_delete($this);
        parent::delete();
    }

    /**
     * Retrieve the course module id of this scheduler
     */
    public function get_cmid() {
        return $this->cm->id;
    }

    /**
     * Retrieve the course id of this scheduler
     */
    public function get_courseid() {
        return $this->data->course;
    }

    /**
     * Retrieve the activity module context of this scheduler
     */
    public function get_context() {
        if ($this->context == null) {
            $this->context = context_module::instance($this->get_cmid());
        }
        return $this->context;
    }

    /**
     * Return the last modification date (as stored in database) for this scheduler instance.
     */
    public function get_timemodified() {
        return $this->data->timemodified;
    }

    /**
     * Retrieve the name of this scheduler
     * @param boolean $applyfilters whether o apply filters so that the output is printable
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
     * @param boolean $applyfilters whether to apply filters so that the output is printable
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
     */
    public function get_teacher_name() {
        $name = format_string($this->data->staffrolename);
        if (empty($name)) {
            $name = get_string('teacher', 'scheduler');
        }
        return $name;
    }

    /**
     * Retrieve the default duration of a slot
     */
    public function get_default_slot_duration() {
        return $this->data->defaultslotduration;
    }

    /**
     * Retrieve whether group scheduling is enabled in this instance
     */
    public function is_group_scheduling_enabled() {
        global $CFG;
        $globalenable = (bool) $CFG->scheduler_groupscheduling;
        $localenable = $this->groupmode > 0;
        return $globalenable && $localenable;
    }

    /**
     * get the last location of a certain teacher in this scheduler
     * @param $user
     * @uses $DB
     * @return the last known location for the current user (teacher)
     */
    public function get_last_location($user) {
        global $DB;

        $select = 'schedulerid = :schedulerid AND teacherid = :teacherid ORDER BY timemodified DESC LIMIT 1';
        $params = array('schedulerid' => $this->data->id, 'teacherid' => $user->id);
        $lastlocation = $DB->get_field('scheduler_slots', 'appointmentlocation', $params, IGNORE_MULTIPLE);
        if (!$lastlocation) {
            $lastlocation = '';
        }
        return $lastlocation;
    }

    /**
     * Checks whether this scheduler allows a student (in principle) to book several slots at a time
     * @return boolean whether the student can book multiple appointements
     */
    public function allows_multiple_bookings() {
        return ($this->maxbookings != 1);
    }

    /**
     * Checks whether this scheduler uses grading at all.
     * @return boolean
     */
    public function uses_grades() {
        return ($this->scale != 0);
    }

    public function get_scale_levels() {
        global $DB;

        if (is_null($this->scalecache)) {
            $this->scalecache = array();
            if ($this->scale < 0) {
                $scaleid = -($this->scale);
                if ($scale = $DB->get_record('scale', array('id' => $scaleid))) {
                    $levels = explode(',', $scale->scale);
                    foreach ($levels as $id => $value) {
                        $this->scalecache[$id+1] = $value;
                    }
                }
            }
        }
        return $this->scalecache;
    }

    /* *********************** Loading lists of slots *********************** */


    /**
     * Fetch a generic list of slots from the database
     */
    protected function fetch_slots($wherecond, $havingcond, array $params, $limitfrom='', $limitnum='', $orderby='s.id') {
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

        $order = 'ORDER BY '.$orderby;

        $sql = "$select $where $having $order";

        $slotdata = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        $slots = array();
        foreach ($slotdata as $slotrecord) {
            $slot = new scheduler_slot($this);
            $slot->load_record($slotrecord);
            $slots[] = $slot;
        }
        return $slots;
    }

    /**
     * Subquery that counts appointments in the current slot.
     * Only to be used in conjunction with fetch_slots()
     */
    protected function appointment_count_query() {
        return '(SELECT COUNT(a.id) FROM {scheduler_appointment} a WHERE a.slotid=s.id)';
    }

	protected $studparno = 0;
    protected function student_in_slot_condition(&$params, $studentid, $mustbeattended, $mustbeunattended) {
        $cond = 'EXISTS (SELECT 1 FROM {scheduler_appointment} a WHERE a.studentid = :studentid'.$this->studparno.' and a.slotid=s.id';
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

    public function get_slot($id) {

        global $DB;

        $slotdata = $DB->get_record('scheduler_slots', array('id' => $id, 'schedulerid'=> $this->id), '*', MUST_EXIST);
        $slot = new scheduler_slot($this);
        $slot->load_record($slotdata);
        return $slot;
    }

    public function get_slots() {
        return $this->slots->get_children();
    }

    public function get_slot_count() {
        return $this->slots->get_child_count();
    }

    public function get_all_slots($limitfrom='', $limitnum='') {
        return $this->fetch_slots('', '', array(), $limitfrom, $limitnum);
    }

    public function get_slots_for_teacher($teacherid) {
        $wherecond = 'teacherid = :teacherid';
        $paras = array('teacherid' => $teacherid);
        return $this->fetch_slots($wherecond, '', $paras, '', '');
    }

    /**
     * Retrieves attended of a student. These will be sorted by start time.
     *
     * @param int $studentid
     */
    public function get_attended_slots_for_student($studentid) {

        $params = array();
        $wherecond = $this->student_in_slot_condition($params, $studentid, true, false);

        $slots = $this->fetch_slots($wherecond, '', $params, '', '', 's.starttime');

        return $slots;
    }

    /**
     * Retrieves upcoming slots booked by a student. These will be sorted by start time.
     * A slot is "upcoming" if it is not attended, but can no longer be rebooked;
     * because it is closer than the "guard time" to the current time.
     *
     * @param int $studentid
     */
    public function get_upcoming_slots_for_student($studentid) {

        $params = array();
        $wherecond = '(s.starttime <= :guard)';
        $params['guard'] = time() + $this->guardtime;
        $wherecond .= ' AND '.$this->student_in_slot_condition($params, $studentid, false, true);

        $slots = $this->fetch_slots($wherecond, '', $params, '', '', 's.starttime');

        return $slots;
    }

    /**
     * retrieves slots available to a student
     * Note: this does not check for scheduling conflicts.
     *
     * @param int $studentid
     * @param boolean $includebooked include slots that were booked by this student (but not yet attended)
     * @uses $CFG
     * @uses $DB
     */
    public function get_slots_available_to_student($studentid, $includebooked = false) {
        global $CFG, $DB;

        $params = array();
        $wherecond = '(s.starttime > :cutofftime) AND (s.hideuntil < :nowhide)';
        $params['nowhide'] = time();
        $params['cutofftime'] = time() + $this->guardtime;
        $subcond = '(s.exclusivity = 0 OR s.exclusivity > '.$this->appointment_count_query().')'
             . ' AND NOT ('.$this->student_in_slot_condition($params, $studentid, false, false).')';
        if ($includebooked) {
            $subcond = '('.$subcond.') OR ('.$this->student_in_slot_condition($params, $studentid, false, true).')';
        }
        $wherecond .= ' AND ('.$subcond.')';
        $order = 's.starttime ASC';
        $slots = $this->fetch_slots($wherecond, '', $params, '', '', $order);

        return $slots;
    }

    /**
     * retrieves slots without any appointment made
     */
    public function get_slots_without_appointment() {
        $wherecond = $this->appointment_count_query().' = 0';
        $slots = $this->fetch_slots($wherecond, '', array());
        return $slots;
    }

    /* ************** End of slot retrieveal routines ******************** */

    /**
     * retrieves an appointment and the corresponding slot
     */
    public function get_slot_appointment($appointmentid) {
        global $DB;

        $appointrec = $DB->get_record('scheduler_appointment', array('id' => $appointmentid), '*', MUST_EXIST);
        $slotrec = $DB->get_record('scheduler_slots', array('id' => $appointrec->slotid), '*', MUST_EXIST);

        $slot = new scheduler_slot($this);
        $slot->load_record($slotrec);
        $appointment = new scheduler_appointment($slot);
        $appointment->load_record($appointrec);

        return array($slot, $appointment);
    }

    /**
     * Create a new slot relating to this scheduler.
     */
    public function create_slot() {
        return $this->slots->create_child();
    }

    /**
     * Computes how many appointments a student can still book.
     *
     * @param int $studentid
     * @return int the number of bookable or changeable appointments, possibly 0; returns -1 if unlimited.
     */
    public function count_bookable_appointments($studentid) {
        global $DB;

        // find how many slots have already been booked/assigned, unchangeably
        $sql = 'SELECT COUNT(*) FROM {scheduler_slots} s'
                        .' JOIN {scheduler_appointment} a ON s.id = a.slotid'
                                        .' WHERE s.schedulerid = :schedulerid AND a.studentid=:studentid';
        if ($this->schedulermode == 'onetime') {
            $sql .= ' AND s.starttime <= :cutofftime AND a.attended = 0';
        } else {
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
     * get list of possible attendees (i.e., users that can make an appointment)
     * @param $groups - single group or array of groups - only return
     *                  users who are in one of these group(s).
     * @return array of moodle user records
     */
    public function get_possible_attendees($groups = '') {
        // TODO does this need to go to the controller?
        $attendees = get_users_by_capability($this->get_context(), 'mod/scheduler:appoint', '',
                        'lastname, firstname', '', '', $groups, '', false, false, false);

        return $attendees;
    }

    /**
     * Get a list of students that can still mae an appointment
     *
     * @param $groups - single group or array of groups - only return
     *                  users who are in one of these group(s).
     * @param int $cutoff - if the number of students in the course is more than this limit,
     * 						the routine will return
     *                      (this is for performance reasons)
     * @return int|array of moodle user records; or integer 0 if there are no students in the course;
     *                    or the number of students if there are too many students
     */
    public function get_students_for_scheduling($groups = '', $cutoff = 0) {
        $studs = $this->get_possible_attendees($groups);
        if (($cutoff > 0 && count($studs) > $cutoff) || count($studs) == 0) {
            return count($studs);
        }
        $schedstuds = array();
        foreach ($studs as $stud) {
            if ($this->count_bookable_appointments($stud->id) > 0) {
                $schedstuds[] = $stud;
            }
        }
        return $schedstuds;
    }


    /**
     * Delete an appointment, and do whatever is needed
     *
     * N.B. this might delete certain empty slots as well.
     *
     * @param int $appointmentid
     * @param object $slot
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
     * frees all empty slots that are in the past, hance no longer bookable
     * @uses $CFG
     * @uses $DB
     */
    public function free_late_unused_slots() {
        global $CFG, $DB;

        $now = time();
        $sql =  'SELECT DISTINCT s.id FROM {scheduler_slots} s '
                        .'LEFT JOIN {scheduler_appointment} a ON s.id = a.slotid '
                                        .'WHERE a.studentid IS NULL AND s.schedulerid = ? AND starttime < ?';
        $todelete = $DB->get_records_sql($sql, array($this->id, $now));
        if ($todelete) {
            list($usql, $params) = $DB->get_in_or_equal(array_keys($todelete));
            $DB->delete_records_select('scheduler_slots', " id $usql ", $params);
        }
    }

}
