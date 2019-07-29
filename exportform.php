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
 * Export settings form
 *
 * @package    mod_scheduler
 * @copyright  2015 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use \mod_scheduler\model\scheduler;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/scheduler/exportlib.php');

/**
 * Export settings form (using Moodle formslib)
 *
 * @package    mod_scheduler
 * @copyright  2015 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_export_form extends moodleform {

    /**
     * @var scheduler the scheduler to be exported
     */
    protected $scheduler;

    /**
     * Create a new export settings form.
     *
     * @param string $action
     * @param scheduler $scheduler the scheduler to export
     * @param object $customdata
     */
    public function __construct($action, scheduler $scheduler, $customdata=null) {
        $this->scheduler = $scheduler;
        parent::__construct($action, $customdata);
    }

    /**
     * Form definition
     */
    protected function definition() {

        $mform = $this->_form;

        // General introduction.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $radios = array();
        $radios[] = $mform->createElement('radio', 'content', '',
                                          get_string('onelineperslot', 'scheduler'), 'onelineperslot');
        $radios[] = $mform->createElement('radio', 'content', '',
                                          get_string('onelineperappointment', 'scheduler'),  'onelineperappointment');
        $radios[] = $mform->createElement('radio', 'content', '',
                                          get_string('appointmentsgrouped', 'scheduler'), 'appointmentsgrouped');
        $mform->addGroup($radios, 'contentgroup',
                                          get_string('contentformat', 'scheduler'), null, false);
        $mform->setDefault('content', 'onelineperappointment');
        $mform->addHelpButton('contentgroup', 'contentformat', 'scheduler');

        if (has_capability('mod/scheduler:canseeotherteachersbooking', $this->scheduler->get_context())) {
            $selopt = array('me' => get_string('myself', 'scheduler'),
                'all' => get_string ('everyone', 'scheduler'));
            $mform->addElement('select', 'includewhom', get_string('includeslotsfor', 'scheduler'), $selopt);
            $mform->setDefault('includewhom', 'all');

            $selopt = array('all' => get_string('allononepage', 'scheduler'),
                'perteacher' => get_string('pageperteacher', 'scheduler', $this->scheduler->get_teacher_name()) );
            $mform->addElement('select', 'paging', get_string('pagination', 'scheduler'),  $selopt);
            $mform->addHelpButton('paging', 'pagination', 'scheduler');

        }

        $timeoptions = [
                0 => get_string('exporttimerangeall', 'scheduler'),
                1 => get_string('exporttimerangefuture', 'scheduler'),
                2 => get_string('exporttimerangepast', 'scheduler')
        ];
        $mform->addElement('select', 'timerange', get_string('exporttimerange', 'scheduler'), $timeoptions);
        $mform->setDefault('timerange', 0);

        $mform->addElement('selectyesno', 'includeemptyslots', get_string('includeemptyslots', 'scheduler'));
        $mform->setDefault('includeemptyslots', 1);

        // Select data to export.
        $mform->addElement('header', 'datafieldhdr', get_string('datatoinclude', 'scheduler'));
        $mform->addHelpButton('datafieldhdr', 'datatoinclude', 'scheduler');

        $this->add_exportfield_group('slot', 'slot');
        $this->add_exportfield_group('student', 'student');
        $this->add_exportfield_group('appointment', 'appointment');

        $mform->setDefault('field-date', 1);
        $mform->setDefault('field-starttime', 1);
        $mform->setDefault('field-endtime', 1);
        $mform->setDefault('field-teachername', 1);
        $mform->setDefault('field-studentfullname', 1);
        $mform->setDefault('field-attended', 1);

        // Output file format.
        $mform->addElement('header', 'fileformathdr', get_string('fileformat', 'scheduler'));
        $mform->addHelpButton('fileformathdr', 'fileformat', 'scheduler');

        $radios = array();
        $radios[] = $mform->createElement('radio', 'outputformat', '', get_string('csvformat', 'scheduler'), 'csv');
        $radios[] = $mform->createElement('radio', 'outputformat', '', get_string('excelformat', 'scheduler'),  'xls');
        $radios[] = $mform->createElement('radio', 'outputformat', '', get_string('odsformat', 'scheduler'), 'ods');
        $radios[] = $mform->createElement('radio', 'outputformat', '', get_string('htmlformat', 'scheduler'), 'html');
        $radios[] = $mform->createElement('radio', 'outputformat', '', get_string('pdfformat', 'scheduler'), 'pdf');
        $mform->addGroup($radios, 'outputformatgroup', get_string('fileformat', 'scheduler'), null, false);
        $mform->setDefault('outputformat', 'csv');

        $selopt = array('comma'     => get_string('sepcomma', 'scheduler'),
                        'colon'     => get_string('sepcolon', 'scheduler'),
                        'semicolon' => get_string('sepsemicolon', 'scheduler'),
                        'tab'       => get_string('septab', 'scheduler'));
        $mform->addElement('select', 'csvseparator', get_string('csvfieldseparator', 'scheduler'),  $selopt);
        $mform->setDefault('csvseparator', 'comma');
        $mform->disabledIf('csvseparator', 'outputformat', 'neq', 'csv');

        $selopt = array('P' => get_string('portrait', 'scheduler'),
                        'L' => get_string('landscape', 'scheduler'));
        $mform->addElement('select', 'pdforientation', get_string('pdforientation', 'scheduler'),  $selopt);
        $mform->disabledIf('pdforientation', 'outputformat', 'neq', 'pdf');

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'preview', get_string('preview', 'scheduler'));
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('createexport', 'scheduler'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

    }

    /**
     * Add a group of export fields to the form.
     *
     * @param string $groupid id of the group in the list of fields
     * @param string $labelid language string id for the group label
     */
    private function add_exportfield_group($groupid, $labelid) {

        $mform = $this->_form;
        $fields = scheduler_get_export_fields($this->scheduler);
        $checkboxes = array();

        foreach ($fields as $field) {
            if ($field->get_group() == $groupid && $field->is_available($this->scheduler)) {
                $inputid = 'field-'.$field->get_id();
                $label = $field->get_formlabel($this->scheduler);
                $checkboxes[] = $mform->createElement('checkbox', $inputid, '', $label);
            }
        }
        $grouplabel = get_string($labelid, 'scheduler');
        $mform->addGroup($checkboxes, 'fields-'.$groupid, $grouplabel, null, false);
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

        return $errors;
    }

}
