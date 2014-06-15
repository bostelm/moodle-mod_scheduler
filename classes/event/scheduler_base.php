<?php
/**
 * Base class for scheduler events.
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mod_scheduler\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_scheduler abstract base event class.
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class scheduler_base extends \core\event\base {

    protected $scheduler;

    /**
     * Legacy log data.
     *
     * @var array
     */
    protected $legacylogdata;

    protected static function base_data(\scheduler_instance $scheduler) {
        return array(
            'context' => $scheduler->get_context(),
            'objectid' => $scheduler->id
        );
    }

    protected function set_scheduler(\scheduler_instance $scheduler) {
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
     * @return \scheduler_instance
     */
    public function get_scheduler() {
        if ($this->is_restored()) {
            throw new \coding_exception('get_scheduler() is intended for event observers only');
        }
        if (!isset($this->scheduler)) {
            debugging('scheduler property should be initialised in each event', DEBUG_DEVELOPER);
            global $CFG;
            require_once($CFG->dirroot . '/mod/scheduler/locallib.php');
            $this->scheduler = \scheduler_instance::load_by_coursemodule_id($this->contextinstanceid);
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
