@mod_scheduler
Feature: Students viewing slots available for booking
  In order to view slots that are available for booking
  As a student
  I need to have appopriate permissions in the student screen.

  Background:
    Given the following "users" exist:
      | username | firstname   | lastname | email                |
      | manager1 | Manager     | 1        | manager1@example.com | 
      | teacher1 | Teacher     | 1        | teacher1@example.com |
      | student1 | Student     | 1        | student1@example.com |
      | student2 | Student     | 2        | student2@example.com |
      | student3 | Student     | 3        | student3@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user  | course | role           |  
      | teacher1  | C1 | editingteacher |
      | student1  | C1 | student        |
      | student2  | C1 | student        |
      | student3  | C1 | student        |
    And the following "system role assigns" exist:
      | user     | role    |
      | manager1 | manager |
    And the following "activities" exist:
      | activity  | name           | intro | course | idnumber   | groupmode | schedulermode | maxbookings | guardtime |
      | scheduler | Test scheduler | n     | C1     | scheduler1 | 0         | oneonly       | 1           | 172800    |
    And I log in as "teacher1"
    And I follow "Course 1"
    # Slot 1 is available to only 1 student and is not yet booked  
    And I add a slot 5 days ahead at 0100 in "Test scheduler" scheduler and I fill the form with:
      | exclusivity | 1 |
    # Slot 2 is available to only 1 student and is already booked  
    And I add a slot 5 days ahead at 0200 in "Test scheduler" scheduler and I fill the form with:
      | exclusivity  | 1         |
      | studentid[0] | Student 3 |
    # Slot 3 is a group slot that is empty 
    And I add a slot 5 days ahead at 0300 in "Test scheduler" scheduler and I fill the form with:
      | exclusivity | 3        |
    # Slot 4 is a group slot that is partially booked 
    And I add a slot 5 days ahead at 0400 in "Test scheduler" scheduler and I fill the form with:
      | exclusivity  | 2         |
      | studentid[0] | Student 3 |
    # Slot 5 is an unlimited group slot that is empty 
    And I add a slot 5 days ahead at 0500 in "Test scheduler" scheduler and I fill the form with:
      | exclusivityenable | 0         |
    # Slot 6 is an unlimited group slot that is partially booked 
    And I add a slot 5 days ahead at 0600 in "Test scheduler" scheduler and I fill the form with:
      | exclusivityenable | 0         |
      | studentid[0]      | Student 3 |
    # Slot 7 is not yet available to students
    And I add a slot 5 days ahead at 0700 in "Test scheduler" scheduler and I fill the form with:
      | hideuntil[year] | 2040 |
    # Slot 8 is no longer available since the it's too close in the future
    And I add a slot 1 days ahead at 0800 in "Test scheduler" scheduler and I fill the form with:
      | appointmentlocation | My office |
    And I log out

  @javascript
  Scenario: A student can see only available upcoming slots (default setting)
           
    When I log in as "student1"
    And I follow "Course 1"    
    And I follow "Test scheduler"
    Then "Book slot" "button" should exist in the "1:00 AM" "table_row"
    And I should not see "2:00 AM" in the "slotbookertable" "table"
    And "Book slot" "button" should exist in the "3:00 AM" "table_row"
    And "Book slot" "button" should exist in the "4:00 AM" "table_row"
    And "Book slot" "button" should exist in the "5:00 AM" "table_row"
    And "Book slot" "button" should exist in the "6:00 AM" "table_row"
    And I should not see "7:00 AM" in the "slotbookertable" "table"
    And I should not see "8:00 AM" in the "slotbookertable" "table"

    When I click on "Book slot" "button" in the "1:00 AM" "table_row"
    Then "Cancel booking" "button" should exist
    And "slotbookertable" "table" should not exist

    When I click on "Cancel booking" "button"
    Then "Book slot" "button" should exist in the "1:00 AM" "table_row"
    
    When I click on "Book slot" "button" in the "4:00 AM" "table_row"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"    
    And I follow "Test scheduler"
    Then "Book slot" "button" should exist in the "3:00 AM" "table_row"
    And I should not see "4:00 AM" in the "slotbookertable" "table"
    And I log out

  @javascript
  Scenario: Students can view all slots, even full ones
    Given the following "permission overrides" exist:
      | capability                  | permission | role    | contextlevel | reference |
      | mod/scheduler:appoint       | Allow      | student | Course       | C1        |
      | mod/scheduler:viewslots     | Allow      | student | Course       | C1        |
      | mod/scheduler:viewfullslots | Allow      | student | Course       | C1        |
    
    When I log in as "student1"
    And I follow "Course 1"    
    And I follow "Test scheduler"
    Then "Book slot" "button" should exist in the "1:00 AM" "table_row"
    And I should see "2:00 AM" in the "slotbookertable" "table"
    Then "Book slot" "button" should not exist in the "2:00 AM" "table_row"
    And "Book slot" "button" should exist in the "3:00 AM" "table_row"
    And "Book slot" "button" should exist in the "4:00 AM" "table_row"
    And "Book slot" "button" should exist in the "5:00 AM" "table_row"
    And "Book slot" "button" should exist in the "6:00 AM" "table_row"
    And I should not see "7:00 AM" in the "slotbookertable" "table"
    And I should not see "8:00 AM" in the "slotbookertable" "table"

    When I click on "Book slot" "button" in the "1:00 AM" "table_row"
    Then "Cancel booking" "button" should exist
    And "slotbookertable" "table" should exist
    And I should not see "1:00 AM" in the "slotbookertable" "table"
    And "Book slot" "button" should not exist

    When I click on "Cancel booking" "button"
    Then "Book slot" "button" should exist in the "1:00 AM" "table_row"
    
    When I click on "Book slot" "button" in the "4:00 AM" "table_row"
    And I log out
    And I log in as "student2"
    And I follow "Course 1"    
    And I follow "Test scheduler"
    Then "Book slot" "button" should exist in the "3:00 AM" "table_row"
    And I should see "4:00 AM" in the "slotbookertable" "table"
    And "Book slot" "button" should not exist in the "4:00 AM" "table_row"
    And I log out
    
  @javascript
  Scenario: Students can view all slots, but they cannot book any
    Given the following "permission overrides" exist:
      | capability                  | permission | role    | contextlevel | reference |
      | mod/scheduler:appoint       | Prevent    | student | Course       | C1        |
      | mod/scheduler:viewslots     | Allow      | student | Course       | C1        |
      | mod/scheduler:viewfullslots | Allow      | student | Course       | C1        |
    
    When I log in as "student1"
    And I follow "Course 1"    
    And I follow "Test scheduler"
    Then "Book slot" "button" should not exist
    And I should see "1:00 AM" in the "slotbookertable" "table"
    And I should see "2:00 AM" in the "slotbookertable" "table"
    And I should see "3:00 AM" in the "slotbookertable" "table"
    And I should see "4:00 AM" in the "slotbookertable" "table"
    And I should see "5:00 AM" in the "slotbookertable" "table"
    And I should see "6:00 AM" in the "slotbookertable" "table"
    And I should not see "7:00 AM" in the "slotbookertable" "table"
    And I should not see "8:00 AM" in the "slotbookertable" "table"
    
    And I log out
    
 @javascript
 Scenario: Students can view bookable slots, but they cannot book any
    Given the following "permission overrides" exist:
      | capability                  | permission | role    | contextlevel | reference |
      | mod/scheduler:appoint       | Prevent    | student | Course       | C1        |
      | mod/scheduler:viewslots     | Allow      | student | Course       | C1        |
      | mod/scheduler:viewfullslots | Prevent    | student | Course       | C1        |
    
    When I log in as "student1"
    And I follow "Course 1"    
    And I follow "Test scheduler"
    Then "Book slot" "button" should not exist
    And I should see "1:00 AM" in the "slotbookertable" "table"
    And I should not see "2:00 AM" in the "slotbookertable" "table"
    And I should see "3:00 AM" in the "slotbookertable" "table"
    And I should see "4:00 AM" in the "slotbookertable" "table"
    And I should see "5:00 AM" in the "slotbookertable" "table"
    And I should see "6:00 AM" in the "slotbookertable" "table"
    And I should not see "7:00 AM" in the "slotbookertable" "table"
    And I should not see "8:00 AM" in the "slotbookertable" "table"
    
    And I log out
 