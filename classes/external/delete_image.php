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

namespace mod_scheduler\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

use \mod_scheduler\model\scheduler;

/**
 * This is the external API for this component.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_image extends external_api {

    /**
     * Delete image parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'imageid' => new external_value(PARAM_INT, 'The image id', VALUE_REQUIRED),
        ]);
    }

    public static function execute(int $imageid): bool {
        global $DB;

        [
            'imageid' => $imageid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'imageid' => $imageid,
        ]);

        $filerecord = $DB->get_record('files', ['id' => $imageid]);
        if ($filerecord->component !== 'mod_scheduler') {
            return false;
        }
        if ($filerecord->filearea !== 'message') {
            return false;
        }
        $ctx = \context::instance_by_id($filerecord->contextid);
        if (!has_any_capability(['mod/scheduler:manage', 'mod/scheduler:manageallappointments'], $ctx)) {
            return false;
        }
        $fs = get_file_storage();
        $file = $fs->get_file($filerecord->contextid, 'mod_scheduler', 'message', $filerecord->itemid, $filerecord->filepath, $filerecord->filename);
        $file->delete();
        return true;
    }

    /**
     * External method return value
     *
     * @return external_value
     */
    public static function execute_returns(): external_value {
        return new external_value(PARAM_BOOL, 'Success');
    }
}
