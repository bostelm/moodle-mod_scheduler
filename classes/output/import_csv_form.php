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
 * Import CSV.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_scheduler\output;
defined('MOODLE_INTERNAL') || die();

use core_text;
use csv_import_reader;
use moodleform;

require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->libdir . '/formslib.php');

/**
 * Import CSV.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_csv_form extends moodleform {

    /**
     * Definition.
     */
    function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('csvfile', 'mod_scheduler'));

        $mform->addElement('filepicker', 'file', get_string('file'));
        $mform->addRule('file', null, 'required');
        $mform->addHelpButton('file', 'importslots', 'mod_scheduler');

        $choices = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimname', get_string('csvfieldseparator', 'mod_scheduler'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimname', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimname', 'semicolon');
        } else {
            $mform->setDefault('delimname', 'comma');
        }

        $choices = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'core_grades'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $choices = ['10' => 10, '20' => 20, '100' => 100, '1000' => 1000, '10000' => 10000];
        $mform->addElement('select', 'previewrows', get_string('rowpreviewnum', 'core_grades'), $choices);
        $mform->setType('previewrows', PARAM_INT);

        $this->add_action_buttons(false, get_string('continue'));
    }
}
