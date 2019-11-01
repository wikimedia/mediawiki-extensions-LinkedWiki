<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

abstract class StorageMethodAbstract {
	/**
	 * @return string
	 */
	abstract public function getQueryReadStringWithTagLang();

	/**
	 * @return string
	 */
	abstract public function getQueryReadStringWithoutTagLang();

	/**
	 * @return string
	 */
	abstract public function getQueryReadValue();

	/**
	 * @return string
	 */
	abstract public function getQueryInsertValue();

	/**
	 * @return string
	 */
	abstract public function getQueryDeleteSubject();

	/**
	 * @param string $url
	 * @return string
	 */
	abstract public function getQueryLoadData( $url );
}
