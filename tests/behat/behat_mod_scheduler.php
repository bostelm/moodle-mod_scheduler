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
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

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
     * @Given /^I add a slot (\d+) days ahead at (\d+) in "(?P<activityname_string>(?:[^"]|\\")*)" scheduler and I fill the form with:$/
     *
     * @param int $slotcount
     * @param int $daysahead
     * @param int $time
     * @param string $activityname
     * @param TableNode $fielddata
     */
    public function i_add_a_slot_days_ahead_at_in_scheduler_and_i_fill_the_form_with(
                              $daysahead, $time, $activityname, TableNode $fielddata) {

        $hours = floor($time / 100);
        $mins  = $time - 100 * $hours;
        $startdate = time() + $daysahead * DAYSECS;

        $this->execute('behat_general::click_link', $this->escape($activityname));
        $this->execute('behat_general::i_click_on', array('Add slots', 'link'));
        $this->execute('behat_general::click_link', 'Add single slot');

        $rows = array();
        $rows[] = array('starttime[day]', date("j", $startdate));
        $rows[] = array('starttime[month]', date("F", $startdate));
        $rows[] = array('starttime[year]', date("Y", $startdate));
        $rows[] = array('starttime[hour]', $hours);
        $rows[] = array('starttime[minute]', $mins);
        $rows[] = array('duration', '45');
        foreach ($fielddata->getRows() as $row) {
            $rows[] = $row;
        }
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', new TableNode($rows));

        $this->execute('behat_general::i_click_on', array('Save changes', 'button'));
    }


    /**
     * Adds a series of slots to the scheduler
     *
     * @Given /^I add (\d+) slots (\d+) days ahead in "(?P<activityname_string>(?:[^"]|\\")*)" scheduler and I fill the form with:$/
     *
     * @param int $slotcount
     * @param int $daysahead
     * @param string $activityname
     * @param TableNode $fielddata
     */
    public function i_add_slots_days_ahead_in_scheduler_and_i_fill_the_form_with(
                        $slotcount, $daysahead, $activityname, TableNode $fielddata) {

        $startdate = time() + $daysahead * DAYSECS;

        $this->execute('behat_general::click_link', $this->escape($activityname));
        $this->execute('behat_general::i_click_on', array('Add slots', 'link'));
        $this->execute('behat_general::click_link', 'Add repeated slots');

        $rows = array();
        $rows[] = array('rangestart[day]', date("j", $startdate));
        $rows[] = array('rangestart[month]', date("F", $startdate));
        $rows[] = array('rangestart[year]', date("Y", $startdate));
        $rows[] = array('Saturday', '1');
        $rows[] = array('Sunday', '1');
        $rows[] = array('starthour', '1');
        $rows[] = array('endhour', $slotcount + 1);
        $rows[] = array('duration', '45');
        $rows[] = array('break', '15');
        foreach ($fielddata->getRows() as $row) {
            $rows[] = $row;
        }

        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', new TableNode($rows));

        $this->execute('behat_general::i_click_on', array('Save changes', 'button'));

    }

    /**
     * Add the "upcoming events" block, globally on every page.
     *
     * This is useful as it provides an easy way of checking a user's calendar entries.
     *
     * @Given /^I add the upcoming events block globally$/
     */
    public function i_add_the_upcoming_events_block_globally() {

        $home = $this->escape(get_string('sitehome'));
        $turnon = $this->escape(get_string('turneditingon'));

        $this->execute('behat_data_generators::the_following_exist', array('users',
                        new TableNode(array(
                            array('username', 'firstname', 'lastname', 'email'),
                            array('globalmanager1', 'GlobalManager', '1', 'globalmanager1@example.com')
                        )) ) );

        $this->execute('behat_data_generators::the_following_exist', array('system role assigns',
                        new TableNode(array(
                            array('user', 'role'),
                            array('globalmanager1', 'manager')
                        )) ) );
        $this->execute('behat_auth::i_log_in_as', 'globalmanager1');
        $this->execute('behat_general::click_link', $home);
        $this->execute('behat_general::i_click_on_in_the', array($turnon, 'link', 'Administration', 'block'));
        $this->execute('behat_blocks::i_add_the_block', 'Upcoming events');

        $this->execute('behat_blocks::i_open_the_blocks_action_menu', 'Upcoming events');
        $this->execute('behat_general::click_link', 'Configure Upcoming events block');
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', new TableNode(array(
                            array('Page contexts', 'Display throughout the entire site')
                        )) );
        $this->execute('behat_general::i_click_on', array('Save changes', 'button'));
        $this->execute('behat_auth::i_log_out');

    }
}
