<?php
/**
 * @copyright (c) 2016 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link http://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-nc-sa V3.0
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


abstract class StorageMethodAbstract
{
    abstract public function getQueryReadStringWithTagLang();
    abstract public function getQueryReadStringWithoutTagLang();
    abstract public function getQueryReadValue();
    abstract public function getQueryInsertValue();
    abstract public function getQueryDeleteSubject();

}