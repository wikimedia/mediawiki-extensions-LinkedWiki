<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class LinkedWikiStatus {

	/**
	 * Page property
	 * Type of value: timestamp
	 */
	public const PAGEPROP_DB_TOUCHED = "wgLinkedWiki_DB_touched";

	/**
	 * Page property
	 * Type of value: bool
	 */
	public const PAGEPROP_READER_QUERY = "wgLinkedWiki_page_with_sparql_query_without_cache";

	/**
	 * Page property
	 * Type of value: bool
	 */
	public const PAGEPROP_READER_QUERY_CACHED = "wgLinkedWiki_page_with_sparql_query_cached";

	/**
	 * Page property
	 * Type of value: bool
	 */
	public const PAGEPROP_READER_MODULE = "wgLinkedWiki_page_with_RDF_reader_module";

	/**
	 * Page property
	 * Type of value: bool
	 */
	public const PAGEPROP_WRITER_MODULE = "wgLinkedWiki_page_with_RDF_writer_module";

	/**
	 * Page property
	 * Type of value: bool
	 */
	public const PAGEPROP_WRITER_TAG = "wgLinkedWiki_page_with_RDF_tag";

	/**
	 * Page property
	 * Type of value: bool
	 */
	public const PAGEPROP_SHACL = "wgLinkedWiki_page_with_SHACL";

	/**
	 * Page property
	 * Type of value: string
	 */
	public const PAGEPROP_ERROR_MESSAGE = "wgLinkedWiki_page_with_error_message";

	/**
	 * Calculate the last update of the RDF database with the property "wgLinkedWiki_DB_touched"
	 *
	 * @return false|int timestamp
	 */
	public static function getLastUpdate() {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$result = $dbr->selectField(
			'page_props',
			'pp_value',
			[
				'pp_propname' => self::PAGEPROP_DB_TOUCHED
			],
			__METHOD__,
			[
				'ORDER BY' => 'pp_value ASC',
				'LIMIT' => 1
			]
		);

		return intval( $result );
	}

	/**
	 * Copy of this function in the class PagePropsTest
	 *
	 * @param Title $title
	 * @param array $properties [ propertyName => value, ... ]
	 */
	public static function setProperties( Title $title, $properties ) {
		$rows = [];
		foreach ( $properties as $propertyName => $propertyValue ) {
			$rows[] = [
				'pp_page' => $title->getArticleID(),
				'pp_propname' => $propertyName,
				'pp_value' => $propertyValue
			];
		}

		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->replace(
			'page_props',
			[
				[
					'pp_page',
					'pp_propname'
				]
			],
			$rows,
			__METHOD__
		);
	}

	/**
	 * Delete a property
	 *
	 * @param Title $title
	 * @param string $propertyName
	 */
	public static function unsetProperty( Title $title, $propertyName ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$result = $dbr->delete(
			'page_props',
			[
				'pp_propname' => $propertyName,
				'pp_page' => $title->getArticleID()
			],
			__METHOD__
		);
	}

	/**
	 * Copy of this function in the class PagePropsTest
	 *
	 * @param Title $title
	 * @param string $propertyName
	 * @param string|bool $propertyValue
	 */
	public static function setProperty( Title $title, $propertyName, $propertyValue ) {
		$properties = [
			$propertyName => $propertyValue
		];
		self::setProperties( $title, $properties );
	}

	/**
	 * @param Title $title
	 * @param string $propertyName
	 * @return mixed
	 */
	public static function getProperty( Title $title, $propertyName ) {
		// TODO in PHP 8 : $propertyName is a type ENUM
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$result = $dbr->selectField(
			'page_props',
			'pp_value',
			[
				'pp_propname' => $propertyName,
				'pp_page' => $title->getArticleID()
			],
			__METHOD__,
			[]
		);
		return $result;
	}

	/**
	 * Detect if the page contains RDF
	 *
	 * @param Title $title
	 * @return bool
	 */
	public static function isPageWithRDF( Title $title ) {
		return boolval( self::getProperty( $title, self::PAGEPROP_WRITER_TAG ) );
	}

	/**
	 * Get all page with SHACL constraints
	 *
	 * @return array of page id
	 */
	public static function getPagesWithSHACL() {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$resultDb = $dbr->select(
			[
				'page_props'
			],
			[
				'pp_page'
			],
			[
				'pp_propname' => self::PAGEPROP_SHACL,
				'pp_value' => true
			],
			__METHOD__,
			[],
			[]
		);
		$ids = [];
		foreach ( $resultDb as $row ) {
			$ids[] = $row->pp_page;
		}
		return $ids;
	}

	/**
	 * Clear the wiki of LinkedWiki properties
	 */
	public static function clearPropertiesInDatabase() {
		self::clearPropertyInDatabase( self::PAGEPROP_DB_TOUCHED );
		self::clearPropertyInDatabase( self::PAGEPROP_READER_QUERY );
		self::clearPropertyInDatabase( self::PAGEPROP_READER_QUERY_CACHED );
		self::clearPropertyInDatabase( self::PAGEPROP_READER_MODULE );
		self::clearPropertyInDatabase( self::PAGEPROP_WRITER_MODULE );
		self::clearPropertyInDatabase( self::PAGEPROP_WRITER_TAG );
		self::clearPropertyInDatabase( self::PAGEPROP_ERROR_MESSAGE );
		self::clearPropertyInDatabase( self::PAGEPROP_SHACL );
	}

	/**
	 * Clear the LinkedWiki properties for one page
	 *
	 * @param Parser &$parser
	 */
	public static function clearPagePropertiesViaParser( &$parser ) {
		if ( empty( $parser ) ) {
			return;
		}
		$out = $parser->getOutput();
		if ( empty( $out ) ) {
			return;
		}

		$out->unsetPageProperty( self::PAGEPROP_DB_TOUCHED );
		$out->unsetPageProperty( self::PAGEPROP_READER_QUERY );
		$out->unsetPageProperty( self::PAGEPROP_READER_QUERY_CACHED );
		$out->unsetPageProperty( self::PAGEPROP_READER_MODULE );
		$out->unsetPageProperty( self::PAGEPROP_WRITER_MODULE );
		$out->unsetPageProperty( self::PAGEPROP_WRITER_TAG );
		$out->unsetPageProperty( self::PAGEPROP_ERROR_MESSAGE );
		$out->unsetPageProperty( self::PAGEPROP_SHACL );
	}

// /**
//	 * Clear the LinkedWiki properties for one page
//	 * (not use for the moment)
//	 *
//	 * @param Title $title
//	 */
//	public static function clearPageProperties( $title ) {
//		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
//		$conditions = [
//			'pp_page' => $title->getArticleID(),
//			$dbw->makeList( [
//				$dbw->makeList(
//					[ 'pp_propname' => self::PAGEPROP_DB_TOUCHED, ],
//					LIST_AND
//				),
//				$dbw->makeList(
//					[ 'pp_propname' => self::PAGEPROP_READER_QUERY, ],
//					LIST_AND
//				),
//				$dbw->makeList(
//					[ 'pp_propname' => self::PAGEPROP_READER_QUERY_CACHED, ],
//					LIST_AND
//				),
//				$dbw->makeList(
//					[ 'pp_propname' => self::PAGEPROP_READER_MODULE, ],
//					LIST_AND
//				),
//				$dbw->makeList(
//					[ 'pp_propname' => self::PAGEPROP_WRITER_MODULE, ],
//					LIST_AND
//				),
//				$dbw->makeList(
//					[ 'pp_propname' => self::PAGEPROP_WRITER_TAG, ],
//					LIST_AND
//				),
//				$dbw->makeList(
//					[ 'pp_propname' => self::PAGEPROP_ERROR_MESSAGE, ],
//					LIST_AND
//				),
//				$dbw->makeList(
//					[ 'pp_propname' => self::PAGEPROP_SHACL, ],
//					LIST_AND
//				)
//			], LIST_OR )
//		];
//		$dbw->delete(
//			'page_props',
//			$conditions,
//			__METHOD__
//		);
//	}

	/**
	 * Clear LinkedWiki's jobs
	 * @throws JobQueueError
	 */
	public static function clearJobsInDatabase() {
		if ( method_exists( MediaWikiServices::class, 'getJobQueueGroup' ) ) {
			// MW 1.37+
			$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroup();
		} else {
			$jobQueueGroup = JobQueueGroup::singleton();
		}
		$jobQueue = $jobQueueGroup->get( "InvalidatePageWithQuery" );
		$jobQueue->delete();
		$jobQueue = $jobQueueGroup->get( "LoadRDF" );
		$jobQueue->delete();
	}

	/**
	 * Load the RDF of a page in the database
	 *
	 * @param Title $title
	 * @throws Exception
	 */
	public static function loadTagsRDFInPage( Title $title ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wgLinkedWiki' );
		$configDefaultSaveData = $config->get( "SPARQLServiceSaveDataOfWiki" );
		if ( empty( $configDefaultSaveData ) ) {
			return;
		}

		$configSaveData = new LinkedWikiConfig( $configDefaultSaveData );

		$query = $configSaveData->getQueryLoadData( $title->getFullURL( 'action=raw&export=rdf' ) ) . ' ;' . "\n";
		$endpoint = $configSaveData->getInstanceEndpoint();
		$response = $endpoint->query( $query, 'raw' );
		$err = $endpoint->getErrors();
		if ( $err ) {
			$error = print_r( $err, true );
			self::setProperty( $title, self::PAGEPROP_ERROR_MESSAGE, $error );
			throw new Exception( $error );
		} else {
			self::setProperty( $title, self::PAGEPROP_DB_TOUCHED, time() );
			$job = new InvalidatePageWithQueryJob();
			if ( method_exists( MediaWikiServices::class, 'getJobQueueGroup' ) ) {
				// MW 1.37+
				MediaWikiServices::getInstance()->getJobQueueGroup()->lazyPush( $job );
			} else {
				JobQueueGroup::singleton()->lazyPush( $job );
			}
		}
	}

	/**
	 * Refresh all the wiki's pages
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function invalidateAllPages() {
		// Find the Nb pages in this wiki
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$titleArray = TitleArray::newFromResult(
			$dbr->select( 'page',
				[ 'page_id', 'page_namespace', 'page_title' ]
			) );
		$nbPage = $titleArray->count();
		$html = "<br/>Nb pages in this wiki : " . $nbPage;

		if ( $nbPage ) {
			$jobs = [];
			foreach ( $titleArray as $title ) {
				$jobParams = [];
				$jobs[] = new RefreshLinksJob( $title, $jobParams );
			}
			if ( method_exists( MediaWikiServices::class, 'getJobQueueGroup' ) ) {
				// MW 1.37+
				MediaWikiServices::getInstance()->getJobQueueGroup()->push( $jobs );
			} else {
				JobQueueGroup::singleton()->push( $jobs );
			}
		}
		$html = "<br/>Nb inserted job in the queue: " . $nbPage;
		return $html;
	}

	/**
	 * Load all RDF of wiki in the database and return a report of the query with the response of server.
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function loadAllTagsRDFInPage() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wgLinkedWiki' );
		$configDefaultSaveData = $config->get( "SPARQLServiceSaveDataOfWiki" );
		if ( empty( $configDefaultSaveData ) ) {
			return "Database by default for the Wiki is not precised
				(parameter SPARQLServiceSaveDataOfWiki)";
		}
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$resultDb = $dbr->select(
			[
				'page',
				'page_props'
			],
			[
				'page_id', 'page_title'
			],
			[
				'pp_propname' => self::PAGEPROP_WRITER_TAG,
				'pp_value' => true
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
		$query = "";
		$configSaveData = new LinkedWikiConfig( $configDefaultSaveData );
		$titles = [];
		foreach ( $resultDb as $row ) {
			$title = Title::newFromID( $row->page_id );
			$titles[] = $title;
			$query .= $configSaveData->getQueryLoadData(
					$title->getFullURL( 'action=raw&export=rdf' )
				)
				. ' ;' . "\n";
		}

		$html = "<br/>Query executed: <pre>" . htmlentities( $query ) . "</pre>";
		if ( !empty( $query ) ) {

			$endpoint = $configSaveData->getInstanceEndpoint();
			$response = $endpoint->query( $query, 'raw' );
			$err = $endpoint->getErrors();
			if ( $err ) {
				throw new Exception( print_r( $err, true ) );
			} else {
				foreach ( $titles as $title ) {
					self::setProperty( $title, self::PAGEPROP_DB_TOUCHED, time() );
					self::unsetProperty( $title, self::PAGEPROP_ERROR_MESSAGE );
				}
				$html .= "<br/>Result: <pre>" . htmlentities( $response ) . "</pre>";
			}
		}
		return $html;
	}

	/**
	 * Clear the named graph by default of wiki.
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function clearDefaultGraph() {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wgLinkedWiki' );
		$configDefaultSaveData = $config->get( "SPARQLServiceSaveDataOfWiki" );
		$configSaveData = new LinkedWikiConfig( $configDefaultSaveData );
		$query = "CLEAR GRAPH <" . $configDefaultSaveData . ">";
		$html = "<br/>Query executed : <pre>" . htmlentities( $query ) . "</pre>";
		if ( !empty( $query ) ) {
			$endpoint = $configSaveData->getInstanceEndpoint();
			$response = $endpoint->query( $query, 'raw' );
			$err = $endpoint->getErrors();
			if ( $err ) {
				throw new Exception( print_r( $err, true ) );
			} else {
				$html .= "<br/>Result : <pre>" . htmlentities( $response ) . "</pre>";
			}
		}
		return $html;
	}

	private static function clearPropertyInDatabase( $name ) {
		$conditions = [ 'pp_propname' => $name ];
		$dbw = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_PRIMARY );
		$dbw->delete(
			'page_props',
			$conditions,
			__METHOD__
		);
	}
}
