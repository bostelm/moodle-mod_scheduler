<?PHP
/**
 * Slot-related forms of the scheduler module
 * (using Moodle formslib)
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2013 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Base class for slot-related forms
 */
abstract class scheduler_slotform_base extends moodleform {

    protected $scheduler;
    protected $cm;
    protected $context;
    protected $usergroups;
    protected $has_duration = false;

    public function __construct($action, scheduler_instance $scheduler, $cm, $usergroups, $customdata=null) {
        $this->scheduler = $scheduler;
        $this->cm = $cm;
        $this->context = context_module::instance($cm->id);
        $this->usergroups = $usergroups;
        parent::__construct($action, $customdata);
    }

    protected function add_base_fields() {

        global $CFG, $USER;

        $mform = $this->_form;

        // exclusivity
        $maxexclusive = $CFG->scheduler_maxstudentsperslot;
        $exclusivemenu['0'] = get_string('unlimited', 'scheduler');
        for ($i = 1; $i <= $maxexclusive; $i++) {
            $exclusivemenu[(string)$i] = $i;
        }
        $mform->addElement('select', 'exclusivity', get_string('multiplestudents', 'scheduler'), $exclusivemenu);
        $mform->setDefault('exclusivity', 1);
        $mform->addHelpButton('exclusivity', 'exclusivity', 'scheduler');

        // location of the appointment
        $mform->addElement('text', 'appointmentlocation', get_string('location', 'scheduler'), array('size'=>'30'));
        $mform->setType('appointmentlocation', PARAM_TEXT);
        $mform->addRule('appointmentlocation', get_string('error'), 'maxlength', 255);
        $mform->setDefault('appointmentlocation', $this->scheduler->get_last_location($USER));
        $mform->addHelpButton('appointmentlocation', 'location', 'scheduler');

        // Choose the teacher (if allowed)
        if (has_capability('mod/scheduler:canscheduletootherteachers', $this->context)) {
            $teachername = s($this->scheduler->get_teacher_name());
            $teachers = scheduler_get_attendants($this->cm->id);
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

    protected function add_minutes_field($name, $label, $defaultval, $minuteslabel = 'minutes') {
        $mform = $this->_form;
        $group = array();
        $group[] =& $mform->createElement('text', $name, '', array('size' => 5));
        $group[] =& $mform->createElement('static', $name.'mintext', '', get_string($minuteslabel, 'scheduler'));
        $mform->addGroup($group, $name.'group', get_string($label, 'scheduler'), array(' '), false);
        $mform->setType($name, PARAM_INT);
        $mform->setDefault($name, $defaultval);
    }

    protected function add_duration_field($minuteslabel = 'minutes') {
        $this->add_minutes_field('duration', 'duration', $this->scheduler->defaultslotduration, $minuteslabel);
        $this->has_duration = true;
    }


    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check duration for valid range
        if ($this->has_duration) {
            $limits = array('min' => 1, 'max' => 24*60);
            if ($data['duration'] < $limits['min'] || $data['duration'] > $limits['max']) {
                $errors['durationgroup'] = get_string('durationrange', 'scheduler', $limits);
            }
        }

        return $errors;
    }
}

class scheduler_editslot_form extends scheduler_slotform_base {

    protected $slotid;

    protected function definition() {

        global $DB, $output;

        $mform = $this->_form;
        $this->slotid = 0;
        if (isset($this->_customdata['slotid'])) {
            $this->slotid = $this->_customdata['slotid'];
        }

        // Start date/time of the slot
        $mform->addElement('date_time_selector', 'starttime', get_string('date', 'scheduler'));
        $mform->setDefault('starttime', time());
        $mform->addHelpButton('starttime', 'choosingslotstart', 'scheduler');

        // Duration of the slot
        $this->add_duration_field();

        // Ignore conflict checkbox
        $mform->addElement('checkbox', 'ignoreconflicts', get_string('ignoreconflicts', 'scheduler'));
        $mform->setDefault('ignoreconflicts', false);
        $mform->addHelpButton('ignoreconflicts', 'ignoreconflicts', 'scheduler');

        // Common fields
        $this->add_base_fields();

        // Display slot from date
        $mform->addElement('date_selector', 'hideuntil', get_string('displayfrom', 'scheduler'));
        $mform->setDefault('hideuntil', time());

        // Send e-mail reminder
        $mform->addElement('date_selector', 'emaildate', get_string('emailreminderondate', 'scheduler'), array('optional'  => true));
        $mform->setDefault('remindersel', -1);

        // Slot comments
        $mform->addElement('editor', 'notes', get_string('comments', 'scheduler'), array('rows' => 3, 'columns' => 60), array('collapsed' => true));
        $mform->setType('notes', PARAM_RAW); // must be PARAM_RAW for rich text editor content

        // Appointments

        $repeatarray = array();
        $grouparray = array();
        $repeatarray[] = $mform->createElement('header', 'appointhead', get_string('appointmentno', 'scheduler', '{no}'));

        // Choose student
        $students = $this->scheduler->get_possible_attendees($this->usergroups);
        $studentsmenu = array('0' => get_string('choosedots'));
        if ($students) {
            foreach ($students as $astudent) {
/*                if ($this->scheduler->schedulermode == 'oneonly' && scheduler_has_slot($astudent->id, $this->scheduler, true, false, $this->slotid)) {
                    continue;
                }
                if ($this->scheduler->schedulermode == 'onetime' && scheduler_has_slot($astudent->id, $this->scheduler, true, true, $this->slotid)) {
                    continue;
                }*/
                $studentsmenu[$astudent->id] = fullname($astudent);
            }
        }
        $grouparray[] = $mform->createElement('select', 'studentid', '', $studentsmenu);

        // Seen tickbox
        $grouparray[] = $mform->createElement('static', 'attendedlabel', '', get_string('seen', 'scheduler'));
        $grouparray[] = $mform->createElement('checkbox', 'attended');

        // Grade
        if ($this->scheduler->scale != 0) {
            $gradechoices = $output->grading_choices($this->scheduler);
            $grouparray[] = $mform->createElement('static', 'attendedlabel', '', get_string('grade', 'scheduler'));
            $grouparray[] = $mform->createElement('select', 'grade', '', $gradechoices);
        }

        $repeatarray[] = $mform->createElement('group', 'studgroup', get_string('student', 'scheduler'), $grouparray, null, false);

        // Appointment notes
        $repeatarray[] = $mform->createElement('editor', 'appointmentnote', get_string('appointmentnotes', 'scheduler'),
                          array('rows' => 3, 'columns' => 60), array('collapsed' => true));

        if (isset($this->_customdata['repeats'])) {
            $repeatno = $this->_customdata['repeats'];
        } else if ($this->slotid) {
            $repeatno = $DB->count_records('scheduler_appointment', array('slotid' => $this->slotid));
            $repeatno += 1;
        } else {
            $repeatno = 1;
        }

        $repeateloptions = array();
        $nostudcheck = array('studentid', 'eq', 0);
        $repeateloptions['attended']['disabledif'] = $nostudcheck;
        $repeateloptions['appointmentnote']['disabledif'] = $nostudcheck;
        $repeateloptions['grade']['disabledif'] = $nostudcheck;
        $repeateloptions['appointhead']['expanded'] = true;

        $this->repeat_elements($repeatarray, $repeatno, $repeateloptions,
                        'appointment_repeats', 'appointment_add', 1, get_string('addappointment', 'scheduler'));

        $this->add_action_buttons();

    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Check number of appointments vs exclusivity
        $numappointments = 0;
        for ($i = 0; $i < $data['appointment_repeats']; $i++) {
            if ($data['studentid'][$i] > 0) {
                $numappointments++;
            }
        }
        if ($data['exclusivity'] > 0 && $numappointments > $data['exclusivity']) {
            $errors['exclusivity'] = get_string('exclusivityoverload', 'scheduler', $numappointments);
        }

        // Avoid empty slots starting in the past
        if ($numappointments == 0 && $data['starttime'] < time()) {
            $errors['starttime'] = get_string('startpast', 'scheduler');
        }

        // Check whether students have been selected several times
        for ($i = 0; $i < $data['appointment_repeats']; $i++) {
            for ($j = 0; $j < $i; $j++) {
                if ($data['studentid'][$i] > 0 && $data['studentid'][$i] == $data['studentid'][$j]) {
                    $errors['studgroup['.$i.']'] = get_string('studentmultiselect', 'scheduler');
                    $errors['studgroup['.$j.']'] = get_string('studentmultiselect', 'scheduler');
                }
            }
        }

        if (!isset($data['ignoreconflicts'])) {
            // Avoid overlapping slots, by asking the user if they'd like to overwrite the existing ones...
            // for other scheduler, we check independently of exclusivity. Any slot here conflicts
            // for this scheduler, we check against exclusivity. Any complete slot here conflicts
            $conflicts_remote = scheduler_get_conflicts($this->scheduler->id,
                            $data['starttime'], $data['starttime'] + $data['duration'] * 60, $data['teacherid'], 0, SCHEDULER_OTHERS, false);
            $conflicts_local = scheduler_get_conflicts($this->scheduler->id,
                            $data['starttime'], $data['starttime'] + $data['duration'] * 60, $data['teacherid'], 0, SCHEDULER_SELF, true);
            if (!$conflicts_remote) {
                $conflicts_remote = array();
            }
            if (!$conflicts_local) {
                $conflicts_local = array();
            }
            $conflicts = $conflicts_remote + $conflicts_local;

            // remove itself from conflicts when updating
            if (array_key_exists($this->slotid, $conflicts)) {
                unset($conflicts[$this->slotid]);
            }

            if (count($conflicts)) {
                $msg = get_string('slotwarning', 'scheduler');

                foreach ($conflicts as $conflict) {
                    $students = scheduler_get_appointed($conflict->id);

                    $slotmsg = userdate($conflict->starttime);
                    $slotmsg .= ' [';
                    $slotmsg .= $conflict->duration.' '.get_string('minutes');
                    $slotmsg .= ']';

                    if ($students) {
                        $slotmsg .= ' (';
                        $appointed = array();
                        foreach ($students as $astudent) {
                            $appointed[] = fullname($astudent);
                        }
                        if (count ($appointed)) {
                            $slotmsg .= implode(', ', $appointed);
                        }
                        unset ($appointed);
                        $slotmsg .= ')';
                        $slotmsg = html_writer::tag('b', $slotmsg);
                    }
                    $msg .= html_writer::div($slotmsg);
                }

                $errors['starttime'] = $msg;
            }
        }
        return $errors;
    }
}


class scheduler_addsession_form extends scheduler_slotform_base {

    protected function definition() {

        global $DB;

        $mform = $this->_form;

        // Start and end of range
        $mform->addElement('date_selector', 'rangestart', get_string('date', 'scheduler'));
        $mform->setDefault('rangestart', time());

        $mform->addElement('date_selector', 'rangeend', get_string('enddate', 'scheduler'),
                            array('optional'  => true) );

        // Weekdays selection
        $weekdays = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday');
        foreach ($weekdays as $day) {
            $label = ($day == 'monday') ? get_string('addondays', 'scheduler') : '';
            $mform->addElement('advcheckbox', $day, $label, get_string($day, 'scheduler'));
            $mform->setDefault($day, true);
        }
        $mform->addElement('advcheckbox', 'saturday', '', get_string('saturday', 'scheduler'));
        $mform->addElement('advcheckbox', 'sunday', '', get_string('sunday', 'scheduler'));

        // Start and end time
        $hours = array();
        $minutes = array();
        for ($i=0; $i<=23; $i++) {
            $hours[$i] = sprintf("%02d", $i);
        }
        for ($i=0; $i<60; $i+=5) {
            $minutes[$i] = sprintf("%02d", $i);
        }
        $starttimegroup = array();
        $starttimegroup[] = $mform->createElement('select', 'starthour', get_string('hour', 'form'), $hours);
        $starttimegroup[] = $mform->createElement('select', 'startminute', get_string('minute', 'form'), $minutes);
        $mform->addGroup ($starttimegroup, 'starttime', get_string('starttime', 'scheduler'), null, false);
        $endtimegroup = array();
        $endtimegroup[] = $mform->createElement('select', 'endhour', get_string('hour', 'form'), $hours);
        $endtimegroup[] = $mform->createElement('select', 'endminute', get_string('minute', 'form'), $minutes);
        $mform->addGroup ($endtimegroup, 'endtime', get_string('endtime', 'scheduler'), null, false);

        // Divide into slots?
        $mform->addElement('selectyesno', 'divide', get_string('divide', 'scheduler'));
        $mform->setDefault('divide', 1);

        // Duration of the slot
        $this->add_duration_field('minutesperslot');

        // Break between slots
        $this->add_minutes_field('break', 'break', 0, 'minutes');

        // Force when overlap?
        $mform->addElement('selectyesno', 'forcewhenoverlap', get_string('forcewhenoverlap', 'scheduler'));
        $mform->addHelpButton('forcewhenoverlap', 'forcewhenoverlap', 'scheduler');

        // Common fields
        $this->add_base_fields();

        // Display slot from date - relative
        $hideuntilsel = array();
        $hideuntilsel[0] =  get_string('now', 'scheduler');
        $hideuntilsel[DAYSECS] = get_string('onedaybefore', 'scheduler');
        for ($i = 2; $i < 7; $i++) {
            $hideuntilsel[DAYSECS*$i] = get_string('xdaysbefore', 'scheduler', $i);
        }
        $hideuntilsel[WEEKSECS] = get_string('oneweekbefore', 'scheduler');
        for ($i = 2; $i < 7; $i++) {
            $hideuntilsel[WEEKSECS*$i] = get_string('xweeksbefore', 'scheduler', $i);
        }
        $mform->addElement('select', 'hideuntilrel', get_string('displayfrom', 'scheduler'), $hideuntilsel);
        $mform->setDefault('hideuntilsel', 0);

        // E-mail reminder from
        $remindersel = array();
        $remindersel[-1] = get_string('never', 'scheduler');
        $remindersel[0] = get_string('onthemorningofappointment', 'scheduler');
        $remindersel[DAYSECS] = get_string('onedaybefore', 'scheduler');
        for ($i = 2; $i < 7; $i++) {
            $remindersel[DAYSECS * $i] = get_string('xdaysbefore', 'scheduler', $i);
        }
        $remindersel[WEEKSECS] = get_string('oneweekbefore', 'scheduler');
        for ($i = 2; $i < 7; $i++) {
            $remindersel[WEEKSECS*$i] = get_string('xweeksbefore', 'scheduler', $i);
        }

        $mform->addElement('select', 'emaildaterel', get_string('emailreminder', 'scheduler'), $remindersel);
        $mform->setDefault('remindersel', -1);

        $this->add_action_buttons();

    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Range is negative
        $fordays = 0;
        if ($data['rangeend'] > 0) {
            $fordays = ($data['rangeend'] - $data['rangestart']) / DAYSECS;
            if ($fordays < 0) {
                $errors['rangeend'] = get_string('negativerange', 'scheduler');
            }
        }

        // Time range is negative
        $starttime = $data['starthour']*60+$data['startminute'];
        $endtime = $data['endhour']*60+$data['endminute'];
        if ($starttime > $endtime)  {
            $errors['endtime'] = get_string('negativerange', 'scheduler');
        }

        // First slot is in the past
        if ($data['rangestart'] < time() - DAYSECS) {
            $errors['rangestart'] = get_string('startpast', 'scheduler');
        }

        // Break must be nonnegative
        if ($data['break'] < 0) {
            $errors['breakgroup'] = get_string('breaknotnegative', 'scheduler');
        }

        // TODO conflict checks

        /*

        /// make a base slot for generating
        $slot = new stdClass();
        $slot->appointmentlocation = $data->appointmentlocation;
        $slot->exclusivity = $data->exclusivity;
        $slot->duration = $data->duration;
        $slot->schedulerid = $scheduler->id;
        $slot->timemodified = time();
        $slot->teacherid = $data->teacherid;

        /// check if overlaps. Check also if some slots are in allowed day range
        $startfrom = $data->rangestart;
        $noslotsallowed = true;
        for ($d = 0; $d <= $fordays; $d ++){
        $starttime = $startfrom + ($d * DAYSECS);
        $eventdate = usergetdate($starttime);
        $dayofweek = $eventdate['wday'];
        if ((($dayofweek == 1) && ($data->monday == 1)) ||
                        (($dayofweek == 2) && ($data->tuesday == 1)) ||
                        (($dayofweek == 3) && ($data->wednesday == 1)) ||
                        (($dayofweek == 4) && ($data->thursday == 1)) ||
                        (($dayofweek == 5) && ($data->friday == 1)) ||
                        (($dayofweek == 6) && ($data->saturday == 1)) ||
                        (($dayofweek == 0) && ($data->sunday == 1))){
                        $noslotsallowed = false;
                        $data->starttime = make_timestamp($eventdate['year'], $eventdate['mon'], $eventdate['mday'], $data->starthour, $data->startminute);
                        $conflicts = scheduler_get_conflicts($scheduler->id, $data->starttime, $data->starttime + $data->duration * 60, $data->teacherid, 0, SCHEDULER_ALL, false);
                        if (!$data->forcewhenoverlap){
                        if ($conflicts){
                        unset($erroritem);
                        $erroritem->message = get_string('overlappings', 'scheduler');
                        $erroritem->on = 'range';
                        $errors[] = $erroritem;
                        }
                        }
                        }
                        }

                        /// Finally check if some slots are allowed (an error is thrown to ask care to this situation)
                        if ($noslotsallowed){
                        unset($erroritem);
                        $erroritem->message = get_string('allslotsincloseddays', 'scheduler');
                        $erroritem->on = 'days';
                        $errors[] = $erroritem;
                        }
         */

        return $errors;
    }
}
