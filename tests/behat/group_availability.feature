@mod @mod_scheduler
Feature: As a teacher I need to see an accurate list of users to be scheduled
  In order to see who needs to schedule an appointment
  As a teacher
  I need to view the table of students in the teacher view

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher  | Teacher   | Teacher  | teacher@example.com |
      | student1 | Student   | 1        | student.1@example.com |
      | student2 | Student   | 2        | student.2@example.com |
      | student3 | Student   | 3        | student.3@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher  | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
      | student3 | C1 | student |
    And the following "groups" exist:
      | name | course | idnumber |
      | Group 1 | C1 | G1 |
      | Group 2 | C1 | G2 |
    And the following "group members" exist:
      | user        | group |
      | student1    | G1    |
      | student2    | G2    |
    And the following "groupings" exist:
      | name        | course | idnumber |
      | Grouping 1  | C1     | GG1      |
    And the following "grouping groups" exist:
      | grouping | group |
      | GG1      | G1    |
    And the following config values are set as admin:
      | enableavailability | 1 |
    And the following "activities" exist:
      | activity  | name           | intro | course | idnumber   |
      | scheduler | Test scheduler | n     | C1     | scheduler1 |

  @javascript
  Scenario: A scheduler that is restricted to a single group
    When I am on the "scheduler1" Activity page logged in as teacher
    Then I should see "Student 1" in the "studentstoschedule" "table"
    And I should see "Student 2" in the "studentstoschedule" "table"
    And I should see "Student 3" in the "studentstoschedule" "table"

    When I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Group" "button" in the "Add restriction..." "dialogue"
    And I set the field with xpath "//select[@name='id']" to "Group 2"
    And I press "Save and display"
    Then I should not see "Student 1" in the "studentstoschedule" "table"
    And I should see "Student 2" in the "studentstoschedule" "table"
    And I should not see "Student 3" in the "studentstoschedule" "table"

  @javascript
  Scenario: A scheduler that is restricted to a grouping
    When I am on the "scheduler1" Activity page logged in as teacher
    Then I should see "Student 1" in the "studentstoschedule" "table"
    And I should see "Student 2" in the "studentstoschedule" "table"
    And I should see "Student 3" in the "studentstoschedule" "table"

    When I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I click on "Add restriction..." "button"
    And I click on "Grouping" "button" in the "Add restriction..." "dialogue"
    And I set the field with xpath "//select[@name='id']" to "Grouping 1"
    And I press "Save and display"
    Then I should see "Student 1" in the "studentstoschedule" "table"
    And I should not see "Student 2" in the "studentstoschedule" "table"
    And I should not see "Student 3" in the "studentstoschedule" "table"
