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
 * Unit tests for the MVC model classes
 *
 * @package    mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler;

defined('MOODLE_INTERNAL') || die();

use \mod_scheduler\model\scheduler;
use \mod_scheduler\model\appointment_factory;

global $CFG;
require_once($CFG->dirroot . '/mod/scheduler/locallib.php');

/**
 * Unit tests for the MVC model classes
 *
 * @group mod_scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class model_test extends \advanced_testcase {

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
     * @var int User id used for testing
     */
    protected $userid;

    protected function setUp(): void {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $options = array();
        $options['slottimes'] = array();
        $options['slotstudents'] = array();
        for ($c = 0; $c < 4; $c++) {
            $options['slottimes'][$c] = time() + ($c + 1) * DAYSECS;
            $options['slotstudents'][$c] = array($this->getDataGenerator()->create_user()->id);
        }
        $options['slottimes'][4] = time() + 10 * DAYSECS;
        $options['slottimes'][5] = time() + 11 * DAYSECS;
        $options['slotstudents'][5] = array(
                                            $this->getDataGenerator()->create_user()->id,
                                            $this->getDataGenerator()->create_user()->id
                                           );

        $scheduler = $this->getDataGenerator()->create_module('scheduler', array('course' => $course->id), $options);
        $coursemodule = $DB->get_record('course_modules', array('id' => $scheduler->cmid));

        $this->schedulerid = $scheduler->id;
        $this->moduleid  = $coursemodule->id;
        $this->courseid  = $coursemodule->course;
        $this->userid = 2;  // Admin user.
    }

    /**
     * Test loading a scheduler instance from the database
     *
     * @covers \mod_scheduler\model\scheduler::load_by_coursemodule_id
     */
    public function test_scheduler() {
        global $DB;

        $dbdata = $DB->get_record('scheduler', array('id' => $this->schedulerid));

        $instance = scheduler::load_by_coursemodule_id($this->moduleid);

        $this->assertEquals( $dbdata->name, $instance->get_name());

    }

    /**
     * Test the "appointment" data object
     * (basic functionality, with minimal reference to slots)
     *
     * @covers \mod_scheduler\model\scheduler::load_by_coursemodule_id
     */
    public function test_appointment() {

        global $DB;

        $instance = scheduler::load_by_coursemodule_id($this->moduleid);
        $slot = array_values($instance->get_slots())[0];
        $factory = new appointment_factory($slot);

        $user = $this->getdataGenerator()->create_user();

        $app0 = new \stdClass();
        $app0->slotid = 1;
        $app0->studentid = $user->id;
        $app0->attended = 0;
        $app0->grade = 0;
        $app0->appointmentnote = 'testnote';
        $app0->teachernote = 'confidentialtestnote';
        $app0->timecreated = time();
        $app0->timemodified = time();

        $id1 = $DB->insert_record('scheduler_appointment', $app0);

        $appobj = $factory->create_from_id($id1);
        $this->assertEquals($user->id, $appobj->studentid);
        $this->assertEquals(fullname($user), fullname($appobj->get_student()));
        $this->assertFalse($appobj->is_attended());
        $this->assertEquals(0, $appobj->grade);

        $app0->attended = 1;
        $app0->grade = -7;
        $id2 = $DB->insert_record('scheduler_appointment', $app0);

        $appobj = $factory->create_from_id($id2);
        $this->assertEquals($user->id, $appobj->studentid);
        $this->assertEquals(fullname($user), fullname($appobj->get_student()));
        $this->assertTrue($appobj->is_attended());
        $this->assertEquals(-7, $appobj->grade);

    }

}
