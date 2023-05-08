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
 * mod_scheduler data generator
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Scheduler module PHPUnit data generator class
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_scheduler_generator extends testing_module_generator {

    /**
     * set default
     *
     * @param stdClass $record
     * @param string $property
     * @param mixed $value
     */
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
                $slot->teacherid = isset($options['slotteachers'][$slotkey]) ?
                    $options['slotteachers'][$slotkey] : 2; // Admin user as default.
                $slot->appointmentlocation = 'Test Loc';
                $slot->timemodified = time();
                $slot->notes = '';
                $slot->slotnote = '';
                $slot->exclusivity = isset($options['slotexclusivity'][$slotkey]) ? $options['slotexclusivity'][$slotkey] : 0;
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
                        $appointment->teachernote = '';
                        $appointment->timecreated = time();
                        $appointment->timemodified = time();
                        $appointmentid = $DB->insert_record('scheduler_appointment', $appointment);
                    }
                }

                if (isset($options['slotwatchers'][$slotkey])) {
                    $userids = (array) $options['slotwatchers'][$slotkey];
                    foreach ($userids as $userid) {
                        $DB->insert_record('scheduler_watcher', (object) ['userid' => $userid, 'slotid' => $slotid]);
                    }
                }
            }
        }

        return $modinst;
    }

    /**
     * Create a scheduler slot, optionally with appointment for one student`.
     *
     * @param array $data
     */
    public function create_slot(array $data): void {

        $scheduler = \mod_scheduler\model\scheduler::load_by_coursemodule_id($data['schedulerid']);

        $slot = new \mod_scheduler\model\slot($scheduler);
        $slot->teacherid = $data['teacherid'];
        $slot->starttime = $data['starttime'];
        $slot->duration = $data['duration'];
        $slot->appointmentlocation = isset($data['location']) ? $data['location'] : '';
        $slot->exclusivity = isset($data['exclusivity']) ? $data['exclusivity'] : 1;
        $slot->hideuntil = isset($data['hideuntil']) ? $data['hideuntil'] : 0;

        if (isset($data['studentid']) && $data['studentid'] > 0) {
            $app = $slot->create_appointment();
            $app->studentid = $data['studentid'];
            $app->seen = isset($data['seen']) ? $data['seen'] : 0;
            $app->grade = isset($data['grade']) ? $data['grade'] : -1;
        }

        $slot->save();
    }

}
