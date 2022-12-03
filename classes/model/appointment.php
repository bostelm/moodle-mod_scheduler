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
 * A class for representing a scheduler appointment.
 *
 * @package    mod_scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\model;

defined('MOODLE_INTERNAL') || die();

// Elements from lib.php needed for grade functionality.
require_once($CFG->dirroot.'/mod/scheduler/lib.php');

/**
 * A class for representing a scheduler appointment.
 *
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class appointment extends mvc_child_record_model {

    /**
     * get_table
     *
     * @return string
     */
    protected function get_table() {
        return 'scheduler_appointment';
    }

    /**
     * appointment constructor.
     *
     * @param slot $slot
     */
    public function __construct(slot $slot) {
        parent::__construct();
        $this->data = new \stdClass();
        $this->set_parent($slot);
        $this->data->slotid = $slot->get_id();
        $this->data->attended = 0;
        $this->data->appointmentnoteformat = FORMAT_HTML;
        $this->data->teachernoteformat = FORMAT_HTML;
    }

    /**
     * save
     */
    public function save() {
        $this->data->slotid = $this->get_parent()->get_id();
        parent::save();
        $scheddata = $this->get_scheduler()->get_data();
        scheduler_update_grades($scheddata, $this->studentid);
    }

    /**
     * delete
     */
    public function delete() {
        $studid = $this->studentid;
        parent::delete();

        $scheddata = $this->get_scheduler()->get_data();
        scheduler_update_grades($scheddata, $studid);

        $fs = get_file_storage();
        $cid = $this->get_scheduler()->get_context()->id;
        $fs->delete_area_files($cid, 'mod_scheduler', 'appointmentnote', $this->get_id());
        $fs->delete_area_files($cid, 'mod_scheduler', 'teachernote', $this->get_id());
        $fs->delete_area_files($cid, 'mod_scheduler', 'studentnote', $this->get_id());

    }

    /**
     * Retrieve the slot associated with this appointment
     *
     * @return slot;
     */
    public function get_slot() {
        return $this->get_parent();
    }

    /**
     * Retrieve the scheduler associated with this appointment
     *
     * @return scheduler
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
