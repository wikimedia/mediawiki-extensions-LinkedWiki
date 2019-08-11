<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
 */

class DatabaseExampleMethod extends StorageMethodAbstract
{
    private $graphNamed = "http://databaseExample";

    public function setGraph($graphNamed)
    {
        $graphNamed = trim($graphNamed);
        $this->graphNamed = $graphNamed;
    }

    public function getGraph()
    {
        return $this->graphNamed;
    }

    public function getQueryReadStringWithTagLang()
    {
        return <<<EOT
SELECT DISTINCT  ?value  
WHERE 
    {GRAPH <$this->graphNamed>  
        { 
            ?subject ?property ?value . 
        } 
    FILTER langMatches( lang(?value), ?lang )
    }
EOT;
    }

    public function getQueryReadStringWithoutTagLang()
    {
        return <<<EOT
SELECT DISTINCT  ?value  
WHERE 
    {GRAPH <$this->graphNamed>  
        { 
            ?subject ?property ?value . 
        } 
    FILTER ( lang(?value) = "" )
    }
EOT;
    }

    public function getQueryReadValue()
    {
        return <<<EOT
SELECT DISTINCT  ?value  
WHERE 
    {GRAPH <$this->graphNamed>  
        { 
            ?subject ?property ?value . 
        } 
    }
EOT;
    }

    public function getQueryInsertValue()
    {
        return <<<EOT
INSERT DATA 
    {GRAPH <$this->graphNamed>  
        { 
            ?subject ?property ?value . 
        } 
    }
EOT;
    }

    public function getQueryDeleteSubject()
    {
        return <<<EOT
DELETE  
    {GRAPH <$this->graphNamed>  
        { ?subject ?property ?value . } 
    } 
WHERE 
    {GRAPH <$this->graphNamed>  
        { ?subject ?property ?value . } 
    }
EOT;
    }

    public function getQueryLoadData($url)
    {
        return <<<EOT
LOAD <$url> INTO GRAPH <$this->graphNamed>
EOT;
    }
}
