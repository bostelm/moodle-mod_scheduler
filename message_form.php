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
 * Message form for invitations
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_scheduler\model\scheduler;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Message form for invitations (using Moodle formslib)
 *
 * @package    mod_scheduler
 * @copyright  2016 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scheduler_message_form extends moodleform {

    /**
     * @var scheduler scheduler in whose context the messages are sent
     */
    protected $scheduler;

    /**
     * Create a new messge form
     *
     * @param string $action
     * @param scheduler $scheduler scheduler in whose context the messages are sent
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

        // Select users to sent the message to.
        $checkboxes = array();
        $recipients = $this->_customdata['recipients'];
        foreach ($recipients as $recipient) {
            $inputid = 'recipient['.$recipient->id.']';
            $label = fullname($recipient);
            $checkboxes[] = $mform->createElement('checkbox', $inputid, '', $label);
            $mform->setDefault($inputid, true);
        }
        $mform->addGroup($checkboxes, 'recipients', get_string('recipients', 'scheduler'), null, false);

        if (get_config('mod_scheduler', 'showemailplain')) {
            $maillist = array();
            foreach ($recipients as $recipient) {
                $maillist[] = trim($recipient->email);
            }
            $maildisplay = html_writer::div(implode(', ', $maillist));
            $mform->addElement('html', $maildisplay);
        }

        $mform->addElement('selectyesno', 'copytomyself', get_string('copytomyself', 'scheduler'));
        $mform->setDefault('copytomyself', true);

        $mform->addElement('text', 'subject', get_string('messagesubject', 'scheduler'), array('size' => '60'));
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required');
        if (isset($this->_customdata['subject'])) {
            $mform->setDefault('subject', $this->_customdata['subject']);
        }

        $bodyedit = $mform->addElement('editor', 'body', get_string('messagebody', 'scheduler'),
                                       array('rows' => 15, 'columns' => 60), array('collapsed' => true));
        $mform->setType('body', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
        if (isset($this->_customdata['body'])) {
            $bodyedit->setValue(array('text' => $this->_customdata['body']));
        }

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('sendmessage', 'scheduler'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

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
