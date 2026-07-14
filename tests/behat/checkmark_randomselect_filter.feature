@mod @mod_checkmark @javascript
Feature: Configure Checkmark random selection filters
  In order to control the data basis for random presentation selection
  As a teacher
  I need to select Checkmark activities and presentation inclusion criteria

  Scenario: Teacher configures filter criteria for random presentation selection
    Given the following config values are set as admin:
      | includeexistingpresentations | 1 | checkmark_randomselect |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
      | Course 2 | C2        | 0        |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity  | course | idnumber | name                   | visible | presentationgrading | presentationgrade |
      | checkmark | C1     | CMMAIN   | Zulu Checkmark         | 1       | 1                   | 100               |
      | checkmark | C1     | CMALPHA  | Alpha Checkmark        | 1       | 1                   | 100               |
      | checkmark | C1     | CMNOPRES | No Presentation        | 1       | 0                   | 0                 |
      | checkmark | C1     | CMHIDDEN | Hidden Checkmark       | 0       | 1                   | 100               |
      | checkmark | C2     | CMOTHER  | Other Course Checkmark | 1       | 1                   | 100               |
    When I am on the "CMMAIN" Activity page logged in as teacher1
    And I follow "Start random selection for presentation"
    And I click on "//button[@aria-controls='checkmark-randomselect-filtercriteria']" "xpath_element"
    Then I should see "Checkmark activities from this course" in the "#checkmark-randomselect-filtercriteria" "css_element"
    And I should see "Include participants with existing presentation" in the "#checkmark-randomselect-filtercriteria" "css_element"
    And I should see "Alpha Checkmark" in the "#checkmark-randomselect-filtercriteria" "css_element"
    And I should see "Zulu Checkmark" in the "#checkmark-randomselect-filtercriteria" "css_element"
    And I should see "Current activity" in the "//label[contains(., 'Zulu Checkmark')]" "xpath_element"
    And I should not see "Current activity" in the "//label[contains(., 'Alpha Checkmark')]" "xpath_element"
    And I should not see "No Presentation" in the "#checkmark-randomselect-filtercriteria" "css_element"
    And I should not see "Hidden Checkmark" in the "#checkmark-randomselect-filtercriteria" "css_element"
    And I should not see "Other Course Checkmark" in the "#checkmark-randomselect-filtercriteria" "css_element"
    And the field "checkmark-randomselect-checkmark-selection-all" matches value "1"
    And the field "Alpha Checkmark" matches value "1"
    And the "Alpha Checkmark" "checkbox" should be disabled
    And the field "Zulu Checkmark" matches value "1"
    And the field "Include participants with existing presentation" matches value "Yes"
    When I click on "None" "link" in the "#checkmark-randomselect-filtercriteria" "css_element"
    Then the field "checkmark-randomselect-checkmark-selection-selected" matches value "1"
    And the field "Alpha Checkmark" matches value "0"
    And the "Alpha Checkmark" "checkbox" should be enabled
    And the field "Zulu Checkmark" matches value "0"
    When I click on "All" "link" in the "#checkmark-randomselect-filtercriteria" "css_element"
    Then the field "Alpha Checkmark" matches value "1"
    And the field "Zulu Checkmark" matches value "1"

  Scenario: Teacher cannot start random presentation selection without presentation grading
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
      | activity  | course | idnumber | name                  | presentationgrading |
      | checkmark | C1     | CMNOPRES | Checkmark without pres | 0                   |
    When I am on the "CMNOPRES" Activity page logged in as teacher1
    Then I should not see "Start random selection for presentation"
