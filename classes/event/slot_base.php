<?php

/**
 * Defines a base class for slot-based events.
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_scheduler abstract base event class for slot-based events.
 *
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class slot_base extends \core\event\base {

    /**
     * @var \scheduler_slot the slot associated with this event
     */
    protected $slot;

    /**
     * Return the base data fields for a slot
     *
     * @param \scheduler_slot $slot the slot in question
     * @return array
     */
    protected static function base_data(\scheduler_slot $slot) {
        return array(
            'context' => $slot->get_scheduler()->get_context(),
            'objectid' => $slot->id,
            'relateduserid' => $slot->teacherid
        );
    }

    /**
     * Set the slot associated with this event
     *
     * @param \scheduler_slot $slot
     */
    protected function set_slot(\scheduler_slot $slot) {
        $this->add_record_snapshot('scheduler_slots', $slot->data);
        $this->add_record_snapshot('scheduler', $slot->get_scheduler()->data);
        $this->slot = $slot;
        $this->data['objecttable'] = 'scheduler_slots';
    }

    /**
     * Get slot object.
     *
     * NOTE: to be used from observers only.
     *
     * @throws \coding_exception
     * @return \scheduler_slot
     */
    public function get_slot() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_slot() is intended for event observers only');
        }
        return $this->slot;
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
