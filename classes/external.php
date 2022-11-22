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
 * This is the external API for this component.
 *
 * @package    mod_scheduler
 * @copyright  2022 University of Glasgow
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

use \mod_scheduler\model\scheduler;

/**
 * This is the external API for this component.
 *
 * @copyright  2022 University of Glasgow
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * studentid parameters
     *
     * @return external_function_parameters
     */
    public static function studentid_parameters() {
        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'The search query', VALUE_REQUIRED),
            'scheduler' => new external_value(PARAM_INT, 'The scheduler id', VALUE_REQUIRED),
            'groupids' => new external_value(PARAM_INT, 'The group ids', VALUE_DEFAULT)
        ]);
    }

    /**
     * Fetch the details of a user's data request.
     *
     * @since Moodle 3.5
     * @param string $query The search query.
     * @param string $scheduler The scheduler id.
     * @param string $groupids The group ids.
     * @return array
     * @throws required_capability_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */

    public static function studentid($query, $scheduler, $groupids) {
        $params = external_api::validate_parameters(self::studentid_parameters(), [
            'query' => $query,
            'scheduler' => $scheduler,
            'groupids' => $groupids
        ]);
        $query = $params['query'];
        $scheduler = $params['scheduler'];
        $groupids = $params['groupids'];

        $scheduler = scheduler::load_by_id($scheduler);
        $availablestudents = $scheduler->get_available_students();

        $students = [];
        $i = 0;
        foreach ($availablestudents as $id => $student) {
            $fullname = fullname($student);

            if (mb_strpos($fullname, $query) !== false) {
                $students[] = ['id' => $id, 'fullname' => fullname($student)];
                $i++;
            }
        }

        return $students;

    }

    /**
     * Parameter description for get_users().
     *
     * @since Moodle 3.5
     * @return external_description
     * @throws coding_exception
     */
    public static function studentid_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id'    => new external_value(PARAM_INT, 'User ID'),
                'fullname'  => new external_value(PARAM_NOTAGS, 'User fullname')
            ])
        );
    }

}
