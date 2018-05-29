<?php

/**
 * A class for representing a scheduler appointment.
 *
 * @package    mod_scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once('modellib.php');


/**
 * A class for representing a scheduler appointment.
 *
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_appointment extends mvc_child_record_model {


    protected function get_table() {
        return 'scheduler_appointment';
    }

    public function __construct(scheduler_slot $slot) {
        parent::__construct();
        $this->data = new stdClass();
        $this->set_parent($slot);
        $this->data->slotid = $slot->get_id();
        $this->data->roleid = 0;
        $this->data->attended = 0;
        $this->data->appointmentnoteformat = FORMAT_HTML;
        $this->data->teachernoteformat = FORMAT_HTML;
    }

    public function save() {
        global $DB;

        $this->data->slotid = $this->get_parent()->get_id();
        if (isset($_REQUEST['roleid']) && !empty($_REQUEST['roleid'])) {
            if (!is_array($_REQUEST['roleid'])) {
                $this->data->roleid = intval($_REQUEST['roleid']);
            } else {
                if ($apps = array_values($DB->get_records('scheduler_appointment', 
                        array('slotid' => intval($_REQUEST['slotid']))))) {
                    foreach ($apps as $i => $app) {
                        if ($app->studentid == $this->data->studentid) {
                            $this->data->roleid = intval($_REQUEST['roleid'][$i]);
                            break;
                        }
                    }
                }
            }
        }
        $uses_roles = $this->get_scheduler()->uses_roles();
        if (!$uses_roles || ($this->data->roleid && $uses_roles && 
                check_slot_role_limit($this->data->roleid, $this->data->studentid))) {
            parent::save();
        }
        $scheddata = $this->get_scheduler()->get_data();
        scheduler_update_grades($scheddata, $this->studentid);
    }

    public function delete() {
        $studid = $this->studentid;
        parent::delete();
        $scheddata = $this->get_scheduler()->get_data();
        scheduler_update_grades($scheddata, $studid);
    }

    /**
     * Retrieve the slot associated with this appointment
     *
     * @return scheduler_slot;
     */
    public function get_slot() {
        return $this->get_parent();
    }

    /**
     * Retrieve the scheduler associated with this appointment
     *
     * @return scheduler_instance
     */
    public function get_scheduler() {
        return $this->get_parent()->get_parent();
    }

    /**
     * Return the student object.
     * May be null if no student is assigned to this appointment (this _should_ never happen).
     */
    public function get_student() {
        global $DB;
        if ($this->data->studentid) {
            return $DB->get_record('user', array('id' => $this->data->studentid), '*', MUST_EXIST);
        } else {
            return null;
        }
    }

    /**
     * Has this student attended?
     */
    public function is_attended() {
        return (boolean) $this->data->attended;
    }

    /**
     * Are there any student notes associated with this appointment?
     * @return boolean
     */
    public function has_studentnotes() {
        return $this->get_scheduler()->uses_studentnotes() &&
                strlen(trim(strip_tags($this->studentnote))) > 0;
    }

    /**
     * How many files has the student uploaded for this appointment?
     *
     * @return int
     */
    public function count_studentfiles() {
        if (!$this->get_scheduler()->uses_studentnotes()) {
            return 0;
        }
        $ctx = $this->get_scheduler()->context->id;
        $fs = get_file_storage();
        $files = $fs->get_area_files($ctx, 'mod_scheduler', 'studentfiles', $this->id, "filename", false);
        return count($files);
    }

}

/**
 * A factory class for scheduler appointments.
 *
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_appointment_factory extends mvc_child_model_factory {
    public function create_child(mvc_record_model $parent) {
        return new scheduler_appointment($parent);
    }
}
