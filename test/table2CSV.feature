@login
Feature: table2CSV
  Background:
    Given I am logged in
      And I has a empty graph http://example.com/data in the triplestore http://192.168.1.40:8181
      And I do this SPARQL query in the triplestore http://192.168.1.40:8181:
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
     
#Check export CSV  
Scenario: Export CSV file of LinkedWiki's table
    Given I am at page that does not exist
    When I click link Create source
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
|endpoint=http://192.168.1.40:8181/sparql/
}}
        """
      And I click Save page button
      And I click link refresh
      And I click link export CSV
      And I confirm popup
    Then file CSV should be there:
        | title | creator |
        | Book 1 | A.N.Other1 |
        | Book 2 | A.N.Other2 |
      
      
