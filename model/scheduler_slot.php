<?php

/**
 * A class for representing a scheduler slot.
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * A class for representing a scheduler slot.
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_slot extends mvc_child_record_model {

    protected $appointments;

    protected function get_table() {
        return 'scheduler_slots';
    }

    public function __construct(scheduler_instance $scheduler) {
        parent::__construct();
        $this->data = new stdClass();
        $this->data->id = 0;
        $this->set_parent($scheduler);
        $this->data->schedulerid = $scheduler->get_id();
        $this->appointments = new mvc_child_list($this, 'scheduler_appointment', 'slotid',
            new scheduler_appointment_factory($this));
    }

    /**
     * Create a scheduler slot from the database.
     */
    public static function load_by_id($id, scheduler_instance $scheduler) {
        $slot = new scheduler_slot($scheduler);
        $slot->load($id);
        return $slot;
    }

    /**
     * Create a scheduler slot from an already loaded record
     */
    /*  public static function load_from_record(stdClass $record, scheduler_instance $scheduler) {
     $slot = new scheduler_slot($scheduler);
     $slot->data = $record;
     return $slot;
     }

     */
    /**
     * Save any changes to the database
     */
    public function save() {
        parent::save();
        $this->appointments->save_children();
        $this->update_calendar();
    }


    /**
     * Return the teacher object
     */
    public function get_teacher() {
        global $DB;
        if ($this->data->teacherid) {
            return $DB->get_record('user', array('id' => $this->data->teacherid), '*', MUST_EXIST);
        } else {
            return null;
        }
    }


    /**
     * Return the end time of the slot
     */
    public function get_endtime() {
        return $this->data->starttime + $this->data->duration*MINSECS;
    }

    /**
     * Is the slot re-usable?
     */
    public function is_reusable() {
        return (boolean) ($this->data->reuse);
    }

    /**
     * Is this a group slot (i.e., more than one student is permitted)
     */
    public function is_groupslot() {
        return (boolean) !($this->data->exclusivity == 1);
    }


    /**
     * Does the slot have at least one appointment?
     */
    public function is_appointed() {
        return (boolean) ($this->data->appointcnt > 0);
    }

    public function get_appointment_count() {
        return $this->appointments->get_child_count();
    }

    /**
     * Has the slot been attended?
     */
    public function is_attended() {
        return (boolean) ($this->data->isattended);
    }

    /**
     * Has the slot been booked by a specific student?
     */
    public function is_booked_by_student($studentid) {
        $result = false;
        foreach ($this->get_appointments() as $appointment) {
            $result = $result || $appointment->studentid == $studentid;
        }
        return $result;
    }

    /**
     *  Get an array of all appointments
     */
    public function get_appointments() {
        return array_values($this->appointments->get_children());
    }

    /**
     * Create a new appointment relating to this slot.
     */
    public function create_appointment() {
        return $this->appointments->create_child();
    }

    /**
     * Remove an appointment from this slot.
     */
    public function remove_appointment($app) {
        $this->appointments->remove_child($app);
    }

    public function delete() {
        $this->appointments->delete_children();
        $this->clear_calendar();
        parent::delete();
    }


    /* The event code is SSstu (for a student event) or SSsup (for a teacher event).
     * then, the id of the scheduler slot that it belongs to.
     * finally, the courseID (legacy reasons -- not really used),
     * all in a colon delimited string. This will run into problems when the IDs of slots and courses
     * are bigger than 7 digits in length...
     */

    private function get_teacher_eventtype() {
        $slotid = $this->get_id();
        $courseid = $this->get_parent()->get_courseid();
        return "SSsup:{$slotid}:{$courseid}";
    }


    private function get_student_eventtype() {
        $slotid = $this->get_id();
        $courseid = $this->get_parent()->get_courseid();
        return "SSstu:{$slotid}:{$courseid}";
    }

    private function clear_calendar() {
        global $DB;
        $DB->delete_records('event', array('eventtype' => $this->get_teacher_eventtype()));
        $DB->delete_records('event', array('eventtype' => $this->get_student_eventtype()));
    }

    private function update_calendar() {

        global $DB;

        $scheduler = $this->get_parent();

        $myappointments = $this->appointments->get_children();

        $studentids = array();
        foreach ($myappointments as $appointment) {
            $studentids[] = $appointment->studentid;
        }

        $teacher = $DB->get_record('user', array('id' => $this->teacherid));
        $students = $DB->get_records_list('user', 'id', $studentids);
        $studentnames = array();
        foreach ($students as $student) {
            $studentnames[] = fullname($student);
        }

        $schedulername = $scheduler->get_name(true);
        $schedulerdescription = $scheduler->get_intro();

        $slotid = $this->get_id();
        $courseid = $scheduler->get_courseid();

        $baseevent = new stdClass();
        $baseevent->description = "$schedulername<br/><br/>$schedulerdescription";
        $baseevent->format = 1;
        $baseevent->modulename = 'scheduler';
        $baseevent->courseid = 0;
        $baseevent->instance = $this->get_parent_id();
        $baseevent->timestart = $this->starttime;
        $baseevent->timeduration = $this->duration * MINSECS;
        $baseevent->visible = 1;

        // Update student events.

        $studentevent = clone($baseevent);
        $studenteventname = get_string('meetingwith', 'scheduler').' '.$scheduler->get_teacher_name().', '.fullname($teacher);
        $studentevent->name = shorten_text($studenteventname, 200);

        $this->update_calendar_events( $this->get_student_eventtype(), $studentids, $studentevent);

        // Update teacher events.

        $teacherids = array();
        $teacherevent = clone($baseevent);
        if (count($studentids) > 0) {
            $teacherids[] = $teacher->id;
            if (count($studentids) > 1) {
                $teachereventname = get_string('meetingwithplural', 'scheduler').' '.
                    get_string('students', 'scheduler').', '.implode(', ', $studentnames);
            } else {
                $teachereventname = get_string('meetingwith', 'scheduler').' '.
                    get_string('student', 'scheduler').', '.$studentnames[0];
            }
            $teacherevent->name = shorten_text($teachereventname, 200);
        }

        $this->update_calendar_events( $this->get_teacher_eventtype(), $teacherids, $teacherevent);

    }

    private function update_calendar_events($eventtype, array $userids, $eventdata) {

        global $CFG, $DB;
        require_once($CFG->dirroot.'/calendar/lib.php');

        $eventdata->eventtype = $eventtype;

        $existingevents = $DB->get_records('event', array('modulename' => 'scheduler', 'eventtype' => $eventtype));
        $handledevents = array();
        $handledusers = array();

        // Update existing calendar events.
        foreach ($existingevents as $eventid => $existingdata) {
            if (in_array($existingdata->userid, $userids)) {
                $eventdata->userid = $existingdata->userid;
                $calendarevent = calendar_event::load($existingdata);
                $calendarevent->update($eventdata, false);
                $handledevents[] = $eventid;
                $handledusers[] = $existingdata->userid;
            }
        }

        // Add new calendar events.
        foreach ($userids as $userid) {
            if (!in_array($userid, $handledusers)) {
                $thisevent = clone($eventdata);
                $thisevent->userid = $userid;
                calendar_event::create($thisevent, false);
            }
        }

        // Remove old, obsolete calendar events.
        foreach ($existingevents as $eventid => $existingdata) {
            if (!in_array($eventid, $handledevents)) {
                $calendarevent = calendar_event::load($existingdata);
                $calendarevent->delete();
            }
        }

    }


}

class scheduler_slot_factory extends mvc_child_model_factory {
    public function create_child(mvc_record_model $parent) {
        return new scheduler_slot($parent);
    }
}

