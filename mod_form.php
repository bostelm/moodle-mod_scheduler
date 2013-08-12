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
	    $mform->setType('name', PARAM_CLEANHTML);
	    $mform->addRule('name', null, 'required', null, 'client');

        // Introduction.
        $this->add_intro_editor(false, get_string('introduction', 'scheduler'));

	    $mform->addElement('text', 'staffrolename', get_string('staffrolename', 'scheduler'), array('size'=>'48'));
	    $mform->setType('staffrolename', PARAM_CLEANHTML);
	    $mform->addHelpButton('staffrolename', 'staffrolename', 'scheduler');
	
	    $modeoptions['onetime'] = get_string('oneatatime', 'scheduler');
	    $modeoptions['oneonly'] = get_string('oneappointmentonly', 'scheduler');
	    $mform->addElement('select', 'schedulermode', get_string('mode', 'scheduler'), $modeoptions);
	    $mform->addHelpButton('schedulermode', 'appointmentmode', 'scheduler');

	    $reuseguardoptions[24] = 24 . ' ' . get_string('hours');
	    $reuseguardoptions[48] = 48 . ' ' . get_string('hours');
	    $reuseguardoptions[72] = 72 . ' ' . get_string('hours');
	    $reuseguardoptions[96] = 96 . ' ' . get_string('hours');
	    $reuseguardoptions[168] = 168 . ' ' . get_string('hours');
	    $mform->addElement('select', 'reuseguardtime', get_string('reuseguardtime', 'scheduler'), $reuseguardoptions);
	    $mform->addHelpButton('reuseguardtime', 'reuseguardtime', 'scheduler');

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

		// Legacy. This field is still in the DB but is meaningless, meanwhile.
	    $mform->addElement('hidden', 'teacher');
	    $mform->setType('teacher', PARAM_INT);
	     
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

}

?>