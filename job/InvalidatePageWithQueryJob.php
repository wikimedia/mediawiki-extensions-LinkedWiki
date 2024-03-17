<?php

use MediaWiki\MediaWikiServices;

/**
 * @copyright (c) 2021 Bordercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

class InvalidatePageWithQueryJob extends Job {
	public function __construct() {
		// Replace synchroniseThreadArticleData with an identifier for your job.
		parent::__construct( 'InvalidatePageWithQuery', Title::newFromText( "ForAllQuery" ), [] );
		$this->removeDuplicates = true;
	}

	/**
	 * Execute the job
	 *
	 * @return bool
	 */
	public function run() {
		$nbPageInvalidated = 0;
		$nbJobRefreshlinksAdded = 0;

		$dbr = wfGetDB( DB_PRIMARY );
		$lang = MediaWikiServices::getInstance()->getContentLanguage();
		$resultDb = $dbr->select(
			[
				'page',
				'page_props'
			],
			[
				'page_title', 'page_namespace'
			],
			[
				$dbr->makeList( [
// $dbr->makeList(
//						[
//							'pp_propname' => LinkedWikiStatus::PAGEPROP_READER_QUERY,
//							'pp_value' => true
//						],
//						LIST_AND
//					),
					$dbr->makeList(
						[
							'pp_propname' => LinkedWikiStatus::PAGEPROP_READER_QUERY_CACHED,
							'pp_value' => true
						],
						LIST_AND
					),
					$dbr->makeList(
						[
							'pp_propname' => LinkedWikiStatus::PAGEPROP_READER_MODULE,
							'pp_value' => true
						],
						LIST_AND
					),
					$dbr->makeList(
						[
							'pp_propname' => LinkedWikiStatus::PAGEPROP_WRITER_MODULE,
							'pp_value' => true
						],
						LIST_AND
					)
				], LIST_OR )
			],
			__METHOD__,
			[],
			[
				'page_props' => [
					'INNER JOIN',
					[ 'page_id = pp_page' ]
				]
			]
		);

		$nbPageInvalidated = $resultDb->numRows();
		$keys = [];
		foreach ( $resultDb as $row ) {
			if ( !isset( $keys[$row->page_namespace] ) ) {
				$keys[$row->page_namespace] = [];
			}
			$keys[$row->page_namespace][] = $row->page_title;
			// echo $row->page_title."\n";
		}
		foreach ( $keys as $namespace => $pagekeys ) {
			PurgeJobUtils::invalidatePages( $dbr, $namespace, $pagekeys );
		}

		$resultDb = $dbr->select(
			[
				'page',
				'page_props'
			],
			[
				'page_id', 'page_title'
			],
			[
				$dbr->makeList( [
					$dbr->makeList(
						[
							'pp_propname' => LinkedWikiStatus::PAGEPROP_READER_MODULE,
							'pp_value' => true
						],
						LIST_AND
					),
					$dbr->makeList(
						[
							'pp_propname' => LinkedWikiStatus::PAGEPROP_WRITER_MODULE,
							'pp_value' => true
						],
						LIST_AND
					)
				], LIST_OR )
			],
			__METHOD__,
			[],
			[
				'page_props' => [
					'INNER JOIN',
					[ 'page_id = pp_page' ]
				]
			]
		);
		// $nbJobRefreshlinksAdded = $resultDb->numRows();
		$jobs = [];
		foreach ( $resultDb as $row ) {

			// echo $row->page_title."\n";
			$jobParams = [];
			$jobs[] = new RefreshLinksJob( Title::newFromID( $row->page_id ), $jobParams );
		}
		if ( method_exists( MediaWikiServices::class, 'getJobQueueGroup' ) ) {
			// MW 1.37+
			MediaWikiServices::getInstance()->getJobQueueGroup()->push( $jobs );
		} else {
			JobQueueGroup::singleton()->push( $jobs );
		}

		// print(
		//			"Job InvalidatePageWithQueryJob: nbJobRefreshlinksAdded " . $nbJobRefreshlinksAdded
		//			. " nbPageInvalidated " . $nbPageInvalidated . "\n"
		//		);
		return true;
	}
}
