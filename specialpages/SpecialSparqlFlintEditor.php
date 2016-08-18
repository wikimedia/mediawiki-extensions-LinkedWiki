<?php
/**
 * @version 1.0.0.0
 * @package Bourdercloud/linkedwiki
 * @copyright (c) 2011 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link http://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-nc-sa V3.0
 *
 * Last version : http://github.com/BorderCloud/LinkedWiki
 
 Description : http://www.mediawiki.org/wiki/Extension:LinkedWiki
 
 Copyright (c) 2010 Bourdercloud.com

	This work is licensed under the Creative Commons 
	Attribution-NonCommercial-ShareAlike 3.0 
	Unported License. To view a copy of this license, 
	visit http://creativecommons.org/licenses/by-nc-sa/3.0/ 
	or send a letter to Creative Commons,
	171 Second Street, Suite 300, San Francisco, 
	California, 94105, USA.

 */
if (!defined('MEDIAWIKI')) die();

class SpecialSparqlFlintEditor extends SpecialPage {

	public function __construct() {
 		parent::__construct( 'linkedwiki-specialsparqlflinteditor' );
	}

	public function execute($par = null) {
		global $wgOut;
		$wgOut->addModules('ext.LinkedWiki.flint');  
		$wgOut->addHTML("<div id=\"flint-test\">");
		$this->setHeaders();
	}
}
