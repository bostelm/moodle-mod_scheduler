<?php
// This file is part of Moodle - http://moodle.org/
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
 * Base class for MVC controllers.
 *
 * @package    mod_scheduler
 * @copyright  2019 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\permission;

/**
 * The base class for controllers.
 *
 * @copyright  2019 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class permissions_manager {

    /** @var int */
    protected $userid;
    /** @var \context */
    protected $context;
    /** @var string */
    protected $prefix;
    /** @var array */
    protected $caps;

    /**
     * permissions_manager constructor.
     *
     * @param string $pluginname
     * @param \context $context
     * @param int $userid
     */
    protected function __construct($pluginname, \context $context, $userid) {

        $this->userid = $userid;
        $this->context = $context;
        $this->prefix = str_replace('_', '/', $pluginname) . ':';

        $this->caps = array();
    }

    /**
     * has_capability
     *
     * @param string $cap
     * @return bool|mixed
     */
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

    /**
     * has_any_capability
     *
     * @param array $caps
     * @return bool
     */
    protected function has_any_capability(array $caps) {
        foreach ($caps as $cap) {
            if ($this->has_capability($cap)) {
                return true;
            }
        }
        return false;
    }

    /**
     * get_context
     *
     * @return \context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * ensure
     *
     * @param mixed $condition
     * @throws \moodle_exception
     */
    public function ensure($condition) {
        if (!$condition) {
            throw new \moodle_exception('nopermissions');
        }
    }
}
