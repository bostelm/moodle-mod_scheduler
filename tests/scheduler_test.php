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

	private function assert_slot_times($expected, $actual, $options) {
        $this->assertEquals(count($expected), count($actual));
        $slottimes = array();
        foreach ($expected as $e) {
            $slottimes[] = $options['slottimes'][$e];
        }
        foreach ($actual as $a) {
            $this->assertTrue( in_array($a->starttime, $slottimes));
        }
	}

	private function check_timed_slots($schedulerid, $studentid, $slotoptions, $expAttended, $expUpcoming, $expAvailable, $expBookable) {

        $sched = scheduler_instance::load_by_id($schedulerid);

        $attended = $sched->get_attended_slots_for_student($studentid);
        $this->assert_slot_times($expAttended, $attended, $slotoptions);

        $upcoming = $sched->get_upcoming_slots_for_student($studentid);
        $this->assert_slot_times($expUpcoming, $upcoming, $slotoptions);

        $available = $sched->get_slots_available_to_student($studentid, false);
        $this->assert_slot_times($expAvailable, $available, $slotoptions);

        $bookable = $sched->get_slots_available_to_student($studentid, true);
        $this->assert_slot_times($expBookable, $bookable, $slotoptions);

	}

    public function test_load_slot_timing() {

		global $DB;

		$currentstud = $this->getDataGenerator()->create_user()->id;

        $options = array();
        $options['slottimes'] = array();
        $options['slotstudents'] = array();
        $options['slotattended'] = array();

        // Create slots 0 to 5, n days in the future, booked by the student but not attended
        for ($c = 0; $c <= 5; $c++) {
            $options['slottimes'][$c] = time()+$c*DAYSECS+12*HOURSECS;
            $options['slotstudents'][$c] = $currentstud;
	        $options['slotattended'][$c] = false;
        }

        // Create slot 6, located in the past, booked by the student but not attended
        $options['slottimes'][6] = time()-3*DAYSECS;
        $options['slotstudents'][6] = $currentstud;
        $options['slotattended'][6] = false;

        // Create slot 7, located in the past, booked by the student and attended
        $options['slottimes'][7] = time()-4*DAYSECS;
        $options['slotstudents'][7] = $currentstud;
        $options['slotattended'][7] = true;

        // Create slot 8, located less than one day in the future but marked attended
        $options['slottimes'][8] = time()+9*HOURSECS;
        $options['slotstudents'][8] = $currentstud;
        $options['slotattended'][8] = true;

        // Create slots 10 to 14, (n-10) days in the future, open for booking
        for ($c = 10; $c <= 14; $c++) {
            $options['slottimes'][$c] = time()+($c-10)*DAYSECS+10*HOURSECS;
        }

        $schedrec = $this->getDataGenerator()->create_module('scheduler', array('course'=>$this->courseid), $options);
        $schedid = $schedrec->id;

		$schedrec->guardtime = 0;
		$DB->update_record('scheduler', $schedrec);

	    $this->check_timed_slots($schedid, $currentstud, $options,
	     			array(7, 8),
	     			array(6),
	     			array(10, 11, 12, 13, 14),
	     			array(10, 11, 12, 13, 14, 0, 1, 2, 3, 4, 5) );

		$schedrec->guardtime = DAYSECS;
		$DB->update_record('scheduler', $schedrec);

	    $this->check_timed_slots($schedid, $currentstud, $options,
	     			array(7, 8),
	     			array(6, 0),
	     			array(11, 12, 13, 14),
	     			array(11, 12, 13, 14, 1, 2, 3, 4, 5) );

		$schedrec->guardtime = 4*DAYSECS;
		$DB->update_record('scheduler', $schedrec);

	    $this->check_timed_slots($schedid, $currentstud, $options,
	     			array(7, 8),
	     			array(6, 0, 1, 2, 3),
	     			array(14),
	     			array(14, 4, 5) );

		$schedrec->guardtime = 20*DAYSECS;
		$DB->update_record('scheduler', $schedrec);

	    $this->check_timed_slots($schedid, $currentstud, $options,
	     			array(7, 8),
	     			array(6, 0, 1, 2, 3, 4, 5),
	     			array(),
	     			array() );

    }

}
