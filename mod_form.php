<?php

/**
 * Defines the scheduler module settings form.
 *
 * @package    mod_scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Scheduler modedit form - overrides moodleform
 *
 * @package    mod_scheduler
 * @copyright  2011 Henning Bostelmann and others (see README.txt)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_scheduler_mod_form extends moodleform_mod {

    protected $editoroptions;

    protected $_rolescount = 5;

    function definition() {

        global $CFG, $COURSE, $OUTPUT;
        $mform    =& $this->_form;

        $cmid = optional_param('update', 0, PARAM_INT);
        $roles = get_scheduler_roles($cmid);
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

        $noteoptions['0'] = get_string('usenotesnone', 'scheduler');
        $noteoptions['1'] = get_string('usenotesstudent', 'scheduler');
        $noteoptions['2'] = get_string('usenotesteacher', 'scheduler');
        $noteoptions['3'] = get_string('usenotesboth', 'scheduler');
        $mform->addElement('select', 'usenotes', get_string('usenotes', 'scheduler'), $noteoptions);
        $mform->setDefault('usenotes', '1');

        $mform->addElement('header', 'roleshdr', get_string('roles'));

        $mform->addElement('checkbox', 'rolessupport', get_string('rolessupport', 'scheduler'));
        $mform->setDefault('rolessupport', false);
        //$mform->addHelpButton('rolessupport', 'ignoreconflicts', 'scheduler');

        for($i=1;$i<=$this->_rolescount;$i++) {
            $mform->addElement('text', 'rolename['.($i-1).']', get_string('role').' '.$i.' '. get_string('name'), array('size' => '48'));
            $mform->setType('rolename['.($i-1).']', PARAM_TEXT);
            $mform->addRule('rolename['.($i-1).']', get_string('error'), 'maxlength', 255);
            $mform->disabledIf('rolename['.($i-1).']', 'rolessupport');
            if (isset($roles[$i-1]->rname) && !empty($roles[$i-1]->rname)) {
                $mform->setDefault('rolename['.($i-1).']', $roles[$i-1]->rname);
            }

            $mform->addElement('text', 'rolelimit['.($i-1).']', get_string('role').' '.$i.' '.get_string('limit', 'scheduler'), array('size' => '20'));
            $mform->setType('rolelimit['.($i-1).']', PARAM_INT);
            $mform->addRule('rolelimit['.($i-1).']', get_string('error'), 'maxlength', 4);
            $mform->disabledIf('rolelimit['.($i-1).']', 'rolessupport');
            if (isset($roles[$i-1]->rlimit) && !empty($roles[$i-1]->rlimit)) {
                $mform->setDefault('rolelimit['.($i-1).']', $roles[$i-1]->rlimit);
            }
        }

        // Grade settings.
        $this->standard_grading_coursemodule_elements();

        $mform->setDefault('grade', 0);

        $gradingstrategy[SCHEDULER_MEAN_GRADE] = get_string('meangrade', 'scheduler');
        $gradingstrategy[SCHEDULER_MAX_GRADE] = get_string('maxgrade', 'scheduler');
        $mform->addElement('select', 'gradingstrategy', get_string('gradingstrategy', 'scheduler'), $gradingstrategy);
        $mform->addHelpButton('gradingstrategy', 'gradingstrategy', 'scheduler');
        $mform->disabledIf('gradingstrategy', 'grade[modgrade_type]', 'eq', 'none');

        // Booking form and student-supplied data.
        $mform->addElement('header', 'bookinghdr', get_string('bookingformoptions', 'scheduler'));

        $mform->addElement('selectyesno', 'usebookingform', get_string('usebookingform', 'scheduler'));
        $mform->addHelpButton('usebookingform', 'usebookingform', 'scheduler');

        $this->editoroptions = array('trusttext' => true, 'maxfiles' => -1, 'maxbytes' => 0,
                                     'context' => $this->context, 'collapsed' => true);
        $mform->addElement('editor', 'bookinginstructions_editor', get_string('bookinginstructions', 'scheduler'),
                array('rows' => 3, 'columns' => 60), $this->editoroptions);
        $mform->setType('bookinginstructions', PARAM_RAW); // Must be PARAM_RAW for rich text editor content.
        $mform->disabledIf('bookinginstructions_editor', 'usebookingform', 'eq', '0');
        $mform->addHelpButton('bookinginstructions_editor', 'bookinginstructions', 'scheduler');

        $studentnoteoptions['0'] = get_string('no');
        $studentnoteoptions['1'] = get_string('yesoptional', 'scheduler');
        $studentnoteoptions['2'] = get_string('yesrequired', 'scheduler');
        $mform->addElement('select', 'usestudentnotes', get_string('usestudentnotes', 'scheduler'), $studentnoteoptions);
        $mform->setDefault('usestudentnotes', '0');
        $mform->disabledIf('usestudentnotes', 'usebookingform', 'eq', '0');
        $mform->addHelpButton('usestudentnotes', 'usestudentnotes', 'scheduler');

        $uploadgroup = array();

        $filechoices = array();
        for ($i = 0; $i <= get_config('mod_scheduler', 'uploadmaxfiles'); $i++) {
            $filechoices[$i] = $i;
        }
        $uploadgroup[] = $mform->createElement('select', 'uploadmaxfiles', get_string('uploadmaxfiles', 'scheduler'), $filechoices);
        $mform->setDefault('uploadmaxfiles', 0);
        $mform->disabledIf('uploadmaxfiles', 'usebookingform', 'eq', '0');
        $uploadgroup[] = $mform->createElement('advcheckbox', 'requireupload', '', get_string('requireupload', 'scheduler'));
        $mform->disabledIf('requireupload', 'usebookingform', 'eq', '0');

        $mform->addGroup($uploadgroup, 'uploadgrp', get_string('uploadmaxfiles', 'scheduler'), ' ', false);
        $mform->addHelpButton('uploadgrp', 'uploadmaxfiles', 'scheduler');

        $sizechoices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes, 0);
        $mform->addElement('select', 'uploadmaxsize', get_string('uploadmaxsize', 'scheduler'), $sizechoices);
        $mform->setDefault('assignsubmission_file_maxsizebytes', $COURSE->maxbytes);
        $mform->disabledIf('uploadmaxsize', 'usebookingform', 'eq', '0');
        $mform->disabledIf('uploadmaxsize', 'uploadmaxfiles', 'eq', '0');
        $mform->addHelpButton('uploadmaxsize', 'uploadmaxsize', 'scheduler');

        if (!empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey)) {
            $mform->addElement('selectyesno', 'usecaptcha', get_string('usecaptcha', 'scheduler'), $studentnoteoptions);
            $mform->setDefault('usecaptcha', '0');
            $mform->disabledIf('usecaptcha', 'usebookingform', 'eq', '0');
            $mform->addHelpButton('usecaptcha', 'usecaptcha', 'scheduler');
        }

        // Common module settings.
        $this->standard_coursemodule_elements();
        $mform->setDefault('groupmode', NOGROUPS);

        $this->add_action_buttons();
    }

    function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);
        if ($this->current->instance) {
            $newvalues = file_prepare_standard_editor((object)$defaultvalues, 'bookinginstructions',
                             $this->editoroptions, $this->context,
                            'mod_scheduler', 'bookinginstructions', 0);
            $defaultvalues['bookinginstructions_editor'] = $newvalues->bookinginstructions_editor;
        }
        if (array_key_exists('scale', $defaultvalues)) {
            $dgrade = $defaultvalues['scale'];
            $defaultvalues['grade'] = $dgrade;
            $type = 'none';
            if ($dgrade > 0) {
                $type = 'point';
            } else if ($dgrade < 0) {
                $type = 'scale';
            }
            $defaultvalues['grade[modgrade_type]'] = $type;
        }
    }

    public function save_mod_data(stdClass $data, context_module $context) {
        global $DB;

        $editor = $data->bookinginstructions_editor;
        if ($editor) {
            $data->bookinginstructions = file_save_draft_area_files($editor['itemid'], $context->id,
                                            'mod_scheduler', 'bookinginstructions', 0,
                                            $this->editoroptions, $editor['text']);
            $data->bookinginstructionsformat = $editor['format'];
            $DB->update_record('scheduler', $data);
            $schedulerid = 0;
            if (isset($data->instance) && !empty($data->instance)) {
                $schedulerid = intval($data->instance);
            } else {
                if (isset($data->id) && !empty($data->id)) {
                    $schedulerid = intval($data->id);
                }
            }
            if ($schedulerid) {
                $cmid = optional_param('update', 0, PARAM_INT);
                $roles = get_scheduler_roles($cmid);
                if (isset($data->rolessupport) && !empty($data->rolessupport)) {
                    $rolesnames = array();
                    if (isset($data->rolename) && !empty($data->rolename) && is_array($data->rolename)) {
                        foreach ($data->rolename as $i => $rolename) {
                            $rolename = trim(clean_param($rolename, PARAM_CLEAN));
                            if (!empty($rolename)) {
                                $rolesnames[$i] = $rolename;
                            } else {
                                if (isset($roles[$i]) && !empty($roles[$i])) {
                                    if ($DB->get_record('scheduler_roles', array('id' => $roles[$i]->id))) {
                                        $DB->delete_records('scheduler_roles', array('id' => $roles[$i]->id));
                                    }
                                }
                            }
                        }
                    }
                    $roleslimits = array();
                    if (isset($data->rolelimit) && !empty($data->rolelimit) && is_array($data->rolelimit)) {
                        foreach ($data->rolelimit as $i => $rolelimit) {
                            $rolelimit = intval($rolelimit);
                            if ($rolelimit > 0) {
                                $roleslimits[$i] = $rolelimit;
                            } else {
                                if (isset($roles[$i]) && !empty($roles[$i])) {
                                    if ($DB->get_record('scheduler_roles', array('id' => $roles[$i]->id))) {
                                        $DB->delete_records('scheduler_roles', array('id' => $roles[$i]->id));
                                    }
                                }
                            }
                        }
                    }
                    if ($rolesnames) {
                        $args = new stdClass();
                        $args->schedulerid = $schedulerid;
                        foreach ($rolesnames as $i => $rolename) {
                            if (isset($roleslimits[$i]) && !empty($roleslimits[$i])) {
                                $args->rname = $rolename;
                                $args->rlimit = $roleslimits[$i];
                                if ($row = $DB->get_record('scheduler_roles', array('schedulerid' => $schedulerid, 'rname' => $rolename))) {
                                    $args->id = $row->id;
                                    $DB->update_record('scheduler_roles', $args);
                                } else {
                                    $DB->insert_record('scheduler_roles', $args);
                                }
                            }
                        }
                    }
                } else {
                    $DB->set_field('scheduler', 'rolessupport', 0);
                    if ($DB->get_records('scheduler_roles', array('schedulerid' => $schedulerid))) {
                        $DB->delete_records('scheduler_roles', array('schedulerid' => $schedulerid));
                    }
                }
            }
        }
    }



}
