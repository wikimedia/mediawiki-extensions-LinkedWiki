@login
Feature: client_sparql

  Background:
    Given I am logged in
     
#Check if the process work    
  Scenario:Go to page that does not exist
    Given I am at page that does not exist
    Then link Create should be there

#Check if I can write in the wiki
 Scenario: Start a new page using the URL
    Given I am at page that does not exist
    When I click link Create
      And I enter article text
      And I click Save page button
    Then newly created page should open
      And page title should be there
      And page text should be there
      
Scenario: Create a table
    Given I am at page that does not exist
    When I click link Create
      And I enter the wikitext:
        """
{| class="wikitable sortable" 
|- 
!x!!y!!z
|- 
|1||2||3
|}
        """
      And I click Save page button
    Then newly created page should open
      And table should be there:
        | x | y | z |
        | 1 | 2 | 3 |
      
Scenario: Print a empty table with a query SPARQL
    Given I am at page that does not exist
    And I has a empty graph http://example.com/data in the triplestore http://192.168.1.100:8181
    When I click link Create
      And I enter the wikitext:
        """
{{#sparql:
SELECT * WHERE {
GRAPH <http://example.com/data> 
{ ?x ?y ?z . } 
}
|endpoint=http://192.168.1.100:8181/sparql/
}}
        """
      And I click Save page button
    Then newly created page should open
      And LinkedWiki's table should be there:
        | x | y | z |
        
Scenario: Print a table with a query SPARQL
    Given I am at page that does not exist
    And I has a empty graph http://example.com/data in the triplestore http://192.168.1.100:8181
    And I do this SPARQL query in the triplestore http://192.168.1.100:8181:
        """
PREFIX dc: <http://purl.org/dc/elements/1.1/>
INSERT DATA
{ GRAPH <http://example.com/data> {
  <http://example/book1> dc:title "Book 1" ;
                         dc:creator "A.N.Other1" .
  <http://example/book2> dc:title "Book 2" ;
                         dc:creator "A.N.Other2" .
}}
        """
    When I click link Create
      And I enter the wikitext:
        """
{{#sparql:
PREFIX dc: <http://purl.org/dc/elements/1.1/>
SELECT ?title ?creator WHERE {
GRAPH <http://example.com/data> 
{ ?x dc:title ?title ;
     dc:creator ?creator .
} 
} ORDER BY  ?title ?creator
|endpoint=http://192.168.1.100:8181/sparql/
}}
        """
      And I click Save page button
    Then newly created page should open
      And LinkedWiki's table should be there:
        | title | creator |
        | Book 1 | A.N.Other1 |
        | Book 2 | A.N.Other2 |
