@mod @mod_checkmark @javascript
Feature: Configure Checkmark random selection application
  In order to control which examples are assigned by random presentation selection
  As a teacher
  I need to select examples from the current Checkmark activity

  Scenario: Teacher configures the field of application for random presentation selection
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity  | course | idnumber | name           | presentationgrading | presentationgrade | examplecount | examplestart | grade |
      | checkmark | C1     | CMMAIN   | Main Checkmark | 1                   | 100               | 4            | 6            | 80    |
    When I am on the "CMMAIN" Activity page logged in as teacher1
    And I follow "Start random selection for presentation"
    And I click on "//button[@aria-controls='checkmark-randomselect-fieldofapplication']" "xpath_element"
    Then I should see "Examples from this checkmark activity" in the "#checkmark-randomselect-fieldofapplication" "css_element"
    And I should see "Number of examples per student" in the "#checkmark-randomselect-fieldofapplication" "css_element"
    And I should see "Example 6 (20 Points)" in the "#checkmark-randomselect-fieldofapplication" "css_element"
    And I should see "Example 7 (20 Points)" in the "#checkmark-randomselect-fieldofapplication" "css_element"
    And I should see "Example 8 (20 Points)" in the "#checkmark-randomselect-fieldofapplication" "css_element"
    And I should see "Example 9 (20 Points)" in the "#checkmark-randomselect-fieldofapplication" "css_element"
    And the field "checkmark-randomselect-example-selection-all" matches value "1"
    And the field "Example 6 (20 Points)" matches value "1"
    And the "Example 6 (20 Points)" "checkbox" should be disabled
    And the field "Number of examples per student" matches value "1"
    And the "Number of examples per student" select box should contain "4"
    When I click on "#checkmark-randomselect-example-selection-selected" "css_element"
    Then the "Example 6 (20 Points)" "checkbox" should be enabled
    When I click on "None" "link" in the "#checkmark-randomselect-fieldofapplication" "css_element"
    Then the field "checkmark-randomselect-example-selection-selected" matches value "1"
    And the field "Example 6 (20 Points)" matches value "0"
    And the "Number of examples per student" "select" should be disabled
    When I click on "Example 6 (20 Points)" "checkbox" in the "#checkmark-randomselect-fieldofapplication" "css_element"
    Then the "Number of examples per student" "select" should be enabled
    And the field "Number of examples per student" matches value "1"
    And the "Number of examples per student" select box should not contain "2"
    When I click on "All" "link" in the "#checkmark-randomselect-fieldofapplication" "css_element"
    Then the field "Example 6 (20 Points)" matches value "1"
    And the field "Example 9 (20 Points)" matches value "1"
    And the "Number of examples per student" select box should contain "4"
