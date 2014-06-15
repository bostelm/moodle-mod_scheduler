<?PHP
/**
 * Appointment-related forms of the scheduler module
 * (using Moodle formslib)
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2014 Henning Bostelmann and others (see README.txt)
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

    public function __construct(scheduler_appointment $appointment, $action, $editgrade, $distribute) {
        $this->appointment = $appointment;
        $this->distribute = $distribute;
        $this->editgrade = $editgrade;
        parent::__construct($action, null);
    }

    protected function definition() {

        global $output;

        $mform = $this->_form;
        $scheduler = $this->appointment->get_scheduler();

        // Seen tickbox
        $mform->addElement('checkbox', 'attended', get_string('attended', 'scheduler'));


        // Grade
        if ($scheduler->scale != 0) {
            if ($this->editgrade) {
                $gradechoices = $output->grading_choices($scheduler);
                $mform->addElement('select', 'grade', get_string('grade', 'scheduler'), $gradechoices);
                $mform->disabledIf('grade', 'attended', 'notchecked');
            } else {
                $gradetext = $output->format_grade($scheduler, $this->appointment->grade);
                $mform->addElement('static', 'gradedisplay', get_string('grade', 'scheduler'), $gradetext);
            }
        }
        // Appointment notes
        $mform->addElement('editor', 'appointmentnote', get_string('appointmentnotes', 'scheduler'),
                           array('rows' => 3, 'columns' => 60), array('collapsed' => true));
        $mform->setType('appointmentnote', PARAM_RAW); // must be PARAM_RAW for rich text editor content
        if ($this->distribute) {
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
        $newdata->appointmentnote=array();
        $newdata->appointmentnote['text'] = $appointment->appointmentnote;
        $newdata->appointmentnote['format'] = $appointment->appointmentnoteformat;
        return $newdata;
    }

    public function extract_appointment_data(stdClass $data) {
        $newdata = clone($data);
        $newdata->attended = isset($data->attended);
        $newdata->appointmentnoteformat = $data->appointmentnote['format'];
        $newdata->appointmentnote = $data->appointmentnote['text'];

        return $newdata;
    }
}

