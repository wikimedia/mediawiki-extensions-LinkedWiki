<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

class SpecialLinkedWikiConfig extends SpecialPage {

	public function __construct() {
		parent::__construct( 'linkedwiki-speciallinkedwikiconfig' );
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
		$this->setHeaders();
		$output = $this->getOutput();

		$output->addWikiTextAsInterface(
			"SPARQL services configurated in the system via the file LinkedWiki/extension.json 
			and via the localsettings of wiki. 
			[https://www.mediawiki.org/wiki/Extension:LinkedWiki/Configuration Details]"
		);

		$config = new LinkedWikiConfig();
		$output->addWikiTextAsInterface( "== SPARQL services configurated ==" );
		$output->addHTML( $config->info() );

		$output->addWikiTextAsInterface( "== Other options ==" );
		$output->addHTML( $config->infoOtherOptions() );
	}
}
