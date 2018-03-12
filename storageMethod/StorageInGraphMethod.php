<?php
/**
 * @copyright (c) 2018 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link http://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
 *
 *  Last version : http://github.com/BorderCloud/LinkedWiki
 *
 *
 * This work is licensed under the Creative Commons
 * Attribution-NonCommercial-ShareAlike 3.0
 * Unported License. To view a copy of this license,
 * visit http://creativecommons.org/licenses/by-nc-sa/3.0/
 * or send a letter to Creative Commons,
 * 171 Second Street, Suite 300, San Francisco,
 * California, 94105, USA.
 */

class StorageInGraphMethod extends StorageMethodAbstract
{
    private $graphNamed = "http://example.com";

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
