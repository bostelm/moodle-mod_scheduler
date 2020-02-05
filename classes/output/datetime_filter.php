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
 * Datetime filter.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use mod_scheduler\slots_query_builder;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/form/group.php');

/**
 * Datetime filter.
 *
 * @package    mod_scheduler
 * @copyright  2019 Royal College of Art
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_scheduler_output_datetime_filter extends \MoodleQuickForm_group {

    /**
     * Constructor.
     *
     * @param string $elementname Name of the group.
     * @param string $elementlabel Group label.
     * @param array $elements Array of HTML_QuickForm_element elements to group.
     * @param string $separator String to seperate elements..
     */
    public function __construct($elementname = null, $elementlabel = null, $elements = null, $separator = null) {
        parent::__construct($elementname, $elementlabel, $elements, $separator, false);
    }

    /**
     * Create the elements.
     *
     * @return void
     */
    public function _createElements() {
        $this->_elements = [];

        $operator = $this->createFormElement('select', $this->getName() . '[op]', '', [
            slots_query_builder::OPERATOR_AT => get_string('filterdatetimeat', 'mod_scheduler'),
            slots_query_builder::OPERATOR_ON => get_string('filterdatetimeon', 'mod_scheduler'),
            slots_query_builder::OPERATOR_BEFORE => get_string('filterdatetimebefore', 'mod_scheduler'),
            slots_query_builder::OPERATOR_AFTER => get_string('filterdatetimeafter', 'mod_scheduler'),
        ]);
        $this->_elements[] = $operator;

        $datetime = $this->createFormElement('date_time_selector', $this->getName() . '[dt]', '', [
            'optional' => true,
            'defaulttime' => strtotime('midnight')
        ]);
        $this->_elements[] = $datetime;

        foreach ($this->_elements as $element) {
            if (method_exists($element, 'setHiddenLabel')) {
                $element->setHiddenLabel(true);
            }
        }
    }

    /**
     * Export value.
     *
     * @param  array $submitValues The values.
     * @param  bool  $notused Not used.
     * @return array field name => value. The value is the time interval in seconds.
     */
    function exportValue(&$submitValues, $notused = false) {
        // Get the values from all the child elements.
        $values = [];
        foreach ($this->_elements as $element) {
            $thisexport = $element->exportValue($submitValues[$this->getName()], true);
            if ($thisexport !== null && !empty($thisexport[$this->getName()])) {
                $values += $thisexport[$this->getName()];
            }
        }

        if (empty($values) || empty($values['dt'])) {
            return [$this->getName() => null];
        }

        return [$this->getName() => $values];
    }
}

// Auto register the element.
MoodleQuickForm::registerElementType('mod_scheduler_datetime_filter', __FILE__, 'mod_scheduler_output_datetime_filter');
