@mod_scheduler @wip
Feature: Booking of appointments with individual tutors per group
  In order to organize appointments with the students in my group
  As a tutor
  I can use a scheduler to let students choose a time slot.

  Background:
    Given the following "users" exist:
      | username  | firstname   | lastname | email                 |
      | manager1  | Manager     | 1        | manager1@example.com  | 
      | coor1     | Coordinator | 1        | coor1@example.com     |
      | tutor2    | Tutor       | 2        | tutor2@example.com    |
      | tutor3    | Tutor       | 3        | tutor3@example.com    |
      | student1a | Student     | 1a       | student1a@example.com |
      | student1b | Student     | 1b       | student1b@example.com |
      | student2a | Student     | 2a       | student2a@example.com |
      | student2b | Student     | 2b       | student2b@example.com |
      | student3a | Student     | 3a       | student3a@example.com |
      | student3b | Student     | 3b       | student3b@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exist:
      | user  | course | role           |  
      | coor1     | C1 | editingteacher |
      | tutor2    | C1 | teacher        |
      | tutor3    | C1 | teacher        |
      | student1a | C1 | student        |
      | student1b | C1 | student        |
      | student2a | C1 | student        |
      | student2b | C1 | student        |
      | student3a | C1 | student        |
      | student3b | C1 | student        |
    And the following "groups" exist:
      | name    | course | idnumber |
      | Group 1 | C1     | G1       |
      | Group 2 | C1     | G2       |
      | Group 3 | C1     | G3       |
    And the following "group members" exist:
      | user       | group |
      | coor1      | G1    |
      | tutor2     | G2    |
      | tutor3     | G3    |
      | student1a  | G1    |
      | student1b  | G1    |
      | student2a  | G2    |
      | student2b  | G2    |
      | student3a  | G3    |
      | student3b  | G3    |
    And the following "system role assigns" exist:
      | user     | role    |
      | manager1 | manager |
    And the following "permission overrides" exist:
      | capability                  | permission | role    | contextlevel | reference |
      | moodle/site:accessallgroups | Prevent    | teacher | Course       | C1        |
    And the following "activities" exist:
      | activity  | name           | intro | course | idnumber   | groupmode | schedulermode | maxbookings |
      | scheduler | Tutor sessions | n     | C1     | scheduler1 | 1         | oneonly       | 0           |
    And I add the upcoming events block globally

  @javascript
  Scenario: A tutor adds slots, and students book them
    When I log in as "tutor2"
    And I follow "Course 1"
    And I add 10 slots 5 days ahead in "Tutor sessions" scheduler and I fill the form with:
      | Location | My office |
    Then I should see "10 slots have been added"
    And I should see "2 students still need to make an appointment"
    And I should see "Student 2a" in the "studentstoschedule" "table"
    And I should see "Student 2b" in the "studentstoschedule" "table"
    And I log out
           
    When I log in as "student2a"
    And I follow "Course 1"    
    And I follow "Tutor sessions"
    Then I should see "1:00 AM" in the "slotbookertable" "table"
    And I should see "10:00 AM" in the "slotbookertable" "table"
    When I click on "Book slot" "button" in the "2:00 AM" "table_row"
    Then "Cancel booking" "button" should exist
    And I should see "Meeting with your Teacher, Tutor 2" in the "Upcoming events" "block"
    And I log out
    
    When I log in as "student2b"
    And I follow "Course 1"    
    And I follow "Tutor sessions"
    Then I should see "1:00 AM" in the "slotbookertable" "table"
    And I should not see "2:00 AM" in the "slotbookertable" "table"
    And I should see "10:00 AM" in the "slotbookertable" "table"
    When I click on "Book slot" "button" in the "5:00 AM" "table_row" 
    Then "Cancel booking" "button" should exist
    And I should see "Meeting with your Teacher, Tutor 2" in the "Upcoming events" "block"
    And I log out
    
    When I log in as "tutor2"
    And I follow "Course 1"    
    And I follow "Tutor sessions"
    Then I should see "1:00 AM" in the "slotmanager" "table"
    And I should see "Student 2a" in the "2:00 AM" "table_row"
    And I should see "Student 2b" in the "5:00 AM" "table_row"
    And I should see "10:00 AM" in the "slotmanager" "table"
    And I should see "Meeting with your Student, Student 2a" in the "Upcoming events" "block"
    And I should see "Meeting with your Student, Student 2b" in the "Upcoming events" "block"
    And I should see "2 students still need to make an appointment"
    And I log out

  @javascript
  Scenario: Several tutors add slots, they can be seen only by relevant users
    When I log in as "coor1"
    And I follow "Course 1"
    And I add 10 slots 5 days ahead in "Tutor sessions" scheduler and I fill the form with:
      | Location | Office 1 |
    Then I should see "10 slots have been added"
    And I should see "2 students still need to make an appointment"
    And I should see "Student 1a" in the "studentstoschedule" "table"
    And I should see "Student 1b" in the "studentstoschedule" "table"
    And I log out

    When I log in as "tutor2"
    And I follow "Course 1"
    And I add 10 slots 5 days ahead in "Tutor sessions" scheduler and I fill the form with:
      | Location | Office 2 |
    Then I should see "10 slots have been added"
    And I should see "2 students still need to make an appointment"
    And I should see "Student 2a" in the "studentstoschedule" "table"
    And I should see "Student 2b" in the "studentstoschedule" "table"
    And I log out

    When I log in as "tutor3"
    And I follow "Course 1"
    And I add 10 slots 5 days ahead in "Tutor sessions" scheduler and I fill the form with:
      | Location | Office 2 |
    Then I should see "10 slots have been added"
    And I should see "2 students still need to make an appointment"
    And I should see "Student 3a" in the "studentstoschedule" "table"
    And I should see "Student 3b" in the "studentstoschedule" "table"
    And I log out

    When I log in as "student1a"
    And I follow "Course 1"    
    And I follow "Tutor sessions"
    Then I should see "1:00 AM" in the "slotbookertable" "table"
    And I should see "10:00 AM" in the "slotbookertable" "table"
    And I should see "Coordinator 1" in the "slotbookertable" "table"
    And I should not see "Tutor 2" in the "slotbookertable" "table"
    And I should not see "Tutor 3" in the "slotbookertable" "table"
    When I click on "Book slot" "button" in the "1:00 AM" "table_row"
    Then "Cancel booking" "button" should exist
    And I should see "Meeting with your Teacher, Coordinator 1" in the "Upcoming events" "block"
    And I log out

    When I log in as "student2a"
    And I follow "Course 1"    
    And I follow "Tutor sessions"
    Then I should see "1:00 AM" in the "slotbookertable" "table"
    And I should see "10:00 AM" in the "slotbookertable" "table"
    And I should not see "Coordinator 1" in the "slotbookertable" "table"
    And I should see "Tutor 2" in the "slotbookertable" "table"
    And I should not see "Tutor 3" in the "slotbookertable" "table"
    When I click on "Book slot" "button" in the "2:00 AM" "table_row"
    Then "Cancel booking" "button" should exist
    And I should see "Meeting with your Teacher, Tutor 2" in the "Upcoming events" "block"
    And I log out
    
    When I log in as "coor1"
    And I follow "Course 1"    
    And I follow "Tutor sessions"
    Then I should see "Student 1a" in the "slotmanager" "table"
    And I should not see "Student 1b" in the "slotmanager" "table"
    And I should not see "Student 2a" in the "slotmanager" "table"
    And I should see "Student 1a" in the "Upcoming events" "block"
    And I should not see "Student 1b" in the "Upcoming events" "block"
    And I should not see "Student 2a" in the "Upcoming events" "block"
    When I follow "All appointments"
    Then I should see "Student 1a" in the "slotmanager" "table"
    And I should not see "Student 1b" in the "slotmanager" "table"
    And I should see "Student 2a" in the "slotmanager" "table"    
    And I log out
    
    When I log in as "tutor2"
    And I follow "Course 1"    
    And I follow "Tutor sessions"
    Then I should not see "Student 1a" in the "slotmanager" "table"
    And I should not see "Student 1b" in the "slotmanager" "table"
    And I should see "Student 2a" in the "slotmanager" "table"
    And I should not see "Student 1a" in the "Upcoming events" "block"
    And I should see "Student 2a" in the "Upcoming events" "block"
    And I should not see "Student 2b" in the "Upcoming events" "block"
    When I follow "All appointments"
    Then I should not see "Student 1a" in the "slotmanager" "table"
    And I should not see "Student 1b" in the "slotmanager" "table"
    And I should see "Student 2a" in the "slotmanager" "table"    
    And I log out
    
    When I log in as "tutor3"
    And I follow "Course 1"    
    And I follow "Tutor sessions"
    Then I should not see "Student 1a" in the "slotmanager" "table"
    And I should not see "Student 1b" in the "slotmanager" "table"
    And I should not see "Student 2a" in the "slotmanager" "table"
    When I follow "All appointments"
    Then I should not see "Student 1a" in the "slotmanager" "table"
    And I should not see "Student 1b" in the "slotmanager" "table"
    And I should not see "Student 2a" in the "slotmanager" "table"    
    And I log out
    
    When I log in as "manager1"
    And I follow "Courses"
    And I follow "Course 1"    
    And I follow "Tutor sessions"
    And I follow "Statistics"
    And I follow "My appointments"
    Then "slotmanager" "table" should not exist
    And I should see "No students available for scheduling"
    When I follow "All appointments"
    Then I should see "Student 1a" in the "slotmanager" "table"
    And I should not see "Student 1b" in the "slotmanager" "table"
    And I should see "Student 2a" in the "slotmanager" "table"    
    And I log out
    