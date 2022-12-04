@mod @mod_scheduler
Feature: Teachers can write notes on slots and appointments
  In order to record details about a meeting
  As a teacher
  I need to enter notes for the appointment

  Background:
    Given the following "users" exist:
      | username   | firstname      | lastname | email                  |
      | edteacher1 | Editingteacher | 1        | edteacher1@example.com |
      | neteacher1 | Nonedteacher   | 1        | neteacher1@example.com |
      | student1   | Student        | 1        | student1@example.com   |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user       | course | role           |
      | edteacher1 | C1     | editingteacher |
      | neteacher1 | C1     | teacher        |
      | student1   | C1     | student        |
    And the following "activities" exist:
      | activity  | name               | intro | course | idnumber   | usenotes |
      | scheduler | Test scheduler     | n     | C1     | scheduler1 | 3        |
    And the following "mod_scheduler > slots" exist:
      | scheduler  | starttime        | duration | teacher     | location |
      | scheduler1 | ##tomorrow 3am## | 45       | edteacher1  | Here     |
      | scheduler1 | ##tomorrow 4am## | 45       | edteacher1  | Here     |
      | scheduler1 | ##tomorrow 5am## | 45       | edteacher1  | Here     |

  Scenario: Teachers can enter slot notes and appointment notes for others to see
    When I am on the "scheduler1" Activity page logged in as "edteacher1"
    And I follow "Statistics"
    And I follow "All appointments"
    And I click on "Edit" "link" in the "4:00 AM" "table_row"
    And I set the following fields to these values:
      | Comments | Note-for-slot |
    And I click on "Save" "button"
    Then I should see "slot updated"
    When I click on "Edit" "link" in the "4:00 AM" "table_row"
    Then I should see "Note-for-slot"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "student1"
    Then I should see "Note-for-slot" in the "4:00 AM" "table_row"
    When I click on "Book slot" "button" in the "4:00 AM" "table_row"
    Then I should see "Note-for-slot"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "edteacher1"
    And I follow "Statistics"
    And I follow "All appointments"
    And I click on "Student 1" "text" in the "4:00 AM" "table_row"
    Then I should see ", 4:00 AM" in the "Date and time" "table_row"
    And I should see "4:45 AM" in the "Date and time" "table_row"
    And I should see "Editingteacher 1" in the "Teacher" "table_row"
    And I set the following fields to these values:
      | Attended | 1 |
      | Notes for appointment (visible to student) | note-for-appointment |
      | Confidential notes (visible to teacher only) | note-confidential |
    And I click on "Save changes" "button"
    Then I should see "note-for-appointment"
    And I should see "note-confidential"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "student1"
    Then I should see "Attended slots"
    And I should see "note-for-appointment"
    And I should not see "note-confidential"
    And I log out

  Scenario: Teachers see only the comments fields specified in the configuration

    When I am on the "scheduler1" Activity page logged in as "student1"
    And I click on "Book slot" "button" in the "4:00 AM" "table_row"
    Then I should see "Upcoming slots"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "edteacher1"
    And I follow "Statistics"
    And I follow "All appointments"
    And I click on "Student 1" "text" in the "4:00 AM" "table_row"
    And I set the following fields to these values:
      | Notes for appointment (visible to student) | note-for-appointment |
      | Confidential notes (visible to teacher only) | note-confidential |
    And I click on "Save changes" "button"
    Then I should see "note-for-appointment"
    And I should see "note-confidential"

    When I am on the "scheduler1" Activity page
    And I navigate to "Settings" in current page administration
    And I set the field "Use notes for appointments" to "0"
    And I click on "Save and display" "button"
    And I click on "//a[text()='Student 1']" "xpath_element" in the "4:00 AM" "table_row"
    Then I should not see "Notes for appointment"
    And I should not see "note-for-appointment"
    And I should not see "Confidential notes"
    And I should not see "note-confidential"
    And I click on "Save changes" "button"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "student1"
    Then I should not see "note-for-appointment"
    And I should not see "note-confidential"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "edteacher1"
    And I navigate to "Settings" in current page administration
    And I set the field "Use notes for appointments" to "1"
    And I click on "Save and display" "button"
    And I click on "Student 1" "text" in the "4:00 AM" "table_row"
    Then I should see "Notes for appointment"
    And I should see "note-for-appointment"
    And I should not see "Confidential notes"
    And I should not see "note-confidential"
    And I click on "Save changes" "button"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "student1"
    Then I should see "note-for-appointment"
    And I should not see "note-confidential"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "edteacher1"
    And I navigate to "Settings" in current page administration
    And I set the field "Use notes for appointments" to "2"
    And I click on "Save and display" "button"
    And I click on "Student 1" "text" in the "4:00 AM" "table_row"
    Then I should not see "Notes for appointment"
    And I should not see "note-for-appointment"
    And I should see "Confidential notes"
    And I should see "note-confidential"
    And I click on "Save changes" "button"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "student1"
    Then I should not see "note-for-appointment"
    And I should not see "note-confidential"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "edteacher1"
    And I navigate to "Settings" in current page administration
    And I set the field "Use notes for appointments" to "3"
    And I click on "Save and display" "button"
    And I click on "Student 1" "text" in the "4:00 AM" "table_row"
    Then I should see "Notes for appointment"
    And I should see "note-for-appointment"
    And I should see "Confidential notes"
    And I should see "note-confidential"
    And I log out
