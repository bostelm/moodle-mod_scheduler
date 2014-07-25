<?php

/**
 * Defines the scheduler module settings form.
 *
 * @package    mod
 * @subpackage scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
//require_once($CFG->dirroot . '/mod/scheduler/locallib.php');

/**
* overrides moodleform for test setup
*/
class mod_scheduler_mod_form extends moodleform_mod {

	function definition() {

	    global $CFG, $COURSE, $OUTPUT;
	    $mform    =& $this->_form;

	    $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
	    $mform->setType('name', PARAM_TEXT);
	    $mform->addRule('name', null, 'required', null, 'client');

        // Introduction.
        $this->add_intro_editor(false, get_string('introduction', 'scheduler'));

	    $mform->addElement('text', 'staffrolename', get_string('staffrolename', 'scheduler'), array('size'=>'48'));
	    $mform->setType('staffrolename', PARAM_TEXT);
		$mform->addRule('staffrolename', get_string('error'), 'maxlength', 255);
		$mform->addHelpButton('staffrolename', 'staffrolename', 'scheduler');

	    $modegroup = array();
	    $modegroup[] = $mform->createElement('static', 'modeintro', '', get_string('modeintro', 'scheduler'));

	    $maxbookoptions = array();
        $maxbookoptions['0'] = get_string('unlimited', 'scheduler');
        for ($i = 1; $i <= 10; $i++) {
            $maxbookoptions[(string)$i] = $i;
        }
        $modegroup[] = $mform->createElement('select', 'maxbookings', '', $maxbookoptions);
        $mform->setDefault('maxbookings', 1);

	    $modegroup[] = $mform->createElement('static', 'modeappointments', '', get_string('modeappointments', 'scheduler'));

	    $modeoptions['oneonly'] = get_string('modeoneonly', 'scheduler');
	    $modeoptions['onetime'] = get_string('modeoneatatime', 'scheduler');
	    $modegroup[] = $mform->createElement('select', 'schedulermode', '', $modeoptions);
	    $mform->setDefault('schedulermode', 'oneonly');

	    $mform->addGroup($modegroup, 'modegrp', get_string('mode', 'scheduler'), ' ', false);
	    $mform->addHelpButton('modegrp', 'appointmentmode', 'scheduler');


		$mform->addElement('duration', 'guardtime', get_string('guardtime', 'scheduler'), array('optional' => true));
	    $mform->addHelpButton('guardtime', 'guardtime', 'scheduler');

	    $mform->addElement('text', 'defaultslotduration', get_string('defaultslotduration', 'scheduler'), array('size'=>'2'));
	    $mform->setType('defaultslotduration', PARAM_INT);
	    $mform->addHelpButton('defaultslotduration', 'defaultslotduration', 'scheduler');
        $mform->setDefault('defaultslotduration', 15);

        $mform->addElement('modgrade', 'scale', get_string('grade'));
        $mform->setDefault('scale', 0);

        $gradingstrategy[MEAN_GRADE] = get_string('meangrade', 'scheduler');
        $gradingstrategy[MAX_GRADE] = get_string('maxgrade', 'scheduler');
	    $mform->addElement('select', 'gradingstrategy', get_string('gradingstrategy', 'scheduler'), $gradingstrategy);
	    $mform->addHelpButton('gradingstrategy', 'gradingstrategy', 'scheduler');
        $mform->disabledIf('gradingstrategy', 'scale', 'eq', 0);

        $yesno[0] = get_string('no');
        $yesno[1] = get_string('yes');
	    $mform->addElement('select', 'allownotifications', get_string('notifications', 'scheduler'), $yesno);
	    $mform->addHelpButton('allownotifications', 'notifications', 'scheduler');

        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

}
