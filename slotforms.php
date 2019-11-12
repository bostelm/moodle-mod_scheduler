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
 * Slot-related forms of the scheduler module (using Moodle formslib)
 *
 * @package    mod_scheduler
 * @copyright  2013 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_scheduler\model\scheduler;
use \mod_scheduler\model\slot;

require_once($CFG->libdir.'/formslib.php');

/**
 * Base class for slot-related forms
 *
 * @package    mod_scheduler
 * @copyright  2013 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class scheduler_slotform_base extends moodleform {

    /**
     * @var scheduler the scheduler that this form refers to
     */
    protected $scheduler;

    /**
     * @var array user groups to filter for
     */
    protected $usergroups;

    /**
     * @var bool does this form have a duration field?
     */
    protected $hasduration = false;

    /**
     * @var array options for note fields
     */
    protected $noteoptions;

    /**
     * Create a new form
     *
     * @param mixed $action the action attribute for the form
     * @param scheduler $scheduler
     * @param object $cm unused
     * @param array $usergroups groups to filter for
     * @param array $customdata
     */
    public function __construct($action, scheduler $scheduler, $cm, $usergroups, $customdata=null) {
        $this->scheduler = $scheduler;
        $this->usergroups = $usergroups;
        $this->noteoptions = array('trusttext' => true, 'maxfiles' => -1, 'maxbytes' => 0,
                                   'context' => $scheduler->get_context(), 'subdirs' => false);

        parent::__construct($action, $customdata);
    }

    /**
     * Add basic fields to this form. To be used in definition() methods of subclasses.
     */
    protected function add_base_fields() {

        global $CFG, $USER;

        $mform = $this->_form;

        // Exclusivity.
        $exclgroup = array();

        $exclgroup[] = $mform->createElement('text', 'exclusivity', '', array('size' => '10'));
        $mform->setType('exclusivity', PARAM_INTEGER);
        $mform->setDefault('exclusivity', 1);

        $exclgroup[] = $mform->createElement('advcheckbox', 'exclusivityenable', '', get_string('enable'));
        $mform->setDefault('exclusivityenable', 1);
        $mform->disabledIf('exclusivity', 'exclusivityenable', 'eq', 0);

        $mform->addGroup($exclgroup, 'exclusivitygroup', get_string('maxstudentsperslot', 'scheduler'), ' ', false);
        $mform->addHelpButton('exclusivitygroup', 'exclusivity', 'scheduler');

        // Location of the appointment.
        $mform->addElement('text', 'appointmentlocation', get_string('location', 'scheduler'), array('size' => '30'));
        $mform->setType('appointmentlocation', PARAM_TEXT);
        $mform->addRule('appointmentlocation', get_string('error'), 'maxlength', 255);
        $mform->setDefault('appointmentlocation', $this->scheduler->get_last_location($USER));
        $mform->addHelpButton('appointmentlocation', 'location', 'scheduler');

        // Choose the teacher (if allowed).
        if (has_capability('mod/scheduler:canscheduletootherteachers', $this->scheduler->get_context())) {
            $teachername = s($this->scheduler->get_teacher_name());
            $teachers = $this->scheduler->get_available_teachers();
            $teachersmenu = array();
            if ($teachers) {
                foreach ($teachers as $teacher) {
                    $teachersmenu[$teacher->id] = fullname($teacher);
                }
                $mform->addElement('select', 'teacherid', $teachername, $teachersmenu);
                $mform->addRule('teacherid', get_string('noteacherforslot', 'scheduler'), 'required');
                $mform->setDefault('teacherid', $USER->id);
            } else {
                $mform->addElement('static', 'teacherid', $teachername, get_string('noteachershere', 'scheduler', $teachername));
            }
            $mform->addHelpButton('teacherid', 'bookwithteacher', 'scheduler');
        } else {
            $mform->addElement('hidden', 'teacherid');
            $mform->setDefault('teacherid', $USER->id);
            $mform->setType('teacherid', PARAM_INT);
        }

    }

    /**
     * Add an input field for a number of minutes
     *
     * @param string $name field name
     * @param string $label language key for field label
     * @param int $defaultval default value
     * @param string $minuteslabel language key for suffix "minutes"
     */
    protected function add_minutes_field($name, $label, $defaultval, $minuteslabel = 'minutes') {
        $mform = $this->_form;
        $group = array();
        $group[] =& $mform->createElement('text', $name, '', array('size' => 5));
        $group[] =& $mform->createElement('static', $name.'mintext', '', get_string($minuteslabel, 'scheduler'));
        $mform->addGroup($group, $name.'group', get_string($label, 'scheduler'), array(' '), false);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $defaultval);
    }

    /**
     * Add theduration field to the form.
     * @param string $minuteslabel language key for the "minutes" label
     */
    protected function add_duration_field($minuteslabel = 'minutes') {
        $this->add_minutes_field('duration', 'duration', $this->scheduler->defaultslotduration, $minuteslabel);
        $this->hasduration = true;
    }

    /**
     * Form validation
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check duration for valid range.
        if ($this->hasduration) {
            $limits = array('min' => 1, 'max' => 24 * 60);
            if ($data['duration'] < $limits['min'] || $data['duration'] > $limits['max']) {
                $errors['durationgroup'] = get_string('durationrange', 'scheduler', $limits);
            }
        }

        return $errors;
    }

}

/**
 * Slot edit form
 *
 * @package    mod_scheduler
 * @copyright  2013 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_editslot_form extends scheduler_slotform_base {

    /**
     * @var int id of the slot being edited
     */
    protected $slotid;

    /**
     * Form definition
     */
    protected function definition() {

        global $DB, $output;

        $mform = $this->_form;
        $this->slotid = 0;
        if (isset($this->_customdata['slotid'])) {
            $this->slotid = $this->_customdata['slotid'];
        }
        $timeoptions = null;
        if (isset($this->_customdata['timeoptions'])) {
            $timeoptions = $this->_customdata['timeoptions'];
        }

        // Start date/time of the slot.
        $mform->addElement('date_time_selector', 'starttime', get_string('date', 'scheduler'), $timeoptions);
        $mform->setDefault('starttime', time());
        $mform->addHelpButton('starttime', 'choosingslotstart', 'scheduler');

        // Duration of the slot.
        $this->add_duration_field();

        // Ignore conflict checkbox.
        $mform->addElement('checkbox', 'ignoreconflicts', get_string('ignoreconflicts', 'scheduler'));
        $mform->setDefault('ignoreconflicts', false);
        $mform->addHelpButton('ignoreconflicts', 'ignoreconflicts', 'scheduler');

        // Common fields.
        $this->add_base_fields();

        // Display slot from this date.
        $mform->addElement('date_selector', 'hideuntil', get_string('displayfrom', 'scheduler'));
        $mform->setDefault('hideuntil', time());

        // Send e-mail reminder?
        $mform->addElement('date_selector', 'emaildate', get_string('emailreminderondate', 'scheduler'),
                            array('optional'  => true));
        $mform->setDefault('remindersel', -1);

        // Slot comments.
        $mform->addElement('editor', 'notes_editor', get_string('comments', 'scheduler'),
                           array('rows' => 3, 'columns' => 60), $this->noteoptions);
        $mform->setType('notes', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.

        // Appointments.

        $repeatarray = array();
        $grouparray = array();
        $repeatarray[] = $mform->createElement('header', 'appointhead', get_string('appointmentno', 'scheduler', '{no}'));

        // Choose student.
        $students = $this->scheduler->get_available_students($this->usergroups);
        $studentchoices = array();
        if ($students) {
            foreach ($students as $astudent) {
                $studentchoices[$astudent->id] = fullname($astudent);
            }
        }
        $grouparray[] = $mform->createElement('searchableselector', 'studentid', '', $studentchoices);
        $grouparray[] = $mform->createElement('hidden', 'appointid', 0);

        // Seen tickbox.
        $grouparray[] = $mform->createElement('static', 'attendedlabel', '', get_string('seen', 'scheduler'));
        $grouparray[] = $mform->createElement('checkbox', 'attended');

        // Grade.
        if ($this->scheduler->scale != 0) {
            $gradechoices = $output->grading_choices($this->scheduler);
            $grouparray[] = $mform->createElement('static', 'attendedlabel', '', get_string('grade', 'scheduler'));
            $grouparray[] = $mform->createElement('select', 'grade', '', $gradechoices);
        }

        $repeatarray[] = $mform->createElement('group', 'studgroup', get_string('student', 'scheduler'), $grouparray, null, false);

        // Appointment notes, visible to teacher and/or student.

        if ($this->scheduler->uses_appointmentnotes()) {
            $repeatarray[] = $mform->createElement('editor', 'appointmentnote_editor', get_string('appointmentnote', 'scheduler'),
                                                   array('rows' => 3, 'columns' => 60), $this->noteoptions);
        }
        if ($this->scheduler->uses_teachernotes()) {
            $repeatarray[] = $mform->createElement('editor', 'teachernote_editor', get_string('teachernote', 'scheduler'),
                                                   array('rows' => 3, 'columns' => 60), $this->noteoptions);
        }

        // Tickbox to remove the student.
        $repeatarray[] = $mform->createElement('advcheckbox', 'deletestudent', '', get_string('deleteonsave', 'scheduler'));

        if (isset($this->_customdata['repeats'])) {
            $repeatno = $this->_customdata['repeats'];
        } else if ($this->slotid) {
            $repeatno = $DB->count_records('scheduler_appointment', array('slotid' => $this->slotid));
            $repeatno += 1;
        } else {
            $repeatno = 1;
        }

        $repeateloptions = array();
        $repeateloptions['appointid']['type'] = PARAM_INT;
        $repeateloptions['studentid']['disabledif'] = array('appointid', 'neq', 0);
        $nostudcheck = array('studentid', 'eq', 0);
        $repeateloptions['attended']['disabledif'] = $nostudcheck;
        $repeateloptions['appointmentnote_editor']['disabledif'] = $nostudcheck;
        $repeateloptions['teachernote_editor']['disabledif'] = $nostudcheck;
        $repeateloptions['grade']['disabledif'] = $nostudcheck;
        $repeateloptions['deletestudent']['disabledif'] = $nostudcheck;
        $repeateloptions['appointhead']['expanded'] = true;

        $this->repeat_elements($repeatarray, $repeatno, $repeateloptions,
                        'appointment_repeats', 'appointment_add', 1, get_string('addappointment', 'scheduler'));

        $this->add_action_buttons();

    }

    /**
     * Form validation
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        global $output;

        $errors = parent::validation($data, $files);

        // Check number of appointments vs exclusivity.
        $numappointments = 0;
        for ($i = 0; $i < $data['appointment_repeats']; $i++) {
            if ($data['studentid'][$i] > 0 && $data['deletestudent'][$i] == 0) {
                $numappointments++;
            }
        }
        if ($data['exclusivityenable'] && $data['exclusivity'] <= 0) {
            $errors['exclusivitygroup'] = get_string('exclusivitypositive', 'scheduler');
        } else if ($data['exclusivityenable'] && $numappointments > $data['exclusivity']) {
            $errors['exclusivitygroup'] = get_string('exclusivityoverload', 'scheduler', $numappointments);
        }

        // Avoid empty slots starting in the past.
        if ($numappointments == 0 && $data['starttime'] < time()) {
            $errors['starttime'] = get_string('startpast', 'scheduler');
        }

        // Check whether students have been selected several times.
        for ($i = 0; $i < $data['appointment_repeats']; $i++) {
            for ($j = 0; $j < $i; $j++) {
                if ($data['deletestudent'][$j] == 0 && $data['studentid'][$i] > 0
                        && $data['studentid'][$i] == $data['studentid'][$j]) {
                    $errors['studgroup['.$i.']'] = get_string('studentmultiselect', 'scheduler');
                    $errors['studgroup['.$j.']'] = get_string('studentmultiselect', 'scheduler');
                }
            }
        }

        if (!isset($data['ignoreconflicts'])) {
            /* Avoid overlapping slots by warning the user */
            $conflicts = $this->scheduler->get_conflicts(
                            $data['starttime'], $data['starttime'] + $data['duration'] * 60,
                            $data['teacherid'], 0, SCHEDULER_ALL, $this->slotid);

            if (count($conflicts) > 0) {

                $cl = new scheduler_conflict_list();
                $cl->add_conflicts($conflicts);

                $msg = get_string('slotwarning', 'scheduler');
                $msg .= $output->render($cl);
                $msg .= $output->doc_link('mod/scheduler/conflict', '', true);

                $errors['starttime'] = $msg;
            }
        }
        return $errors;
    }

    /**
     * Fill the form data from an existing slot
     *
     * @param slot $slot
     * @return stdClass form data
     */
    public function prepare_formdata(slot $slot) {

        $context = $slot->get_scheduler()->get_context();

        $data = $slot->get_data();
        $data->exclusivityenable = ($data->exclusivity > 0);

        $data = file_prepare_standard_editor($data, "notes", $this->noteoptions, $context,
                'mod_scheduler', 'slotnote', $slot->id);
        $data->notes = array();
        $data->notes['text'] = $slot->notes;
        $data->notes['format'] = $slot->notesformat;

        if ($slot->emaildate < 0) {
            $data->emaildate = 0;
        }

        $i = 0;
        foreach ($slot->get_appointments() as $appointment) {
            $data->appointid[$i] = $appointment->id;
            $data->studentid[$i] = $appointment->studentid;
            $data->attended[$i] = $appointment->attended;

            $draftid = file_get_submitted_draft_itemid('appointmentnote');
            $currenttext = file_prepare_draft_area($draftid, $context->id,
                    'mod_scheduler', 'appointmentnote', $appointment->id,
                    $this->noteoptions, $appointment->appointmentnote);
            $data->appointmentnote_editor[$i] = array('text' => $currenttext,
                    'format' => $appointment->appointmentnoteformat,
                    'itemid' => $draftid);

            $draftid = file_get_submitted_draft_itemid('teachernote');
            $currenttext = file_prepare_draft_area($draftid, $context->id,
                    'mod_scheduler', 'teachernote', $appointment->id,
                    $this->noteoptions, $appointment->teachernote);
            $data->teachernote_editor[$i] = array('text' => $currenttext,
                    'format' => $appointment->teachernoteformat,
                    'itemid' => $draftid);

            $data->grade[$i] = $appointment->grade;
            $i++;
        }

        return $data;
    }

    /**
     * Save a slot object, updating it with data from the form
     * @param int $slotid
     * @param mixed $data form data
     * @return slot the updated slot
     */
    public function save_slot($slotid, $data) {

        $context = $this->scheduler->get_context();

        if ($slotid) {
            $slot = slot::load_by_id($slotid, $this->scheduler);
        } else {
            $slot = new slot($this->scheduler);
        }

        // Set data fields from input form.
        $slot->starttime = $data->starttime;
        $slot->duration = $data->duration;
        $slot->exclusivity = $data->exclusivityenable ? $data->exclusivity : 0;
        $slot->teacherid = $data->teacherid;
        $slot->appointmentlocation = $data->appointmentlocation;
        $slot->hideuntil = $data->hideuntil;
        $slot->emaildate = $data->emaildate;
        $slot->timemodified = time();

        if (!$slotid) {
            $slot->save(); // Make sure that a new slot has a slot id before proceeding.
        }

        $editor = $data->notes_editor;
        $slot->notes = file_save_draft_area_files($editor['itemid'], $context->id, 'mod_scheduler', 'slotnote', $slotid,
                $this->noteoptions, $editor['text']);
        $slot->notesformat = $editor['format'];

        $currentapps = $slot->get_appointments();
        for ($i = 0; $i < $data->appointment_repeats; $i++) {
            if ($data->deletestudent[$i] != 0) {
                if ($data->appointid[$i]) {
                    $app = $slot->get_appointment($data->appointid[$i]);
                    $slot->remove_appointment($app);
                }
            } else if ($data->studentid[$i] > 0) {
                $app = null;
                if ($data->appointid[$i]) {
                    $app = $slot->get_appointment($data->appointid[$i]);
                } else {
                    $app = $slot->create_appointment();
                    $app->studentid = $data->studentid[$i];
                    $app->save();
                }
                $app->attended = isset($data->attended[$i]);

                if (isset($data->grade)) {
                    $selgrade = $data->grade[$i];
                    $app->grade = ($selgrade >= 0) ? $selgrade : null;
                }

                if ($this->scheduler->uses_appointmentnotes()) {
                    $editor = $data->appointmentnote_editor[$i];
                    $app->appointmentnote = file_save_draft_area_files($editor['itemid'], $context->id,
                            'mod_scheduler', 'appointmentnote', $app->id,
                            $this->noteoptions, $editor['text']);
                    $app->appointmentnoteformat = $editor['format'];
                }
                if ($this->scheduler->uses_teachernotes()) {
                    $editor = $data->teachernote_editor[$i];
                    $app->teachernote = file_save_draft_area_files($editor['itemid'], $context->id,
                            'mod_scheduler', 'teachernote', $app->id,
                            $this->noteoptions, $editor['text']);
                    $app->teachernoteformat = $editor['format'];
                }
            }
        }

        $slot->save();

        $slot = $this->scheduler->get_slot($slot->id);

        return $slot;
    }
}

/**
 * "Add session" form
 *
 * @package    mod_scheduler
 * @copyright  2013 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_addsession_form extends scheduler_slotform_base {

    /**
     * Form definition
     */
    protected function definition() {

        global $DB;

        $mform = $this->_form;

        // Start and end of range.
        $mform->addElement('date_selector', 'rangestart', get_string('date', 'scheduler'));
        $mform->setDefault('rangestart', time());

        $mform->addElement('date_selector', 'rangeend', get_string('enddate', 'scheduler'),
                            array('optional'  => true) );

        // Weekdays selection.
        $checkboxes = array();
        $weekdays = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');
        foreach ($weekdays as $day) {
            $checkboxes[] = $mform->createElement('advcheckbox', $day, '', get_string($day, 'scheduler'));
            $mform->setDefault($day, true);
        }
        $checkboxes[] = $mform->createElement('advcheckbox', 'saturday', '', get_string('saturday', 'scheduler'));
        $checkboxes[] = $mform->createElement('advcheckbox', 'sunday', '', get_string('sunday', 'scheduler'));
        $mform->addGroup($checkboxes, 'weekdays', get_string('addondays', 'scheduler'), null, false);

        // Start and end time.
        $hours = array();
        $minutes = array();
        for ($i = 0; $i <= 23; $i++) {
            $hours[$i] = sprintf("%02d", $i);
        }
        for ($i = 0; $i < 60; $i += 5) {
            $minutes[$i] = sprintf("%02d", $i);
        }
        $timegroup = array();
        $timegroup[] = $mform->createElement('static', 'timefrom', '', get_string('timefrom', 'scheduler'));
        $timegroup[] = $mform->createElement('select', 'starthour', get_string('hour', 'form'), $hours);
        $timegroup[] = $mform->createElement('select', 'startminute', get_string('minute', 'form'), $minutes);
        $timegroup[] = $mform->createElement('static', 'timeto', '', get_string('timeto', 'scheduler'));
        $timegroup[] = $mform->createElement('select', 'endhour', get_string('hour', 'form'), $hours);
        $timegroup[] = $mform->createElement('select', 'endminute', get_string('minute', 'form'), $minutes);
        $mform->addGroup($timegroup, 'timerange', get_string('timerange', 'scheduler'), null, false);

        // Divide into slots?
        $mform->addElement('selectyesno', 'divide', get_string('divide', 'scheduler'));
        $mform->setDefault('divide', 1);

        // Duration of the slot.
        $this->add_duration_field('minutesperslot');
        $mform->disabledIf('duration', 'divide', 'eq', '0');

        // Break between slots.
        $this->add_minutes_field('break', 'break', 0, 'minutes');
        $mform->disabledIf('break', 'divide', 'eq', '0');

        // Force when overlap?
        $mform->addElement('selectyesno', 'forcewhenoverlap', get_string('forcewhenoverlap', 'scheduler'));
        $mform->addHelpButton('forcewhenoverlap', 'forcewhenoverlap', 'scheduler');

        // Common fields.
        $this->add_base_fields();

        // Display slot from date - relative.
        $hideuntilsel = array();
        $hideuntilsel[0] = get_string('now', 'scheduler');
        $hideuntilsel[DAYSECS] = get_string('onedaybefore', 'scheduler');
        for ($i = 2; $i < 7; $i++) {
            $hideuntilsel[DAYSECS * $i] = get_string('xdaysbefore', 'scheduler', $i);
        }
        $hideuntilsel[WEEKSECS] = get_string('oneweekbefore', 'scheduler');
        for ($i = 2; $i < 7; $i++) {
            $hideuntilsel[WEEKSECS * $i] = get_string('xweeksbefore', 'scheduler', $i);
        }
        $mform->addElement('select', 'hideuntilrel', get_string('displayfrom', 'scheduler'), $hideuntilsel);
        $mform->setDefault('hideuntilsel', 0);

        // E-mail reminder from.
        $remindersel = array();
        $remindersel[-1] = get_string('never', 'scheduler');
        $remindersel[0] = get_string('onthemorningofappointment', 'scheduler');
        $remindersel[DAYSECS] = get_string('onedaybefore', 'scheduler');
        for ($i = 2; $i < 7; $i++) {
            $remindersel[DAYSECS * $i] = get_string('xdaysbefore', 'scheduler', $i);
        }
        $remindersel[WEEKSECS] = get_string('oneweekbefore', 'scheduler');
        for ($i = 2; $i < 7; $i++) {
            $remindersel[WEEKSECS * $i] = get_string('xweeksbefore', 'scheduler', $i);
        }

        $mform->addElement('select', 'emaildaterel', get_string('emailreminder', 'scheduler'), $remindersel);
        $mform->setDefault('remindersel', -1);

        $this->add_action_buttons();

    }

    /**
     * Form validation
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Range is negative.
        $fordays = 0;
        if ($data['rangeend'] > 0) {
            $fordays = ($data['rangeend'] - $data['rangestart']) / DAYSECS;
            if ($fordays < 0) {
                $errors['rangeend'] = get_string('negativerange', 'scheduler');
            }
        }

        // Time range is negative.
        $starttime = $data['starthour'] * 60 + $data['startminute'];
        $endtime = $data['endhour'] * 60 + $data['endminute'];
        if ($starttime > $endtime) {
            $errors['timerange'] = get_string('negativerange', 'scheduler');
        }

        // First slot is in the past.
        if ($data['rangestart'] < time() - DAYSECS) {
            $errors['rangestart'] = get_string('startpast', 'scheduler');
        }

        // Break must be nonnegative.
        if ($data['break'] < 0) {
            $errors['breakgroup'] = get_string('breaknotnegative', 'scheduler');
        }

        // Conflict checks are now being done after submitting the form.

        return $errors;
    }
}
