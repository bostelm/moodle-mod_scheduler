@mod @mod_scheduler
Feature: Users can only see their own groups if the scheduler is in group mode
  In order to see slots
  As a user
  I must be allowed to see the group of the relevant teacher

  Background:
    Given the following "users" exist:
      | username   | firstname      | lastname | email                  |
      | edteacher1 | Editingteacher | 1        | edteacher1@example.com |
      | neteacher1 | Nonedteacher   | 1        | neteacher1@example.com |
      | neteacher2 | Nonedteacher   | 2        | neteacher2@example.com |
      | student1   | Student        | 1        | student1@example.com   |
      | student2   | Student        | 2        | student2@example.com   |
      | student3   | Student        | 3        | student3@example.com   |
      | student4   | Student        | 4        | student4@example.com   |
      | student5   | Student        | 5        | student5@example.com   |
      | student6   | Student        | 6        | student5@example.com   |
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
      | student4   | C1     | student        |
      | student5   | C1     | student        |
      | student6   | C1     | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group A | C1     | GA       |
      | Group B | C1     | GB       |
      | Group C | C1     | GC       |
      | Group D | C1     | GD       |
    And the following "group members" exist:
      | user       | group |
      | edteacher1 | GA    |
      | edteacher1 | GB    |
      | neteacher1 | GB    |
      | neteacher1 | GC    |
      | student1   | GA    |
      | student2   | GA    |
      | student3   | GB    |
      | student4   | GB    |
      | student5   | GD    |
    And the following "activities" exist:
      | activity  | name                    | intro | course | idnumber   | groupmode |
      | scheduler | Test scheduler none     | n     | C1     | schedulern | 0         |
      | scheduler | Test scheduler separate | n     | C1     | schedulers | 1         |
      | scheduler | Test scheduler visible  | n     | C1     | schedulerv | 2         |
    And the following "permission overrides" exist:
      | capability                  | permission | role    | contextlevel | reference |
      | moodle/site:accessallgroups | Prevent    | teacher | Course       | C1        |
      | mod/scheduler:canseeotherteachersbooking | Allow | teacher | Course  | C1     |
    And I add the upcoming events block globally
    And I log in as "edteacher1"
    And I am on "Course 1" course homepage
    And I add 5 slots 10 days ahead in "Test scheduler none" scheduler and I fill the form with:
      | Location  | Here |
    And I am on "Course 1" course homepage
    And I add 5 slots 11 days ahead in "Test scheduler visible" scheduler and I fill the form with:
      | Location  | Here |
    And I am on "Course 1" course homepage
    And I add 5 slots 12 days ahead in "Test scheduler separate" scheduler and I fill the form with:
      | Location  | Here |
    And I log out
    And I log in as "neteacher1"
    And I am on "Course 1" course homepage
    And I add 5 slots 10 days ahead in "Test scheduler none" scheduler and I fill the form with:
      | Location  | There |
    And I am on "Course 1" course homepage
    And I add 5 slots 11 days ahead in "Test scheduler visible" scheduler and I fill the form with:
      | Location  | There |
    And I am on "Course 1" course homepage
    And I add 5 slots 12 days ahead in "Test scheduler separate" scheduler and I fill the form with:
      | Location  | There |
    And I log out

  @javascript
  Scenario: Editing teachers can see all slots and all groups
    When I log in as "edteacher1"
    And I am on "Course 1" course homepage
    And I follow "Test scheduler none"
    And I follow "Statistics"
    And I follow "All appointments"
    Then I should see "Editingteacher 1" in the "slotmanager" "table"
    And I should see "Nonedteacher 1" in the "slotmanager" "table"
    And I should see "Student 1" in the "studentstoschedule" "table"
    And I should see "Student 3" in the "studentstoschedule" "table"
    And I should see "Student 5" in the "studentstoschedule" "table"
    And I should see "Student 6" in the "studentstoschedule" "table"

    When I am on "Course 1" course homepage
    And I follow "Test scheduler visible"
    And I follow "Statistics"
    And I follow "All appointments"
    Then I should see "Visible groups"
    And the "group" select box should contain "All participants"
    And the "group" select box should contain "Group A"
    And the "group" select box should contain "Group B"
    And the "group" select box should contain "Group C"
    And the "group" select box should contain "Group D"
    When I set the field "group" to "All participants"
    Then I should see "Editingteacher 1" in the "slotmanager" "table"
    And I should see "Nonedteacher 1" in the "slotmanager" "table"
    When I set the field "group" to "Group A"
    Then I should see "Editingteacher 1" in the "slotmanager" "table"
    And I should not see "Nonedteacher 1" in the "slotmanager" "table"
    When I set the field "group" to "Group B"
    Then I should see "Editingteacher 1" in the "slotmanager" "table"
    And I should see "Nonedteacher 1" in the "slotmanager" "table"
    When I set the field "group" to "Group C"
    Then I should not see "Editingteacher 1" in the "slotmanager" "table"
    And I should see "Nonedteacher 1" in the "slotmanager" "table"

    When I am on "Course 1" course homepage
    And I follow "Test scheduler separate"
    And I follow "Statistics"
    And I follow "All appointments"
    Then I should see "Separate groups"
    And the "group" select box should contain "All participants"
    And the "group" select box should contain "Group A"
    And the "group" select box should contain "Group B"
    And the "group" select box should contain "Group C"
    And the "group" select box should contain "Group D"
    When I set the field "group" to "All participants"
    Then I should see "Editingteacher 1" in the "slotmanager" "table"
    And I should see "Nonedteacher 1" in the "slotmanager" "table"
    And I should see "Student 1" in the "studentstoschedule" "table"
    And I should see "Student 3" in the "studentstoschedule" "table"
    And I should see "Student 5" in the "studentstoschedule" "table"
    And I should see "Student 6" in the "studentstoschedule" "table"
    When I set the field "group" to "Group A"
    Then I should see "Editingteacher 1" in the "slotmanager" "table"
    And I should not see "Nonedteacher 1" in the "slotmanager" "table"
    And I should see "Student 1" in the "studentstoschedule" "table"
    And I should not see "Student 3" in the "studentstoschedule" "table"
    And I should not see "Student 5" in the "studentstoschedule" "table"
    And I should not see "Student 6" in the "studentstoschedule" "table"
    When I set the field "group" to "Group B"
    Then I should see "Editingteacher 1" in the "slotmanager" "table"
    And I should see "Nonedteacher 1" in the "slotmanager" "table"
    And I should not see "Student 1" in the "studentstoschedule" "table"
    And I should see "Student 3" in the "studentstoschedule" "table"
    And I should not see "Student 5" in the "studentstoschedule" "table"
    And I should not see "Student 6" in the "studentstoschedule" "table"
    When I set the field "group" to "Group C"
    Then I should not see "Editingteacher 1" in the "slotmanager" "table"
    And I should see "Nonedteacher 1" in the "slotmanager" "table"

    # In the "My appointments" tab, the teacher should only see students to schedule from their groups,
    # i.e., groups A and B.
    # Students outside any group should not be visible.
    When I am on "Course 1" course homepage
    And I follow "Test scheduler separate"
    And I follow "Statistics"
    And I follow "My appointments"
    Then I should see "Group mode: Separate groups"
    And I should see "Only students in Group A, Group B can book"
    And I should see "Student 1" in the "studentstoschedule" "table"
    And I should see "Student 3" in the "studentstoschedule" "table"
    And I should not see "Student 5" in the "studentstoschedule" "table"
    And I should not see "Student 6" in the "studentstoschedule" "table"
    And I log out

  @javascript
  Scenario: Nonediting teachers can see groups only if allowed by the group mode

    When I log in as "neteacher1"
    And I am on "Course 1" course homepage
    And I follow "Test scheduler none"
    And I follow "Statistics"
    And I follow "My appointments"
    Then I should see "6 students still need to make an appointment"
    When I follow "All appointments"
    Then I should see "Editingteacher 1" in the "slotmanager" "table"
    And I should see "Nonedteacher 1" in the "slotmanager" "table"

    When I am on "Course 1" course homepage
    And I follow "Test scheduler visible"
    And I follow "Statistics"
    And I follow "My appointments"
    Then I should see "2 students still need to make an appointment"
    When I follow "All appointments"
    Then I should see "Visible groups"
    And the "group" select box should contain "All participants"
    And the "group" select box should contain "Group A"
    And the "group" select box should contain "Group B"
    And the "group" select box should contain "Group C"
    And the "group" select box should contain "Group D"
    When I set the field "group" to "All participants"
    Then I should see "Editingteacher 1" in the "slotmanager" "table"
    And I should see "Nonedteacher 1" in the "slotmanager" "table"
    When I set the field "group" to "Group A"
    Then I should see "Editingteacher 1" in the "slotmanager" "table"
    And I should not see "Nonedteacher 1" in the "slotmanager" "table"
    When I set the field "group" to "Group B"
    Then I should see "Editingteacher 1" in the "slotmanager" "table"
    And I should see "Nonedteacher 1" in the "slotmanager" "table"
    When I set the field "group" to "Group C"
    Then I should not see "Editingteacher 1" in the "slotmanager" "table"
    And I should see "Nonedteacher 1" in the "slotmanager" "table"

    When I am on "Course 1" course homepage
    And I follow "Test scheduler separate"
    And I follow "Statistics"
    And I follow "My appointments"
    Then I should see "2 students still need to make an appointment"
    When I follow "All appointments"
    Then I should see "Separate groups"
    And the "group" select box should not contain "All participants"
    And the "group" select box should not contain "Group A"
    And the "group" select box should contain "Group B"
    And the "group" select box should contain "Group C"
    And the "group" select box should not contain "Group D"
    When I set the field "group" to "Group B"
    Then I should see "Editingteacher 1" in the "slotmanager" "table"
    And I should see "Nonedteacher 1" in the "slotmanager" "table"
    When I set the field "group" to "Group C"
    Then I should not see "Editingteacher 1" in the "slotmanager" "table"
    And I should see "Nonedteacher 1" in the "slotmanager" "table"

    When I set the field "group" to "Group B"
    And I click on "Edit" "link_or_button" in the "Nonedteacher 1" "table_row"
    Then I should see "Appointment 1"
    And "Student 1" "option" should not exist in the "studentid[0]" "field"
    And "Student 3" "option" should exist in the "studentid[0]" "field"
    And I click on "Save changes" "button"

    # In the "My appointments" tab, the teacher should only see students to schedule from their groups,
    # i.e., group B (and C).
    # Students in group 1 and outside any group should not be visible.
    When I am on "Course 1" course homepage
    And I follow "Test scheduler separate"
    And I follow "Statistics"
    And I follow "My appointments"
    Then I should see "Group mode: Separate groups"
    And I should see "Only students in Group B, Group C can book"
    And I should not see "Student 1" in the "studentstoschedule" "table"
    And I should not see "Student 2" in the "studentstoschedule" "table"
    And I should see "Student 3" in the "studentstoschedule" "table"
    And I should see "Student 4" in the "studentstoschedule" "table"
    And I should not see "Student 5" in the "studentstoschedule" "table"
    And I should not see "Student 6" in the "studentstoschedule" "table"
    And I log out

    # neteacher2 sees no students for scheduling in group mode, since he's not member of a group

    When I log in as "neteacher2"
    And I am on "Course 1" course homepage
    And I follow "Test scheduler none"
    And I follow "Statistics"
    And I follow "My appointments"
    Then I should see "6 students still need to make an appointment"

    When I am on "Course 1" course homepage
    And I follow "Test scheduler visible"
    And I follow "Statistics"
    And I follow "My appointments"
    Then I should see "No students available for scheduling"
    And I should see "Group mode: Visible groups"
    And I should see "students cannot book appointments with you"

    When I am on "Course 1" course homepage
    And I follow "Test scheduler separate"
    And I follow "Statistics"
    And I follow "My appointments"
    Then I should see "No students available for scheduling"
    And I should see "Group mode: Separate groups"
    And I should see "students cannot book appointments with you"

  @javascript
  Scenario: Students can see slots available to their own groups, or a slots if group mode is off
    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test scheduler none"
    Then I should see "Editingteacher 1"
    And I should see "Nonedteacher 1"
    When I am on "Course 1" course homepage
    And I follow "Test scheduler visible"
    Then I should see "Editingteacher 1"
    And I should not see "Nonedteacher 1"
    When I am on "Course 1" course homepage
    And I follow "Test scheduler separate"
    Then I should see "Editingteacher 1"
    And I should not see "Nonedteacher 1"
    And I log out

    When I log in as "student3"
    And I am on "Course 1" course homepage
    And I follow "Test scheduler none"
    Then I should see "Editingteacher 1"
    And I should see "Nonedteacher 1"
    When I am on "Course 1" course homepage
    And I follow "Test scheduler visible"
    Then I should see "Editingteacher 1"
    And I should see "Nonedteacher 1"
    When I am on "Course 1" course homepage
    And I follow "Test scheduler separate"
    Then I should see "Editingteacher 1"
    And I should see "Nonedteacher 1"
    And I log out

    When I log in as "student5"
    And I am on "Course 1" course homepage
    And I follow "Test scheduler none"
    Then I should see "Editingteacher 1"
    And I should see "Nonedteacher 1"
    When I am on "Course 1" course homepage
    And I follow "Test scheduler visible"
    Then I should see "No slots are available"
    When I am on "Course 1" course homepage
    And I follow "Test scheduler separate"
    Then I should see "No slots are available"
    And I log out

    When I log in as "student6"
    And I am on "Course 1" course homepage
    And I follow "Test scheduler none"
    Then I should see "Editingteacher 1"
    And I should see "Nonedteacher 1"
    When I am on "Course 1" course homepage
    And I follow "Test scheduler visible"
    Then I should see "No slots are available"
    When I am on "Course 1" course homepage
    And I follow "Test scheduler separate"
    Then I should see "No slots are available"
    And I log out

  @javascript
  Scenario: Students can see slots available to their own groups in forced group mode
    When I log in as "edteacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" in current page administration
    And I expand all fieldsets
    And I set the field "Group mode" to "Separate groups"
    And I set the field "Force group mode" to "Yes"
    And I press "Save and display"
    Then I should see "Test scheduler none"
    And I log out

    When I log in as "student1"
    And I am on "Course 1" course homepage
    And I follow "Test scheduler none"
    Then I should see "Editingteacher 1"
    And I should not see "Nonedteacher 1"
    And I log out