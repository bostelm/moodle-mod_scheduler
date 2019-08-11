<?php

/**
 * A factory class for scheduler slots.
 *
 * @package    mod_scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\model;

defined('MOODLE_INTERNAL') || die();

/**
 * A factory class for scheduler slots.
 *
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class slot_factory extends mvc_child_model_factory {
    public function create_child(mvc_record_model $parent) {
        return new slot($parent);
    }
}
