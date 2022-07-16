@mod @mod_scheduler @javascript @_file_upload
Feature: Student-supplied data
  In order to collect data from students
  As a teacher
  I can configure a booking form for the scheduler.

  Background:
    Given the following "users" exist:
      | username  | firstname   | lastname | email                |
      | teacher1  | Teacher     | 1        | teacher1@example.com |
      | student1  | Student     | 1        | student1@example.com |
      | student2  | Student     | 2        | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user  | course | role           |
      | teacher1  | C1 | editingteacher |
      | student1  | C1 | student        |
      | student2  | C1 | student        |
    And the following "activities" exist:
      | activity  | name           | intro | course | idnumber   | groupmode | schedulermode | maxbookings |
      | scheduler | Test scheduler | n     | C1     | scheduler1 | 0         | oneonly       | 0           |

  @javascript
  Scenario: A teacher configures a booking form, and students enter data
    When I am on the "scheduler1" Activity page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the field "Use booking form" to "1"
    And I set the field "Booking instructions" to "Please enter your first name"
    And I set the field "Let students enter a message" to "Yes, student must enter a message"
    And I set the field "Maximum number of uploaded files" to "1"
    And I click on "Save and display" "button"
    And I add 10 slots 5 days ahead in "scheduler1" scheduler and I fill the form with:
      | Location | My office |
    Then I should see "10 slots have been added"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "student1"
    Then I should see "3:00 AM" in the "slotbookertable" "table"

    When I click on "Book slot" "button" in the "3:00 AM" "table_row"
    Then I should see "Please enter your first name"

    When I click on "Confirm booking" "button"
    Then I should see "You must enter text into this field"

    When I set the field "Your message" to "Joe"
    And I click on "Confirm booking" "button"
    Then "Cancel booking" "button" should exist
    And I log out

    When I am on the "scheduler1" Activity page logged in as "student2"
    And I click on "Book slot" "button" in the "4:00 AM" "table_row"
    Then I should see "Please enter your first name"

    When I set the field "Your message" to "Jill"
    And I upload "mod/scheduler/tests/fixtures/studentfile.txt" file to "Upload files" filemanager
    And I click on "Confirm booking" "button"
    Then "Cancel booking" "button" should exist
    And I log out

    When I am on the "scheduler1" Activity page logged in as "teacher1"
    And I follow "Statistics"
    And I follow "My appointments"
    Then I should see "Student 1" in the "3:00 AM" "table_row"
    And I should see "Student 2" in the "4:00 AM" "table_row"

    When I click on "Student 1" "text" in the "3:00 AM" "table_row"
    Then I should see "Student 1"
    And I should see "Joe"
    And I should not see "studentfile.txt"

    When I click on "Continue" "button"
    And I click on "Student 2" "text" in the "4:00 AM" "table_row"
    Then I should see "Student 2"
    And I should see "Jill"
    And I should see "studentfile.txt"
    And I log out
