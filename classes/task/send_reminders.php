<?php

/**
 * Scheduled background task for sending automated appointment reminders
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\task;

require_once(dirname(__FILE__).'/../../model/scheduler_instance.php');
require_once(dirname(__FILE__).'/../../model/scheduler_slot.php');
require_once(dirname(__FILE__).'/../../model/scheduler_appointment.php');
require_once(dirname(__FILE__).'/../../mailtemplatelib.php');

class send_reminders extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('sendreminders', 'mod_scheduler');
    }

    public function execute() {

        global $DB;

        $date = make_timestamp(date('Y'), date('m'), date('d'), date('H'), date('i'));

        // Find relevant slots in all schedulers.
        $select = 'emaildate > 0 AND emaildate <= ? AND starttime > ?';
        $slots = $DB->get_records_select('scheduler_slots', $select, array($date, $date), 'starttime');

        foreach ($slots as $slot) {
            // Get teacher record.
            $teacher = $DB->get_record('user', array('id' => $slot->teacherid));

            // Get scheduler, slot and course.
            $scheduler = \scheduler_instance::load_by_id($slot->schedulerid);
            $slotm = $scheduler->get_slot($slot->id);
            $course = $scheduler->get_courserec();

            // Mark as sent. (Do this first for safe fallback in case of an exception.)
            $slot->emaildate = -1;
            $DB->update_record('scheduler_slots', $slot);

            // Send reminder to all students in the slot.
            foreach ($slotm->get_appointments() as $appointment) {
                $student = $DB->get_record('user', array('id' => $appointment->studentid));
                cron_setup_user($student, $course);
                \scheduler_messenger::send_slot_notification($slotm,
                        'reminder', 'reminder', $teacher, $student, $teacher, $student, $course);
            }
        }
        cron_setup_user();
    }

}