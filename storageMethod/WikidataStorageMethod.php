<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
 */

class WikidataStorageMethod extends StorageMethodAbstract
{

    public function getQueryReadStringWithTagLang()
    {
        return <<<EOT
SELECT DISTINCT  ?value
WHERE
        {
            ?subject ?property ?value .
            FILTER ( lang(?value) = ?lang )
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
        return "";
    }

    public function getQueryDeleteSubject()
    {
        return "";
    }

    public function getQueryLoadData($url)
    {
        return "";
    }

}
