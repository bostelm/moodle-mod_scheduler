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
 * Backup activity task for the Scheduler module
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/scheduler/backup/moodle2/backup_scheduler_stepslib.php');

/**
 * Scheduler backup task that provides all the settings and steps to perform one
 *
 * complete backup of the activity.
 *
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_scheduler_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Scheduler only has one structure step.
        $this->add_step(new backup_scheduler_activity_structure_step('scheduler_structure', 'scheduler.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of schedulers.
        $search = "/(".$base."\/mod\/scheduler\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@SCHEDULERINDEX*$2@$', $content);

        // Link to scheduler view by coursemoduleid.
        $search = "/(".$base."\/mod\/scheduler\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@SCHEDULERVIEWBYID*$2@$', $content);

        return $content;
    }
}
