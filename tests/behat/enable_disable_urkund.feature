@plugin @plagiarism @plagiarism_urkund
Feature: Enable Ouriginal
  In order to enable/disable plagiarism features
  As an Admin
  I need to be able to enable/disable the Ouriginal plugin

  Background:
    Given the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following config values are set as admin:
      | enableplagiarism | 1 |
    And the following config values are set as admin:
      | enabled | 1 | plagiarism_urkund |
      | api | https://secure.urkund.com | plagiarism_urkund |
      | username | 1 | plagiarism_urkund |
      | password | 1 | plagiarism_urkund |
      | enable_mod_assign | 1 | plagiarism_urkund |
      | enable_mod_forum | 1 | plagiarism_urkund |
      | enable_mod_workshop | 1 | plagiarism_urkund |

  @javascript
  Scenario: Disable Ouriginal
    Given I log in as "admin"
    And I navigate to "Plugins > Plagiarism > Ouriginal plagiarism plugin" in site administration
    Then the field "Enable Ouriginal" matches value "1"
    And the field "Enable Ouriginal for assign" matches value "1"
    And the field "Enable Ouriginal for forum" matches value "1"
    And the field "Enable Ouriginal for workshop" matches value "1"
    When I set the field "Enable Ouriginal" to "0"
    And I set the field "Enable Ouriginal for assign" to "0"
    And I set the field "Enable Ouriginal for forum" to "0"
    And I set the field "Enable Ouriginal for workshop" to "0"
    And I press "Save changes"
    Then the field "Enable Ouriginal" matches value "0"
    And the field "Enable Ouriginal for assign" matches value "0"
    And the field "Enable Ouriginal for forum" matches value "0"
    And the field "Enable Ouriginal for workshop" matches value "0"
