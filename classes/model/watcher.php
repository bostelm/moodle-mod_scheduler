<?php
// This file is part of a 3rd party created module for Moodle - http://moodle.org/
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
 * Watcher.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\model;
defined('MOODLE_INTERNAL') || die();

/**
 * Watcher.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class watcher extends mvc_child_record_model {

    /**
     * Get the table.
     *
     * @return string
     */
    protected function get_table() {
        return 'scheduler_watcher';
    }

    /**
     * Constructor.
     *
     * @param slot $slot The parent slot.
     */
    public function __construct(slot $slot) {
        parent::__construct();
        $this->set_parent($slot);
        $this->data = new \stdClass();
        $this->data->slotid = null;
        $this->data->userid = null;
        $this->data->notified = 0;
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
     * Get the user.
     *
     * @return \stdClass
     */
    public function get_user() {
        return \core_user::get_user($this->data->userid, '*', MUST_EXIST);
    }

    /**
     * Notify.
     *
     * @param stdClass $teacher The teacher.
     * @return void
     */
    public function notify() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/scheduler/mailtemplatelib.php');

        $teacher = $this->get_slot()->get_teacher();
        $student = $this->get_user();
        $course = $this->get_scheduler()->get_courserec();

        \scheduler_messenger::send_slot_notification($this->get_slot(), 'watchedslotopenedup', 'slotopenedup',
            $teacher, $student, $teacher, $student, $course);
    }

    /**
     * Save.
     */
    public function save() {
        $this->data->slotid = $this->get_parent()->get_id();
        parent::save();
    }

}
