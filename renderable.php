<?php

/**
 * This file contains the definition for the renderable classes for the assignment
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

/**
 * This class represents a table of slots associated with one student
 */
class scheduler_slot_table implements renderable {
    public $slots = array();
    public $scheduler;
    public $showgrades;

    public function add_slot(scheduler_slot $slotmodel, scheduler_appointment $appointmentmodel, $otherstudents) {
        $slot = new stdClass();
        $slot->starttime = $slotmodel->starttime;
        $slot->endtime = $slotmodel->endtime;
        $slot->attended = $appointmentmodel->attended;
        $slot->slotnotes = $slotmodel->notes;
        $slot->slotnotesformat = $slotmodel->notesformat;
        $slot->teacher = $slotmodel->get_teacher();
        $slot->appointmentnotes = $appointmentmodel->appointmentnote;
        $slot->appointmentnotesformat = $appointmentmodel->appointmentnoteformat;
        $slot->otherstudents = $otherstudents;
        if ($this->showgrades) {
            $slot->grade = $appointmentmodel->grade;
        }

        $this->slots[] = $slot;
    }

    public function __construct(scheduler_instance $scheduler, $showgrades=true) {
        $this->scheduler = $scheduler;
        $this->showgrades = $showgrades;
    }

}


/**
 * This class represents a list of students in a slot, to be displayed "inline" within a larger table
 */
class scheduler_student_list implements renderable {
    public $students = array();
    public $scheduler;
    public $showgrades;
    public $expandable = true;
    public $expanded = true;

    public function add_student(scheduler_appointment $appointmentmodel, $highlight) {
        $student = new stdClass();
        $student->user = $appointmentmodel->get_student();
        if ($this->showgrades) {
            $student->grade = $appointmentmodel->grade;
        }
        $student->highlight = $highlight;
        $this->students[]= $student;
    }

    public function __construct(scheduler_instance $scheduler, $showgrades=true) {
        $this->scheduler = $scheduler;
        $this->showgrades = $showgrades;
    }

}


/**
 * This class represents a table of slots which a student can book.
 */
class scheduler_slot_booker implements renderable {
    public $slots = array();
    public $scheduler;
    public $studentid;
    public $style;
    public $actionurl;
    public $maxselect;

    /**
     *  can the student press the "disengage" button?
     */
    public $candisengage = false;

    /**
     * Can the student choose to appoint a group? If yes,
     * this should be set to an array groupid => groupname.
     */
    public $groupchoice = array();

    public function add_slot(scheduler_slot $slotmodel, $canbook, $bookedbyme, $groupinfo, $otherstudents) {
        $slot = new stdClass();
        $slot->slotid = $slotmodel->id;
        $slot->starttime = $slotmodel->starttime;
        $slot->endtime = $slotmodel->endtime;
        $slot->location = $slotmodel->appointmentlocation;
        $slot->bookedbyme = $bookedbyme;
        $slot->canbook = $canbook;
        $slot->groupinfo = $groupinfo;
        $slot->teacher = $slotmodel->get_teacher();
        $slot->otherstudents = $otherstudents;

        $this->slots[] = $slot;
    }

    /**
     * Contructs a slot booker.
     *
     * @param scheduler_instance $scheduler the scheduler in which the booking takes place
     * @param int $studentid the student who books
     * @param moodle_url action_url
     * @param string $style 'one' or 'many'
     * @param int $maxselect the maximum number of boxes a student can select (set 0 for unlimited)
     */
    public function __construct(scheduler_instance $scheduler, $studentid, moodle_url $actionurl,  $style, $maxselect) {
        $this->scheduler = $scheduler;
        $this->studentid = $studentid;
        $this->style = $style;
        $this->actionurl = $actionurl;
        $this->maxselect = $maxselect;
    }

}
