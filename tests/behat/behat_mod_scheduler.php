<?php

/**
 * Steps definitions related with the scheduler activity.
 *
 * @package    mod_scheduler
 * @category   test
 * @copyright  2015 Henning Bostelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
require_once (__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given, Behat\Behat\Context\Step\When as When, Behat\Gherkin\Node\TableNode as TableNode;
/**
 * Scheduler-related steps definitions.
 *
 * @package mod_scheduler
 * @category test
 */
class behat_mod_scheduler extends behat_base {

	/**
	 * Adds a series of slots to the scheduler
	 *
	 * @Given /^I add (\d+) slots (\d+) days ahead in "(?P<activityname_string>(?:[^"]|\\")*)" scheduler and I fill the form with:$/
	 *
	 * @param int $slotcount
	 * @param int $daysahead
	 * @param string $activityname
	 * @param TableNode $fielddata
	 * @return Given[]
	 */
	public function i_add_slots_days_ahead_in_scheduler_and_i_fill_the_form_with($slotcount, $daysahead, $activityname, TableNode $fielddata) {
		$startdate = time () + $daysahead * DAYSECS;

		$steps = array (
				new Given ('I follow "' . $this->escape($activityname) . '"'),
				new Given ('I click on "Add slots" "link"'),
				new Given ('I follow "Add repeated slots"'),
				new Given ('I set the field "rangestart[day]" to "' . date ( "d", $startdate ) . '"'),
				new Given ('I set the field "rangestart[month]" to "' . date ( "F", $startdate ) . '"'),
				new Given ('I set the field "rangestart[year]" to "' . date ( "Y", $startdate ) . '"'),
				new Given ('I set the field "Saturday" to "1"'),
				new Given ('I set the field "Sunday" to "1"'),
				new Given ('I set the field "starthour" to "1"'),
				new Given ('I set the field "endhour" to "' . ($slotcount + 1) . '"'),
				new Given ('I set the field "duration" to "45"'),
				new Given ('I set the field "break" to "15"'),
				new Given ('I set the following fields to these values:', $fielddata),
				new Given ('I press "Save changes"')
		);

		return $steps;
	}
}
