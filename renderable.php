<?php

/**
 * This file contains the definition for the renderable classes for the assignment
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This class represents a table of slots associated with one student
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_slot_table implements renderable {

    /** @var array list of slots in this table */
    public $slots = array();

    /** @var scheduler_instance the scheduler that the slots are in */
    public $scheduler;

    /** @var bool whether to show grades in the table */
    public $showgrades;

    /** @var bool whether any slot in the table has other students to show */
    public $hasotherstudents = false;

    /** @var bool whether to show start/end time of the slots */
    public $showslot = true;

    /** @var bool whether to show the attended/not attended icons */
    public $showattended = false;

    /** @var bool whether to show action buttons (for cancelling) */
    public $showactions = true;

    /** @var bool whether to show (confidential) teacher notes */
    public $showteachernotes = false;

    /** @var bool whether to show a link to edit appointments */
    public $showeditlink = false;

    /** @var bool whether to show the location of the appointment */
    public $showlocation = true;

    /** @var bool whether to show the students in the slot */
    public $showstudent = false;

    /** @var moodle_url|null action URL for buttons */
    public $actionurl;

    /**
     * Add a slot to the table.
     *
     * @param scheduler_slot $slotmodel the slot to be added
     * @param scheduler_appointment $appointmentmodel the corresponding appointment
     * @param array $otherstudents any other students in the same slot
     * @param bool $cancancel whether the use can canel the appointment
     * @param bool $canedit whether the use can edit the slot/appointment
     * @param bool $canview whether the use can view the appointment
     */
    public function add_slot(scheduler_slot $slotmodel, scheduler_appointment $appointmentmodel,
                             $otherstudents, $cancancel = false, $canedit = false, $canview = false) {
        $slot = new stdClass();
        $slot->slotid = $slotmodel->id;
        if ($this->showstudent) {
            $slot->student = $appointmentmodel->student;
        }
        $slot->starttime = $slotmodel->starttime;
        $slot->endtime = $slotmodel->endtime;
        $slot->attended = $appointmentmodel->attended;
        $slot->location = $slotmodel->appointmentlocation;
        $slot->slotnote = $slotmodel->notes;
        $slot->slotnoteformat = $slotmodel->notesformat;
        $slot->teacher = $slotmodel->get_teacher();
        $slot->appointmentid = $appointmentmodel->id;
        if ($this->scheduler->uses_appointmentnotes()) {
            $slot->appointmentnote = $appointmentmodel->appointmentnote;
            $slot->appointmentnoteformat = $appointmentmodel->appointmentnoteformat;
        }
        if ($this->scheduler->uses_teachernotes() && $this->showteachernotes) {
            $slot->teachernote = $appointmentmodel->teachernote;
            $slot->teachernoteformat = $appointmentmodel->teachernoteformat;
        }
        $slot->otherstudents = $otherstudents;
        $slot->cancancel = $cancancel;
        $slot->canedit = $canedit;
        $slot->canview = $canview;
        if ($this->showgrades) {
            $slot->grade = $appointmentmodel->grade;
        }
        $this->showactions = $this->showactions || $cancancel;
        $this->hasotherstudents = $this->hasotherstudents || (bool) $otherstudents;

        $this->slots[] = $slot;
    }

    /**
     * Create a new slot table.
     *
     * @param scheduler_instance $scheduler the scheduler in which the slots are
     * @param bool $showgrades whether to show grades
     * @param moodle_url|null $actionurl action URL for buttons
     */
    public function __construct(scheduler_instance $scheduler, $showgrades=true, $actionurl = null) {
        $this->scheduler = $scheduler;
        $this->showgrades = $showgrades && $scheduler->uses_grades();
        $this->actionurl = $actionurl;
    }

}


/**
 * This class represents a list of students in a slot, to be displayed "inline" within a larger table
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_student_list implements renderable {

    /** @var array list of students to be displayed */
    public $students = array();

    /** @var scheduler_instance the scheduler in whose context the list is */
    public $scheduler;

    /** @var bool whether tho show the grades of the students */
    public $showgrades;

    /** @var bool whether to show students in an expandable list */
    public $expandable = true;

    /** @var bool whether the expandable list is already expanded */
    public $expanded = true;

    /** @var bool whether appointments can be edited */
    public $editable = false;

    /** @var string name of the checkbox group used for marking students as seen */
    public $checkboxname = '';

    /** @var string text of the edit button */
    public $buttontext = '';

    /** @var moodle_url|null action URL for buttons */
    public $actionurl = null;

    /** @var bool whether to include links to individual appointments */
    public $linkappointment = false;

    /**
     * Add a student to the list.
     *
     * @param scheduler_appointment $appointment the appointment to add (one student)
     * @param bool $highlight whether this entry is highlighted
     * @param bool $checked whether the "seen" tickbox is checked
     * @param bool $showgrade whether to show a grade with this entry
     * @param bool $showstudprovided whether to show an icon for student-provided files
     */
    public function add_student(scheduler_appointment $appointment, $highlight, $checked = false,
                                $showgrade = true, $showstudprovided = false) {
        $student = new stdClass();
        $student->user = $appointment->get_student();
        if ($this->showgrades && $showgrade) {
            $student->grade = $appointment->grade;
        } else {
            $student->grade = null;
        }
        $student->highlight = $highlight;
        $student->checked = $checked;
        $student->entryid = $appointment->id;
        $scheduler = $appointment->get_scheduler();
        $student->notesprovided = false;
        $student->filesprovided = 0;
        if ($showstudprovided) {
            $student->notesprovided = $scheduler->uses_studentnotes() && $appointment->has_studentnotes();
            if ($scheduler->uses_studentfiles()) {
                $student->filesprovided = $appointment->count_studentfiles();
            }
        }
        $this->students[] = $student;
    }

    /**
     * Create a new student list.
     *
     * @param scheduler_instance $scheduler the scheduler in whose context the list is
     * @param bool $showgrades whether tho show grades of students
     */
    public function __construct(scheduler_instance $scheduler, $showgrades = true) {
        $this->scheduler = $scheduler;
        $this->showgrades = $showgrades;
    }

}


/**
 * This class represents a table of slots which a student can book.
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_slot_booker implements renderable {

    /**
     * @var array list of slots to be displayed
     */
    public $slots = array();

    /**
     * @var scheduler_instance scheduler in whose context the list is
     */
    public $scheduler;

    /**
     * @var int the id number of the student booking slots
     */
    public $studentid;

    /**
     * @var moodle_url action url for buttons
     */
    public $actionurl;

    /**
     * Add a slot to the list.
     *
     * @param scheduler_slot $slotmodel the slot to be added
     * @param bool $canbook whether the slot can be booked
     * @param bool $bookedbyme whether the slot is already booked by the current student
     * @param string $groupinfo information about group slots
     * @param array $otherstudents other students in this slot
     */
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
     * @param int $maxselect no longer used
     */
    public function __construct(scheduler_instance $scheduler, $studentid, moodle_url $actionurl, $maxselect) {
        $this->scheduler = $scheduler;
        $this->studentid = $studentid;
        $this->actionurl = $actionurl;
    }

}

/**
 * Command bar with action buttons, used by teachers.
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_command_bar implements renderable {

    /**
     * @var array list of drop-down menus in the command bar
     */
    public $menus = array();

    /**
     * @var array list of action_link objects used in the menu
     */
    public $linkactions = array();

    /**
     * @var string title of the menu
     */
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

    /**
     * Creates an action link with an optional confirmation dialogue attached.
     *
     * @param moodle_url $url URL of the action
     * @param string $titlekey key of the link title
     * @param string $iconkey key of the icon to display
     * @param string|null $confirmkey key for the confirmation text
     * @param string|null $id id attribute of the new link
     * @return action_link the new action link
     */
    public function action_link(moodle_url $url, $titlekey, $iconkey, $confirmkey = null, $id = null) {
        $title = get_string($titlekey, 'scheduler');
        $pix = new pix_icon($iconkey, $title, 'moodle', array('class' => 'iconsmall', 'title' => ''));
        $attributes = array();
        if ($id) {
            $attributes['id'] = $id;
        }
        $confirmaction = null;
        if ($confirmkey) {
            $confirmaction = new confirm_action(get_string($confirmkey, 'scheduler'));
        }
        $act = new action_link($url, $title, $confirmaction, $attributes, $pix);
        $act->primary = false;
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
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_slot_manager implements renderable {

    /**
     * @var array list of slots
     */
    public $slots = array();

    /**
     * @var scheduler_instance scheduler in whose context the list is
     */
    public $scheduler;

    /**
     * @var moodle_url action URL for buttons
     */
    public $actionurl;

    /**
     * @var bool should the teacher owning the slot be shown?
     */
    public $showteacher = true;

    /**
     * Add a slot to the list.
     *
     * @param scheduler_slot $slotmodel the slot to be added
     * @param scheduler_student_list $students the list of students in the slot
     * @param bool $editable whether the slot is editable
     */
    public function add_slot(scheduler_slot $slotmodel, scheduler_student_list $students, $editable) {
        $slot = new stdClass();
        $slot->slotid = $slotmodel->id;
        $slot->starttime = $slotmodel->starttime;
        $slot->endtime = $slotmodel->endtime;
        $slot->location = $slotmodel->appointmentlocation;
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
     * @param moodle_url $actionurl action URL for buttons
     */
    public function __construct(scheduler_instance $scheduler, moodle_url $actionurl) {
        $this->scheduler = $scheduler;
        $this->actionurl = $actionurl;
    }

}


/**
 * A list of students displayed to a teacher, with action menus to schedule the students.
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_scheduling_list implements renderable {

    /**
     * @var array lines in the list
     */
    public $lines = array();

    /**
     * @var scheduler_instance the scheduler in whose context the list is
     */
    public $scheduler;

    /**
     * @var array extra headers for custom fields in the list
     */
    public $extraheaders;

    /**
     * @var string HTML id of the list
     */
    public $id = 'schedulinglist';

    /**
     * Add a line to the list.
     *
     * @param string $pix icon to display next to the student's name
     * @param string $name name of the student
     * @param array $extrafields content of extra data fields to be displayed
     * @param array $actions actions to be displayed in an action menu
     */
    public function add_line($pix, $name, array $extrafields, $actions) {
        $line = new stdClass();
        $line->pix = $pix;
        $line->name = $name;
        $line->extrafields = $extrafields;
        $line->actions = $actions;

        $this->lines[] = $line;
    }

    /**
     * Contructs a scheduling list.
     *
     * @param scheduler_instance $scheduler the scheduler in which the booking takes place
     * @param array $extraheaders headers for extra data fields
     */
    public function __construct(scheduler_instance $scheduler, array $extraheaders) {
        $this->scheduler = $scheduler;
        $this->extraheaders = $extraheaders;
    }

}


/**
 * Represents information about a student's total grade in the scheduler, plus gradebook information.
 * To be used in teacher screens.
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_totalgrade_info implements renderable {

    /**
     * @var stdClass|null gradebook grade for the student
     */
    public $gbgrade;

    /**
     * @var scheduler_instance scheduler in whose context the information is
     */
    public $scheduler;

    /**
     * @var bool whether to show a total grade
     */
    public $showtotalgrade;

    /**
     * @var int the total grade to display
     */
    public $totalgrade;

    /**
     * Constructs a grade info object
     *
     * @param scheduler_instance $scheduler the scheduler in question
     * @param stdClass $gbgrade information about the grade in the gradebook (may be null)
     * @param bool $showtotalgrade whether the total grade in the scheduler should be shown
     * @param int $totalgrade the total grade of the student in this scheduler
     */
    public function __construct(scheduler_instance $scheduler, $gbgrade, $showtotalgrade = false, $totalgrade = 0) {
        $this->scheduler = $scheduler;
        $this->gbgrade = $gbgrade;
        $this->showtotalgrade = $showtotalgrade;
        $this->totalgrade = $totalgrade;
    }

}

/**
 * This class represents a list of scheduling conflicts.
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_conflict_list implements renderable {

    /**
     * @var array list of conflicts
     */
    public $conflicts = array();

    /**
     * Add a conflict to the list.
     *
     * @param stdClass $conflict information about the conflict
     * @param stdClass $user the user who is affected
     */
    public function add_conflict(stdClass $conflict, $user = null) {
        $c = clone($conflict);
        if ($user) {
            $c->userfullname = fullname($user);
        } else {
            $c->userfullname = '';
        }
        $this->conflicts[] = $c;
    }

    /**
     * Add several conflicts to the list.
     *
     * @param array $conflicts information about the conflicts
     */
    public function add_conflicts(array $conflicts) {
        foreach ($conflicts as $c) {
            $this->add_conflict($c);
        }
    }

}

/**
 * Information about an appointment in the scheduler.
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_appointment_info implements renderable {

    /**
     * @var scheduler_instance scheduler in whose context the appointment is
     */
    public $scheduler;

    /**
     * @var scheduler_slot slot in which the appointment is
     */
    public $slot;

    /**
     * @var scheduler_appointment the appointment itself
     */
    public $appointment;

    /**
     * @var bool whether to show information about the slot (times, etc.)
     */
    public $showslotinfo;

    /**
     * @var bool whether to show booking instructions
     */
    public $showbookinginfo;

    /**
     * @var bool whether to show information about the student
     */
    public $showstudentdata;

    /**
     * @var string information about the group the booking is for
     */
    public $groupinfo;

    /**
     * @var bool whether the information is shown to a student (rather than a teacher)
     */
    public $onstudentside;

    /**
     * @var bool whether to show grades and appointment notes
     */
    public $showresult;

    /**
     * Create appointment information for a new appointment in a slot.
     *
     * @param scheduler_slot $slot the slot in question
     * @param bool $showbookinginstr whether to show booking instructions
     * @param bool $onstudentside whether the screen is shown to a student
     * @param string $groupinfo information about the group that the booking is for
     * @return scheduler_appointment_info
     */
    public static function make_from_slot(scheduler_slot $slot, $showbookinginstr = true, $onstudentside = true,
                                          $groupinfo = null) {
        $info = new scheduler_appointment_info();
        $info->slot = $slot;
        $info->scheduler = $slot->get_scheduler();
        $info->showslotinfo = true;
        $info->showbookinginfo = $showbookinginstr;
        $info->showstudentdata   = false;
        $info->showresult   = false;
        $info->onstudentside = $onstudentside;
        $info->groupinfo = $groupinfo;

        return $info;
    }

    /**
     * Create appointment information for an existing appointment.
     *
     * @param scheduler_slot $slot the slot in question
     * @param scheduler_appointment $appointment the appointment in question
     * @param string $onstudentside whether the screen is shown to a student
     * @return scheduler_appointment_info
     */
    public static function make_from_appointment(scheduler_slot $slot, scheduler_appointment $appointment, $onstudentside = true) {
        $info = new scheduler_appointment_info();
        $info->slot = $slot;
        $info->appointment = $appointment;
        $info->scheduler = $slot->get_scheduler();
        $info->showslotinfo = true;
        $info->showboookinginfo = true;
        $info->showstudentdata = $info->scheduler->uses_studentdata();
        $info->showresult   = true;
        $info->onstudentside = $onstudentside;
        $info->groupinfo = null;

        return $info;
    }

    /**
     * Create appointment information for an existing appointment, shown to a teacher.
     * This excludes booking instructions and results.
     *
     * @param scheduler_slot $slot the slot in question
     * @param scheduler_appointment $appointment the appointment in question
     * @return scheduler_appointment_info
     */
    public static function make_for_teacher(scheduler_slot $slot, scheduler_appointment $appointment) {
        $info = new scheduler_appointment_info();
        $info->slot = $slot;
        $info->appointment = $appointment;
        $info->scheduler = $slot->get_scheduler();
        $info->showslotinfo = true;
        $info->showboookinginfo = false;
        $info->showstudentdata = $info->scheduler->uses_studentdata();
        $info->showresult   = false;
        $info->onstudentside = false;
        $info->groupinfo = null;

        return $info;
    }

}