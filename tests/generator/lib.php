<?php

/**
 * mod_scheduler data generator
 *
 * @package    mod_scheduler
 * @category   phpunit
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Scheduler module PHPUnit data generator class
 *
 * @package    mod_scheduler
 * @category   phpunit
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_scheduler_generator extends testing_module_generator {

    private function set_default($record, $property, $value) {
        if (!isset($record->$property)) {
            $record->$property = $value;
        }
    }

    /**
     * Create new scheduler module instance
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass activity record with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/mod/scheduler/lib.php");

        $this->instancecount++;
        $i = $this->instancecount;

        $record = (object)(array)$record;
        $options = (array)$options;

        if (empty($record->course)) {
            throw new coding_exception('module generator requires $record->course');
        }
        self::set_default($record, 'name', get_string('pluginname', 'scheduler').' '.$i);
        self::set_default($record, 'intro', 'Test scheduler '.$i);
        self::set_default($record, 'introformat', FORMAT_MOODLE);
        self::set_default($record, 'schedulermode', 'onetime');
        self::set_default($record, 'guardtime', 0);
        self::set_default($record, 'defaultslotduration', 15);
        self::set_default($record, 'staffrolename', '');
        self::set_default($record, 'scale', 0);
        if (isset($options['idnumber'])) {
            $record->cmidnumber = $options['idnumber'];
        } else {
            $record->cmidnumber = '';
        }

        $record->coursemodule = $this->precreate_course_module($record->course, $options);
        $id = scheduler_add_instance($record);
        $modinst = $this->post_add_instance($id, $record->coursemodule);

        if (isset($options['slottimes'])) {
            $slottimes = (array) $options['slottimes'];
            foreach ($slottimes as $slotkey => $time) {
                $slot = new stdClass();
                $slot->schedulerid = $id;
                $slot->starttime = $time;
                $slot->duration = 10;
                $slot->teacherid = 2; // admin - for the moment
                $slot->appointmentlocation = 'Test Loc';
                $slot->timemodified = time();
                $slot->notes = '';
                $slot->appointmentnote = '';
                $slot->exclusivity = 0;
                $slot->emaildate = 0;
                $slot->hideuntil = 0;
                $slotid = $DB->insert_record('scheduler_slots', $slot);

                if (isset($options['slotstudents'][$slotkey])) {
                    $students = (array)$options['slotstudents'][$slotkey];
                    foreach ($students as $studentkey => $userid) {
                        $appointment = new stdClass();
                        $appointment->slotid = $slotid;
                        $appointment->studentid = $userid;
                        $appointment->attended = isset($options['slotattended'][$slotkey]) && $options['slotattended'][$slotkey];
                        $appointment->grade = 0;
                        $appointment->appointmentnote = '';
                        $appointment->timecreated = time();
                        $appointment->timemodified = time();
                        $appointmentid = $DB->insert_record('scheduler_appointment', $appointment);
                    }
                }
            }
        }

        return $modinst;
    }
}
