<?php

use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

/**
 * @copyright (c) 2021 Bordercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

/**
 * This special page can refresh the RDF data of wiki in one named graph in a RDF database and clear this graph.
 *
 * There are 5 types of objects in a wiki page with this extension that:
 * - write static RDF data via RDF tag
 * - write dynamic RDF data via module RDF writer
 * - read dynamic data via module RDF reader
 * - read RDF data via a query in a page with cache via SPARQL parser
 * - read RDF data via a query in a page without cache via SPARQL parser
 *
 * This (experimental) page visualizes all parameters necessary to test the state of cache for each page with these
 * objects.
 * With the parameters Debug=true&runJobs=true, this page executes the jobrunner in order to run all pending jobs
 * during the tests.
 */
class SpecialRDFSave extends SpecialPage {

	public function __construct() {
		parent::__construct( 'linkedwiki-specialrdfsave', "data-edit" );
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
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
			return;
		}

		$output = $this->getOutput();
		$output->enableOOUI();

		$request = $this->getRequest();
		$deleteData = $request->getText( 'deleteData' );
		// $refreshDataPage = $request->getText('refreshDataPage');
		$refreshWikiPage = $request->getText( 'refreshWikiPage' );
		$refreshData = $request->getText( 'refreshData' );
		$debug = $request->getText( 'debug' );
		$runJobs = $request->getText( 'runJobs' );

		// UI

		// Default config for saving the schemas
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wgLinkedWiki' );
		if ( !$config->has( "SPARQLServiceSaveDataOfWiki" )
			|| empty( $config->get( "SPARQLServiceSaveDataOfWiki" ) ) ) {
			$output->addHTML(
				"Database by default for the Wiki is not precised
				(parameter SPARQLServiceSaveDataOfWiki). "
			);
			$output->addHTML(
				"You can refresh the pages with SPARQL (queries and Lua modules)
				 but you cannot save RDF data of your wiki."
			);
		}

		$configDefaultSaveData = $config->get( "SPARQLServiceSaveDataOfWiki" );
		$configSaveData = new LinkedWikiConfig( $configDefaultSaveData );

		// phpcs:disable
		$output->addWikiTextAsContent( <<<EOT
The button "Refresh all the wiki" rebuilds the cache and the properties of wiki's pages (via jobs).
It's useful after an update of the LinkedWiki extension or to reset the error messages in the properties of wiki's pages.
If you want only refresh the RDF database, you can use the next button.
EOT
		);

		// phpcs:enable
		$btnRefreshAll = new OOUI\ButtonWidget( [
			'label' => 'Refresh all the wiki',
			'href' => '?refreshWikiPage=true',
			'id' => 'buttonRefreshAll'
		] );
		$output->addHTML( $btnRefreshAll );

		// phpcs:disable
		$output->addWikiTextAsContent( <<<EOT
The button "Save the RDF data in the wiki and/or refresh the queries" helps you to:
# clear the named graph in the config:  <nowiki>$configDefaultSaveData</nowiki> (if the database for saving the Wiki is precised)
# save quickly all data in RDF tag of wiki (if the database for saving the Wiki is precised)
# refresh the pages with SPARQL queries
# refresh all pages with Linkedwiki modules
EOT
		);
		// phpcs:enable
		$btnRefreshAll = new OOUI\ButtonWidget( [
			'label' => 'Save the RDF data in the wiki and/or refresh the queries',
			'href' => '?refreshData=true',
			'id' => 'buttonRefreshData'
		] );
		$output->addHTML( $btnRefreshAll );

		if ( !empty( $configDefaultSaveData ) ) {
			$output->addWikiTextAsContent( <<<EOT
The button "Clear the graph" helps you to clear the named graph in the config: <nowiki>$configDefaultSaveData</nowiki>
EOT
			);
			$btnClearGraph = new OOUI\ButtonWidget( [
				'label' => 'Clear the graph in the config "' . $configDefaultSaveData . '"',
				'flags' => 'destructive',
				'href' => '?deleteData=true',
				'id' => 'buttonClearNamedGraph'
			] );
			$output->addHTML( $btnClearGraph );
			$output->addWikiTextAsContent( <<<EOT
It's useful if you need to clean the old database before to save the wiki in a new database.
EOT
			);

			if ( !empty( $deleteData ) ) {
				try {
					LinkedWikiStatus::clearJobsInDatabase();
					$output->addHTML( LinkedWikiStatus::clearDefaultGraph() );
				} catch ( Exception $e ) {
					$output->addHTML(
						"There are errors. You need to fix the problem before trying to refresh the wiki."
					);
					$output->addHTML( "<br/>Error: <pre>" . htmlentities( $e->getMessage() ) . "</pre>" );
					$this->endSpecialPage();
					return;
				}
			}
		}

		if ( method_exists( MediaWikiServices::class, 'getJobQueueGroup' ) ) {
			// MW 1.37+
			$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroup();
		} else {
			$jobQueueGroup = JobQueueGroup::singleton();
		}
		// show all pages
		if ( !empty( $refreshWikiPage ) ) {
			try {
				LinkedWikiStatus::clearJobsInDatabase();
				if ( !empty( $configDefaultSaveData ) ) {
					$output->addHTML( LinkedWikiStatus::clearDefaultGraph() );
				}
				$output->addHTML( LinkedWikiStatus::invalidateAllPages() );

				// phpcs:disable
				$output->addHTML(
					<<<EOT
<br/>When all the tasks are done, the wiki will be up to date.
You can follow the number of jobs remaining by clicking on the button "Refresh status of jobs"
EOT
				);
				// phpcs:enable
			} catch ( Exception $e ) {
				$output->addHTML(
					"There are errors. You need to fix the problem before trying to refresh the wiki."
				);
				$output->addHTML( "<br/>Error: <pre>" . htmlentities( $e->getMessage() ) . "</pre>" );
				$this->endSpecialPage();
				return;
			}
			// not lazyPush
			$jobQueueGroup->push( new InvalidatePageWithQueryJob() );
		}

		if ( !empty( $refreshData ) ) {
			try {
				// save all RDF tags
				if ( !empty( $configDefaultSaveData ) ) {
					LinkedWikiStatus::clearJobsInDatabase();
					$output->addHTML( LinkedWikiStatus::clearDefaultGraph() );
					$output->addHTML( LinkedWikiStatus::loadAllTagsRDFInPage() );
				}
				$jobQueueGroup->lazyPush( new InvalidatePageWithQueryJob() );
				// phpcs:disable
				$output->addHTML(
					<<<EOT
<br/>When all the tasks are done, the wiki will be up to date.
You can follow the number of jobs remaining by clicking on the button "Refresh status of jobs"
EOT
				);
				// phpcs:enable

			} catch ( Exception $e ) {
				$output->addHTML(
					"There are errors. You need to fix the problem before trying to refresh the RDF database."
				);
				$output->addHTML( "<br/>Error: <pre>" . htmlentities( $e->getMessage() ) . "</pre>" );
				$this->endSpecialPage();
				return;
			}

			// not lazyPush
			$jobQueueGroup->push( new InvalidatePageWithQueryJob() );
		}

		$output->addHTML( self::printStatus( $configDefaultSaveData ) );

		if ( !empty( $debug ) ) {
			$output->addHTML( "<h2>Job pending</h2>" );

			$output->addHTML( "<br/><a href='?debug=true&runJobs=true'
 class=\"mw-htmlform-submit mw-ui-button mw-ui-primary mw-ui-progressive\"
 >Run now all jobs</a><br/><br/>" );

			if ( !empty( $runJobs ) ) {
				// phpcs:disable
				global $IP;
				// phpcs:enable
				$file = exec( "/usr/bin/php $IP/maintenance/runJobs.php --maxtime 60", $retval );
				$textRetval = print_r( $retval, true );
				$output->addHTML( "<br/>Run jobs: <br/><pre id='testJobs'>" );
				$output->addHTML( htmlentities( $textRetval ) );
				$output->addHTML( "</pre>" );
				$output->addHTML( "<h3>Result after the runJobs: </h3>" );
				$output->addHTML(
					"Nb job queues: <span id='testJobsResult'>"
					. count( $jobQueueGroup->getQueuesWithJobs() ) . "</span>"
				);
			}

			// $output->addHTML("<br/>" . print_r($jobQueueGroup->getDefaultQueueTypes(), true));
			foreach ( $jobQueueGroup->getQueuesWithJobs() as $queue ) {
				$queueObj = $jobQueueGroup->get( $queue );
				if ( $queueObj->getSize() == 0 ) {
					// strange ?? need to clean all jobs for automatic tests (code only for doing the tests)
					$jobQueueGroup->get( $queue )->delete();
				} else {
					$output->addHTML(
						"<br/>" . $queue . ": <span id='testJobsQueue" . $queue . "'>"
						. $queueObj->getSize() . "</span>"
					);
				}
			}
			$output->addHTML(
				"Nb job queues: <span id='testJobsResult'>"
				. count( $jobQueueGroup->getQueuesWithJobs() ) . "</span>"
			);

		}
		$this->endSpecialPage();
	}

	private function endSpecialPage() {
		$this->setHeaders();
	}

	private static function printStatus( $configDefaultSaveData ) {
		if ( method_exists( MediaWikiServices::class, 'getJobQueueGroup' ) ) {
			// MW 1.37+
			$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroup();
		} else {
			$jobQueueGroup = JobQueueGroup::singleton();
		}
		$nbJobInvalidatePageWithQuery = $jobQueueGroup->get( "InvalidatePageWithQuery" )->getSize();
		$html = "<h2>Jobs pending</h2>";
		$html .= "Nb job 'refreshLinks' in the queue: " . $jobQueueGroup->get( "refreshLinks" )->getSize() . "<br/>";
		$html .= "Nb job 'InvalidatePageWithQuery' in the queue: " . $nbJobInvalidatePageWithQuery . "<br/>";
		$html .= "Nb job 'LoadRDF' in the queue: <span id='testJobsQueueLoadRDF'>"
			. $jobQueueGroup->get( "LoadRDF" )->getSize() . "</span><br/>";

		$btnClearGraph = new OOUI\ButtonWidget( [
			'label' => 'Refresh status of jobs',
			'href' => '?',
		] );
		$html .= $btnClearGraph;

		// phpcs:disable
		$html .=
			<<<EOT
<br/>By default, jobs are run at the end of a web request. If possible, it is recommended that you disable this default behaviour by setting \$JobRunRate to <code>0</code>, and instead schedule the running of jobs completely in the background, via the command line.
For example, you could use cron to run the jobs every day at midnight by entering the following in your crontab file:
<pre> 0 0 * * * /usr/bin/php /var/www/wiki/maintenance/runJobs.php > /var/log/runJobs.log 2>&1 </pre>
EOT;
		// phpcs:enable

		$html .= "<h2>Information and current state of refresh about wiki pages</h2>";
		$html .= '<b>Last update of the named graph "' . $configDefaultSaveData . '" by the wiki: </b>';

		$databaseRDFTouched = LinkedWikiStatus::getLastUpdate();
		if ( $databaseRDFTouched ) {
			$html .= MediaWikiServices::getInstance()->getContentLanguage()->timeanddate( $databaseRDFTouched )
				. "<br/>";
		} else {
			$html .= "Never<br/>";
		}

		$rows = [
			Html::rawElement(
				'tr',
				[],
				Html::element(
					'th',
					[],
					wfMessage( 'linkedwiki-col-pages' )->text()
				) .
				Html::element(
					'th',
					[],
					wfMessage( 'linkedwiki-col-page-date-touched' )->text()
				) .
				Html::element(
					'th',
					[],
					wfMessage( 'linkedwiki-col-page-date-links-updated' )->text()
				) .
				Html::element(
					'th',
					[],
					wfMessage( 'linkedwiki-col-page-with-sparql-query' )->text()
				) .
				Html::element(
					'th',
					[],
					wfMessage( 'linkedwiki-col-page-with-sparql-query-cached' )->text()
				) .
				Html::element(
					'th',
					[],
					wfMessage( 'linkedwiki-col-page-with-rdf-reader-module' )->text()
				) .
				Html::element(
					'th',
					[],
					wfMessage( 'linkedwiki-col-page-with-rdf-writer-module' )->text()
				) .
				Html::element(
					'th',
					[],
					wfMessage( 'linkedwiki-col-page-with-rdf-writer-tag' )->text()
				) .
				Html::element(
					'th',
					[],
					wfMessage( 'linkedwiki-col-page-current-state' )->text()
				)
			)
		];
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$lang = MediaWikiServices::getInstance()->getContentLanguage();
		$resultDb = $dbr->select(
			[
				'page',
				'page_props',
				'job'
			],
			[
				'page_touched', 'page_links_updated', 'page_namespace', 'page_title',
				'GROUP_CONCAT(pp_propname) as props', 'GROUP_CONCAT(job_id) as jobs'
			],
			[
				$dbr->makeList( [
					$dbr->makeList(
						[
							'pp_propname' => LinkedWikiStatus::PAGEPROP_DB_TOUCHED
						],
						LIST_AND
					),
					$dbr->makeList(
						[ 'pp_propname' => LinkedWikiStatus::PAGEPROP_READER_QUERY,
							'pp_value' => true
						],
						LIST_AND
					),
					$dbr->makeList(
						[ 'pp_propname' => LinkedWikiStatus::PAGEPROP_READER_QUERY_CACHED,
							'pp_value' => true
						],
						LIST_AND
					),
					$dbr->makeList(
						[ 'pp_propname' => LinkedWikiStatus::PAGEPROP_READER_MODULE,
							'pp_value' => true
						],
						LIST_AND
					),
					$dbr->makeList(
						[ 'pp_propname' => LinkedWikiStatus::PAGEPROP_WRITER_MODULE,
							'pp_value' => true
						],
						LIST_AND
					),
					$dbr->makeList(
						[ 'pp_propname' => LinkedWikiStatus::PAGEPROP_WRITER_TAG,
							'pp_value' => true
						],
						LIST_AND
					),
					$dbr->makeList(
						[ 'pp_propname' => LinkedWikiStatus::PAGEPROP_ERROR_MESSAGE ],
						LIST_AND
					)
				], LIST_OR )
			],
			__METHOD__,
			[
				'ORDER BY' => 'page_namespace,page_title',
				'GROUP BY' => ' page_touched,page_links_updated,page_namespace,page_title'
			],
			[
				'page_props' => [
					'INNER JOIN',
					[ 'page_id = pp_page' ]
				],
				'job' => [
					'LEFT JOIN',
					[ 'page_namespace = job_namespace AND page_title = job_title AND (job_cmd = "LoadRDF")' ]
				]
			]
		);

		$html .= "Nb pages with one or several LinkedWiki modules: <span id='testNbPagesWithModules'>"
			. $resultDb->numRows() . "</span>";
		if ( $resultDb->numRows() === 0 ) {
			return $html;
		}

		foreach ( $resultDb as $row ) {
			$pageTouched = $row->page_touched;
			$pageLinksUpdated = $row->page_links_updated;
			$hasReaderQuery = str_contains( $row->props, LinkedWikiStatus::PAGEPROP_READER_QUERY );
			$hasReaderQueryCached = str_contains( $row->props, LinkedWikiStatus::PAGEPROP_READER_QUERY_CACHED );
			$hasReaderModule = str_contains( $row->props, LinkedWikiStatus::PAGEPROP_READER_MODULE );
			$hasWriterModule = str_contains( $row->props, LinkedWikiStatus::PAGEPROP_WRITER_MODULE );
			$hasWriterTag = str_contains( $row->props, LinkedWikiStatus::PAGEPROP_WRITER_TAG );
			$hasJob = !empty( $row->jobs );
			$hasError = str_contains( $row->props, LinkedWikiStatus::PAGEPROP_ERROR_MESSAGE );

			// (int)$row->rev_id;
			$title = Title::makeTitle( $row->page_namespace, $row->page_title );
			$rows[] = Html::rawElement(
				'tr',
				[],
				Html::rawElement(
					'td',
					[ 'style' => 'text-align: center;' ],
					Html::element(
						'a',
						[ 'href' => $title->getFullURL() ],
						$title->getPrefixedText()
					)
				) .
				Html::element(
					'td',
					[ 'style' => 'text-align: center;' ],
					$lang->timeanddate( $row->page_touched )
				) .
				Html::element(
					'td',
					[ 'style' => 'text-align: center;' ],
					$lang->timeanddate( $row->page_links_updated )
				) .
				Html::element(
					'td',
					[ 'style' => 'text-align: center;' ],
					( $hasReaderQuery ? "X" : "" )
				) .
				Html::element(
					'td',
					[ 'style' => 'text-align: center;' ],
					( $hasReaderQueryCached ? "X" : "" )
				) .
				Html::element(
					'td',
					[ 'style' => 'text-align: center;' ],
					( $hasReaderModule ? "X" : "" )
				) .
				Html::element(
					'td',
					[ 'style' => 'text-align: center;' ],
					( $hasWriterModule ? "X" : "" )
				) .
				Html::element(
					'td',
					[ 'style' => 'text-align: center;' ],
					( $hasWriterTag ? "X" : "" )
				) .
				Html::rawElement(
					'td',
					[ 'style' => 'text-align: center;' ],
					self::getStatusOfCache(
						$title,
						$databaseRDFTouched,
						$pageTouched,
						$pageLinksUpdated,
						$hasReaderQuery,
						$hasReaderQueryCached,
						$hasReaderModule,
						$hasWriterModule,
						$hasWriterTag,
						$nbJobInvalidatePageWithQuery,
						$hasJob,
						$hasError
					)
				)
			);
		}

		return $html .
			Html::rawElement(
				'table',
				[
					'class' => 'wikitable sortable',
					'id' => 'linkedwiki-module-table'
				],
				implode( "\n", $rows )
			);
	}

	/**
	 * Experimental function...
	 *
	 * @param Title $title
	 * @param number $databaseRDFTouched
	 * @param number $pageTouched
	 * @param number $pageLinksUpdated
	 * @param bool $hasReaderQuery
	 * @param bool $hasReaderQueryCached
	 * @param bool $hasReaderModule
	 * @param bool $hasWriterModule
	 * @param bool $hasWriterTag
	 * @param number $nbJobInvalidatePageWithQuery
	 * @param bool $hasJob
	 * @param bool $hasError
	 * @return string
	 */
	private static function getStatusOfCache(
		$title,
		$databaseRDFTouched,
		$pageTouched,
		$pageLinksUpdated,
		$hasReaderQuery,
		$hasReaderQueryCached,
		$hasReaderModule,
		$hasWriterModule,
		$hasWriterTag,
		$nbJobInvalidatePageWithQuery,
		$hasJob,
		$hasError
	) {
		global $wgScriptPath;
		$doWaitJobPending = false;
		$doInvalidatePage = false;
		$doPurge = false;

		if ( $hasReaderQueryCached ) {
			if ( $nbJobInvalidatePageWithQuery > 0 ) {
				$doWaitJobPending = true;
			} elseif ( $databaseRDFTouched > $pageTouched ) {
				$doInvalidatePage = true;
			} elseif ( $databaseRDFTouched > $pageLinksUpdated ) {
				// do nothing
			} else {
				// do nothing
			}
		}
		if ( $hasReaderModule ) {
			if ( $nbJobInvalidatePageWithQuery > 0 ) {
				$doWaitJobPending = true;
			} elseif ( $databaseRDFTouched > $pageTouched ) {
				$doInvalidatePage = true;
			} elseif ( $databaseRDFTouched > $pageLinksUpdated ) {
				// do nothing
			} else {
				// do nothing
			}
		}
		if ( $hasWriterModule ) {
			if ( $nbJobInvalidatePageWithQuery > 0 || $hasJob ) {
				$doWaitJobPending = true;
			} elseif ( $databaseRDFTouched > $pageTouched ) {
				$doInvalidatePage = true;
			} elseif ( $databaseRDFTouched > $pageLinksUpdated ) {
				$doPurge = true;
			} else {
				// do nothing
			}
		}
		if ( $hasWriterTag ) {
			if ( $hasJob ) {
				$doWaitJobPending = true;
			} else {
				// do nothing
			}
		}
		if ( $doWaitJobPending ) {
			return "Job pending";
		} elseif ( $hasError ) {
			// TODO open a nice page with properties
			return new OOUI\ButtonWidget( [
					'label' => 'ERROR',
					'href' => $wgScriptPath . '/api.php?action=query&prop=pageprops&titles='
						. $title->getPrefixedText(),
					'target' => 'blank'
				] );
		} elseif (
			$databaseRDFTouched > $pageTouched
			&& $doInvalidatePage
		) {
			return "Cache osbolete but always validate for the wiki";
		} elseif ( $databaseRDFTouched > $pageLinksUpdated ) {
			if ( $doPurge ) {
				return "Cache osbolete and have to be recalculate";
			} else {
				return "Cache osbolete and will be recalculate the next time";
			}
		} else {
			return "Cache updated";
		}
	}
}
