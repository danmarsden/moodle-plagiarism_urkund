@plugin @plagiarism @plagiarism_urkund
Feature: Enable Urkund
  In order to enable/disable plagiarism features
  As an Admin
  I need to be able to enable/disable the Urkund plugin

  Background:
    Given I log in as "admin"
    And I navigate to "Advanced features" node in "Site administration"
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"

  @javascript
  Scenario: Enable Urkund
    Given I navigate to "URKUND plagiarism plugin" node in "Site administration>Plugins>Plagiarism"
    When I set the field "Enable URKUND" to "1"
    And I set the field "Username" to "1"
    And I set the field "Password" to "1"
    And I set the field "Enable URKUND for assign" to "1"
    And I set the field "Enable URKUND for forum" to "1"
    And I set the field "Enable URKUND for workshop" to "1"
    And I press "Save changes"
    Then the field "Enable URKUND" matches value "1"
    And the field "Enable URKUND for assign" matches value "1"
    And the field "Enable URKUND for forum" matches value "1"
    And the field "Enable URKUND for workshop" matches value "1"

  @javascript
  Scenario: Disable URKUND
    Given I navigate to "URKUND plagiarism plugin" node in "Site administration>Plugins>Plagiarism"
    When I set the field "Enable URKUND" to "0"
    And I set the field "Enable URKUND for assign" to "0"
    And I set the field "Enable URKUND for forum" to "0"
    And I set the field "Enable URKUND for workshop" to "0"
    And I press "Save changes"
    Then the field "Enable URKUND" matches value "0"
    And the field "Enable URKUND for assign" matches value "0"
    And the field "Enable URKUND for forum" matches value "0"
    And the field "Enable URKUND for workshop" matches value "0"