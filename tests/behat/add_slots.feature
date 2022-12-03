@mod @mod_scheduler
Feature: Teacher can add slots to a scheduler activity
  In order to allow students to book a slot
  As a teacher
  I need to add slots to the scheduler

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | 1 | teacher1@example.com |
      | student1 | Student | 1 | student1@example.com |
      | student2 | Student | 2 | student2@example.com |
      | student3 | Student | 3 | student3@example.com |
      | student4 | Student | 4 | student4@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
      | student4 | C1 | student |
    And the following "activities" exist:
      | activity  | name           | intro | course | idnumber   |
      | scheduler | Test scheduler | n     | C1     | scheduler1 |

  Scenario: Teacher adds a single, empty slot to the scheduler
    When I am on the "scheduler1" Activity page logged in as teacher1
    And I click on "Add slots" "link"
    And I follow "Add single slot"
    And I set the following fields to these values:
      | starttime[day]   | 1     |
      | starttime[month] | April |
      | starttime[year]  | 2050  |
      | duration         | 30    |
    And I click on "Save changes" "button"
    Then I should see "1 slot added"
    And I should see "Friday, 1 April 2050"

  Scenario: Teacher enters invalid values when adding a slot
    When I am on the "scheduler1" Activity page logged in as teacher1
    And I click on "Add slots" "link"
    And I follow "Add single slot"
    And I set the following fields to these values:
      | starttime[day]   | 1     |
      | starttime[month] | April |
      | starttime[year]  | 2010  |
    And I click on "Save changes" "button"
    Then I should see "in the past"
    And I set the following fields to these values:
      | starttime[year]  | 2050  |
      | duration         | -1    |
    When I click on "Save changes" "button"
    Then I should see "Slot duration must be between"
    And I set the following fields to these values:
      | duration         | 10    |
      | exclusivity      | -10   |
    And I click on "Save changes" "button"
    And I should see "needs to be 1 or more"
    And I set the following fields to these values:
      | exclusivity      | 5     |
    And I click on "Save changes" "button"
    And I should see "1 slot added"

  @javascript
  Scenario: Teacher enters a slot and schedules 3 students
    When I am on the "scheduler1" Activity page logged in as teacher1
    And I click on "Add slots" "link"
    And I follow "Add single slot"
    And I set the following fields to these values:
      | starttime[day]   | 1         |
      | starttime[month] | April     |
      | starttime[year]  | 2050      |
      | exclusivity      | 2         |
    And I click on "Student 1" item in autocomplete list number 1
    And I click on "Add another student" "button"
    And I click on "Student 2" item in autocomplete list number 2
    And I click on "Add another student" "button"
    And I click on "Student 3" item in autocomplete list number 3
    And I click on "Save changes" "button"
    Then I should see "more than allowed"
    And I set the following fields to these values:
      | exclusivity      | 3         |
    And I click on "Save changes" "button"
    And I should see "1 slot added"
    And I should see "Student 1"
    And I should see "Student 2"
    And I should see "Student 3"

  Scenario: Teacher creates 10 slots at once
    When I log in as "teacher1"
    And I add 10 slots 5 days ahead in "scheduler1" scheduler and I fill the form with:
      | Location  | Here |
    Then I should see "10 slots have been added"
    And I should see "1:00 AM"
    And I should see "2:00 AM"
    And I should see "10:00 AM"
    And I should not see "11:00 AM"
