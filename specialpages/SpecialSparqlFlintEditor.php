<?php
/**
 * @copyright (c) 2021 Bordercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

class SpecialSparqlFlintEditor extends SpecialPage {

	public function __construct() {
		parent::__construct( 'linkedwiki-specialsparqlflinteditor' );
	}

	/**
	 * @return string
	 */
	public function getGroupName() {
		return 'linkedwiki_group';
	}

	/**
	 * @param null $par
	 */
	public function execute( $par = null ) {
		$output = $this->getOutput();
		$output->addModules( [ 'ext.LinkedWiki.flint' ] );
		$output->addHTML( "<div id=\"flint-test\">" );
		$this->setHeaders();
	}
}
