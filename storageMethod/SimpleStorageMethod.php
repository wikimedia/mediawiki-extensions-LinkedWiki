<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
 */

class SimpleStorageMethod extends StorageMethodAbstract
{

    public function getQueryReadStringWithTagLang()
    {
        return <<<EOT
SELECT DISTINCT  ?value
WHERE
        {
            ?subject ?property ?value .
            FILTER langMatches( lang(?value), ?lang )
        }
EOT;
    }

    public function getQueryReadStringWithoutTagLang()
    {
        return <<<EOT
SELECT DISTINCT  ?value
WHERE
        {
            ?subject ?property ?value .
            FILTER ( lang(?value) = "" )
        }
EOT;
    }

    public function getQueryReadValue()
    {
        return <<<EOT
SELECT DISTINCT  ?value
WHERE
        {
            ?subject ?property ?value .
        }
EOT;
    }

    public function getQueryInsertValue()
    {
        return <<<EOT
INSERT DATA
        {
            ?subject ?property ?value .
        }
EOT;
    }

    public function getQueryDeleteSubject()
    {
        return <<<EOT
DELETE
        { ?subject ?property ?value . }
WHERE
        { ?subject ?property ?value . }
EOT;
    }

    public function getQueryLoadData($url)
    {
        return <<<EOT
LOAD <$url> 
EOT;
    }

}
