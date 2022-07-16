@mod @mod_scheduler
Feature: Office hours bookings with Scheduler, one booking per student
  In order to organize my office hours
  As a teacher
  I can use a scheduler to let students choose a time slot.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | manager1 | Manager   | 1        | manager1@example.com |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
      | student2 | Student   | 2        | student2@example.com |
      | student3 | Student   | 3        | student3@example.com |
      | student4 | Student   | 4        | student4@example.com |
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
    And the following "system role assigns" exist:
      | user     | role    |
      | manager1 | manager |
    And the following "activities" exist:
      | activity  | name           | intro | course | idnumber   | schedulermode |
      | scheduler | Test scheduler | n     | C1     | scheduler1 | oneonly       |
    And I add the upcoming events block globally

  @javascript
  Scenario: The teacher adds slots, and students book them
    When I log in as "teacher1"
    And I add 10 slots 5 days ahead in "scheduler1" scheduler and I fill the form with:
      | Location | My office |
    Then I should see "10 slots have been added"
    And I should see "4 students still need to make an appointment"
    And I should see "Student 1" in the "studentstoschedule" "table"
    And I should see "Student 2" in the "studentstoschedule" "table"
    And I should see "Student 3" in the "studentstoschedule" "table"
    And I should see "Student 4" in the "studentstoschedule" "table"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "student1"
    Then I should see "1:00 AM" in the "slotbookertable" "table"
    And I should see "10:00 AM" in the "slotbookertable" "table"
    When I click on "Book slot" "button" in the "2:00 AM" "table_row"
    Then "Cancel booking" "button" should exist
    And I should see "Meeting with your Teacher, Teacher 1" in the "Upcoming events" "block"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "student3"
    Then I should see "1:00 AM" in the "slotbookertable" "table"
    And I should not see "2:00 AM" in the "slotbookertable" "table"
    And I should see "10:00 AM" in the "slotbookertable" "table"
    When I click on "Book slot" "button" in the "5:00 AM" "table_row"
    Then "Cancel booking" "button" should exist
    And I should see "Meeting with your Teacher, Teacher 1" in the "Upcoming events" "block"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "teacher1"
    Then I should see "1:00 AM" in the "slotmanager" "table"
    And I should see "Student 1" in the "2:00 AM" "table_row"
    And I should see "Student 3" in the "5:00 AM" "table_row"
    And I should see "10:00 AM" in the "slotmanager" "table"
    And I should see "Meeting with your Student, Student 1" in the "Upcoming events" "block"
    And I should see "Meeting with your Student, Student 3" in the "Upcoming events" "block"
    And I should see "2 students still need to make an appointment"
    And I should not see "Student 1" in the "studentstoschedule" "table"
    And I should see "Student 2" in the "studentstoschedule" "table"
    And I should not see "Student 3" in the "studentstoschedule" "table"
    And I should see "Student 4" in the "studentstoschedule" "table"
    When I click on "seen[]" "checkbox" in the "2:00 AM" "table_row"
    When I am on the "scheduler1" Activity page
    Then I should not see "Meeting with your Student, Student 1" in the "Upcoming events" "block"
    And I should see "Meeting with your Student, Student 3" in the "Upcoming events" "block"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "student1"
    Then I should see "Attended slots"
    And "slotbookertable" "table" should not exist
    And I should not see "Cancel booking"
    And I should not see "Meeting with your" in the "Upcoming events" "block"
    And I log out
