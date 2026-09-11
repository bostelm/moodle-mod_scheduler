@mod @mod_scheduler @membervisibility
  Feature: Control the visibility of students booked into Scheduler slots
    In order to protect the information of students
    As a teacher
    I need to control who can see the members of a slot

    Background:
      Given the following "users" exist:
        | username   | firstname      | lastname | email                  |
        | teacher1   | Teacher        | 1        | teacher1@example.com |
        | student1   | Student        | 1        | student1@example.com   |
        | student2   | Student        | 2        | student2@example.com   |
        | student3   | Student        | 3        | student3@example.com   |
      And the following "courses" exist:
        | fullname | shortname | category |
        | Course 1 | C1        | 0        |
      And the following "course enrolments" exist:
        | user       | course | role           |
        | teacher1   | C1     | editingteacher |
        | student1   | C1     | student        |
        | student2   | C1     | student        |
        | student3   | C1     | student        |
      And the following "groups" exist:
        | name    | course | idnumber |
        | Group A | C1     | GA       |
        | Group B | C1     | GB       |
      And the following "group members" exist:
        | user       | group |
        | student1   | GA    |
        | student2   | GA    |
        | student3   | GB    |
      And the following "activities" exist:
        | activity  | name           | course | idnumber  |
        | scheduler | Test Scheduler | C1     | scheduler |
      And I am on the "scheduler" Activity page logged in as teacher1
      And I navigate to "Settings" in current page administration
      And I set the following fields to these values:
        | Booking in groups         | Yes, for all groups          |
        | Default member visibility | Only visible to slot members |
      And I click on "Save and return to course" "button"
      And I add a slot 5 days ahead at 800 in "scheduler" scheduler and I fill the form with:
        | Location                      | Large office                 |
        | exclusivity                   | 5                            |
      And I log out

    Scenario: Slot members can see each other
      When I am on the "scheduler" Activity page logged in as student1
      And I select "Group A" from the "appointgroup" singleselect
      And I click on "Book slot" "button" in the "8:00 AM" "table_row"
      Then I should see "Cancel booking" in the "8:00 AM" "table_row"
      And I should see "Student 2" in the "8:00 AM" "table_row"

    Scenario: Students outside the slot cannot see the members
      Given I am on the "scheduler" Activity page logged in as student1
      And I select "Group A" from the "appointgroup" singleselect
      And I click on "Book slot" "button" in the "8:00 AM" "table_row"
      And I log out

      When I am on the "scheduler" Activity page logged in as student3
      Then I should not see "Student 1" in the "8:00 AM" "table_row"
      And I should not see "Student 2" in the "8:00 AM" "table_row"

    Scenario: Slot members cannot see other members of an anonymous slot
      Given I am on the "scheduler" Activity page logged in as teacher1
      And I add a slot 5 days ahead at 900 in "scheduler" scheduler and I fill the form with:
        | Location                      | Large office |                      
        | exclusivity                   | 5            |
        | Member visibility of the slot | Anonymous    |
      And I log out

      When I am on the "scheduler" Activity page logged in as student1
      And I select "Group A" from the "appointgroup" singleselect
      And I click on "Book slot" "button" in the "9:00 AM" "table_row"
      Then I should see "Cancel booking" in the "9:00 AM" "table_row"
      And I should not see "Student 2" in the "9:00 AM" "table_row"

    Scenario: All course students can see the members of a slot if the slot member visibility is set to "Visible"
      Given I am on the "scheduler" Activity page logged in as teacher1
      And I add a slot 5 days ahead at 1000 in "scheduler" scheduler and I fill the form with:
        | Location                      | Large office |
        | exclusivity                   | 5            |
        | Member visibility of the slot | Visible      |

      When I am on the "scheduler" Activity page logged in as student1
      And I select "Group A" from the "appointgroup" singleselect
      And I click on "Book slot" "button" in the "10:00 AM" "table_row"
      Then I should see "Cancel booking" in the "10:00 AM" "table_row"
      And I should see "Student 2" in the "10:00 AM" "table_row"
      And I log out

      When I am on the "scheduler" Activity page logged in as student3
      Then I should see "Student 1" in the "10:00 AM" "table_row"
      And I should see "Student 2" in the "10:00 AM" "table_row"