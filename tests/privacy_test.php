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
 * Data provider tests.
 *
 * @package    mod_scheduler
 * @category   test
 * @copyright  2018 Henning Bostelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace mod_scheduler;

defined('MOODLE_INTERNAL') || die();

global $CFG;

use core_privacy\tests\provider_testcase;
use mod_scheduler\privacy\provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;

require_once($CFG->dirroot.'/mod/scheduler/locallib.php');

/**
 * Data provider testcase class.
 *
 * @group      mod_scheduler
 * @copyright  2018 Henning Bostelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class privacy_test extends provider_testcase {

    /**
     * @var int course_module id used for testing
     */
    protected $moduleid;

    /**
     * @var the module context used for testing
     */
    protected $context;

    /**
     * @var int Course id used for testing
     */
    protected $courseid;

    /**
     * @var int Scheduler id used for testing
     */
    protected $schedulerid;

    /**
     * @var int One of the slots used for testing
     */
    protected $slotid;

    /**
     * @var int first student used in testing - a student that has an appointment
     */
    protected $student1;

    /**
     * @var int second student used in testing - a student that has an appointment
     */
    protected $student2;

    /**
     * @var array all students (only id) involved in the scheduler
     */
    protected $allstudents;

    protected function setUp(): void {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $this->courseid  = $course->id;

        $this->student1 = $this->getDataGenerator()->create_user();
        $this->student2 = $this->getDataGenerator()->create_user();
        $this->allstudents = [$this->student1->id, $this->student2->id];

        $options = array();
        $options['slottimes'] = array();
        $options['slotstudents'] = array();
        for ($c = 0; $c < 4; $c++) {
            $options['slottimes'][$c] = time() + ($c + 1) * DAYSECS;
            $stud = $this->getDataGenerator()->create_user()->id;
            $this->allstudents[] = $stud;
            $options['slotstudents'][$c] = array($stud);
        }
        $options['slottimes'][4] = time() + 10 * DAYSECS;
        $options['slottimes'][5] = time() + 11 * DAYSECS;
        $options['slotstudents'][5] = array(
                                        $this->student1->id,
                                        $this->student2->id
                                      );

        $scheduler = $this->getDataGenerator()->create_module('scheduler', array('course' => $course->id), $options);
        $coursemodule = $DB->get_record('course_modules', array('id' => $scheduler->cmid));

        $this->schedulerid = $scheduler->id;
        $this->moduleid  = $coursemodule->id;
        $this->context = \context_module::instance($scheduler->cmid);

        $recs = $DB->get_records('scheduler_slots', array('schedulerid' => $scheduler->id), 'id DESC');
        $this->slotid = array_keys($recs)[0];
        $this->appointmentids = array_keys($DB->get_records('scheduler_appointment', array('slotid' => $this->slotid)));
    }

    /**
     * Asserts whether or not an appointment exists in a scheduler for a certian student.
     *
     * @param int $schedulerid the id of the scheduler to test
     * @param int $studentid the user id of the student to test
     * @param boolean $expected whether an appointment is expected to exist or not
     */
    private function assert_appointment_status($schedulerid, $studentid, $expected) {
        global $DB;

        $sql = "SELECT * FROM {scheduler} s
                         JOIN {scheduler_slots} t ON t.schedulerid = s.id
                         JOIN {scheduler_appointment} a ON a.slotid = t.id
                        WHERE s.id = :schedulerid AND a.studentid = :studentid";

        $params = array('schedulerid' => $schedulerid, 'studentid' => $studentid);
        $actual = $DB->record_exists_sql($sql, $params);
        $this->assertEquals($expected, $actual, "Checking whether student $studentid has appointment in scheduler $schedulerid");
    }

    /**
     * Test getting the contexts for a user.
     *
     * @covers \mod_scheduler\privacy\provider::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid() {

        // Get contexts for the first user.
        $contextids = provider::get_contexts_for_userid($this->student1->id)->get_contextids();
        $this->assertEquals([$this->context->id], $contextids, '', 0.0, 10, true);
    }

    /**
     * Test getting the users within a context.
     *
     * @covers \mod_scheduler\privacy\provider::get_users_in_context
     */
    public function test_get_users_in_context() {
        global $DB;
        $component = 'mod_scheduler';

        // Ensure userlist for context contains all users.
        $userlist = new \core_privacy\local\request\userlist($this->context, $component);
        provider::get_users_in_context($userlist);

        $expected = $this->allstudents;
        $expected[] = 2; // The teacher involved.
        $actual = $userlist->get_userids();
        sort($expected);
        sort($actual);
        $this->assertEquals($expected, $actual);
    }


    /**
     * Export test for teacher data.
     *
     * @covers \mod_scheduler\privacy\provider::export_user_data
     */
    public function test_export_teacher_data() {
        global $DB;

        // Export all contexts for the teacher.
        $contextids = [$this->context->id];
        $teacher = $DB->get_record('user', array('id' => 2));
        $appctx = new approved_contextlist($teacher, 'mod_scheduler', $contextids);
        provider::export_user_data($appctx);
        $data = writer::with_context($this->context)->get_data([]);
        $this->assertNotEmpty($data);
    }

    /**
     * Export test for student1's data.
     *
     * @covers \mod_scheduler\privacy\provider::export_user_data
     */
    public function test_export_user_data1() {

        // Export all contexts for the first user.
        $contextids = [$this->context->id];
        $appctx = new approved_contextlist($this->student1, 'mod_scheduler', $contextids);
        provider::export_user_data($appctx);
        $data = writer::with_context($this->context)->get_data([]);
        $this->assertNotEmpty($data);
    }

    /**
     * Test for delete_data_for_all_users_in_context().
     *
     * @covers \mod_scheduler\privacy\provider::delete_data_for_all_users_in_context
     */
    public function test_delete_data_for_all_users_in_context() {
        provider::delete_data_for_all_users_in_context($this->context);

        foreach ($this->allstudents as $u) {
            $this->assert_appointment_status($this->schedulerid, $u, false);
        }
    }

    /**
     * Test for delete_data_for_user().
     *
     * @covers \mod_scheduler\privacy\provider::delete_data_for_user
     */
    public function test_delete_data_for_user() {
        $appctx = new approved_contextlist($this->student1, 'mod_scheduler', [$this->context->id]);
        provider::delete_data_for_user($appctx);

        $this->assert_appointment_status($this->schedulerid, $this->student1->id, false);
        $this->assert_appointment_status($this->schedulerid, $this->student2->id, true);

    }

    /**
     * Test for delete_data_for_users().
     *
     * @covers \mod_scheduler\privacy\provider::delete_data_for_users
     */
    public function test_delete_data_for_users() {
        $component = 'mod_scheduler';

        $approveduserids = [$this->student1->id, $this->student2->id];
        $approvedlist = new approved_userlist($this->context, $component, $approveduserids);
        provider::delete_data_for_users($approvedlist);

        $this->assert_appointment_status($this->schedulerid, $this->student1->id, false);
        $this->assert_appointment_status($this->schedulerid, $this->student2->id, false);
    }
}
