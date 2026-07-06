@mod @mod_checkmark @javascript
Feature: Preview and apply Checkmark random presentation selection
  In order to check a random presentation selection before it is saved
  As a teacher
  I need to preview and apply selected students for presentation

  Scenario: Teacher previews and applies a random presentation selection
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | One      | student1@example.com |
      | student2 | Student   | Two      | student2@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |
      | student2 | C1     | student        |
    And the following "activities" exist:
      | activity  | course | idnumber | name           | presentationgrading | presentationgrade | examplecount | grade |
      | checkmark | C1     | CMMAIN   | Main Checkmark | 1                   | 100               | 2            | 20    |
    And the following "mod_checkmark > submissions" exist:
      | checkmark      | user     | example1 | example2 |
      | Main Checkmark | student1 | 1        | 0        |
      | Main Checkmark | student2 | 0        | 0        |
    When I am on the "CMMAIN" Activity page logged in as teacher1
    And I follow "Start random selection for presentation"
    Then I should see "No preview available!"
    When I press "Create new preview"
    Then I should see "1 Example(s) cannot be pre-selected for students"
    And I should see "Example 2"
    And I should see "1 Example(s) can be pre-selected for 1 students"
    And I should see "Example 1"
    And I should see "Student One"
    When I press "Apply random selection"
    Then I should see "1 example(s) successfully assigned to 1 students"
    And I should see "Student One"
    And I should not see "Student Two"
    And I should see "[Random selected]:"
