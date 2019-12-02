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
 * Controller for scheduler module.
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
class scheduler_permissions extends permissions_manager {

    /**
     * scheduler_permissions constructor.
     *
     * @param \context $context
     * @param int $userid
     */
    public function __construct(\context $context, $userid) {
        parent::__construct('mod_scheduler', $context, $userid);
    }

    /**
     * teacher_can_see_slot
     *
     * @param \mod_scheduler\model\slot $slot
     * @return bool
     */
    public function teacher_can_see_slot(\mod_scheduler\model\slot $slot) {
        if ($this->has_any_capability(['manageallappointments', 'canseeotherteachersbooking'])) {
            return true;
        } else if ($this->has_any_capability(['manage', 'attend'])) {
            return $this->userid == $slot->teacherid;
        } else {
            return false;
        }
    }

    /**
     * can_edit_slot
     *
     * @param \mod_scheduler\model\slot $slot
     * @return bool
     */
    public function can_edit_slot(\mod_scheduler\model\slot $slot) {
        if ($this->has_capability('manageallappointments')) {
            return true;
        } else if ($this->has_capability('manage')) {
            return $this->userid == $slot->teacherid;
        } else {
            return false;
        }
    }

    /**
     * can_edit_own_slots
     *
     * @return bool
     */
    public function can_edit_own_slots() {
        return $this->has_any_capability(['manage', 'manageallappointments']);
    }

    /**
     * can_edit_all_slots
     *
     * @return bool|mixed
     */
    public function can_edit_all_slots() {
        return $this->has_capability('manageallappointments');
    }

    /**
     * can_see_all_slots
     *
     * @return bool
     */
    public function can_see_all_slots() {
        return $this->has_any_capability(['manageallappointments', 'canseeotherteachersbooking']);
    }

    /**
     * can_see_appointment
     *
     * @param \mod_scheduler\model\appointment $app
     * @return bool
     */
    public function can_see_appointment(\mod_scheduler\model\appointment $app) {
        if ($this->has_any_capability(['manageallappointments', 'canseeotherteachersbooking'])) {
            return true;
        } else if ($this->has_capability('attend') && $this->userid == $app->get_slot()->teacherid) {
            return true;
        } else if ($this->has_capability('appoint') && $this->userid == $app->studentid) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * can_edit_grade
     *
     * @param \mod_scheduler\model\appointment $app
     * @return bool
     */
    public function can_edit_grade(\mod_scheduler\model\appointment $app) {
        if ($this->has_any_capability(['manageallappointments', 'editallgrades'])) {
            return true;
        } else {
            return $this->userid == $app->get_slot()->teacherid;
        }
    }

    /**
     * can_edit_attended
     *
     * @param \mod_scheduler\model\appointment $app
     * @return bool
     */
    public function can_edit_attended(\mod_scheduler\model\appointment $app) {
        if ($this->has_any_capability(['manageallappointments', 'editallattended'])) {
            return true;
        } else {
            return $this->userid == $app->get_slot()->teacherid;
        }
    }

    /**
     * can_edit_notes
     *
     * @param \mod_scheduler\model\appointment $app
     * @return bool
     */
    public function can_edit_notes(\mod_scheduler\model\appointment $app) {
        if ($this->has_any_capability(['manageallappointments', 'editallnotes'])) {
            return true;
        } else {
            return $this->userid == $app->get_slot()->teacherid;
        }
    }

}
