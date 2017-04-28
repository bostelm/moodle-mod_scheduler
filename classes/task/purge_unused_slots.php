<?php

/**
 * Scheduled background task for sending automated appointment reminders
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\task;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/../../model/scheduler_instance.php');

/**
 * Scheduled background task for sending automated appointment reminders
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 class purge_unused_slots extends \core\task\scheduled_task {
    public function get_name() {
        return get_string('purgeunusedslots', 'mod_scheduler');
    }

    public function execute() {
        \scheduler_instance::free_late_unused_slots();
    }
}