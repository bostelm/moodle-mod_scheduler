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
 * Unit tests for scheduler slots
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler;

defined('MOODLE_INTERNAL') || die();

use \mod_scheduler\model\scheduler;
use \mod_scheduler\model\slot;

global $CFG;
require_once($CFG->dirroot . '/mod/scheduler/locallib.php');

/**
 * Unit tests for the scheduler_slots class.
 *
 * @group      mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class slot_test extends \advanced_testcase {

    /**
     * @var int Course_modules id used for testing
     */
    protected $moduleid;

    /**
     * @var int Course id used for testing
     */
    protected $courseid;

    /**
     * @var int Scheduler id used for testing
     */
    protected $schedulerid;

    /**
     * @var int User id of teacher used for testing
     */
    protected $teacherid;

    /**
     * @var int a slot used for testing
     */
    protected $slotid;

    /**
     * @var int[] appointments used for testing
     */
    protected $appointmentids;

    /**
     * @var int[] id of students used for testing
     */
    protected $students;

    protected function setUp(): void {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();

        $this->students = array();
        for ($i = 0; $i < 3; $i++) {
            $this->students[$i] = $this->getDataGenerator()->create_user()->id;
        }

        $options = array();
        $options['slottimes'] = array();
        $options['slotstudents'] = array();
        $options['slottimes'][0] = time() + DAYSECS;
        $options['slotstudents'][0] = $this->students;

        $scheduler = $this->getDataGenerator()->create_module('scheduler', array('course' => $course->id), $options);
        $coursemodule = $DB->get_record('course_modules', array('id' => $scheduler->cmid));

        $this->schedulerid = $scheduler->id;
        $this->moduleid  = $coursemodule->id;
        $this->courseid  = $coursemodule->course;
        $this->teacherid = 2;  // Admin user.
        $this->slotid = $DB->get_field('scheduler_slots', 'id', array('schedulerid' => $scheduler->id), MUST_EXIST);
        $this->appointmentids = array_keys($DB->get_records('scheduler_appointment', array('slotid' => $this->slotid)));
    }

    /**
     * Assert that a record is present in the DB
     *
     * @param string $table name of table to test
     * @param int $id id of record to look for
     * @param string $msg message
     */
    private function assert_record_present($table, $id, $msg = "") {
        global $DB;

        $ex = $DB->record_exists($table, array('id' => $id));
        $this->assertTrue($ex, "Checking whether record $id is present in table $table: $msg");
    }

    /**
     * Assert that a record is absent from the DB
     *
     * @param string $table name of table to test
     * @param int $id id of record to look for
     * @param string $msg message
     */
    private function assert_record_absent($table, $id, $msg = "") {
        global $DB;

        $ex = $DB->record_exists($table, array('id' => $id));
        $this->assertFalse($ex, "Checking whether record $id is absent in table $table: $msg");
    }

    /**
     * Test creating a slot with appointments
     *
     * @covers \mod_scheduler\model\scheduler::load_by_id
     */
    public function test_create() {

        global $DB;

        $scheduler = scheduler::load_by_id($this->schedulerid);
        $slot = $scheduler->create_slot();

        $slot->teacherid = $this->getDataGenerator()->create_user()->id;
        $slot->starttime = time();
        $slot->duration = 60;

        $newapp1 = $slot->create_appointment();
        $newapp1->studentid = $this->getDataGenerator()->create_user()->id;
        $newapp2 = $slot->create_appointment();
        $newapp2->studentid = $this->getDataGenerator()->create_user()->id;

        $slot->save();

        $newid = $slot->get_id();
        $this->assertNotEquals(0, $newid, "Checking slot id after creation");

        $newcnt = $DB->count_records('scheduler_appointment', array('slotid' => $newid));
        $this->assertEquals(2, $newcnt, "Counting number of appointments after addition");

    }


    /**
     * Test deleting a slot and associated data
     *
     * @covers \mod_scheduler\model\scheduler::load_by_id
     */
    public function test_delete() {

        $scheduler = scheduler::load_by_id($this->schedulerid);

        // Make sure calendar events are all created.
        $slot = slot::load_by_id($this->slotid, $scheduler);
        $start = $slot->starttime;
        $slot->save();

        // Load again, to delete.
        $slot = slot::load_by_id($this->slotid, $scheduler);
        $slot->delete();

        $this->assert_record_absent('scheduler_slots', $this->slotid);
        foreach ($this->appointmentids as $id) {
            $this->assert_record_absent('scheduler_appointment', $id);
        }

        $this->assert_event_absent($this->teacherid, $start, "");
        foreach ($this->students as $student) {
            $this->assert_event_absent($student, $start, "");
        }

    }

    /**
     * Test adding an appointment to a slot.
     *
     * @covers \mod_scheduler\model\scheduler::load_by_id
     */
    public function test_add_appointment() {

        global $DB;

        $scheduler = scheduler::load_by_id($this->schedulerid);
        $slot = slot::load_by_id($this->slotid, $scheduler);

        $oldcnt = $DB->count_records('scheduler_appointment', array('slotid' => $slot->get_id()));
        $this->assertEquals(3, $oldcnt, "Counting number of appointments before addition");

        $newapp = $slot->create_appointment();
        $newapp->studentid = $this->getDataGenerator()->create_user()->id;

        $slot->save();

        $newcnt = $DB->count_records('scheduler_appointment', array('slotid' => $slot->get_id()));
        $this->assertEquals(4, $newcnt, "Counting number of appointments after addition");

    }

    /**
     * Test removing an appointment from a slot.
     *
     * @covers \mod_scheduler\model\scheduler::load_by_id
     */
    public function test_remove_appointment() {

        global $DB;

        $scheduler = scheduler::load_by_id($this->schedulerid);
        $slot = slot::load_by_id($this->slotid, $scheduler);

        $apps = $slot->get_appointments();
        $appointment = array_pop($apps);
        $delid = $appointment->get_id();

        $this->assert_record_present('scheduler_appointment', $delid);

        $slot->remove_appointment($appointment);
        $slot->save();

        $this->assert_record_absent('scheduler_appointment', $delid);
    }

    /**
     * Test presence or absence of event records when appointments are modified.
     *
     * @covers \mod_scheduler\model\scheduler::load_by_id
     */
    public function test_calendar_events() {
        global $DB;

        $scheduler = scheduler::load_by_id($this->schedulerid);
        $slot = slot::load_by_id($this->slotid, $scheduler);
        $slot->save();

        $oldstart = $slot->starttime;

        $this->assert_event_exists($this->teacherid, $slot->starttime, "Meeting with your Students");
        foreach ($this->students as $student) {
            $this->assert_event_exists($student, $slot->starttime, "Meeting with your Teacher");
        }

        $newstart = time() + 3 * DAYSECS;
        $slot->starttime = $newstart;
        $slot->save();

        foreach ($this->students as $student) {
            $this->assert_event_absent($student, $oldstart);
            $this->assert_event_exists($student, $newstart, "Meeting with your Teacher");
        }
        $this->assert_event_absent($this->teacherid, $oldstart);
        $this->assert_event_exists($this->teacherid, $newstart, "Meeting with your Students");

        // Delete one of the appointments.
        $app = $slot->get_appointment($this->appointmentids[0]);
        $slot->remove_appointment($app);
        $slot->save();

        $this->assert_event_absent($this->students[0], $newstart);
        $this->assert_event_exists($this->students[1], $newstart, "Meeting with your Teacher");
        $this->assert_event_exists($this->teacherid, $newstart, "Meeting with your Students");

        // Delete all appointments.
        $DB->delete_records('scheduler_appointment', array('slotid' => $this->slotid));
        $slot = slot::load_by_id($this->slotid, $scheduler);
        $slot->save();

        foreach ($this->students as $student) {
            $this->assert_event_absent($student, $newstart);
        }
        $this->assert_event_absent($this->teacherid, $newstart);

    }

    /**
     * Assert that a calendar event exists in the DB.
     *
     * @param int $userid user associated with event
     * @param int $time start time of the event
     * @param string $titlestart beginning of the title of the event
     */
    private function assert_event_exists($userid, $time, $titlestart) {
        global $DB;
        $events = calendar_get_events($time - MINSECS, $time + HOURSECS, $userid, false, false);
        $this->assertEquals(1, count($events), "Expecting exactly one event at time $time for user $userid");
        $event = array_pop($events);
        $this->assertEquals($time, $event->timestart);
        $this->assertEquals('scheduler', $event->modulename);
        $this->assertTrue(strpos($event->name, $titlestart) === 0, "Checking event title start: $titlestart");
    }

    /**
     * Assert that a calendar event at a certain time is absent from the DB.
     *
     * @param int $userid user id associated with event
     * @param int $time start time of the event
     */
    private function assert_event_absent($userid, $time) {
        $events = calendar_get_events($time - MINSECS, $time + HOURSECS, $userid, false, false);
        $this->assertEquals(0, count($events), "Expecting no event at time $time for user $userid");
    }
}
