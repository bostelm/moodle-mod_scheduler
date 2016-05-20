<?php

/**
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Define all the backup steps that will be used by the backup_scheduler_activity_task
 */

/**
 * Define the complete scheduler structure for backup, with file and id annotations
 */
class backup_scheduler_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $scheduler = new backup_nested_element('scheduler', array('id'), array(
            'name', 'intro', 'introformat', 'schedulermode', 'maxbookings',
            'guardtime', 'defaultslotduration', 'allownotifications', 'staffrolename',
            'scale', 'gradingstrategy', 'bookingrouping', 'usenotes', 'timemodified'));

        $slots = new backup_nested_element('slots');

        $slot = new backup_nested_element('slot', array('id'), array(
            'starttime', 'duration', 'teacherid', 'appointmentlocation',
            'timemodified', 'notes', 'notesformat', 'exclusivity',
            'emaildate', 'hideuntil'));

        $appointments = new backup_nested_element('appointments');

        $appointment = new backup_nested_element('appointment', array('id'), array(
            'studentid', 'attended', 'grade',
            'appointmentnote', 'appointmentnoteformat', 'teachernote', 'teachernoteformat',
            'timecreated', 'timemodified'));

        // Build the tree.

        $scheduler->add_child($slots);
        $slots->add_child($slot);

        $slot->add_child($appointments);
        $appointments->add_child($appointment);

        // Define sources.
        $scheduler->set_source_table('scheduler', array('id' => backup::VAR_ACTIVITYID));
        $scheduler->annotate_ids('grouping', 'bookingrouping');

        // Include appointments only if we back up user information.
        if ($userinfo) {
            $slot->set_source_table('scheduler_slots', array('schedulerid' => backup::VAR_PARENTID));
            $appointment->set_source_table('scheduler_appointment', array('slotid' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $scheduler->annotate_ids('scale', 'scale');

        if ($userinfo) {
            $slot->annotate_ids('user', 'teacherid');
            $appointment->annotate_ids('user', 'studentid');
        }

        // Define file annotations.
        $scheduler->annotate_files('mod_scheduler', 'intro', null); // Files stored in intro field.
        $slot->annotate_files('mod_scheduler', 'slotnote', 'id'); // Files stored in slot notes.
        $appointment->annotate_files('mod_scheduler', 'appointmentnote', 'id'); // Files stored in appointment notes.
        $appointment->annotate_files('mod_scheduler', 'teachernote', 'id'); // Files stored in teacher-only notes.

        // Return the root element (scheduler), wrapped into standard activity structure.
        return $this->prepare_activity_structure($scheduler);
    }
}
