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

/**
 * Scheduler modedit form - overrides moodleform
 */
class mod_scheduler_mod_form extends moodleform_mod {

    function definition() {

        global $CFG, $COURSE, $OUTPUT;
        $mform    =& $this->_form;

        // -------------------------------------------------------------------------------
        // General introduction.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements(get_string('introduction', 'scheduler'));

        // -------------------------------------------------------------------------------
        // Scheduler options.
        $mform->addElement('header', 'optionhdr', get_string('options', 'scheduler'));
        $mform->setExpanded('optionhdr');

        $mform->addElement('text', 'staffrolename', get_string('staffrolename', 'scheduler'), array('size' => '48'));
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

        if (get_config('mod_scheduler', 'groupscheduling')) {
            $selopt = array(
                            -1 => get_string('no'),
                             0 => get_string('yesallgroups', 'scheduler')
                           );
            $groupings = groups_get_all_groupings($COURSE->id);
            foreach ($groupings as $grouping) {
                $selopt[$grouping->id] = get_string('yesingrouping', 'scheduler', $grouping->name);
            }
            $mform->addElement('select', 'bookingrouping', get_string('groupbookings', 'scheduler'), $selopt);
	        $mform->addHelpButton('bookingrouping', 'groupbookings', 'scheduler');
	        $mform->setDefault('bookingrouping', '-1');
        }

        $mform->addElement('duration', 'guardtime', get_string('guardtime', 'scheduler'), array('optional' => true));
        $mform->addHelpButton('guardtime', 'guardtime', 'scheduler');

        $mform->addElement('text', 'defaultslotduration', get_string('defaultslotduration', 'scheduler'), array('size' => '2'));
        $mform->setType('defaultslotduration', PARAM_INT);
        $mform->addHelpButton('defaultslotduration', 'defaultslotduration', 'scheduler');
        $mform->setDefault('defaultslotduration', 15);

        $mform->addElement('selectyesno', 'allownotifications', get_string('notifications', 'scheduler'));
        $mform->addHelpButton('allownotifications', 'notifications', 'scheduler');

        // -------------------------------------------------------------------------------
        // Grade settings.
        $this->standard_grading_coursemodule_elements();

        $mform->setDefault('grade', 0);

        $gradingstrategy[SCHEDULER_MEAN_GRADE] = get_string('meangrade', 'scheduler');
        $gradingstrategy[SCHEDULER_MAX_GRADE] = get_string('maxgrade', 'scheduler');
        $mform->addElement('select', 'gradingstrategy', get_string('gradingstrategy', 'scheduler'), $gradingstrategy);
        $mform->addHelpButton('gradingstrategy', 'gradingstrategy', 'scheduler');
        $mform->disabledIf('gradingstrategy', 'grade[modgrade_type]', 'eq', 'none');

        // -------------------------------------------------------------------------------
        // Common module settings.
        $this->standard_coursemodule_elements();

        $this->add_action_buttons();
    }

    function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);
        if (array_key_exists('scale', $defaultvalues)) {
            $defaultvalues['grade'] = $defaultvalues['scale'];
        }
    }


}
