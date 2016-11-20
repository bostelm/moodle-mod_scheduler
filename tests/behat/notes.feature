@mod_scheduler
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
      | scheduler | Test scheduler     | n     | C1     | schedulern | 3        |
    And I log in as "edteacher1"
    And I follow "Course 1"
    And I add 5 slots 10 days ahead in "Test scheduler" scheduler and I fill the form with:
      | Location  | Here |
    And I log out
    
  @javascript
  Scenario: Teachers can enter slot notes and appointment notes for others to see
    When I log in as "edteacher1"
    And I follow "Course 1"
    And I follow "Test scheduler"
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

    When I log in as "student1"
    And I follow "Course 1"
    And I follow "Test scheduler"
    Then I should see "Note-for-slot" in the "4:00 AM" "table_row"
    When I click on "Book slot" "button" in the "4:00 AM" "table_row"
    Then I should see "Note-for-slot"
    And I log out
    
    When I log in as "edteacher1"
    And I follow "Course 1"
    And I follow "Test scheduler"
    And I follow "Statistics"
    And I follow "All appointments"
    And I click on "//a[text()='Student 1']" "xpath_element" in the "4:00 AM" "table_row"
    Then I should see "Appointment on"
    And I should see "4:00 AM to 4:45 AM with Editingteacher 1"
    And I set the following fields to these values:
      | Attended | 1 |
      | Notes for appointment (visible to student) | note-for-appointment |
      | Confidential notes (visible to teacher only) | note-confidential |
    And I click on "Save changes" "button"
    Then I should see "note-for-appointment"
    And I should see "note-confidential"
    And I log out
        
    When I log in as "student1"
    And I follow "Course 1"
    And I follow "Test scheduler"
    Then I should see "Attended slots"
    And I should see "note-for-appointment"
    And I should not see "note-confidential"
    And I log out
    
  @javascript
  Scenario: Teachers see only the comments fields specified in the configuration

    When I log in as "student1"
    And I follow "Course 1"
    And I follow "Test scheduler"
    And I click on "Book slot" "button" in the "4:00 AM" "table_row"
    Then I should see "Upcoming slots"
    And I log out
    
    When I log in as "edteacher1"
    And I follow "Course 1"
    And I follow "Test scheduler"
    And I follow "Statistics"
    And I follow "All appointments"
    And I click on "//a[text()='Student 1']" "xpath_element" in the "4:00 AM" "table_row"
    And I set the following fields to these values:
      | Notes for appointment (visible to student) | note-for-appointment |
      | Confidential notes (visible to teacher only) | note-confidential |
    And I click on "Save changes" "button"
    Then I should see "note-for-appointment"
    And I should see "note-confidential"

	When I follow "Test scheduler"
	And I click on "Edit settings" "link" in the "Administration" "block"
	And I set the field "Use notes for appointments" to "0"
	And I click on "Save and display" "button"
    And I click on "//a[text()='Student 1']" "xpath_element" in the "4:00 AM" "table_row"
	Then I should not see "Notes for appointment"
	And I should not see "note-for-appointment"
	And I should not see "Confidential notes"
	And I should not see "note-confidential"
	And I click on "Save changes" "button"
	And I log out

    When I log in as "student1"
    And I follow "Course 1"
    And I follow "Test scheduler"
    Then I should not see "note-for-appointment"
    And I should not see "note-confidential"
    And I log out

    When I log in as "edteacher1"
    And I follow "Course 1"
    And I follow "Test scheduler"
    And I click on "Edit settings" "link" in the "Administration" "block"
	And I set the field "Use notes for appointments" to "1"
	And I click on "Save and display" "button"
    And I click on "//a[text()='Student 1']" "xpath_element" in the "4:00 AM" "table_row"
	Then I should see "Notes for appointment"
	And I should see "note-for-appointment"
	And I should not see "Confidential notes"
	And I should not see "note-confidential"
	And I click on "Save changes" "button"
	And I log out
	
	When I log in as "student1"
    And I follow "Course 1"
    And I follow "Test scheduler"
    Then I should see "note-for-appointment"
    And I should not see "note-confidential"
    And I log out
    
    When I log in as "edteacher1"
    And I follow "Course 1"
    And I follow "Test scheduler"
    And I click on "Edit settings" "link" in the "Administration" "block"
	And I set the field "Use notes for appointments" to "2"
	And I click on "Save and display" "button"
    And I click on "//a[text()='Student 1']" "xpath_element" in the "4:00 AM" "table_row"
	Then I should not see "Notes for appointment"
	And I should not see "note-for-appointment"
	And I should see "Confidential notes"
	And I should see "note-confidential"
	And I click on "Save changes" "button"
	And I log out

    When I log in as "student1"
    And I follow "Course 1"
    And I follow "Test scheduler"
    Then I should not see "note-for-appointment"
    And I should not see "note-confidential"
    And I log out

    When I log in as "edteacher1"
    And I follow "Course 1"
    And I follow "Test scheduler"
    And I click on "Edit settings" "link" in the "Administration" "block"
	And I set the field "Use notes for appointments" to "3"
	And I click on "Save and display" "button"
    And I click on "//a[text()='Student 1']" "xpath_element" in the "4:00 AM" "table_row"
	Then I should see "Notes for appointment"
	And I should see "note-for-appointment"
	And I should see "Confidential notes"
	And I should see "note-confidential"
	And I log out