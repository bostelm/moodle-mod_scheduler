<?php

/**
 * This file contains a renderer for the scheduler module
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the scheduler module.
 *
 */
class mod_scheduler_renderer extends plugin_renderer_base {


    /**
     * Formats a grade in a specific scheduler for display
     * @param scheduler_instance $scheduler
     * @param string $grade the grade to be displayed
     * @param boolean $short formats the grade in short form (result empty if grading is
     * not used, or no grade is available; parantheses are put around the grade if it is present)
     * @return string the formatted grade
     */
    public function format_grade($scheduler, $grade, $short=false) {

        $result = '';
        if ($scheduler->scale == 0 || is_null($grade) ) {
            // Scheduler doesn't allow grading, or no grade entered.
            if (!$short) {
                $result = get_string('nograde');
            }
        } else {
            if ($scheduler->scale > 0) {
                // numeric grades
                $result .= $grade;
                if (strlen($grade) > 0) {
                    $result .=  '/' . $scheduler->scale;
                }
            } else {
                // grade on scale
                if ($grade > 0) {
                    $result .= $scheduler->get_scale_levels()[$grade];
                }
            }
            if ($short && (strlen($result)>0)) {
                $result = '('.$result.')';
            }
        }
        return $result;
    }

    public function user_profile_link($scheduler, $user) {
        $profileurl = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $scheduler->course));
        return html_writer::link($profileurl, fullname($user));
    }

    /**
     * Rendering a table of slots
     *
     * @param scheduler_slot_table $slottable the table to rended
     * @return string the HTML output
     */
    public function render_scheduler_slot_table(scheduler_slot_table $slottable) {
        $table = new html_table();

        $table->head  = array(get_string('date', 'scheduler'),
            $slottable->scheduler->get_teacher_name(),
            get_string('comments', 'scheduler'));
        $table->align = array('left', 'center', 'left');

        if ($slottable->showgrades) {
            $table->head[] = get_string('grade', 'scheduler');
            $table->align[] = 'left';
        }

        $table->data = array();

        $previousdate = '';
        $previoustime = '';
        $previousendtime = '';

        foreach ($slottable->slots as $slot) {
            $rowdata = array();

            $startdate = scheduler_userdate($slot->starttime, 1);
            $starttime = scheduler_usertime($slot->starttime, 1);
            $endtime = scheduler_usertime($slot->endtime, 1);
            // Simplify display of dates, start and end times.
            if ($startdate == $previousdate && $starttime == $previoustime && $endtime == $previousendtime) {
                // If this row exactly matches previous, there's nothing to display.
                $startdatestr = '';
                $starttimestr = '';
                $endtimestr = '';
            } else if ($startdate == $previousdate) {
                // If this date matches previous date, just display times.
                $startdatestr = '';
                $starttimestr = $starttime;
                $endtimestr = $endtime;
            } else {
                // Otherwise, display all elements.
                $startdatestr = $startdate;
                $starttimestr = $starttime;
                $endtimestr = $endtime;
            }

            $timedata = html_writer::div($startdatestr, 'datelabel attended');
            $timedata .= html_writer::div("[$starttimestr - $endtimestr]", 'timelabel');

            $rowdata[] = $timedata;

            $rowdata[] = $this->user_profile_link($slottable->scheduler, $slot->teacher);

            $studentnotes1 = '';
            $studentnotes2 = '';
            $textoptions = array('context' => $slottable->scheduler->context);
            if ($slot->slotnotes != '') {
                $studentnotes1 = html_writer::tag('strong', get_string('yourslotnotes', 'scheduler'));
                $studentnotes1 .= html_writer::empty_tag('br');
                $studentnotes1 .= format_text($slot->slotnotes, $slot->slotnotesformat, $textoptions);
                $studentnotes1 = html_writer::div($studentnotes1, 'slotnotes');
            }
            if ($slot->appointmentnotes != '') {
                $studentnotes2 = html_writer::tag('strong', get_string('yourappointmentnote', 'scheduler'));
                $studentnotes2 .= html_writer::empty_tag('br');
                $studentnotes2 .= format_text($slot->appointmentnotes, $slot->appointmentnotesformat, $textoptions);
                $studentnotes2 = html_writer::div($studentnotes2, 'appointmentnotes');
            }
            $studentnotes = $studentnotes1.$studentnotes2;

            $rowdata[] = $studentnotes;

            if ($slottable->showgrades) {
                if ($slot->otherstudents) {
                    $gradedata = $this->render($slot->otherstudents);
                } else {
                    $gradedata = $this->format_grade($slottable->scheduler, $slot->grade);
                }
                $rowdata[] = $gradedata;
            }

            $table->data[] = $rowdata;

            $previoustime = $starttime;
            $previousendtime = $endtime;
            $previousdate = $startdate;
        }

        return html_writer::table($table);
    }

    /**
     * Rendering a list of student, to be displayed within a larger table
     *
     * @param scheduler_slot_table $slottable the table to rended
     * @return string the HTML output
     */
    public function render_scheduler_student_list(scheduler_student_list $studentlist) {

        $o = '';

        $toggleid = html_writer::random_id('toggle');

        if ($studentlist->expandable && count($studentlist->students) > 0) {
            $this->page->requires->yui_module('moodle-mod_scheduler-studentlist',
                'M.mod_scheduler.studentlist.init',
                array($toggleid, (boolean) $studentlist->expanded) );
            $imgclass = 'studentlist-togglebutton';
            $alttext = get_string('showparticipants', 'scheduler');
            $o .= $this->output->pix_icon('t/switch', $alttext, 'moodle',
                              array('id' => $toggleid, 'class' => $imgclass));
        }

        $divprops = array('id' => 'list'.$toggleid);
        $o .= html_writer::start_div('studentlist', $divprops);

        foreach ($studentlist->students as $student) {
            $class = 'otherstudent';
            $name = fullname($student->user);
            if ($student->highlight) {
                $class .= ' highlight';
            }
            $picture = $this->user_picture($student->user, array('courseid' => $studentlist->scheduler->courseid));
            $grade = '';
            if ($studentlist->showgrades && $student->grade) {
                $grade = $this->format_grade($studentlist->scheduler, $student->grade, true);
            }
            $o .= html_writer::div("$picture $name $grade", $class);
        }

        $o .= html_writer::end_div();

        return $o;
    }

    public function render_scheduler_slot_booker(scheduler_slot_booker $booker) {

        $table = new html_table();
        $table->head  = array( get_string('date', 'scheduler'), get_string('start', 'scheduler'),
            get_string('end', 'scheduler'), get_string('location', 'scheduler'),
            get_string('choice', 'scheduler'),
            s($booker->scheduler->get_teacher_name()),
            get_string('groupsession', 'scheduler'));
        $table->align = array ('left', 'left', 'left', 'left', 'center', 'left', 'left');
        $table->id = 'slotbookertable';
        $table->data = array();

        $previousdate = '';
        $previoustime = '';
        $previousendtime = '';
        $canappoint = false;

        foreach ($booker->slots as $slot) {

            $rowdata = array();

            $startdate = scheduler_userdate($slot->starttime, 1);
            $starttime = scheduler_usertime($slot->starttime, 1);
            $endtime = scheduler_usertime($slot->endtime, 1);
            // Simplify display of dates, start and end times.
            if ($startdate == $previousdate && $starttime == $previoustime && $endtime == $previousendtime) {
                // If this row exactly matches previous, there's nothing to display.
                $startdatestr = '';
                $starttimestr = '';
                $endtimestr = '';
            } else if ($startdate == $previousdate) {
                // If this date matches previous date, just display times.
                $startdatestr = '';
                $starttimestr = $starttime;
                $endtimestr = $endtime;
            } else {
                // Otherwise, display all elements.
                $startdatestr = $startdate;
                $starttimestr = $starttime;
                $endtimestr = $endtime;
            }

            $rowdata[] = $startdatestr;
            $rowdata[] = $starttimestr;
            $rowdata[] = $endtimestr;

            $rowdata[] = s($slot->location);

            if ($booker->style == 'multi') {
                $inputname = "slotcheck[{$slot->slotid}]";
                $inputelm = html_writer::checkbox($inputname, $slot->slotid, $slot->bookedbyme, '', array('class' => 'slotbox'));
            } else {
                $inputparms = array('type' => 'radio', 'name' => 'slotid', 'value' => $slot->slotid);
                if ($slot->bookedbyme) {
                    $inputparms['checked'] = 1;
                }
                $inputelm = html_writer::empty_tag('input', $inputparms);
            }

            $groupinfo = $slot->bookedbyme ? get_string('complete', 'scheduler') : $slot->groupinfo;
            if ($slot->otherstudents) {
                $groupinfo .= $this->render($slot->otherstudents);
            }

            $rowdata[] = $inputelm;

            $rowdata[] = $this->user_profile_link($booker->scheduler, $slot->teacher);
            $rowdata[] = $groupinfo;

            $rowclass = ($slot->bookedbyme) ? 'booked' : 'bookable';

            $table->data[] = $rowdata;
            $table->rowclasses[] = $rowclass;

            $previoustime = $starttime;
            $previousendtime = $endtime;
            $previousdate = $startdate;
        }

        if ($booker->style == 'multi' && $booker->maxselect > 0) {
            $this->page->requires->yui_module('moodle-mod_scheduler-limitchoices',
                        'M.mod_scheduler.limitchoices.init', array($booker->maxselect) );
        }

        $controls = '';
        if (count($booker->groupchoice) > 0) {
            $controls .= get_string('appointfor', 'scheduler');
            $choices = $booker->groupchoice;
            $choices[0] = get_string('appointsolo', 'scheduler');
            ksort($choices);
            $controls .= html_writer::select($choices, 'appointgroup', '', '');
            $controls .= $this->help_icon('appointagroup', 'scheduler');
            $controls .= ' ';
        }
        $controls .= html_writer::empty_tag('input', array('type' => 'submit',
            'class' => 'bookerbutton', 'name' => 'savechoice',
            'value' => get_string('savechoice', 'scheduler')));
        $controls .= ' ';
        if ($booker->candisengage) {
            $disengagelink =new moodle_url('/mod/scheduler/view.php',
                array('what' => 'disengage',
                    'id' => $booker->scheduler->cmid,
                    'sesskey' => sesskey() ));
            $controls .= $this->action_link($disengagelink, get_string('disengage', 'scheduler'));
        }

        $o = '';
        $o .= html_writer::start_tag('form', array('action' => $booker->actionurl,
            'method' => 'post', 'class' => 'bookerform'));

        $o .= html_writer::input_hidden_params($booker->actionurl);

        $o .= html_writer::table($table);

        $o .= html_writer::div($controls, 'bookercontrols');

        $o .= html_writer::end_tag('form');

        return $o;
    }

}