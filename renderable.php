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
    public $showactions;
    public $actionurl;

    public function add_slot(scheduler_slot $slotmodel, scheduler_appointment $appointmentmodel, $otherstudents, $cancancel = false) {
        $slot = new stdClass();
        $slot->slotid = $slotmodel->id;
        $slot->starttime = $slotmodel->starttime;
        $slot->endtime = $slotmodel->endtime;
        $slot->attended = $appointmentmodel->attended;
        $slot->location = $slotmodel->appointmentlocation;
        $slot->slotnotes = $slotmodel->notes;
        $slot->slotnotesformat = $slotmodel->notesformat;
        $slot->teacher = $slotmodel->get_teacher();
        $slot->appointmentid = $appointmentmodel->id;
        $slot->appointmentnotes = $appointmentmodel->appointmentnote;
        $slot->appointmentnotesformat = $appointmentmodel->appointmentnoteformat;
        $slot->otherstudents = $otherstudents;
        $slot->cancancel = $cancancel;
        if ($this->showgrades) {
            $slot->grade = $appointmentmodel->grade;
        }
        $this->showactions = $this->showactions || $cancancel;

        $this->slots[] = $slot;
    }

    public function __construct(scheduler_instance $scheduler, $showgrades=true, $actionurl = null) {
        $this->scheduler = $scheduler;
        $this->showgrades = $showgrades;
        $this->actionurl = $actionurl;
        $this->showactions = false;
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
    public $editable = false;
    public $checkboxname = '';
    public $buttontext = '';
    public $actionurl = null;
    public $linkappointment = false;

    public function add_student(scheduler_appointment $appointmentmodel, $highlight, $checked = false, $showgrade = true) {
        $student = new stdClass();
        $student->user = $appointmentmodel->get_student();
        if ($this->showgrades && $showgrade) {
            $student->grade = $appointmentmodel->grade;
        } else {
            $student->grade = null;
        }
        $student->highlight = $highlight;
        $student->checked = $checked;
        $student->entryid = $appointmentmodel->id;
        $this->students[] = $student;
    }

    public function __construct(scheduler_instance $scheduler, $showgrades = true) {
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
    public $actionurl;
    public $maxselect;

    public function add_slot(scheduler_slot $slotmodel, $canbook, $bookedbyme, $groupinfo, $otherstudents) {
        $slot = new stdClass();
        $slot->slotid = $slotmodel->id;
        $slot->starttime = $slotmodel->starttime;
        $slot->endtime = $slotmodel->endtime;
        $slot->location = $slotmodel->appointmentlocation;
        $slot->notes = $slotmodel->notes;
        $slot->notesformat = $slotmodel->notesformat;
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
     * @param int $maxselect the maximum number of boxes a student can select (set 0 for unlimited)
     */
    public function __construct(scheduler_instance $scheduler, $studentid, moodle_url $actionurl, $maxselect) {
        $this->scheduler = $scheduler;
        $this->studentid = $studentid;
        $this->actionurl = $actionurl;
        $this->maxselect = $maxselect;
    }

}


class scheduler_command_bar implements renderable {
    public $menus = array();
    public $linkactions = array();
    public $title = '';

    /**
     * Adds a group of menu items in a menu.
     *
     * @param string $title the title of the group
     * @param array $actions an array of action_menu_link instances, representing the commands
     */
    public function add_group($title, array $actions) {
        $menu = new action_menu($actions);
        $menu->actiontext = $title;
        $this->menus[] = $menu;
    }

    public function action_link(moodle_url $url, $titlekey, $iconkey, $confirmkey = null, $id = null) {
        $title = get_string($titlekey, 'scheduler');
        $pix = new pix_icon($iconkey, $title, 'moodle', array('class' => 'iconsmall', 'title' => ''));
        $attributes = array();
        if ($id) {
            $attributes['id'] = $id;
        }
        if ($confirmkey) {
            if (!$id) {
                $id = html_writer::random_id('command_link');
            }
            $attributes['id'] = $id;
            $this->linkactions[$id] = new confirm_action(get_string($confirmkey, 'scheduler'));
        }
        $act = new action_menu_link_secondary($url, $pix, $title, $attributes);
        return $act;
    }

    /**
     * Contructs a command bar
     */
    public function __construct() {
        // Nothing to add right now.
    }

}

/**
 * This class represents a table of slots displayed to a teacher, with options to modify the list.
 */
class scheduler_slot_manager implements renderable {

    public $slots = array();
    public $scheduler;
    public $studentid;
    public $actionurl;

    /**
     *  should the teacher owning the slot be shown?
     */
    public $showteacher = true;

    public function add_slot(scheduler_slot $slotmodel, scheduler_student_list $students, $editable) {
        $slot = new stdClass();
        $slot->slotid = $slotmodel->id;
        $slot->starttime = $slotmodel->starttime;
        $slot->endtime = $slotmodel->endtime;
        $slot->teacher = $slotmodel->get_teacher();
        $slot->students = $students;
        $slot->editable = $editable;
        $slot->isattended = $slotmodel->is_attended();
        $slot->isappointed = $slotmodel->get_appointment_count();
        $slot->exclusivity = $slotmodel->exclusivity;

        $this->slots[] = $slot;
    }

    /**
     * Contructs a slot manager.
     *
     * @param scheduler_instance $scheduler the scheduler in which the booking takes place
     * @param moodle_url action_url
     */
    public function __construct(scheduler_instance $scheduler, moodle_url $actionurl) {
        $this->scheduler = $scheduler;
        $this->actionurl = $actionurl;
    }

}


/**
 * This class represents a table of slots displayed to a teacher, with options to modify the list.
 */
class scheduler_scheduling_list implements renderable {

    public $lines = array();
    public $scheduler;
    public $extraheaders;
    public $id = 'schedulinglist';

    public function add_line($pix, $name, array $extrafields, $actions) {
        $line = new stdClass();
        $line->pix = $pix;
        $line->name = $name;
        $line->extrafields = $extrafields;
        $line->actions = $actions;

        $this->lines[] = $line;
    }

    /**
     * Contructs a scheduling list
     *
     * @param scheduler_instance $scheduler the scheduler in which the booking takes place
     * @param moodle_url action_url
     */
    public function __construct(scheduler_instance $scheduler, array $extraheaders) {
        $this->scheduler = $scheduler;
        $this->extraheaders = $extraheaders;
    }

}


/**
 * Represents information about a student's total grade in the scheduler, plus gradebook information.
 * To be used in teacher screens.
 */
class scheduler_totalgrade_info implements renderable {

    public $gbgrade;
    public $scheduler;
    public $showtotalgrade;
    public $totalgrade;

    /**
     * Constructs a grade info object
     *
     * @param scheduler_instance $scheduler the scheduler in question
     * @param stdClass $gbgrade information about the grade in the gradebook (may be null)
     * @param string $showtotalgrade whether the total grade in the scheduler should be shown
     * @param int $totalgrade the total grade of the student in this scheduler
     */
    public function __construct(scheduler_instance $scheduler, $gbgrade, $showtotalgrade = false, $totalgrade = 0) {
        $this->scheduler = $scheduler;
        $this->gbgrade = $gbgrade;
        $this->showtotalgrade = $showtotalgrade;
        $this->totalgrade = $totalgrade;
    }

}