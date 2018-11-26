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
 * Privacy Subsystem implementation for mod_scheduler.
 *
 * @package    mod_scheduler
 * @copyright  2018 Henning Bostelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\content_writer;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for the scheduler activity module.
 *
 * @package    mod_scheduler
 * @copyright  2018 Henning Bostelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This plugin stores personal data.
        \core_privacy\local\metadata\provider,

        // This plugin is a core_user_data_provider.
        \core_privacy\local\request\plugin\provider {


    private static $renderer;

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'scheduler_slots',
            [
                'teacherid' => 'privacy:metadata:scheduler_slots:teacherid',
                'starttime' => 'privacy:metadata:scheduler_slots:starttime',
                'duration'  => 'privacy:metadata:scheduler_slots:duration',
                'appointmentlocation' => 'privacy:metadata:scheduler_slots:appointmentlocation',
                'notes' => 'privacy:metadata:scheduler_slots:notes',
                'notesformat' => 'privacy:metadata:scheduler_slots:notesformat',
                'exclusivity' => 'privacy:metadata:scheduler_slots:exclusivity'
                 // The fields "timemodified", "emaildate" and "hideuntil" do not contain personal data.
            ],
            'privacy:metadata:scheduler_slots'
        );
        $collection->add_database_table(
            'scheduler_appointment',
            [
                'studentid' => 'privacy:metadata:scheduler_appointment:studentid',
                'attended' => 'privacy:metadata:scheduler_appointment:attended',
                'grade' => 'privacy:metadata:scheduler_appointment:grade',
                'appointmentnote' => 'privacy:metadata:scheduler_appointment:appointmentnote',
                'appointmentnoteformat' => 'privacy:metadata:scheduler_appointment:appointmentnoteformat',
                'teachernote' => 'privacy:metadata:scheduler_appointment:teachernote',
                'teachernoteformat' => 'privacy:metadata:scheduler_appointment:teachernoteformat',
                'studentnote' => 'privacy:metadata:scheduler_appointment:studentnote',
                'studentnoteformat' => 'privacy:metadata:scheduler_appointment:studentnoteformat'
                // The fields "timecreated" and "timemodifed" are technical only, they do not contain personal data.
            ],
            'privacy:metadata:scheduler_appointment'
        );

        // Subsystems used.
        $collection->link_subsystem('core_files', 'privacy:metadata:filepurpose');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        // Fetch all scheduler records for teachers.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {scheduler} s ON s.id = cm.instance
            INNER JOIN {scheduler_slots} t ON t.schedulerid = s.id
                 WHERE t.teacherid = :userid";

        $params = [
            'modname'       => 'scheduler',
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $userid
        ];

        $contextlist->add_from_sql($sql, $params);

        // Fetch all scheduler records for students.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {scheduler} s ON s.id = cm.instance
            INNER JOIN {scheduler_slots} t ON t.schedulerid = s.id
            INNER JOIN {scheduler_appointment} a ON a.slotid = t.id
                 WHERE a.studentid = :userid";

        $params = [
                'modname'       => 'scheduler',
                'contextlevel'  => CONTEXT_MODULE,
                'userid'        => $userid
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {

        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        // Fetch teachers.
        $sql = "SELECT t.teacherid
                  FROM {course_modules} cm
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {scheduler} s ON s.id = cm.instance
            INNER JOIN {scheduler_slots} t ON t.schedulerid = s.id
                 WHERE cm.id = :cmid";

        $params = [
                'modname'       => 'scheduler',
                'cmid'          => $context->instanceid
        ];

        $userlist->add_from_sql('teacherid', $sql, $params);

        // Fetch students.
        $sql = "SELECT a.studentid
                  FROM {course_modules} cm
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {scheduler} s ON s.id = cm.instance
            INNER JOIN {scheduler_slots} t ON t.schedulerid = s.id
            INNER JOIN {scheduler_appointment} a ON a.slotid = t.id
                 WHERE cm.id = :cmid";

        $params = [
                'modname'       => 'scheduler',
                'cmid'          => $context->instanceid
        ];

        $userlist->add_from_sql('studentid', $sql, $params);

        return $userlist;
    }

    /**
     * Load a scheduler instance from a context.
     *
     * Will return null if the context was not found.
     *
     * @param \context $context the context of the scheduler.
     * @return \scheduler_instance scheduler object, or null if not found.
     */
    private static function load_scheduler_for_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return null;
        }

        $sql = "SELECT s.id as schedulerid
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                JOIN {scheduler} s ON s.id = cm.instance
                WHERE cm.id = :cmid";
        $params = ['cmid' => $context->instanceid, 'modname' => 'scheduler'];
        $rec = $DB->get_record_sql($sql, $params);
        if ($rec) {
            return \scheduler_instance::load_by_id($rec->schedulerid);
        } else {
            return null;
        }

    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        if (!$contextlist->count()) {
            return;
        }

        self::$renderer = new \mod_scheduler_renderer();

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "SELECT cm.id AS cmid, s.name AS schedulername, s.id as schedulerid, cm.course AS courseid,
                t.id as slotid, t.teacherid, t.starttime, t.duration,
                t.appointmentlocation, t.notes, t.notesformat, t.exclusivity,
                a.id as appointmentid,
                a.studentid, a.attended, a.grade,
                a.appointmentnote, a.appointmentnoteformat,
                a.teachernote, a.teachernoteformat,
                a.studentnote, a.studentnoteformat
                FROM {context} ctx
                JOIN {course_modules} cm ON cm.id = ctx.instanceid
                JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                JOIN {scheduler} s ON s.id = cm.instance
                JOIN {scheduler_slots} t ON t.schedulerid = s.id
                JOIN {scheduler_appointment} a ON a.slotid = t.id
                WHERE ctx.id {$contextsql} AND ctx.contextlevel = :contextlevel
                AND t.teacherid = :userid1 OR a.studentid = :userid2
                ORDER BY cm.id, t.id, a.id";
        $rs = $DB->get_recordset_sql($sql, $contextparams + ['contextlevel' => CONTEXT_MODULE,
                'modname' => 'scheduler', 'userid1' => $user->id, 'userid2' => $user->id]);

        $context = null;
        $lastrow = null;
        $scheduler = null;
        foreach ($rs as $row) {
            if (!$context || $context->instanceid != $row->cmid) {
                // This row belongs to the different scheduler than the previous row.
                // Export the data for the previous module.
                self::export_scheduler($context, $user);
                // Start new scheduler module.
                $context = \context_module::instance($row->cmid);
                $scheduler = \scheduler_instance::load_by_id($row->schedulerid);
            }

            if (!$lastrow || $row->slotid != $lastrow->slotid) {
                // Export previous slot record.
                self::export_slot($context, $user, $row);
            }
            self::export_appointment($context, $scheduler, $user, $row);
            $lastrow = $row;
        }
        $rs->close();
        self::export_slot($context, $user, $lastrow);
        self::export_scheduler($context, $user);
    }

    private static function format_note($notetext, $noteformat, $filearea, $id,
            \context $context, content_writer $wrc, $exportarea) {
        $message = $notetext;
        if ($filearea) {
            $message = $wrc->rewrite_pluginfile_urls($exportarea, 'mod_scheduler', $filearea, $id, $notetext);
        }
        $opts = (object) [
                'para'    => false,
                'context' => $context
        ];
        $message = format_text($message, $noteformat, $opts);
        return $message;
    }

    /**
     * Export one slot in a scheduler (one record in {scheduler_slots} table)
     *
     * @param \context $context
     * @param \stdClass $user
     * @param \stdClass $record
     */
    protected static function export_slot($context, $user, $record) {
        if (!$record) {
            return;
        }
        $slotarea = ['slot '.$record->slotid];
        $wrc = writer::with_context($context);

        $data = [
            'teacherid' => transform::user($record->teacherid),
            'starttime' => transform::datetime($record->starttime),
            'duration'  => $record->duration,
            'appointmentlocation' => format_string($record->appointmentlocation),
            'notes' => self::format_note($record->notes, $record->notesformat,
                                         'slotnote', $record->slotid, $context, $wrc, $slotarea),
            'exclusivity' => $record->exclusivity,
        ];

        // Data about the slot.
        $wrc->export_data($slotarea, (object)$data);
        $wrc->export_area_files($slotarea, 'mod_scheduler', 'slotnote', $record->slotid);
    }

    /**
     * Export one appointment in a scheduler (one record in {scheduler_appointment} table)
     *
     * @param \context $context
     * @param \scheduler_instance $scheduler
     * @param \stdClass $user
     * @param \stdClass $record
     */
    protected static function export_appointment($context, $scheduler, $user, $record) {
        if (!$record) {
            return;
        }
        $wrc = writer::with_context($context);
        $apparea = ['slot '.$record->slotid, 'appointment '.$record->appointmentid];

        $revealteachernote = ($user->id == $record->teacherid) ||
                             get_config('mod_scheduler', 'revealteachernotes');

        $data = [
                'studentid' => transform::user($record->studentid),
                'attended' => transform::yesno($record->attended),
                'grade' => self::$renderer->format_grade($scheduler, $record->grade),
                'appointmentnote' => self::format_note($record->appointmentnote, $record->appointmentnoteformat,
                                         'appointmentnote', $record->appointmentid, $context, $wrc, $apparea),
                'studentnote' => self::format_note($record->studentnote, $record->studentnoteformat,
                                     '', 0, $context, $wrc, $apparea),
        ];
        if ($revealteachernote) {
            $data['teachernote'] = self::format_note($record->teachernote, $record->teachernoteformat,
                                       'teachernote', $record->appointmentid, $context, $wrc, $apparea);
        }

        // Data about the appointment.

        $wrc->export_data($apparea, (object)$data);

        $wrc->export_area_files($apparea, 'mod_scheduler', 'appointmentnote', $record->appointmentid);
        if ($revealteachernote) {
            $wrc->export_area_files($apparea, 'mod_scheduler', 'teachernote', $record->appointmentid);
        }
        $wrc->export_area_files($apparea, 'mod_scheduler', 'studentfiles', $record->appointmentid);
    }

    /**
     * Export basic info about a scheduler activity module
     *
     * @param \context $context
     * @param \stdClass $user
     */
    protected static function export_scheduler($context, $user) {
        if (!$context) {
            return;
        }
        $contextdata = helper::get_context_data($context, $user);
        helper::export_context_files($context, $user);
        writer::with_context($context)->export_data([], $contextdata);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * This will delete both slots and appointments for all users.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($scheduler = self::load_scheduler_for_context($context)) {
            $scheduler->delete_all_slots();
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * This will delete only appointments where the specified user is a student.
     * No data will be deleted if the user is (only) a teacher for the relevant slot/appointment,
     * since deleting it may lose data for other users (namely, the students).
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {

            if ($scheduler = self::load_scheduler_for_context($context)) {
                $apps = $scheduler->get_appointments_for_student($user->id);
                foreach ($apps as $app) {
                    $app->delete();
                }
            }
        }
    }

    /**
     * Delete all user data for the specified users (plural), in the specified context.
     *
     * This will delete only appointments where the specified user is a student.
     * No data will be deleted if the user is (only) a teacher for the relevant slot/appointment,
     * since deleting it may lose data for other users (namely, the students).
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {

        $context = $userlist->get_context();
        $users = $userlist->get_userids();

        if ($scheduler = self::load_scheduler_for_context($context)) {
            foreach ($users as $userid) {
                $apps = $scheduler->get_appointments_for_student($userid);
                foreach ($apps as $app) {
                    $app->delete();
                }
            }
        }
    }

}
