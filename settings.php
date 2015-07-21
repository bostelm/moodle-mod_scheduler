<?php

/**
 * Global configuration settings for the scheduler module.
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    require_once($CFG->dirroot.'/mod/scheduler/lib.php');

    $settings->add(new admin_setting_configcheckbox('mod_scheduler/allteachersgrading',
                     get_string('allteachersgrading', 'scheduler'),
                     get_string('allteachersgrading_desc', 'scheduler'),
                     0));

    $settings->add(new admin_setting_configcheckbox('mod_scheduler/showemailplain',
                     get_string('showemailplain', 'scheduler'),
                     get_string('showemailplain_desc', 'scheduler'),
                     0));

    $settings->add(new admin_setting_configcheckbox('mod_scheduler/groupscheduling',
                     get_string('groupscheduling', 'scheduler'),
                     get_string('groupscheduling_desc', 'scheduler'),
                     1));

    $settings->add(new admin_setting_configtext('mod_scheduler/maxstudentlistsize',
                     get_string('maxstudentlistsize', 'scheduler'),
                     get_string('maxstudentlistsize_desc', 'scheduler'),
                     200, PARAM_INT));

}
