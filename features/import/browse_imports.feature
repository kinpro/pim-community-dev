@javascript
Feature: Browse imports
  In order to view the list of import jobs that have been created
  As an user
  I need to be able to view a list of them

  Scenario: Successfully display all the import jobs
    Given the following jobs:
      | connector | alias            | code                  | label                       | type   |
      | Akeneo    | product_export   | acme_product_export   | Product export for Acme.com | export |
      | Akeneo    | attribute_export | acme_attribute_export | Attribute export            | export |
      | Akeneo    | product_export   | foo_product_export    | Product export for foo      | export |
      | Akeneo    | product_import   | acme_product_import   | Product import for Acme.com | import |
    Given I am logged in as "admin"
    And I am on the imports index page
    And the grid should contain 1 element
    And the grid should not contains the elements "acme_product_export", "acme_attribute_export" and "foo_product_export"
    And the grid should contains the elements "acme_product_import"
    And the column "connector" of the row "acme_product_import" should contains the value "Akeneo"
    And I should see the filters "Code", "Label", "Job", "Connector" and "Status"