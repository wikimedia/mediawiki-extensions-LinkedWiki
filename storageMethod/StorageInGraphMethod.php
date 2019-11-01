<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

class StorageInGraphMethod extends StorageMethodAbstract {
	private $graphNamed = "http://example.com";

	/**
	 * @param string $graphNamed
	 */
	public function setGraph( $graphNamed ) {
		$graphNamed = trim( $graphNamed );
		$this->graphNamed = $graphNamed;
	}

	/**
	 * @return string
	 */
	public function getGraph() {
		return $this->graphNamed;
	}

	/**
	 * @return string
	 */
	public function getQueryReadStringWithTagLang() {
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

	/**
	 * @return string
	 */
	public function getQueryReadStringWithoutTagLang() {
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

	/**
	 * @return string
	 */
	public function getQueryReadValue() {
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

	/**
	 * @return string
	 */
	public function getQueryInsertValue() {
		return <<<EOT
INSERT DATA
    {GRAPH <$this->graphNamed>
        {
            ?subject ?property ?value .
        }
    }
EOT;
	}

	/**
	 * @return string
	 */
	public function getQueryDeleteSubject() {
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

	/**
	 * @param string $url
	 * @return string
	 */
	public function getQueryLoadData( $url ) {
		return <<<EOT
LOAD <$url> INTO GRAPH <$this->graphNamed>
EOT;
	}
}
