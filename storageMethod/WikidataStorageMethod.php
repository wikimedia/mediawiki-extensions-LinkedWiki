<?php
/**
 * @copyright (c) 2021 Bordercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

class WikidataStorageMethod extends StorageMethodAbstract {
	/**
	 * @return string
	 */
	public function getQueryReadStringWithTagLang() {
		return <<<EOT
SELECT DISTINCT  ?value
WHERE
        {
            ?subject ?property ?value .
            FILTER ( lang(?value) = ?lang )
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
        {
            ?subject ?property ?value .
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
        {
            ?subject ?property ?value .
        }
EOT;
	}

	/**
	 * @return string
	 */
	public function getQueryInsertValue() {
		return "";
	}

	/**
	 * @return string
	 */
	public function getQueryDeleteSubject() {
		return "";
	}

	/**
	 * @param string $url
	 * @return string
	 */
	public function getQueryLoadData( $url ) {
		return "";
	}

}
