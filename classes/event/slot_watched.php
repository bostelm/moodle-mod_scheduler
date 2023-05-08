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
 * Slot watched.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\event;

/**
 * Slot watched.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class slot_watched extends \core\event\base {

    /**
     * Create this event from a watcher.
     *
     * @param \mod_scheduler\model\watcher $watcher The watcher.
     * @return \core\event\base
     */
    public static function create_from_watcher(\mod_scheduler\model\watcher $watcher) {
        $slot = $watcher->get_slot();
        $event = self::create([
            'context' => $slot->get_scheduler()->get_context(),
            'objectid' => $watcher->slotid,
            'relateduserid' => $watcher->userid
        ]);
        $event->add_record_snapshot('scheduler_watcher', $watcher->data);
        $event->add_record_snapshot('scheduler_slots', $slot->data);
        $event->add_record_snapshot('scheduler', $slot->get_scheduler()->data);
        return $event;
    }

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'scheduler_slots';
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_slotwatched', 'scheduler');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' set the user with id '{$this->relateduserid}' as a watcher of " .
                "the slot with id '{$this->objectid}' in the scheduler with course module id '$this->contextinstanceid'.";
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
        } else if (empty($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be provided.');
        }
    }
}
