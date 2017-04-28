<?php

/**
 * Defines the mod_scheduler slot deleted event.
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_scheduler slot deleted event.
 *
 * Indicates that a teacher has deleted a slot.
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class slot_deleted extends slot_base {

    /**
     * Create this event on a given slot.
     *
     * @param \scheduler_slot $slot
     * @return \core\event\base
     */
    public static function create_from_slot(\scheduler_slot $slot, $action) {
        $data = self::base_data($slot);
        $data['other'] = array('action' => $action);
        $event = self::create($data);
        $event->set_slot($slot);
        return $event;
    }

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_slotdeleted', 'scheduler');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        $desc = "The user with id '$this->userid' deleted the slot with id  '{$this->objectid}'"
                ." in the scheduler with course module id '$this->contextinstanceid'";
        if ($act = $this->other['action']) {
            $desc .= " during action '$act'";
        }
        $desc .= '.';
        return $desc;
    }
}
