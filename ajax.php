<?php

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
