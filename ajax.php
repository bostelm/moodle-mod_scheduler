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
 * Process ajax requests
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

use \mod_scheduler\model\scheduler;
use \mod_scheduler\permission\scheduler_permissions;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once('locallib.php');

$id = required_param('id', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);

$cm = get_coursemodule_from_id('scheduler', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$scheduler = scheduler::load_by_coursemodule_id($id);

require_login($course, true, $cm);
require_sesskey();

$permissions = new scheduler_permissions($scheduler->context, $USER->id);

$return = 'OK';

switch ($action) {
    case 'saveseen':

        $appid = required_param('appointmentid', PARAM_INT);
        list($slot, $app) = $scheduler->get_slot_appointment($appid);
        $newseen = required_param('seen', PARAM_BOOL);

        $permissions->ensure($permissions->can_edit_attended($app));

        $app->attended = $newseen;
        $slot->save();

        break;
}

echo json_encode($return);
die;
