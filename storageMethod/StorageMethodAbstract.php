<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
 */

abstract class StorageMethodAbstract
{
    abstract public function getQueryReadStringWithTagLang();
    abstract public function getQueryReadStringWithoutTagLang();
    abstract public function getQueryReadValue();
    abstract public function getQueryInsertValue();
    abstract public function getQueryDeleteSubject();
    abstract public function getQueryLoadData($url);
}
