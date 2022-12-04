@mod @mod_scheduler
Feature: Teachers can grade student appointments with totals automatically computed
  In order to grade a student
  As a teacher
  I need to enter the grade into the appointment screen

  Background:
    Given the following "users" exist:
      | username   | firstname      | lastname | email                  |
      | teacher1   | Editingteacher | 1        | teacher1@example.com   |
      | student1   | Student        | 1        | student1@example.com   |
      | student2   | Student        | 2        | student2@example.com   |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user       | course | role           |
      | teacher1   | C1     | editingteacher |
      | student1   | C1     | student        |
      | student2   | C1     | student        |
    And the following "activities" exist:
      | activity  | name               | intro | course | idnumber   | grade |
      | scheduler | Test scheduler     | n     | C1     | scheduler1 | 10    |
    And the following "mod_scheduler > slots" exist:
      | scheduler  | starttime        | duration | teacher   | location | student  |
      | scheduler1 | ##tomorrow 3am## | 45       | teacher1  | Here     | student2 |
      | scheduler1 | ##tomorrow 4am## | 45       | teacher1  | Here     | student2 |
      | scheduler1 | ##tomorrow 5am## | 45       | teacher1  | Here     | student1 |
      | scheduler1 | ##tomorrow 6am## | 45       | teacher1  | Here     | student2 |

  Scenario: Teachers can enter a grade for a student
    When I am on the "scheduler1" Activity page logged in as "teacher1"
    And I click on "Student 1" "text" in the "5:00 AM" "table_row"
    And I set the following fields to these values:
      | Grade | 7 |
    And I click on "Save" "button"
    Then I should see "7/10" in the "div.totalgrade" "css_element"
    And I am on the "scheduler1" Activity page
    And I should see "7/10" in the "5:00 AM" "table_row"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "student1"
    Then I should see "7/10"

    When I am on the "C1" Course page
    And I follow "Grades" in the user menu
    And I follow "Course 1"
    Then I should see "7.00" in the "Test scheduler" "table_row"
    And I log out

  Scenario: Teachers can enter several grades for a student, and the best is taken
    When I am on the "scheduler1" Activity page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I set the field "Grading strategy" to "Take the highest grade"
    And I press "Save and display"

    And I click on "Student 2" "text" in the "3:00 AM" "table_row"
    And I set the following fields to these values:
      | Grade | 3 |
    And I click on "Save" "button"
    Then I should see "3/10" in the "div.totalgrade" "css_element"
    And I log out

    When I am on the "C1" Course page logged in as "student2"
    And I follow "Grades" in the user menu
    And I follow "Course 1"
    Then I should see "3.00" in the "Test scheduler" "table_row"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "teacher1"
    And I click on "Student 2" "text" in the "6:00 AM" "table_row"
    And I set the following fields to these values:
      | Grade | 6 |
    And I click on "Save" "button"
    Then I should see "6/10" in the "div.totalgrade" "css_element"

    When I am on the "scheduler1" Activity page
    And I click on "Student 2" "text" in the "4:00 AM" "table_row"
    And I set the following fields to these values:
      | Grade | 4 |
    And I click on "Save" "button"
    And I should see "6/10" in the "div.totalgrade" "css_element"
    And I log out

    When I am on the "C1" Course page logged in as "student2"
    And I follow "Grades" in the user menu
    And I follow "Course 1"
    Then I should see "6.00" in the "Test scheduler" "table_row"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "teacher1"
    And I click on "Student 2" "text" in the "6:00 AM" "table_row"
    And I set the following fields to these values:
      | Grade | 2 |
    And I click on "Save" "button"
    And I should see "4/10" in the "div.totalgrade" "css_element"
    And I log out
    And I am on the "C1" Course page logged in as "student2"
    And I follow "Grades" in the user menu
    And I follow "Course 1"
    Then I should see "4.00" in the "Test scheduler" "table_row"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "teacher1"
    And I click on "Student 2" "text" in the "3:00 AM" "table_row"
    And I set the following fields to these values:
      | Grade | 8 |
    And I click on "Save" "button"
    And I should see "8/10" in the "div.totalgrade" "css_element"
    And I log out
    And I am on the "C1" Course page logged in as "student2"
    And I follow "Grades" in the user menu
    And I follow "Course 1"
    Then I should see "8.00" in the "Test scheduler" "table_row"
    And I log out

  Scenario: Teachers can switch from best grade to mean value of grades

    When I am on the "scheduler1" Activity page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I set the field "Grading strategy" to "Take the highest grade"
    And I press "Save and display"
    And I click on "Student 2" "text" in the "3:00 AM" "table_row"
    And I set the following fields to these values:
      | Grade | 3 |
    And I click on "Save" "button"
    And I should see "3/10" in the "div.totalgrade" "css_element"
    And I am on the "scheduler1" Activity page
    And I click on "Student 2" "text" in the "4:00 AM" "table_row"
    And I set the following fields to these values:
      | Grade | 9 |
    And I click on "Save" "button"
    And I should see "9/10" in the "div.totalgrade" "css_element"
    And I am on the "scheduler1" Activity page
    And I click on "Student 2" "text" in the "6:00 AM" "table_row"
    And I set the following fields to these values:
      | Grade | 4 |
    And I should see "9/10" in the "div.totalgrade" "css_element"
    And I click on "Save" "button"
    And I log out
    And I am on the "C1" Course page logged in as "student2"
    And I follow "Grades" in the user menu
    And I follow "Course 1"
    Then I should see "9.00" in the "Test scheduler" "table_row"
    And I log out

    When I am on the "scheduler1" Activity page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I set the field "Grading strategy" to "Take the mean grade"
    And I press "Save and display"
    And I am on the "C1" Course page logged in as "student2"
    And I follow "Grades" in the user menu
    And I follow "Course 1"
    Then I should see "5.33" in the "Test scheduler" "table_row"
    And I log out

  @javascript
  Scenario: Teachers can edit grades via the edit slot form

    When I am on the "scheduler1" Activity page logged in as "teacher1"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    And I set the field "Grading strategy" to "Take the highest grade"
    And I press "Save and display"
    And I click on "Edit" "icon" in the "3:00 AM" "table_row"
    And I set the following fields to these values:
      | grade[0] | 5 |
    And I click on "Save" "button"
    Then I should see "5/10" in the "3:00 AM" "table_row"
    And I click on "Student 2" "text" in the "3:00 AM" "table_row"
    And I should see "5/10" in the "div.totalgrade" "css_element"

    When I am on the "scheduler1" Activity page logged in as "teacher1"
    And I click on "Edit" "icon" in the "4:00 AM" "table_row"
    And I set the following fields to these values:
      | grade[0] | 7 |
    And I click on "Save" "button"
    Then I should see "5/10" in the "3:00 AM" "table_row"
    And I should see "7/10" in the "4:00 AM" "table_row"
    And I click on "Student 2" "text" in the "3:00 AM" "table_row"
    And I should see "7/10" in the "div.totalgrade" "css_element"

    When I am on the "scheduler1" Activity page logged in as "teacher1"
    And I click on "Edit" "icon" in the "4:00 AM" "table_row"
    And I set the following fields to these values:
      | grade[0] | 2 |
    And I click on "Save" "button"
    Then I should see "5/10" in the "3:00 AM" "table_row"
    And I should see "2/10" in the "4:00 AM" "table_row"
    And I click on "Student 2" "text" in the "3:00 AM" "table_row"
    And I should see "5/10" in the "div.totalgrade" "css_element"

    When I am on the "scheduler1" Activity page logged in as "teacher1"
    And I click on "Edit" "icon" in the "3:00 AM" "table_row"
    And I set the following fields to these values:
      | grade[0] | No grade |
    And I click on "Save" "button"
    Then I should not see "/10" in the "3:00 AM" "table_row"
    But I should see "2/10" in the "4:00 AM" "table_row"
    And I click on "Student 2" "text" in the "3:00 AM" "table_row"
    And I should see "2/10" in the "div.totalgrade" "css_element"
