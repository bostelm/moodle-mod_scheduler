<?php

/**
 * Appointment-related forms of the scheduler module
 * (using Moodle formslib)
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Form to edit one appointment
 */
class scheduler_editappointment_form extends moodleform {

    protected $appointment;
    protected $distribute;
    protected $editgrade;

    public $noteoptions;

    public function __construct(scheduler_appointment $appointment, $action, $editgrade, $distribute) {
        $this->appointment = $appointment;
        $this->distribute = $distribute;
        $this->editgrade = $editgrade;
        $this->noteoptions = array('trusttext' => true, 'maxfiles' => -1, 'maxbytes' => 0,
                                   'context' => $appointment->get_scheduler()->get_context(),
                                   'subdirs' => false, 'collapsed' => true);
        parent::__construct($action, null);
    }

    protected function definition() {

        global $output;

        $mform = $this->_form;
        $scheduler = $this->appointment->get_scheduler();

        // Seen tickbox.
        $mform->addElement('checkbox', 'attended', get_string('attended', 'scheduler'));

        // Grade.
        if ($scheduler->scale != 0) {
            if ($this->editgrade) {
                $gradechoices = $output->grading_choices($scheduler);
                $mform->addElement('select', 'grade', get_string('grade', 'scheduler'), $gradechoices);
            } else {
                $gradetext = $output->format_grade($scheduler, $this->appointment->grade);
                $mform->addElement('static', 'gradedisplay', get_string('grade', 'scheduler'), $gradetext);
            }
        }
        // Appointment notes (visible to teacher and/or student).
        if ($scheduler->uses_appointmentnotes()) {
            $mform->addElement('editor', 'appointmentnote_editor', get_string('appointmentnote', 'scheduler'),
                               array('rows' => 3, 'columns' => 60), $this->noteoptions);
            $mform->setType('appointmentnote', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
        }
        if ($scheduler->uses_teachernotes()) {
            $mform->addElement('editor', 'teachernote_editor', get_string('teachernote', 'scheduler'),
                               array('rows' => 3, 'columns' => 60), $this->noteoptions);
            $mform->setType('teachernote', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
        }
        if ($this->distribute && ($scheduler->uses_appointmentnotes() || $scheduler->uses_teachernotes() || $this->editgrade) ) {
            $mform->addElement('checkbox', 'distribute', get_string('distributetoslot', 'scheduler'));
            $mform->setDefault('distribute', false);
        }

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

    public function prepare_appointment_data(scheduler_appointment $appointment) {
        $newdata = clone($appointment->get_data());
        $context = $this->appointment->get_scheduler()->get_context();

        $newdata = file_prepare_standard_editor($newdata, 'appointmentnote', $this->noteoptions, $context,
                                                'mod_scheduler', 'appointmentnote', $this->appointment->id);

        $newdata = file_prepare_standard_editor($newdata, 'teachernote', $this->noteoptions, $context,
                                                'mod_scheduler', 'teachernote', $this->appointment->id);
        return $newdata;
    }

    public function save_appointment_data(stdClass $formdata, scheduler_appointment $appointment) {
        $scheduler = $appointment->get_scheduler();
        $cid = $scheduler->context->id;
        $appointment->set_data($formdata);
        $appointment->attended = isset($formdata->attended);
        if ($scheduler->uses_appointmentnotes() && isset($formdata->appointmentnote_editor)) {
            $editor = $formdata->appointmentnote_editor;
            $appointment->appointmentnote = file_save_draft_area_files($editor['itemid'], $cid,
                    'mod_scheduler', 'appointmentnote', $appointment->id,
                    $this->noteoptions, $editor['text']);
            $appointment->appointmentnoteformat = $editor['format'];
        }
        if ($scheduler->uses_teachernotes() && isset($formdata->teachernote_editor)) {
            $editor = $formdata->teachernote_editor;
            $appointment->teachernote = file_save_draft_area_files($editor['itemid'], $cid,
                    'mod_scheduler', 'teachernote', $appointment->id,
                    $this->noteoptions, $editor['text']);
            $appointment->teachernoteformat = $editor['format'];
        }
        $appointment->save();
        if (isset($formdata->distribute)) {
            $slot = $appointment->get_slot();
            $slot->distribute_appointment_data($appointment);
        }
    }
}

