@mod @mod_scheduler
Feature: Teachers are warned about scheduling conflicts
  In order to create useful slots
  As a teacher
  I need to take care not to create conflicting schedules.

  Background:
    Given the following "users" exist:
      | username | firstname   | lastname | email                |
      | manager1 | Manager     | 1        | manager1@example.com |
      | teacher1 | Teacher     | 1        | teacher1@example.com |
      | teacher2 | Teacher     | 2        | teacher2@example.com |
      | student1 | Student     | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user  | course | role           |
      | teacher1  | C1 | editingteacher |
      | teacher2  | C1 | editingteacher |
      | student1  | C1 | student        |
    And the following "system role assigns" exist:
      | user     | role    |
      | manager1 | manager |
    And the following "activities" exist:
      | activity  | name             | intro | course | idnumber   | groupmode | schedulermode | maxbookings |
      | scheduler | Test scheduler A | n     | C1     | schedulerA | 0         | oneonly       | 1           |
      | scheduler | Test scheduler B | n     | C1     | schedulerB | 0         | oneonly       | 1           |

  Scenario: A teacher edits a single slot and is warned about conflicts

    Given the following "mod_scheduler > slots" exist:
      | scheduler  | starttime            | duration | teacher   | location  |
      | schedulerA | ##tomorrow 1:00am##  | 45       | teacher1  | My office |
      | schedulerA | ##tomorrow 2:00am##  | 45       | teacher1  | My office |
      | schedulerA | ##tomorrow 3:00am##  | 45       | teacher1  | My office |
      | schedulerA | ##tomorrow 4:00am##  | 45       | teacher1  | My office |
      | schedulerA | ##tomorrow 5:00am##  | 45       | teacher1  | My office |
      | schedulerB | ##tomorrow 10:00am## | 15       | teacher1  | My office |
    And I log in as "teacher1"

    When I am on the "schedulerA" Activity page
    And I click on "Edit" "link" in the "2:00 AM" "table_row"
    And I set the following fields to these values:
      | starttime[minute] | 40 |
    And I click on "Save changes" "button"
    Then I should see "conflict"
    And "Save changes" "button" should exist
    And I should see "3:00 AM"
    And I should not see "2:00 AM"

    When I set the following fields to these values:
      | starttime[hour]   | 09 |
      | starttime[minute] | 55 |
    And I click on "Save changes" "button"
    Then I should see "conflict"
    And I should see "in course C1, scheduler Test scheduler B"
    And I should see "10:00 AM"
    And I should not see "2:00 AM"
    And "Save changes" "button" should exist

    When I set the following fields to these values:
      | starttime[hour]   | 09 |
      | starttime[minute] | 55 |
      | Ignore scheduling conflicts | 1 |
    And I click on "Save changes" "button"
    Then I should see "slot updated"
    And "9:55 AM" "table_row" should exist
    And I log out

  Scenario: A manager edits slots for several teachers, creating conflicts

    Given the following "mod_scheduler > slots" exist:
      | scheduler  | starttime           | duration | teacher   | location  |
      | schedulerA | ##tomorrow 1:00am## | 45       | teacher1  | Office T1 |
      | schedulerA | ##tomorrow 2:00am## | 45       | teacher1  | Office T1 |
      | schedulerA | ##tomorrow 3:00am## | 45       | teacher1  | Office T1 |
      | schedulerA | ##tomorrow 4:00am## | 45       | teacher1  | Office T1 |
      | schedulerA | ##tomorrow 5:00am## | 45       | teacher1  | Office T1 |
      | schedulerA | ##tomorrow 6:00am## | 45       | teacher1  | Office T1 |
      | schedulerB | ##tomorrow 1:00am## | 45       | teacher2  | Office T2 |
      | schedulerB | ##tomorrow 2:00am## | 45       | teacher2  | Office T2 |
      | schedulerB | ##tomorrow 3:00am## | 45       | teacher2  | Office T2 |
      | schedulerB | ##tomorrow 4:00am## | 45       | teacher2  | Office T2 |
      | schedulerB | ##tomorrow 5:00am## | 45       | teacher2  | Office T2 |
    And I log in as "manager1"

    When I am on the "schedulerA" Activity page
    And I click on "Edit" "link" in the "3:00 AM" "table_row"
    And I set the following fields to these values:
      | starttime[hour]   | 6  |
      | starttime[minute] | 40 |
      | duration          | 5  |
    And I click on "Save changes" "button"
    Then I should see "conflict"
    And I should see "6:00 AM"
    And I should see "in this scheduler"
    And I should not see "3:00 AM"
    And "Save changes" "button" should exist

    When I set the following fields to these values:
      | starttime[hour]   | 5  |
      | starttime[minute] | 40 |
      | duration          | 5  |
      | Teacher           | Teacher 2 |
    And I click on "Save changes" "button"
    Then I should see "conflict"
    And I should see "5:00 AM"
    And I should see "in course C1, scheduler Test scheduler B"
    And I should not see "3:00 AM"
    And "Save changes" "button" should exist

    When I set the following fields to these values:
      | starttime[hour]   | 6  |
      | starttime[minute] | 40 |
      | duration          | 5  |
      | Teacher           | Teacher 2 |
    And I click on "Save changes" "button"
    Then I should not see "conflict"
    And I should see "slot updated"
    And "6:40 AM" "table_row" should exist
    And "Save changes" "button" should not exist

  Scenario: A teacher adds a series of slots, creating conflicts

    Given the following "mod_scheduler > slots" exist:
      | scheduler  | starttime          | duration | teacher   | location  | student  |
      | schedulerA | ##+5 days 1:25am## | 15       | teacher1  | My office |          |
      | schedulerA | ##+5 days 2:25am## | 100      | teacher1  | My office |          |
      | schedulerA | ##+5 days 8:55am## | 10       | teacher1  | My office | student1 |
      | schedulerB | ##+5 days 6:05am## | 20       | teacher1  | My office |          |

    When I log in as "teacher1"
    And I add 10 slots 5 days ahead in "schedulerA" scheduler and I fill the form with:
      | Location | Lecture hall |
    Then I should see "conflicting slots"
    And I should not see "deleted"
    And I should see "4 slots have been added"
    And  "1:25 AM" "table_row" should exist
    And  "2:25 AM" "table_row" should exist
    And  "8:55 AM" "table_row" should exist
    And  "1:00 AM" "table_row" should not exist
    And  "2:00 AM" "table_row" should not exist
    And  "3:00 AM" "table_row" should not exist
    And  "4:00 AM" "table_row" should not exist
    And  "5:00 AM" "table_row" should exist
    And  "6:00 AM" "table_row" should not exist
    And  "7:00 AM" "table_row" should exist
    And  "8:00 AM" "table_row" should exist
    And  "9:00 AM" "table_row" should not exist
    And "10:00 AM" "table_row" should exist
    And I am on the "schedulerB" Activity page
    And "6:05 AM" "table_row" should exist

    When I add 10 slots 5 days ahead in "schedulerA" scheduler and I fill the form with:
      | Location | Lecture hall |
      | Force when overlap | 1  |
    Then I should see "conflicting slots"
    And I should see "deleted"
    And I should see "8 slots have been added"
    And  "1:25 AM" "table_row" should not exist
    And  "2:25 AM" "table_row" should not exist
    And  "9:55 AM" "table_row" should not exist
    And  "1:00 AM" "table_row" should exist
    And  "2:00 AM" "table_row" should exist
    And  "3:00 AM" "table_row" should exist
    And  "4:00 AM" "table_row" should exist
    And  "5:00 AM" "table_row" should exist
    And  "6:00 AM" "table_row" should not exist
    And  "7:00 AM" "table_row" should exist
    And  "8:00 AM" "table_row" should exist
    And  "9:00 AM" "table_row" should not exist
    And "10:00 AM" "table_row" should exist
    And I am on the "schedulerB" Activity page
    And "6:05 AM" "table_row" should exist
