<?php

/**
 * @copyright (c) 2021 Bordercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

use MediaWiki\Title\Title;

class LoadRDFJob extends Job {
	/**
	 * @param Title $title
	 * @param array $params
	 */
	public function __construct( $title, $params ) {
		// Replace synchroniseThreadArticleData with an identifier for your job.
		parent::__construct( 'LoadRDF', $title, $params );
		$this->removeDuplicates = true;
	}

	/**
	 * Execute the job
	 *
	 * @return bool
	 */
	public function run() {
		try {
			LinkedWikiStatus::loadTagsRDFInPage( $this->title );
		} catch ( Exception $e ) {
			$this->setLastError( "Error LoadRDFJob: " . $e->getMessage() );
		}
		return true;
	}
}
