<?php

/**
 * Unit tests for the MVC model classes
 *
 * @package    mod
 * @subpackage scheduler
 * @category   phpunit
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/scheduler/locallib.php');


class mod_scheduler_scheduler_testcase extends advanced_testcase {

    protected $moduleid;  // course_modules id used for testing
    protected $courseid;  // course id used for testing
    protected $schedulerid; // scheduler id used for testing
    protected $slotid;   // one of the slots used for testing

    protected function setUp() {
        global $DB, $CFG;

        $this->resetAfterTest(true);

        $course = $this->getDataGenerator()->create_course();
        $options = array();
        $options['slottimes'] = array();
        $options['slotstudents'] = array();
        for ($c = 0; $c < 4; $c++) {
            $options['slottimes'][$c] = time()+($c+1)*DAYSECS;
            $options['slotstudents'][$c] = array($this->getDataGenerator()->create_user()->id);
        }
        $options['slottimes'][4] = time()+10*DAYSECS;
        $options['slottimes'][5] = time()+11*DAYSECS;
        $options['slotstudents'][5] = array($this->getDataGenerator()->create_user()->id, $this->getDataGenerator()->create_user()->id);

        $scheduler = $this->getDataGenerator()->create_module('scheduler', array('course'=>$course->id), $options);
        $coursemodule = $DB->get_record('course_modules', array('id'=>$scheduler->cmid));

        $this->schedulerid = $scheduler->id;
        $this->moduleid  = $coursemodule->id;
        $this->courseid  = $coursemodule->course;

        $recs = $DB->get_records('scheduler_slots', array('schedulerid' => $scheduler->id), 'id DESC');
        $this->slotid = array_keys($recs)[0];
        $this->appointmentids = array_keys($DB->get_records('scheduler_appointment', array('slotid' => $this->slotid)));
    }

    private function assert_record_count($table, $field, $value, $expect) {
        global $DB;

        $act = $DB->count_records($table, array($field => $value));
        $this->assertEquals($expect, $act, "Checking whether table $table has $expect records with $field equal to $value");
    }

    public function test_scheduler_instance() {
        global $DB;

        $dbdata = $DB->get_record('scheduler', array('id'=>$this->schedulerid));

        $instance = scheduler_instance::load_by_coursemodule_id($this->moduleid);

        $this->assertEquals( $dbdata->name, $instance->get_name());

    }

    public function test_load_slots() {
        global $DB;

        $instance = scheduler_instance::load_by_coursemodule_id($this->moduleid);

        /* test slot retrieval */

        $slotcount = $instance->get_slot_count();
        $this->assertEquals(6, $slotcount);

        $slots = $instance->get_all_slots(2, 3);
        $this->assertEquals(3, count($slots));

        $slots = $instance->get_slots_without_appointment();
        $this->assertEquals(1, count($slots));

        $allslots = $instance->get_all_slots();
        $this->assertEquals(6, count($allslots));

        $cnt = 0;
        foreach ($allslots as $slot) {
            $this->assertTrue($slot instanceof scheduler_slot);

            if ($cnt == 5) {
                $expectedapp = 2;
            } else if ($cnt == 4) {
                $expectedapp = 0;
            } else {
                $expectedapp = 1;
            }
            $this->assertEquals($expectedapp, $slot->get_appointment_count());

            $apps = $slot->get_appointments($slot->get_appointments());
            $this->assertEquals($expectedapp, count($apps));

            foreach ($apps as $app) {
                $this->assertTrue($app instanceof scheduler_appointment);
            }
            $cnt++;
        }

    }

    public function test_add_slot() {

        $scheduler = scheduler_instance::load_by_coursemodule_id($this->moduleid);

        $newslot = $scheduler->create_slot();
        $newslot->teacherid = $this->getDataGenerator()->create_user()->id;
        $newslot->starttime = time() + MINSECS;
        $newslot->duration = 10;

        $allslots = $scheduler->get_slots();
        $this->assertEquals(7, count($allslots));

        $scheduler->save();

    }

    public function test_delete_scheduler() {

        $options = array();
        $options['slottimes'] = array();
        $options['slotstudents'] = array();
        for ($c = 0; $c < 10; $c++) {
            $options['slottimes'][$c] = time()+($c+1)*DAYSECS;
            $options['slotstudents'][$c] = array($this->getDataGenerator()->create_user()->id);
        }

        $delrec = $this->getDataGenerator()->create_module('scheduler', array('course'=>$this->courseid), $options);
        $delid = $delrec->id;

        $delsched = scheduler_instance::load_by_id($delid);

        $this->assert_record_count('scheduler', 'id', $this->schedulerid, 1);
        $this->assert_record_count('scheduler_slots', 'schedulerid', $this->schedulerid, 6);
        $this->assert_record_count('scheduler_appointment', 'slotid', $this->slotid, 2);

        $this->assert_record_count('scheduler', 'id', $delid, 1);
        $this->assert_record_count('scheduler_slots', 'schedulerid', $delid, 10);

        $delsched->delete();

        $this->assert_record_count('scheduler', 'id', $this->schedulerid, 1);
        $this->assert_record_count('scheduler_slots', 'schedulerid', $this->schedulerid, 6);
        $this->assert_record_count('scheduler_appointment', 'slotid', $this->slotid, 2);

        $this->assert_record_count('scheduler', 'id', $delid, 0);
        $this->assert_record_count('scheduler_slots', 'schedulerid', $delid, 0);

    }

}
