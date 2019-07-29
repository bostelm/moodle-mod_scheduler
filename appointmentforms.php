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
 * Appointment-related forms of the scheduler module (using Moodle formslib)
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_scheduler\model\appointment;
use \mod_scheduler\permission\scheduler_permissions;

require_once($CFG->libdir.'/formslib.php');

/**
 * Form to edit one appointment
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_editappointment_form extends moodleform {

    /**
     * @var appointment the appointment being edited
     */
    protected $appointment;

    /**
     * @var bool whether to distribute grade to all group members
     */
    protected $distribute;

    /**
     * @var array permissions of the teacher
     */
    protected $permissions;

    /**
     * @var array options for notes fields
     */
    public $noteoptions;

    /**
     * Create a new edit appointment form
     *
     * @param appointment $appointment the appointment to edit
     * @param mixed $action the action attribute for the form
     * @param scheduler_permissions $permissions
     * @param bool $distribute whether to distribute grades to all group members
     */
    public function __construct(appointment $appointment, $action, scheduler_permissions $permissions, $distribute) {
        $this->appointment = $appointment;
        $this->distribute = $distribute;
        $this->permissions = $permissions;
        $this->noteoptions = array('trusttext' => true, 'maxfiles' => -1, 'maxbytes' => 0,
                                   'context' => $permissions->get_context(),
                                   'subdirs' => false, 'collapsed' => true);
        parent::__construct($action, null);
    }

    /**
     * Form definition
     */
    protected function definition() {

        global $output;

        $mform = $this->_form;
        $scheduler = $this->appointment->get_scheduler();

        $candistribute = false;

        // Seen tickbox.
        $mform->addElement('checkbox', 'attended', get_string('attended', 'scheduler'));
        if (!$this->permissions->can_edit_attended($this->appointment)) {
            $mform->freeze('attended');
        }

        // Grade.
        if ($scheduler->uses_grades()) {
            if ($this->permissions->can_edit_grade($this->appointment)) {
                $gradechoices = $output->grading_choices($scheduler);
                $mform->addElement('select', 'grade', get_string('grade', 'scheduler'), $gradechoices);
                $candistribute = true;
            } else {
                $gradetext = $output->format_grade($scheduler, $this->appointment->grade);
                $mform->addElement('static', 'gradedisplay', get_string('grade', 'scheduler'), $gradetext);
            }
        }
        // Appointment notes (visible to teacher and/or student).
        if ($scheduler->uses_appointmentnotes()) {
            if ($this->permissions->can_edit_notes($this->appointment)) {
                $mform->addElement('editor', 'appointmentnote_editor', get_string('appointmentnote', 'scheduler'),
                                   array('rows' => 3, 'columns' => 60), $this->noteoptions);
                $mform->setType('appointmentnote', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
                $candistribute = true;
            } else {
                $note = $output->format_notes($this->appointment->appointmentnote, $this->appointment->appointmentnoteformat,
                                              $scheduler->get_context(), 'appointmentnote', $this->appointment->id);
                $mform->addElement('static', 'appointmentnote_display', get_string('appointmentnote', 'scheduler'), $note);
            }
        }
        if ($scheduler->uses_teachernotes()) {
            if ($this->permissions->can_edit_notes($this->appointment)) {
                $mform->addElement('editor', 'teachernote_editor', get_string('teachernote', 'scheduler'),
                                   array('rows' => 3, 'columns' => 60), $this->noteoptions);
                $mform->setType('teachernote', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
                $candistribute = true;
            } else {
                $note = $output->format_notes($this->appointment->teachernote, $this->appointment->teachernoteformat,
                                              $scheduler->get_context(), 'teachernote', $this->appointment->id);
                $mform->addElement('static', 'teachernote_display', get_string('teachernote', 'scheduler'), $note);
            }
        }
        if ($this->distribute && $candistribute) {
            $mform->addElement('checkbox', 'distribute', get_string('distributetoslot', 'scheduler'));
            $mform->setDefault('distribute', false);
        }

        $this->add_action_buttons();
    }

    /**
     * Form validation.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

    /**
     * Prepare form data from an appointment record
     *
     * @param appointment $appointment appointment to edit
     * @return stdClass form data
     */
    public function prepare_appointment_data(appointment $appointment) {
        $newdata = clone($appointment->get_data());
        $context = $this->appointment->get_scheduler()->get_context();

        $newdata = file_prepare_standard_editor($newdata, 'appointmentnote', $this->noteoptions, $context,
                                                'mod_scheduler', 'appointmentnote', $this->appointment->id);

        $newdata = file_prepare_standard_editor($newdata, 'teachernote', $this->noteoptions, $context,
                                                'mod_scheduler', 'teachernote', $this->appointment->id);
        return $newdata;
    }

    /**
     * Save form data into appointment record
     *
     * @param stdClass $formdata data extracted from form
     * @param appointment $appointment appointment to update
     */
    public function save_appointment_data(stdClass $formdata, appointment $appointment) {
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

