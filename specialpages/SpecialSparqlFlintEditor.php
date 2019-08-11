<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
 */

class SpecialSparqlFlintEditor extends SpecialPage {

	public function __construct() {
 		parent::__construct( 'linkedwiki-specialsparqlflinteditor' );
	}
    public function getGroupName() {
        return 'linkedwiki_group';
    }

	public function execute($par = null) {
		global $wgOut;
		$wgOut->addModules('ext.LinkedWiki.flint');
		$wgOut->addHTML("<div id=\"flint-test\">");
		$this->setHeaders();
	}
}
