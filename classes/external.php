<?php
// This file is part of a 3rd party created module for Moodle - http://moodle.org/
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
 * External API.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler;
defined('MOODLE_INTERNAL') || die();

use core_user;
use external_api;
use external_function_parameters;
use external_value;
use mod_scheduler\model\scheduler;
use mod_scheduler\permission\scheduler_permissions;
use scheduler_messenger;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/scheduler/mailtemplatelib.php');

/**
 * External API.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * External function parameters.
     *
     * @return external_function_parameters
     */
    public function revoke_appointment_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT),
            'appointmentid' => new external_value(PARAM_INT),
        ]);
    }

    /**
     * Revoke appointment.
     *
     * @param int $cmid The cmid.
     * @param int $appointmentid The appointment ID.
     * @return null
     */
    public function revoke_appointment($cmid, $appointmentid) {
        global $USER;

        $params = self::validate_parameters(self::revoke_appointment_parameters(),
            ['cmid' => $cmid, 'appointmentid' => $appointmentid]);

        $cmid = $params['cmid'];
        $appointmentid = $params['appointmentid'];

        $scheduler = scheduler::load_by_coursemodule_id($cmid);
        self::validate_context($scheduler->get_context());
        $permissions = new scheduler_permissions($scheduler->get_context(), $USER->id);

        list($slot, $app) = $scheduler->get_slot_appointment($appointmentid);
        $permissions->ensure($permissions->can_edit_slot($slot));
        $slot->remove_appointment($app);

        // Notify the student.
        if ($scheduler->allownotifications) {
            $student = core_user::get_user($app->studentid, '*', MUST_EXIST);
            $teacher = core_user::get_user($slot->teacherid, '*', MUST_EXIST);
            scheduler_messenger::send_slot_notification($slot, 'bookingnotification', 'teachercancelled',
                $teacher, $student, $teacher, $student, $scheduler->get_courserec());
        }

        $slot->save();

        return null;
    }

    /**
     * External function return structure.
     *
     * @return external_value
     */
    public function revoke_appointment_returns() {
        return new external_value(null);
    }

}
