@mod @mod_scheduler
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

  Scenario: A student can see only available upcoming slots (default setting)

    When I am on the "scheduler1" Activity page logged in as "student1"
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

    When I am on the "scheduler1" Activity page logged in as "student2"
    Then "Book slot" "button" should exist in the "3:00 AM" "table_row"
    And I should not see "4:00 AM" in the "slotbookertable" "table"
    And I log out

  Scenario: Students can view all slots, even full ones
    Given the following "permission overrides" exist:
      | capability                  | permission | role    | contextlevel | reference |
      | mod/scheduler:appoint       | Allow      | student | Course       | C1        |
      | mod/scheduler:viewslots     | Allow      | student | Course       | C1        |
      | mod/scheduler:viewfullslots | Allow      | student | Course       | C1        |

    When I am on the "scheduler1" Activity page logged in as "student1"
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

    When I am on the "scheduler1" Activity page logged in as "student2"
    Then "Book slot" "button" should exist in the "3:00 AM" "table_row"
    And I should see "4:00 AM" in the "slotbookertable" "table"
    And "Book slot" "button" should not exist in the "4:00 AM" "table_row"
    And I log out

  Scenario: Students can view all slots, but they cannot book any
    Given the following "permission overrides" exist:
      | capability                  | permission | role    | contextlevel | reference |
      | mod/scheduler:appoint       | Prevent    | student | Course       | C1        |
      | mod/scheduler:viewslots     | Allow      | student | Course       | C1        |
      | mod/scheduler:viewfullslots | Allow      | student | Course       | C1        |

    When I am on the "scheduler1" Activity page logged in as "student1"
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

  Scenario: Students can view bookable slots, but they cannot book any
    Given the following "permission overrides" exist:
      | capability                  | permission | role    | contextlevel | reference |
      | mod/scheduler:appoint       | Prevent    | student | Course       | C1        |
      | mod/scheduler:viewslots     | Allow      | student | Course       | C1        |
      | mod/scheduler:viewfullslots | Prevent    | student | Course       | C1        |

    When I am on the "scheduler1" Activity page logged in as "student1"
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
