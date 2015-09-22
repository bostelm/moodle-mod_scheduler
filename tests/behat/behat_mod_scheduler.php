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
        $startdate = time() + $daysahead * DAYSECS;

        $steps = array(
            new Given('I follow "' . $this->escape($activityname) . '"'),
            new Given('I click on "Add slots" "link"'),
            new Given('I follow "Add repeated slots"'),
            new Given('I set the field "rangestart[day]" to "' . date("j", $startdate) . '"'),
            new Given('I set the field "rangestart[month]" to "' . date("F", $startdate) . '"'),
            new Given('I set the field "rangestart[year]" to "' . date("Y", $startdate) . '"'),
            new Given('I set the field "Saturday" to "1"'),
            new Given('I set the field "Sunday" to "1"'),
            new Given('I set the field "starthour" to "1"'),
            new Given('I set the field "endhour" to "' . ($slotcount + 1) . '"'),
            new Given('I set the field "duration" to "45"'),
            new Given('I set the field "break" to "15"'),
            new Given('I set the following fields to these values:', $fielddata),
            new Given('I press "Save changes"')
        );

        return $steps;
    }

    /**
     * Add the "upcoming events" block, globally on every page.
     *
     * This is useful as it provides an easy way of checking a user's calendar entries.
     *
     * @Given /^I add the upcoming events block globally$/
     *
     * @return Given[]
     */
    public function i_add_the_upcoming_events_block_globally() {
        $steps = array(
            new Given('the following "users" exist:', new TableNode(
                         ' | username | firstname | lastname | email |
                           | globalmanager1 | GlobalManager | 1 | globalmanager1@example.com |')),
            new Given('the following "system role assigns" exist:', new TableNode(
                          '| user | role |
                           | globalmanager1 | manager |')),
            new Given('I log in as "globalmanager1"'),
            new Given('I follow "Site home"'),
            new Given('I follow "Turn editing on"'),
            new Given('I add the "Upcoming events" block'),
            new Given('I click on "Actions" "link_or_button" in the "Upcoming events" "block"'),
            new Given('I follow "Configure Upcoming events block"'),
            new Given('I set the following fields to these values:', new TableNode(
                        '| Page contexts | Display throughout the entire site |')),
            new Given('I click on "Save changes" "button"'),
            new Given('I log out')
        );

        return $steps;
    }
}
