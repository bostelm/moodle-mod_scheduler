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
 * Define all the restore steps that will be used by the restore_scheduler_activity_task
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one scheduler activity
 *
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_scheduler_activity_structure_step extends restore_activity_structure_step {

    /**
     * define_structure
     *
     * @return array
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $scheduler = new restore_path_element('scheduler', '/activity/scheduler');
        $paths[] = $scheduler;

        if ($userinfo) {
            $slot = new restore_path_element('scheduler_slot', '/activity/scheduler/slots/slot');
            $paths[] = $slot;

            $appointment = new restore_path_element('scheduler_appointment',
                                                    '/activity/scheduler/slots/slot/appointments/appointment');
            $paths[] = $appointment;
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * process_scheduler
     *
     * @param stdClass $data
     */
    protected function process_scheduler($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);

        if ($data->scale < 0) { // Scale found, get mapping.
            $data->scale = -($this->get_mappingid('scale', abs($data->scale)));
        }

        if (is_null($data->gradingstrategy)) { // Catch inconsistent data created by pre-1.9 DB schema.
            $data->gradingstrategy = 0;
        }

        if ($data->bookingrouping > 0) {
            $data->bookingrouping = $this->get_mappingid('grouping', $data->bookingrouping);
        }

        // Insert the scheduler record.
        $newitemid = $DB->insert_record('scheduler', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * process_scheduler_slot
     *
     * @param stdClass $data
     */
    protected function process_scheduler_slot($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->schedulerid = $this->get_new_parentid('scheduler');
        $data->starttime = $this->apply_date_offset($data->starttime);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->emaildate = $this->apply_date_offset($data->emaildate);
        $data->hideuntil = $this->apply_date_offset($data->hideuntil);

        $data->teacherid = $this->get_mappingid('user', $data->teacherid);

        $newitemid = $DB->insert_record('scheduler_slots', $data);
        $this->set_mapping('scheduler_slot', $oldid, $newitemid, true);
    }

    /**
     * process_scheduler_appointment
     *
     * @param stdClass $data
     */
    protected function process_scheduler_appointment($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->slotid = $this->get_new_parentid('scheduler_slot');

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $data->studentid = $this->get_mappingid('user', $data->studentid);

        $newitemid = $DB->insert_record('scheduler_appointment', $data);
        $this->set_mapping('scheduler_appointment', $oldid, $newitemid, true);
    }

    /**
     * after_execute
     */
    protected function after_execute() {
        // Add scheduler related files.
        $this->add_related_files('mod_scheduler', 'intro', null);
        $this->add_related_files('mod_scheduler', 'bookinginstructions', null);
        $this->add_related_files('mod_scheduler', 'slotnote', 'scheduler_slot');
        $this->add_related_files('mod_scheduler', 'appointmentnote', 'scheduler_appointment');
        $this->add_related_files('mod_scheduler', 'teachernote', 'scheduler_appointment');
        $this->add_related_files('mod_scheduler', 'studentfiles', 'scheduler_appointment');
    }
}
