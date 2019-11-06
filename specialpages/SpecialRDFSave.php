<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
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
		$output = $this->getOutput();

		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
			return;
		}

		// Default config for saving the schemas
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'wgLinkedWiki' );
		if ( !$config->has( "SPARQLServiceSaveDataOfWiki" ) ) {
			$output->addHTML(
				"Database by default for the Wiki is not precised 
				in the extension.json of the LinkedWiki extension.
				(parameter SPARQLServiceSaveDataOfWiki)"
			);
			return;
		}

		$configDefaultSaveData = $config->get( "SPARQLServiceSaveDataOfWiki" );

		$configSaveData = new LinkedWikiConfig( $configDefaultSaveData );

		$request = $this->getRequest();
		$deleteData = $request->getText( 'deleteData' );
		$refreshDataPage = $request->getText( 'refreshDataPage' );
		$refreshWikiPage = $request->getText( 'refreshWikiPage' );

		// UI
		$output->addHTML( "<a href='?deleteData=true'
 class=\"mw-htmlform-submit mw-ui-button mw-ui-primary mw-ui-progressive\"
 >1. Clean the graph in the config $configDefaultSaveData</a><br/><br/>" );

		$output->addHTML( "<a href='?refreshDataPage=true'
 class=\"mw-htmlform-submit mw-ui-button mw-ui-primary mw-ui-progressive\"
 >2. Import all RDF data pages in the config $configDefaultSaveData</a><br/><br/>" );

		$output->addHTML( "<a href='?refreshWikiPage=true'
 class=\"mw-htmlform-submit mw-ui-button mw-ui-primary mw-ui-progressive\"
 >3. Refresh all pages of wiki (with SPARQL queries and Lua modules)</a><br/>" );

		//
		if ( !empty( $deleteData ) ) {
			$query = "CLEAR GRAPH <" . $configDefaultSaveData . ">";
			$output->addHTML( "<br/>Query executed : <pre>" . htmlentities( $query ) . "</pre>" );
			if ( !empty( $query ) ) {
				$endpoint = $configSaveData->getInstanceEndpoint();
				$response = $endpoint->query( $query, 'raw' );
				$err = $endpoint->getErrors();
				if ( $err ) {
					$output->addHTML( "<br/>Error : <pre>" . htmlentities( print_r( $err, true ) ) . "</pre>" );
				} else {
					$output->addHTML( "<br/>Result : <pre>" . htmlentities( $response ) . "</pre>" );
				}
			}
		}

		if ( !empty( $refreshDataPage ) ) {
			$query = $this->querySaveRDFData( $configSaveData );
			$output->addHTML( "<br/>Query executed : <pre>" . htmlentities( $query ) . "</pre>" );
			if ( !empty( $query ) ) {
				$endpoint = $configSaveData->getInstanceEndpoint();
				$response = $endpoint->query( $query, 'raw' );
				$err = $endpoint->getErrors();
				if ( $err ) {
					$output->addHTML( "<br/>Error : <pre>" . htmlentities( print_r( $err, true ) ) . "</pre>" );
				} else {
					$output->addHTML( "<br/>Result : <pre>" . htmlentities( $response ) . "</pre>" );
				}
			}
		}

		$jobQueue = JobQueueGroup::singleton()->get( "SynchroniseThreadArticleLinkedDataJob" );
		if ( !empty( $refreshWikiPage ) ) {
			while ( $jobQueue->pop() ) {
   }

			// Find the Nb pages in this wiki
			$dbr = wfGetDB( DB_REPLICA );
			$titleArray = TitleArray::newFromResult(
				$dbr->select( 'page',
					[ 'page_id', 'page_namespace', 'page_title' ]
				) );
			$nbPage = $titleArray->count();
			$output->addHTML( "<br/>Nb pages in this wiki : " . $nbPage );

			$output->addHTML( "<br/>List of pages : <br/><pre>" );
			if ( $nbPage ) {
				foreach ( $titleArray as $title ) {
					$jobParams = [];
					$job = new RefreshLinksJob( $title, $jobParams );
					$output->addHTML( $title->getText() . "\n" );
					JobQueueGroup::singleton()->push( $job );
				}
			}
			$output->addHTML( "</pre>" );
			$output->addHTML( "<br/>Nb inserted job in the queue: " . $nbPage );
		}
		$output->addHTML( "<br/>Nb job in the queue: " . $jobQueue->getSize() );

		// phpcs:disable
		$output->addWikiTextAsInterface(
<<<EOT
By default, jobs are run at the end of a web request. If possible, it is recommended that you disable this default behaviour by setting \$JobRunRate to <code>0</code>, and instead schedule the running of jobs completely in the background, via the command line.
For example, you could use cron to run the jobs every day at midnight by entering the following in your crontab file:
<pre> 0 0 * * * /usr/bin/php /var/www/wiki/maintenance/runJobs.php > /var/log/runJobs.log 2>&1 </pre>
EOT
		);
		// phpcs:enable

		$this->setHeaders();
	}

	/**
	 * @param string $config
	 * @return string
	 */
	public function querySaveRDFData( $config ) {
		$category = Title::newFromText(
			wfMessage( 'linkedwiki-category-rdf-page' )->inContentLanguage()->parse()
		)->getDBKey();

		$dbr = wfGetDB( DB_REPLICA );
		$sql = "SELECT  p.page_id AS pid, p.page_title AS title, t.old_text as text FROM page p 
INNER JOIN revision r ON p.page_latest = r.rev_id
INNER JOIN text t ON r.rev_text_id = t.old_id 
INNER JOIN categorylinks c ON c.cl_from = p.page_id 
INNER JOIN searchindex s ON s.si_page = p.page_id 
 WHERE c.cl_to='" . $category . "' ORDER BY p.page_title ASC";
		// phpcs:disable
		$res = $dbr->query( $sql, __METHOD__ );
		// phpcs:enable
		$q = "";
		$row = $dbr->fetchObject( $res );
		while ( $row ) {
			$q .= $config->getQueryLoadData(
				Title::newFromID( $row->pid )->getFullURL() . '?action=raw&export=rdf'
				)
				. ' ;' . "\n";
			$row = $dbr->fetchObject( $res );
		}

		return $q;
	}
}
