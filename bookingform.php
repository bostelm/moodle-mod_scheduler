<?php

/**
 * Appointment booking form of the scheduler module
 * (using Moodle formslib)
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Student-side form to book or edit an appointment in a selected slot
 */
class scheduler_booking_form extends moodleform {

    protected $slot;
    protected $appointment = null;
    protected $uploadoptions;
    protected $existing;

    public function __construct(scheduler_slot $slot, $action, $existing = false) {
        $this->slot = $slot;
        $this->existing = $existing;
        parent::__construct($action, null);
    }

    protected function definition() {

        global $CFG, $output;

        $mform = $this->_form;
        $scheduler = $this->slot->get_scheduler();

        $this->noteoptions = array('trusttext' => false, 'maxfiles' => 0, 'maxbytes' => 0,
                                   'context' => $scheduler->get_context(),
                                   'collapsed' => true);

        $this->uploadoptions = array('subdirs' => 0,
                                     'maxbytes' => $scheduler->uploadmaxsize,
                                     'maxfiles' => $scheduler->uploadmaxfiles);

        // Text field for student-supplied data.
        if ($scheduler->uses_studentnotes()) {

            $mform->addElement('editor', 'studentnote_editor', get_string('yourstudentnote', 'scheduler'),
                                array('rows' => 3, 'columns' => 60), $this->noteoptions);
            $mform->setType('studentnote', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
            if ($scheduler->usestudentnotes == 2) {
                $mform->addRule('studentnote_editor', get_string('notesrequired', 'scheduler'), 'required');
            }
        }

        // Student file upload.
        if ($scheduler->uses_studentfiles()) {
            $mform->addElement('filemanager', 'studentfiles',
                    get_string('uploadstudentfiles', 'scheduler'),
                    null, $this->uploadoptions );
            if ($scheduler->requireupload) {
                $mform->addRule('studentfiles', get_string('uploadrequired', 'scheduler'), 'required');
            }
        }

        // Captcha.
        if ($scheduler->uses_bookingcaptcha() && !$this->existing) {
            $mform->addElement('recaptcha', 'bookingcaptcha', get_string('security_question', 'auth'), array('https' => true));
            $mform->addHelpButton('bookingcaptcha', 'recaptcha', 'auth');
            $mform->closeHeaderBefore('bookingcaptcha');
        }

        $submitlabel = $this->existing ? null : get_string('confirmbooking', 'scheduler');
        $this->add_action_buttons(true, $submitlabel);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!$this->existing && $this->slot->get_scheduler()->uses_bookingcaptcha()) {
            $recaptcha = $this->_form->getElement('bookingcaptcha');
            if (!empty($this->_form->_submitValues['recaptcha_challenge_field'])) {
                $challenge = $this->_form->_submitValues['recaptcha_challenge_field'];
                $response = $this->_form->_submitValues['recaptcha_response_field'];
                if (true !== ($result = $recaptcha->verify($challenge, $response))) {
                    $errors['bookingcaptcha'] = $result;
                }
            } else {
                $errors['bookingcaptcha'] = get_string('missingrecaptchachallengefield');
            }
        }

        return $errors;
    }

    public function prepare_booking_data(scheduler_appointment $appointment) {
        $this->appointment = $appointment;

        $newdata = clone($appointment->get_data());
        $context = $appointment->get_scheduler()->get_context();

        $newdata = file_prepare_standard_editor($newdata, 'studentnote', $this->noteoptions, $context);

        $draftitemid = file_get_submitted_draft_itemid('studentfiles');
        file_prepare_draft_area($draftitemid, $context->id, 'mod_scheduler', 'studentfiles', $appointment->id);
        $newdata->studentfiles = $draftitemid;

        return $newdata;
    }

    public function save_booking_data(stdClass $formdata, scheduler_appointment $appointment) {
        $scheduler = $appointment->get_scheduler();
        if ($scheduler->uses_studentnotes() && isset($formdata->studentnote_editor)) {
            $editor = $formdata->studentnote_editor;
            $appointment->studentnote = $editor['text'];
            $appointment->studentnoteformat = $editor['format'];
        }
        if ($scheduler->uses_studentfiles()) {
            file_save_draft_area_files($formdata->studentfiles, $scheduler->context->id,
                                       'mod_scheduler', 'studentfiles', $appointment->id,
                                       $this->uploadoptions);
        }
        $appointment->save();
    }
}
