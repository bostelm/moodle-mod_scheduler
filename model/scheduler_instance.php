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
    protected $groupmode;
    protected $slots;

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
        parent::delete();
        $this->slots->delete_children();
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
     */
    public function get_teacher_name() {
        $name = $this->data->staffrolename;
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
        $params = array('schedulerid'=>$this->data->id, 'teacherid'=>$user->id);
        $lastlocation = $DB->get_field('scheduler_slots', 'appointmentlocation', $params, IGNORE_MISSING);
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

    /* *********************** Loading lists of slots *********************** */

    /**
     * Load a generic list of slots
     */
    protected function load_slots($wherecond, $havingcond, array $params, $limitfrom='', $limitnum='') {
        global $DB;
        $acntselect = '(SELECT COUNT(a.id) FROM {scheduler_appointment} a WHERE a.slotid=s.id) AS appointcnt';
        $attendedselect = 'EXISTS(SELECT 1 FROM {scheduler_appointment} a WHERE a.slotid=s.id AND a.attended=1) AS isattended';
        $select = 'SELECT s.*, '.$acntselect.', '.$attendedselect.' FROM {scheduler_slots} s';

        $where = 'WHERE schedulerid = :schedulerid';
        if ($wherecond) {
            $where .= ' AND ('.$wherecond.')';
        }
        $params['schedulerid'] = $this->data->id;

        $having = '';
        if ($havingcond) {
            $having = 'HAVING '.$havingcond;
        }

        $sql = "$select $where $having";

        $slotdata = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        $slots = array();
        foreach ($slotdata as $slotrecord) {
            $slot = new scheduler_slot($this);
            $slot->load_record($slotrecord);
            $slots[$slotrecord->id] = $slot;
        }
        return $slots;
    }

    protected function slots_in_future_condition(&$params) {
        $params['cutofftime'] = time();
        return '(s.starttime > :cutofftime)';
    }

    public function get_slots() {
        return $this->slots->get_children();
    }

    public function get_all_slots($limitfrom='', $limitnum='') {
        return $this->load_slots(null, null, array(), $limitfrom, $limitnum);
    }

    public function get_slot_count() {
        global $DB;
        return $DB->count_records('scheduler_slots', array('schedulerid'=>$this->data->id));
    }

    /**
     * retrieves slots available to a student
     * @param int $studentid
     * @param boolean $includebooked include slots that were booked by this student (but not yet attended)
     * @uses $CFG
     * @uses $DB
     */
    public function get_slots_available_to_student($studentid, $includebooked = false) {
        global $CFG, $DB;

        $params = array();
        $wherecond = '';
        $havingcond = $this->slots_in_future_condition($params).'AND (s.exclusivity = 0 OR s.exclusivity > appointcnt)';
        if ($includebooked) {
            $havingcond = '('.$havingcond.') OR EXISTS '.
                '(SELECT 1 FROM {scheduler_appointment} a WHERE a.studentid = :studentid and a.slotid=s.id AND a.attended = 0)';
            $params['studentid'] = $studentid;
        }
        $slots = $this->load_slots($wherecond, $havingcond, $params);

        return $slots;
    }

    /**
     * retrieves slots without any appointment made
     */
    public function get_slots_without_appointment() {
        $havingcond = 'appointcnt = 0';
        $slots = $this->load_slots('', $havingcond, array());
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
     * get list of possible attendees (i.e., users that can make an appointment)
     * @param $groups - single group or array of groups - only return
     *                  users who are in one of these group(s).
     * @return array of moodle user records
     */
    public function get_possible_attendees($groups = '') {
        // TODO does this need to go to the controller?
        $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        $attendees = get_users_by_capability($context, 'mod/scheduler:appoint', '',
                         'lastname, firstname', '', '', $groups, '', false, false, false);

        return $attendees;
    }

}
