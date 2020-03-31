<?php
// This file is part of a 3rd party created module for Moodle - http://moodle.org/
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
 * Slots filter form.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\output;
defined('MOODLE_INTERNAL') || die();

use core_collator;
use moodleform;
use mod_scheduler\slots_query_builder;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/scheduler/classes/output/datetime_filter.php');

/**
 * Slots filter form.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class slots_filter_form extends moodleform {

    /**
     * Definition.
     */
    public function definition() {
        $scheduler = $this->_customdata['scheduler'];
        $hasfilters = $this->_customdata['hasfilters'];
        $mform = $this->_form;

        // Header.
        $mform->addElement('header', 'settingsheader', get_string('filter'));

        // Start time filter.
        $mform->addElement('mod_scheduler_datetime_filter', 'tfstarttimearr', get_string('filterstarttime', 'mod_scheduler'));
        $mform->addHelpButton('tfstarttimearr', 'filterstarttime', 'mod_scheduler');

        // Location filter.
        $mform->addElement('text', 'tflocation', get_string('location', 'mod_scheduler'));
        $mform->setType('tflocation', PARAM_RAW);

        // Teacher filter.
        if ($this->_customdata['showteacher']) {
            $teacheroptions = array_map(function($user) {
                return fullname($user);
            }, $scheduler->get_teachers());
            core_collator::asort($teacheroptions);
            $teacheroptions = [0 => get_string('choosedots')] + $teacheroptions;
            $mform->addElement('select', 'tfteacherid', $scheduler->get_teacher_name(), $teacheroptions);
        }

        // Add action buttons. We are not using the standard method because we want to include the
        // buttons within the global fieldset.
        $buttons = [];
        $buttons[] = &$mform->createElement('submit', 'submitbutton', get_string('applyfilters', 'mod_scheduler'));
        $buttons[] = &$mform->createElement('cancel', '', get_string('clearfilters', 'mod_scheduler'));
        $mform->addGroup($buttons, 'buttonar', '', [' '], false);

        // Form customisation.
        $mform->setDefault('tfstarttimearr', ['op' => slots_query_builder::OPERATOR_ON]);
        $mform->disable_form_change_checker();
        if (!$hasfilters) {
            $mform->setExpanded('settingsheader', false);
        }
    }

    /**
     * Get the data.
     *
     * We remove the internal tfstarttimearr, and replace it with tfstarttime and tfstarttimeop.
     *
     * @return object
     */
    public function get_data() {
        $data = parent::get_data();
        if (empty($data)) {
            return $data;
        }
        $data = (object) array_intersect_key((array) $data, ['tfstarttimearr' => 1, 'tflocation' => 1, 'tfteacherid' => 1]);

        if (!empty($data->tfstarttimearr) && !empty($data->tfstarttimearr['dt'])) {
            $data->tfstarttimeop = $data->tfstarttimearr['op'];
            $data->tfstarttime = $data->tfstarttimearr['dt'];
            unset($data->tfstarttimearr);
        }

        return $data;
    }

    /**
     * Set data.
     *
     * We convert tfstarttime (and tfstarttimeop) to tfstarttimearr if needed.
     *
     * @param object|array $data The data.
     */
    public function set_data($data) {
        $data = (array) $data;

        if (!empty($data['tfstarttime'])) {
            $data['tfstarttimearr'] = [
                'op' => !empty($data['tfstarttimeop']) ? $data['tfstarttimeop'] : slots_query_builder::OPERATOR_ON,
                'dt' => (int) $data['tfstarttime'],
            ];
            unset($data['tfstarttime']);
            unset($data['tfstarttimeop']);
        }

        parent::set_data($data);
    }
}
