<?php
/**
 * The mod_scheduler booking form removed event.
 *
 * Indicates that a student has removed their booking from a slot.
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mod_scheduler\event;
defined('MOODLE_INTERNAL') || die();

class booking_removed extends slot_base {

    public static function create_from_slot(\scheduler_slot $slot) {
        $event = self::create(self::base_data($slot));
        $event->set_slot($slot);
        return $event;
    }

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_bookingremoved', 'scheduler');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' has removed their booking from the slot with id  '{$this->objectid}'"
                ." in the scheduler with course module id '$this->contextinstanceid'.";
    }
}
