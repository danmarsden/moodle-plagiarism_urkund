@plugin @plagiarism @plagiarism_urkund
Feature: Enable Ouriginal
  In order to enable/disable plagiarism features
  As an Admin
  I need to be able to enable/disable the Ouriginal plugin

  Background:
    Given I log in as "admin"
    And I navigate to "Advanced features" in site administration
    And I set the field "Enable plagiarism plugins" to "1"
    And I press "Save changes"

  @javascript
  Scenario: Enable Urkund
    Given I navigate to "Plugins > Plagiarism > Ouriginal plagiarism plugin" in site administration
    When I set the field "Enable Ouriginal" to "1"
    And I set the field "Username" to "1"
    And I set the field "Password" to "1"
    And I set the field "Enable Ouriginal for assign" to "1"
    And I set the field "Enable Ouriginal for forum" to "1"
    And I set the field "Enable Ouriginal for workshop" to "1"
    And I press "Save changes"
    Then the field "Enable Ouriginal" matches value "1"
    And the field "Enable Ouriginal for assign" matches value "1"
    And the field "Enable Ouriginal for forum" matches value "1"
    And the field "Enable Ouriginal for workshop" matches value "1"

  @javascript
  Scenario: Disable Ouriginal
    Given I navigate to "Plugins > Plagiarism > Ouriginal plagiarism plugin" in site administration
    When I set the field "Enable Ouriginal" to "0"
    And I set the field "Enable Ouriginal for assign" to "0"
    And I set the field "Enable Ouriginal for forum" to "0"
    And I set the field "Enable Ouriginal for workshop" to "0"
    And I press "Save changes"
    Then the field "Enable Ouriginal" matches value "0"
    And the field "Enable Ouriginal for assign" matches value "0"
    And the field "Enable Ouriginal for forum" matches value "0"
    And the field "Enable Ouriginal for workshop" matches value "0"
