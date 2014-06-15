<?php
/**
 * The mod_scheduler appointment list viewed event.
 *
 * Indicates that a teacher has viewed the list of appointments and slots.
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\event;
defined('MOODLE_INTERNAL') || die();

class appointment_list_viewed extends scheduler_base {

    public static function create_from_scheduler(\scheduler_instance $scheduler) {
        $event = self::create(self::base_data($scheduler));
        $event->set_scheduler($scheduler);
        return $event;
    }

    /**
     * Init method.
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_appointmentlistviewed', 'scheduler');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' has viewed the list of appointments in the scheduler with course module id '$this->contextinstanceid'.";
    }
}
