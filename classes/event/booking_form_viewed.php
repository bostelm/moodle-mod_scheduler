<?php

/**
 * Defines the mod_scheduler booking form viewed event.
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_scheduler booking form viewed event.
 *
 * Indicates that a student has viewed the booking form.
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class booking_form_viewed extends scheduler_base {

    /**
     * Create this event on a given scheduler.
     *
     * @param \scheduler_instance $scheduler
     * @return \core\event\base
     */
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
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns localised general event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_bookingformviewed', 'scheduler');
    }

    /**
     * Returns non-localised event description with id's for admin use only.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' has viewed the booking form in the scheduler with course module id '$this->contextinstanceid'.";
    }
}
