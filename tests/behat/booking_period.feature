@mod @mod_scheduler @bookingperiod
Feature: Restrict the booking period for students to a booking period configured by the teacher
  In order to control when appointments can be booked
  As a teacher
  I need students to see slots only during the booking period

  Background:
    Given the following "users" exist:
      | username | firstname   | lastname | email                |
      | teacher1 | Teacher     | 1        | teacher1@example.com |
      | student1 | Student     | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user      | course | role           |
      | teacher1  | C1     | editingteacher |
      | student1  | C1     | student        |

  Scenario: Booking period has not started yet
    Given the following "activities" exist:
      | activity  | name             | course | idnumber   | bookingstart    |
      | scheduler | Test scheduler A | C1     | schedulerA | ##now +2 days## |
    And the following "mod_scheduler > slots" exist:
      | scheduler  | starttime        | duration | teacher   |
      | schedulerA | ##now +2 days##  | 45       | teacher1  |
      | schedulerA | ##now +3 days##  | 45       | teacher1  |
      | schedulerA | ##now +4 days##  | 45       | teacher1  |
    When I am on the "schedulerA" Activity page logged in as "student1"
    Then I should see "Booking appointments will be possible from"
    And "slotbookertable" "table" should not exist
    And "Book slot" "button" should not exist

  Scenario: Booking period is currently open
    Given the following "activities" exist:
      | activity  | name             | course | idnumber   | bookingstart    | bookingend      |
      | scheduler | Test scheduler A | C1     | schedulerA | ##now -2 days## | ##now +2 days## |
    And the following "mod_scheduler > slots" exist:
      | scheduler  | starttime        | duration | teacher   |
      | schedulerA | ##now +1 days##  | 45       | teacher1  |
      | schedulerA | ##now +2 days##  | 45       | teacher1  |
    When I am on the "schedulerA" Activity page logged in as "student1"
    Then I should see "Available slots"
    And "slotbookertable" "table" should exist
    And "Book slot" "button" should exist

  Scenario: Booking period has already ended
    Given the following "activities" exist:
      | activity  | name             | course | idnumber   | bookingstart    | bookingend      |
      | scheduler | Test scheduler A | C1     | schedulerA | ##now -8 days## | ##now -4 days## |
    And the following "mod_scheduler > slots" exist:
      | scheduler  | starttime        | duration | teacher   |
      | schedulerA | ##now -7 days##  | 45       | teacher1  |
      | schedulerA | ##now -6 days##  | 45       | teacher1  |
      | schedulerA | ##now -5 days##  | 45       | teacher1  |
    When I am on the "schedulerA" Activity page logged in as "student1"
    Then I should see "The booking period ended on"
    And "slotbookertable" "table" should not exist
    And "Book slot" "button" should not exist
