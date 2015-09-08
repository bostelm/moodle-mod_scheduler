@mod_scheduler
Feature: Entire groups can be booked into slots at once
  In order to allow booking of entire groups
  As a teacher
  I need to use a scheduler with group bookings

  Background:
    Given the following "users" exist:
      | username   | firstname      | lastname | email                  |
      | edteacher1 | Editingteacher | 1        | edteacher1@example.com |
      | neteacher1 | Nonedteacher   | 1        | neteacher1@example.com |
      | student1   | Student        | 1        | student1@example.com   |
      | student2   | Student        | 2        | student2@example.com   |
      | student3   | Student        | 3        | student3@example.com   |
      | student4   | Student        | 4        | student4@example.com   |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user       | course | role           |
      | edteacher1 | C1     | editingteacher |
      | neteacher1 | C1     | teacher        |
      | student1   | C1     | student        |
      | student2   | C1     | student        |
      | student3   | C1     | student        |
      | student4   | C1     | student        |
    And the following "groups" exist:
      | name     | course | idnumber |
      | Group A1 | C1     | GA1      |
      | Group A2 | C1     | GA2      |
      | Group B1 | C1     | GB1      |
      | Group B2 | C1     | GB2      |
   And the following "groupings" exist:
      | name       | course  | idnumber  |
      | Grouping A | C1      | GROUPINGA |
      | Grouping B | C1      | GROUPINGB |
    And the following "group members" exist:
      | user       | group |
      | neteacher1 | GB1   |
      | neteacher1 | GA1   |
      | student1   | GA1   |
      | student2   | GA1   |
      | student3   | GA2   |  
      | student4   | GA2   |
      | student1   | GB1   |
      | student2   | GB2   |
      | student3   | GB1   |  
      | student4   | GB2   |
    And the following "grouping groups" exist:
      | grouping  | group |
      | GROUPINGA | GA1   |
      | GROUPINGA | GA2   |
      | GROUPINGB | GB1   |
      | GROUPINGB | GB2   |
    And the following "activities" exist:
      | activity  | name                           | intro | course | idnumber   |
      | scheduler | Test scheduler no grouping     | n     | C1     | schedulern |
      | scheduler | Test scheduler grouping A      | n     | C1     | schedulera |
      | scheduler | Test scheduler grouping B      | n     | C1     | schedulerb |
    And I log in as "edteacher1"
    And I follow "Course 1"
    And I follow "Test scheduler no grouping"
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Booking in groups | Yes, for all groups |
    And I click on "Save and return to course" "button"
    And I follow "Test scheduler grouping A"
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Booking in groups | Yes, in grouping Grouping A |
    And I click on "Save and return to course" "button"
    And I follow "Test scheduler grouping B"
    And I follow "Edit settings"
    And I set the following fields to these values:
      | Booking in groups | Yes, in grouping Grouping B |
    And I click on "Save and return to course" "button"
    And I log out
    
  @javascript
  Scenario: Editing teachers can see and schedule relevant groups
    Given I log in as "edteacher1"
    And I follow "Course 1"

    When I follow "Test scheduler no grouping"
    Then I should see "Group A1" in the "groupstoschedule" "table"
    And I should see "Group A2" in the "groupstoschedule" "table"
    And I should see "Group B1" in the "groupstoschedule" "table"
    And I should see "Group B2" in the "groupstoschedule" "table"

    When I follow "Test scheduler grouping A"
    Then I should see "Group A1" in the "groupstoschedule" "table"
    And I should see "Group A2" in the "groupstoschedule" "table"
    And I should not see "Group B" in the "groupstoschedule" "table"

    When I follow "Test scheduler grouping B"
    Then I should not see "Group A" in the "groupstoschedule" "table"
    And I should see "Group B1" in the "groupstoschedule" "table"
    And I should see "Group B2" in the "groupstoschedule" "table"

    When I follow "Test scheduler no grouping"
    And I click on "Schedule" "link_or_button" in the "Group A1" "table_row"
    And I click on "Schedule in slot" "text" in the "Group A1" "table_row"
    And I click on "Save changes" "button"
    Then I should see "Student 1" in the "slotmanager" "table"
    And I should see "Student 2" in the "slotmanager" "table"
    And I should see "2 students still need to make an appointment"
    And I should not see "Group A1" in the "groupstoschedule" "table"
    And I should see "Group A2" in the "groupstoschedule" "table"
    And I should not see "Group B1" in the "groupstoschedule" "table"
    And I should not see "Group B2" in the "groupstoschedule" "table"
    
  @javascript
  Scenario: Students can book their entire group into a slot
    Given I log in as "edteacher1"
    And I follow "Course 1"
    And I follow "Test scheduler no grouping"
    And I add 8 slots 5 days ahead in "Test scheduler" scheduler and I fill the form with:
      | Location    | Large office |
      | exclusivity | 5            | 
    And I add 5 slots 6 days ahead in "Test scheduler" scheduler and I fill the form with:
      | Location    | Small office |
      | exclusivity | 1            | 
    And I log out
   
    When I log in as "student1"
    And I follow "Course 1"
    And I follow "Test scheduler no grouping"
    Then the "appointgroup" select box should contain "Myself"
    And the "appointgroup" select box should contain "Group A1"       
    And the "appointgroup" select box should contain "Group B1"       
    And the "appointgroup" select box should not contain "Group A2"       
    And the "appointgroup" select box should not contain "Group B2"       
   
    When I set the field "appointgroup" to "Group A1"
    And I click on "Book slot" "button" in the "8:00 AM" "table_row"
    Then I should see "8:00 AM" in the "Large office" "table_row"
    And I log out

    When I log in as "edteacher1"
    And I follow "Course 1"
    And I follow "Test scheduler no grouping"
    Then I should see "Student 1" in the "8:00 AM" "table_row"
    And I should see "Student 2" in the "8:00 AM" "table_row"
    And I should see "2 students still need to make an appointment"
    And I should not see "Group A1" in the "groupstoschedule" "table"
    And I should see "Group A2" in the "groupstoschedule" "table"
    And I should not see "Group B1" in the "groupstoschedule" "table"
    And I should not see "Group B2" in the "groupstoschedule" "table"
    And I log out
    
   