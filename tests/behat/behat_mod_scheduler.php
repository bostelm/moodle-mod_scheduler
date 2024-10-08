<?php
// This file is part of Moodle - http://moodle.org/
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
 * @copyright  2015 Henning Bostelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_scheduler extends behat_base {

    /**
     * Adds a series of slots to the scheduler
     *
     * @Given /^I add a slot (\d+) days ahead at (\d+) in "(?P<activityname_string>(?:[^"]|\\")*)" scheduler
     * and I fill the form with:$/
     *
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

        $this->execute('behat_navigation::i_am_on_page_instance', array($this->escape($activityname), 'Activity'));
        $this->execute('behat_general::i_click_on', array('Add slots', 'link'));
        $this->execute('behat_general::click_link', 'Add single slot');

        $this->execute('behat_forms::i_expand_all_fieldsets');

        $rows = array();
        $rows[] = array('starttime[day]', date("j", $startdate));
        $rows[] = array('starttime[month]', date("F", $startdate));
        $rows[] = array('starttime[year]', date("Y", $startdate));
        $rows[] = array('starttime[hour]', $hours);
        $rows[] = array('starttime[minute]', $mins);
        $rows[] = array('duration', '45');
        foreach ($fielddata->getRows() as $row) {
            if ($row[0] == 'studentid[0]') {
                $this->execute('behat_forms::i_open_the_autocomplete_suggestions_list');
                $this->execute('behat_forms::i_click_on_item_in_the_autocomplete_list', $row[1]);
            } else {
                $rows[] = $row;
            }
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

        $this->execute('behat_navigation::i_am_on_page_instance', array($this->escape($activityname), 'Activity'));
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

        $this->execute('behat_data_generators::the_following_entities_exist', array('users',
                        new TableNode(array(
                            array('username', 'firstname', 'lastname', 'email'),
                            array('globalmanager1', 'GlobalManager', '1', 'globalmanager1@example.com')
                        )) ) );

        $this->execute('behat_data_generators::the_following_entities_exist', array('system role assigns',
                        new TableNode(array(
                            array('user', 'role'),
                            array('globalmanager1', 'manager')
                        )) ) );

        $this->execute('behat_auth::i_log_in_as', 'globalmanager1');
        $this->execute('behat_general::i_am_on_site_homepage');
        $this->execute('behat_navigation::i_turn_editing_mode_on');
        $this->execute('behat_blocks::i_add_the_block', 'Upcoming events');

        $this->execute('behat_blocks::i_open_the_blocks_action_menu', 'Upcoming events');
        $this->execute('behat_general::click_link', 'Configure Upcoming events block');
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', new TableNode(array(
                            array('Page contexts', 'Display throughout the entire site')
                        )) );
        $this->execute('behat_general::i_click_on', array('Save changes', 'button'));
        $this->execute('behat_auth::i_log_out');

    }

    /**
     * Select item from the nth autocomplete list.
     *
     * @Given /^I click on "([^"]*)" item in autocomplete list number (\d+)$/
     *
     * @param string $item
     * @param int $listnumber
     */
    public function i_click_on_item_in_the_nth_autocomplete_list($item, $listnumber) {

        $downarrowtarget = "(//span[contains(@class,'form-autocomplete-downarrow')])[$listnumber]";
        $this->execute('behat_general::i_click_on', [$downarrowtarget, 'xpath_element']);

        $xpathtarget = "(//descendant::ul[@class='form-autocomplete-suggestions'][$listnumber]//"
                       ."*[contains(concat('|', string(.), '|'),'|$item|')])";
        $this->execute('behat_general::i_click_on', [$xpathtarget, 'xpath_element']);
    }
}
