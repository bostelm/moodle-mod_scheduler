<?php

/**
 * Base class for MVC controllers.
 *
 * @package    mod_scheduler
 * @copyright  2019 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\permission;

defined('MOODLE_INTERNAL') || die();

/**
 * The base class for controllers.
 *
 * @copyright  2019 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class permissions_manager {


    protected $userid;
    protected $context;
    protected $prefix;

    protected $caps;

    protected function __construct($pluginname, \context $context, $userid) {

        $this->userid = $userid;
        $this->context = $context;
        $this->prefix = str_replace('_', '/', $pluginname) . ':';

        $this->caps = array();
    }

    protected function has_capability($cap) {
        if (key_exists($cap, $this->caps)) {
            return $this->caps[$cap];
        } else {
            $fullname = $this->prefix . $cap;
            $hasit = has_capability($fullname, $this->context, $this->userid);
            $this->caps[$cap] = $hasit;
            return $hasit;
        }
    }

    protected function has_any_capability(array $caps) {
        foreach ($caps as $cap) {
            if ($this->has_capability($cap)) {
                return true;
            }
        }
        return false;
    }

    public function get_context() {
        return $this->context;
    }

    public function ensure($condition) {
        if (!$condition) {
            throw new \moodle_exception('nopermissions');
        }
    }
}
