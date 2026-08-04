@javascript @mod @mod_scheduler
Feature: Testing overview integration in scheduler activity
  In order to summarize the scheduler activity
  As a user
  I need to be able to see the scheduler activity overview

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
    And the following "system role assigns" exist:
      | user     | role    |
      | manager1 | manager |
    And the following "activities" exist:
      | activity  | name           | intro | course | idnumber   | groupmode | schedulermode | maxbookings | guardtime |
      | scheduler | Test scheduler | n     | C1     | scheduler1 | 0         | oneonly       | 1           | 172800    |
    And the following "mod_scheduler > slots" exist:
      | scheduler  | starttime            | duration | teacher   | exclusivity | student  | hideuntil       |
    # Slot 1 is available to only 1 student and is not yet booked
      | scheduler1 | ##+5 days 1:00am##    | 45       | teacher1  | 1           |          |                 |
    # Slot 2 is available to only 1 student and is already booked
      | scheduler1 | ##+5 days 2:00am##    | 45       | teacher1  | 1           | student3 |                 |
    # Slot 3 is a group slot that is empty
      | scheduler1 | ##+5 days 3:00am##    | 45       | teacher1  | 3           |          |                 |
    # Slot 4 is a group slot that is partially booked
      | scheduler1 | ##+5 days 4:00am##    | 45       | teacher1  | 2           | student3 |                 |
    # Slot 5 is an unlimited group slot that is empty
      | scheduler1 | ##+5 days 5:00am##    | 45       | teacher1  | 0           |          |                 |
    # Slot 6 is an unlimited group slot that is partially booked
      | scheduler1 | ##+5 days 6:00am##    | 45       | teacher1  | 0           | student3 |                 |
    # Slot 7 is not yet available to students
      | scheduler1 | ##+5 days 7:00am##    | 45       | teacher1  | 0           |          | ##now +2years## |
    # Slot 8 is no longer available since the it's too close in the future
      | scheduler1 | ##tomorrow 8:00am##  | 45       | teacher1  | 0           |          |                 |

  Scenario: The Scheduler activity index redirect to the activities overview
    Given the site is running Moodle version 5.0 or higher
    When I am on the "C1" "course > activities > scheduler" page logged in as "admin"
    Then I should see "Name" in the "scheduler_overview_collapsible" "region"
    And I should see "Actions" in the "scheduler_overview_collapsible" "region"
    And I should see "Test scheduler"

  Scenario: View a group scheduler in the activities overview
    Given the site is running Moodle version 5.0 or higher
    And the following "activities" exist:
      | activity  | name                 | intro | course | idnumber     | groupmode |
      | scheduler | Group test scheduler | n     | C1     | schedulerVis | 2         |
    And I log in as "teacher1"
    And I add 5 slots 11 days ahead in "schedulerVis" scheduler and I fill the form with:
      | Location  | Here |
    When I am on the "C1" "course > activities > scheduler" page logged in as "admin"
    Then I should see "An overview of all activities in the course"
    And I should see "Name" in the "scheduler_overview_collapsible" "region"
    And I should see "Actions" in the "scheduler_overview_collapsible" "region"
    And I should see "Group test scheduler"
