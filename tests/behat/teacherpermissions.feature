@mod @mod_scheduler
Feature: Teachers can edit other teacher's appointments only by permission
  In order to edit another teacher's appointment
  As a teacher
  I must have the correct permissions.

  Background:
    Given the following "users" exist:
      | username   | firstname      | lastname | email                  |
      | edteacher1 | Editingteacher | 1        | edteacher1@example.com |
      | neteacher1 | Nonedteacher   | 1        | neteacher1@example.com |
      | neteacher2 | Nonedteacher   | 2        | neteacher2@example.com |
      | student1   | Student        | 1        | student1@example.com   |
      | student2   | Student        | 2        | student2@example.com   |
      | student3   | Student        | 3        | student3@example.com   |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user       | course | role           |
      | edteacher1 | C1     | editingteacher |
      | neteacher1 | C1     | teacher        |
      | neteacher2 | C1     | teacher        |
      | student1   | C1     | student        |
      | student2   | C1     | student        |
      | student3   | C1     | student        |
    And the following "activities" exist:
      | activity  | name           | intro | course | idnumber   | groupmode | usenotes | grade |
      | scheduler | Test scheduler | n     | C1     | schedulert | 0         | 3        | 100   |
    And the following "permission overrides" exist:
      | capability                               | permission | role    | contextlevel | reference |
      | mod/scheduler:canseeotherteachersbooking | Allow      | teacher | Course       | C1        |
    And I log in as "edteacher1"
    And I am on "Course 1" course homepage
    And I add a slot 10 days ahead at 0300 in "Test scheduler" scheduler and I fill the form with:
      | Location     | Office ed1 |
      | studentid[0] | Student 1  |
    And I log out
    And I log in as "neteacher1"
    And I am on "Course 1" course homepage
    And I add a slot 10 days ahead at 0400 in "Test scheduler" scheduler and I fill the form with:
      | Location     | Office ne1 |
      | studentid[0] | Student 2  |
    And I log out
    And I log in as "neteacher2"
    And I am on "Course 1" course homepage
    And I add a slot 10 days ahead at 0500 in "Test scheduler" scheduler and I fill the form with:
      | Location     | Office ne2 |
      | studentid[0] | Student 3  |
    And I log out

  @javascript
  Scenario: Editing teachers edit all appointments, nonediting teachers only their own
    When I log in as "edteacher1"
    And I am on "Course 1" course homepage
    And I follow "Test scheduler"
    And I follow "Statistics"
    And I follow "All appointments"
    Then I should see "Student 1" in the "3:00 AM" "table_row"
    And "seen[]" "checkbox" should exist in the "3:00 AM" "table_row"
    And I should see "Student 2" in the "4:00 AM" "table_row"
    And "seen[]" "checkbox" should exist in the "4:00 AM" "table_row"
    And I should see "Student 3" in the "5:00 AM" "table_row"
    And "seen[]" "checkbox" should exist in the "5:00 AM" "table_row"
    When I click on "//a[text()='Student 3']" "xpath_element" in the "5:00 AM" "table_row"
    Then the "Attended" "checkbox" should be enabled
    And "Notes for appointment (visible to student)" "field" should exist
    And "Confidential notes (visible to teacher only)" "field" should exist
    And the "grade" "field" should be enabled
    When I set the following fields to these values:
    | Attended | 1 |
    And I click on "Save changes" "button"
    Then the field "Attended" matches value "1"
    And I log out

    When I log in as "neteacher1"
    And I am on "Course 1" course homepage
    And I follow "Test scheduler"
    And I follow "Statistics"
    And I follow "All appointments"
    Then I should see "Student 1" in the "3:00 AM" "table_row"
    And "seen[]" "checkbox" should not exist in the "3:00 AM" "table_row"
    And I should see "Student 2" in the "4:00 AM" "table_row"
    And "seen[]" "checkbox" should exist in the "4:00 AM" "table_row"
    And I should see "Student 3" in the "5:00 AM" "table_row"
    And "seen[]" "checkbox" should not exist in the "5:00 AM" "table_row"
    When I click on "//a[text()='Student 2']" "xpath_element" in the "4:00 AM" "table_row"
    Then the "Attended" "checkbox" should be enabled
    And "Notes for appointment (visible to student)" "field" should exist
    And "Confidential notes (visible to teacher only)" "field" should exist
    When I set the following fields to these values:
    | Attended | 1 |
    And I click on "Save changes" "button"
    Then the field "Attended" matches value "1"
    When I am on "Course 1" course homepage
    And I follow "Test scheduler"
    And I follow "Statistics"
    And I follow "All appointments"
    When I click on "//a[text()='Student 3']" "xpath_element" in the "5:00 AM" "table_row"
    Then the "Attended" "checkbox" should be disabled
    And "Notes for appointment (visible to student)" "field" should not exist
    And "Confidential notes (visible to teacher only)" "field" should not exist
    And "grade" "field" should not exist
    And I log out

  @javascript
  Scenario: Attended boxes can be edited if the teacher has permission
    Given I log in as "admin"
    And I set the following system permissions of "Non-editing teacher" role:
      | capability                    | permission |
      | mod/scheduler:editallattended | Allow      |
    And I log out

    When I log in as "neteacher1"
    And I am on "Course 1" course homepage
    And I follow "Test scheduler"
    And I follow "Statistics"
    And I follow "All appointments"
    Then I should see "Student 1" in the "3:00 AM" "table_row"
    And "seen[]" "checkbox" should exist in the "3:00 AM" "table_row"
    And I should see "Student 2" in the "4:00 AM" "table_row"
    And "seen[]" "checkbox" should exist in the "4:00 AM" "table_row"
    And I should see "Student 3" in the "5:00 AM" "table_row"
    And "seen[]" "checkbox" should exist in the "5:00 AM" "table_row"
    When I click on "//a[text()='Student 2']" "xpath_element" in the "4:00 AM" "table_row"
    Then the "Attended" "checkbox" should be enabled
    When I set the following fields to these values:
    | Attended | 1 |
    And I click on "Save changes" "button"
    Then the field "Attended" matches value "1"

    When I am on "Course 1" course homepage
    And I follow "Test scheduler"
    And I follow "Statistics"
    And I follow "All appointments"
    When I click on "//a[text()='Student 3']" "xpath_element" in the "5:00 AM" "table_row"
    Then the "Attended" "checkbox" should be enabled
    And "Notes for appointment (visible to student)" "field" should not exist
    And "Confidential notes (visible to teacher only)" "field" should not exist
    And "grade" "field" should not exist
    When I set the following fields to these values:
    | Attended | 1 |
    And I click on "Save changes" "button"
    Then the field "Attended" matches value "1"
    And I log out

  @javascript
  Scenario: Grade boxes can be edited if the teacher has permission
    Given I log in as "admin"
    And I set the following system permissions of "Non-editing teacher" role:
      | capability                  | permission |
      | mod/scheduler:editallgrades | Allow      |
    And I log out

    When I log in as "neteacher1"
    And I am on "Course 1" course homepage
    And I follow "Test scheduler"
    And I follow "Statistics"
    And I follow "All appointments"
    Then I should see "Student 1" in the "3:00 AM" "table_row"
    And "seen[]" "checkbox" should not exist in the "3:00 AM" "table_row"
    And I should see "Student 2" in the "4:00 AM" "table_row"
    And "seen[]" "checkbox" should exist in the "4:00 AM" "table_row"
    And I should see "Student 3" in the "5:00 AM" "table_row"
    And "seen[]" "checkbox" should not exist in the "5:00 AM" "table_row"
    When I click on "//a[text()='Student 2']" "xpath_element" in the "4:00 AM" "table_row"
    Then the "grade" "field" should be enabled
    When I set the following fields to these values:
    | Grade | 42 |
    And I click on "Save changes" "button"
    Then the field "Grade" matches value "42"

    When I am on "Course 1" course homepage
    And I follow "Test scheduler"
    And I follow "Statistics"
    And I follow "All appointments"
    When I click on "//a[text()='Student 3']" "xpath_element" in the "5:00 AM" "table_row"
    Then the "grade" "field" should be enabled
    And the "Attended" "checkbox" should be disabled
    And "Notes for appointment (visible to student)" "field" should not exist
    And "Confidential notes (visible to teacher only)" "field" should not exist
    When I set the following fields to these values:
    | Grade | 33 |
    And I click on "Save changes" "button"
    Then the field "grade" matches value "33"
    And I log out

  @javascript
  Scenario: Comment boxes can be edited if the teacher has permission
    Given I log in as "admin"
    And I set the following system permissions of "Non-editing teacher" role:
      | capability                 | permission |
      | mod/scheduler:editallnotes | Allow      |
    And I log out

    When I log in as "neteacher1"
    And I am on "Course 1" course homepage
    And I follow "Test scheduler"
    And I follow "Statistics"
    And I follow "All appointments"
    Then I should see "Student 1" in the "3:00 AM" "table_row"
    And "seen[]" "checkbox" should not exist in the "3:00 AM" "table_row"
    And I should see "Student 2" in the "4:00 AM" "table_row"
    And "seen[]" "checkbox" should exist in the "4:00 AM" "table_row"
    And I should see "Student 3" in the "5:00 AM" "table_row"
    And "seen[]" "checkbox" should not exist in the "5:00 AM" "table_row"
    When I click on "//a[text()='Student 2']" "xpath_element" in the "4:00 AM" "table_row"
    Then the "Notes for appointment (visible to student)" "field" should be enabled
    And the "Confidential notes (visible to teacher only)" "field" should be enabled
    When I set the following fields to these values:
    | Notes for appointment (visible to student)   | notes-vis |
    | Confidential notes (visible to teacher only) | notes-confid |
    And I click on "Save changes" "button"
    Then I should see "notes-vis"
    And I should see "notes-confid"

    When I am on "Course 1" course homepage
    And I follow "Test scheduler"
    And I follow "Statistics"
    And I follow "All appointments"
    When I click on "//a[text()='Student 3']" "xpath_element" in the "5:00 AM" "table_row"
    Then "grade" "field" should not exist
    And the "Attended" "checkbox" should be disabled
    And the "Notes for appointment (visible to student)" "field" should be enabled
    And the "Confidential notes (visible to teacher only)" "field" should be enabled
    When I set the following fields to these values:
    | Notes for appointment (visible to student)   | notes-vis-3 |
    | Confidential notes (visible to teacher only) | notes-confid-3 |
    And I click on "Save changes" "button"
    Then I should see "notes-vis-3"
    And I should see "notes-confid-3"
    And I log out
