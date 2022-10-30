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
 * Defines a base class for scheduler events.
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\event;

/**
 * The mod_scheduler abstract base event class.
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class scheduler_base extends \core\event\base {

    /**
     * @var \mod_scheduler\model\scheduler the scheduler associated with this event
     */
    protected $scheduler;

    /**
     * Legacy log data.
     *
     * @var array
     */
    protected $legacylogdata;

    /**
     * Retrieve base data for this event from a scheduler.
     *
     * @param \mod_scheduler\model\scheduler $scheduler
     * @return array
     */
    protected static function base_data(\mod_scheduler\model\scheduler $scheduler) {
        return array(
            'context' => $scheduler->get_context(),
            'objectid' => $scheduler->id
        );
    }

    /**
     * Set the scheduler associated with this event.
     *
     * @param \mod_scheduler\model\scheduler $scheduler
     */
    protected function set_scheduler(\mod_scheduler\model\scheduler $scheduler) {
        $this->add_record_snapshot('scheduler', $scheduler->data);
        $this->scheduler = $scheduler;
        $this->data['objecttable'] = 'scheduler';
    }

    /**
     * Get scheduler instance.
     *
     * NOTE: to be used from observers only.
     *
     * @throws \coding_exception
     * @return \mod_scheduler\model\scheduler
     */
    public function get_scheduler() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_scheduler() is intended for event observers only');
        }
        if (!isset($this->scheduler)) {
            debugging('scheduler property should be initialised in each event', DEBUG_DEVELOPER);
            global $CFG;
            require_once($CFG->dirroot . '/mod/scheduler/locallib.php');
            $this->scheduler = \mod_scheduler\model\scheduler::load_by_coursemodule_id($this->contextinstanceid);
        }
        return $this->scheduler;
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
     * Init method.
     */
    protected function init() {
        $this->data['objecttable'] = 'scheduler';
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
