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
 * Global configuration settings for the scheduler module.
 *
 * @package    mod_scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    require_once($CFG->dirroot.'/mod/scheduler/lib.php');

    $settings->add(new admin_setting_configcheckbox('mod_scheduler/showemailplain',
                     get_string('showemailplain', 'scheduler'),
                     get_string('showemailplain_desc', 'scheduler'),
                     0));

    $settings->add(new admin_setting_configcheckbox('mod_scheduler/groupscheduling',
                     get_string('groupscheduling', 'scheduler'),
                     get_string('groupscheduling_desc', 'scheduler'),
                     1));

    $settings->add(new admin_setting_configcheckbox('mod_scheduler/mixindivgroup',
                     get_string('mixindivgroup', 'scheduler'),
                     get_string('mixindivgroup_desc', 'scheduler'),
                     1));

    $settings->add(new admin_setting_configtext('mod_scheduler/maxstudentlistsize',
                     get_string('maxstudentlistsize', 'scheduler'),
                     get_string('maxstudentlistsize_desc', 'scheduler'),
                     200, PARAM_INT));

    $settings->add(new admin_setting_configtext('mod_scheduler/uploadmaxfiles',
                     get_string('uploadmaxfilesglobal', 'scheduler'),
                     get_string('uploadmaxfilesglobal_desc', 'scheduler'),
                     5, PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('mod_scheduler/revealteachernotes',
                    get_string('revealteachernotes', 'scheduler'),
                    get_string('revealteachernotes_desc', 'scheduler'),
                    0));

}
