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
 * Base class for appointment-based events.
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_scheduler abstract base event class for appointment-based events.
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class appointment_base extends \core\event\base {


    /**
     * @var \mod_scheduler\model\appointment the appointment associated with this event
     */
    protected $appointment;

    /**
     * Return the base data fields for an appointment
     *
     * @param \mod_scheduler\model\appointment $appointment the appointment in question
     * @return array
     */
    protected static function base_data(\mod_scheduler\model\appointment $appointment) {
        return array(
            'context' => $appointment->get_parent()->get_context(),
            'objectid' => $appointment->id
        );
    }

    /**
     * Set data of the event from an appointment record.
     *
     * @param \mod_scheduler\model\appointment $appointment
     */
    protected function set_appointment(\mod_scheduler\model\appointment $appointment) {
        $this->add_record_snapshot('scheduler_appointment', $appointment->data);
        $this->add_record_snapshot('scheduler_slots', $appointment->get_parent()->data);
        $this->add_record_snapshot('scheduler', $appointment->get_parent()->get_parent()->data);
        $this->appointment = $appointment;
        $this->data['objecttable'] = 'scheduler_appointments';
    }

    /**
     * Get appointment object.
     *
     * NOTE: to be used from observers only.
     *
     * @throws \coding_exception
     * @return \mod_scheduler\model\appointment
     */
    public function get_appointment() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_appointment() is intended for event observers only');
        }
        return $this->appointment;
    }

    /**
     * Returns relevant URL.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/scheduler/view.php', array('id' => $this->contextinstanceid));
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     */
    protected function validate_data() {
        parent::validate_data();

        if ($this->contextlevel != CONTEXT_MODULE) {
            throw new \coding_exception('Context level must be CONTEXT_MODULE.');
        }
    }
}
