<?php
/**
 * A special page that allows pushing one or more pages to one or more targets.
 * Partly based on MediaWiki's Special:Export.
 *
 * @since 0.1
 *
 * @file Push_Body.php
 * @ingroup Push
 *
 * @author Jeroen De Dauw  < jeroendedauw@gmail.com >
 * @author Karima Rafes < karima.rafes@gmail.com >
 */
class SpecialPush extends SpecialPage {

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 */
	public function __construct() {
		parent::__construct( 'Push', 'bulkpush' );
	}

	/**
	 * @see SpecialPage::getDescription
	 *
	 * @return mixed
	 */
	public function getDescription() {
		return $this->msg( 'special-' . strtolower( $this->getName() ) )->text();
	}

	/**
	 * Sets headers - this should be called from the execute() method of all derived classes!
	 */
	public function setHeaders() {
		$out = $this->getOutput();
		$out->setArticleRelated( false );
		$out->setRobotPolicy( "noindex,nofollow" );
		$out->setPageTitle( $this->getDescription() );
	}

	/**
	 * Main method.
	 *
	 * @since 0.1
	 *
	 * @param string $arg
	 */
	public function execute( $arg ) {
		// global $egPushTargets;
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'egPush' );
		$egPushTargets = [];
		if ( !$config->has( "Targets" ) ) {
			// throw new MWException( "$egPushTargets is not precised in the bundle/Push/extension.json
			// of the LinkedWiki extension." );
		} else {
			$egPushTargets = $config->get( "Targets" );
		}

		$req = $this->getRequest();

		$this->setHeaders();
		$this->outputHeader();

		// If the user is authorized, display the page, if not, show an error.
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
			return;
		}

		if ( count( $egPushTargets ) == 0 ) {
			$this->getOutput()->addHTML( '<p>' . $this->msg( 'push-tab-no-targets' )->escaped() . '</p>' );
			return;
		}

		$doPush = false;

		if ( $req->getCheck( 'addcat' ) ) {
			$pages = $req->getText( 'pages' );
			$catname = $req->getText( 'catname' );

			if ( $catname !== '' && $catname !== null && $catname !== false ) {
				$t = Title::makeTitleSafe( NS_MAIN, $catname );
				if ( $t ) {
					/**
					 * @todo Fixme: this can lead to hitting memory limit for very large
					 * categories. Ideally we would do the lookup synchronously
					 * during the export in a single query.
					 */
					$catpages = $this->getPagesFromCategory( $t );
					if ( $catpages ) {
						$pages .= "\n" . implode( "\n", $catpages );
					}
				}
			}
		} elseif ( $req->getCheck( 'addns' ) ) {
			$pages = $req->getText( 'pages' );
			$nsindex = $req->getText( 'nsindex', '' );

			if ( strval( $nsindex ) !== '' ) {
				/**
				 * Same implementation as above, so same @todo
				 */
				$nspages = $this->getPagesFromNamespace( $nsindex );
				if ( $nspages ) {
					$pages .= "\n" . implode( "\n", $nspages );
				}
			}
		} elseif ( $req->wasPosted() ) {
			$pages = $req->getText( 'pages' );
			if ( $pages != '' ) {
				$doPush = true;
			}
		} else {
			$pages = '';
		}

		if ( $doPush ) {
			$this->doPush( $pages );
		} else {
			$this->displayPushInterface( $pages );
		}
	}

	/**
	 * Outputs the HTML to indicate a push is occurring and
	 * the JavaScript to needed by the push.
	 *
	 * @since 0.2
	 *
	 * @param string $pages
	 */
	protected function doPush( $pages ) {
		global $wgSitename;
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'egPush' );
		$egPushTargets = [];
		$egPushBulkWorkers = 3;
		$egPushBatchSize = 3;
		if ( !$config->has( "Targets" ) ) {
			// throw new MWException( "egPushPushTargets  is not precised in the localsettings." );
		} else {
			$egPushTargets = $config->get( "Targets" );
		}
		if ( !$config->has( "BulkWorkers" ) ) {
			// throw new MWException( "egPushPushBulkWorkers  is not precised in the localsettings." );
		} else {
			$egPushBulkWorkers = $config->get( "BulkWorkers" );
		}
		if ( !$config->has( "BatchSize" ) ) {
			// throw new MWException( "egPushPushBatchSize  is not precised in the localsettings." );
		} else {
			$egPushBatchSize = $config->get( "BatchSize" );
		}

		// Inverted index of all pages to look up
		$pageSet = [];

		// Split up and normalize input
		foreach ( explode( "\n", $pages ) as $pageName ) {
			$pageName = trim( $pageName );
			$title = Title::newFromText( $pageName );
			if ( $title && $title->getInterwiki() == '' && $title->getText() !== '' ) {
				// Only record each page once!
				$pageSet[$title->getPrefixedText()] = true;
			}
		}

		// Look up any linked pages if asked...
		if ( $this->getRequest()->getCheck( 'templates' ) ) {
			$pageSet = PushFunctions::getTemplates( array_keys( $pageSet ), $pageSet );
		}

		$pages = array_keys( $pageSet );

		$targets = [];
		$links = [];

		if ( count( $egPushTargets ) > 1 ) {
			foreach ( $egPushTargets as $targetName => $targetUrl ) {
				if ( $this->getRequest()->getCheck( str_replace( ' ', '_', $targetName ) ) ) {
					$targets[$targetName] = $targetUrl;
					$links[] = "[$targetUrl $targetName]";
				}
			}
		} else {
			$targets = $egPushTargets;
		}

		$out = $this->getOutput();

		$out->addWikiMsg(
			'push-special-pushing-desc',
			$this->getLanguage()->listToText( $links ),
			$this->getLanguage()->formatNum( count( $pages ) )
		);

		$out->addHTML(
			Html::hidden( 'siteName', $wgSitename, [ 'id' => 'siteName' ] ) .
			Html::rawElement(
				'div',
				[
					'id' => 'pushResultDiv',
					'style' => 'width: 100%; height: 300px; overflow: auto'
				],
				Html::rawElement(
					'div',
					[ 'class' => 'innerResultBox' ],
					Html::element( 'ul', [ 'id' => 'pushResultList' ] )
				)
			) . '<br />' .
			Html::element(
				'a',
				[ 'href' => $this->getPageTitle()->getInternalURL() ],
				$this->msg( 'push-special-return' )->text()
			)
		);

		$out->addInlineScript(
			'var wgPushPages = ' . FormatJson::encode( $pages ) . ';' .
			'var wgPushTargets = ' . FormatJson::encode( $targets ) . ';' .
			'var wgPushWorkerCount = ' . $egPushBulkWorkers . ';' .
			'var wgPushBatchSize = ' . $egPushBatchSize . ';' .
			'var wgPushIncFiles = ' . ( $this->getRequest()->getCheck( 'files' ) ? 'true' : 'false' ) . ';'
		);

		$out->addModules( 'ext.push.special' );
	}

	/**
	 * @since 0.2
	 * @param string $pages
	 */
	protected function displayPushInterface( $pages ) {
		// global $egPushTargets, $egPushIncTemplates, $egPushIncFiles;
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'egPush' );
		$egPushTargets = [];
		$egPushIncTemplates = [];
		$egPushIncFiles = [];
		if ( !$config->has( "Targets" ) ) {
			// throw new MWException( "egPushTargets  is not precised in the localsettings." );
		} else {
			$egPushTargets = $config->get( "Targets" );
		}
		if ( !$config->has( "IncTemplates" ) ) {
			// throw new MWException( "egPushBulkWorkers  is not precised in the localsettings." );
		} else {
			$egPushIncTemplates = $config->get( "IncTemplates" );
		}
		if ( !$config->has( "IncFiles" ) ) {
			// throw new MWException( "egPushIncFiles  is not precised in the localsettings." );
		} else {
			$egPushIncFiles = $config->get( "IncFiles" );
		}

		$req = $this->getRequest();

		$this->getOutput()->addWikiMsg( 'push-special-description' );

		$form = Xml::openElement( 'form',
			[
				'method' => 'post',
				'action' => $this->getPageTitle()->getLocalURL( 'action=submit' )
			]
		);
		$form .= Xml::inputLabel(
			$this->msg( 'export-addcattext' )->text(),
			'catname',
			'catname',
			40
			) . '&#160;';
		$form .= Xml::submitButton(
			$this->msg( 'export-addcat' )->text(),
			[ 'name' => 'addcat' ]
			) . '<br />';

		$form .= Html::namespaceSelector( [
			'selected' => $req->getText( 'nsindex', '' ),
			'all' => null,
			'label' => $this->msg( 'export-addnstext' )->text(),
		], [
			'name' => 'nsindex',
			'id' => 'namespace',
			'class' => 'namespaceselector',
		] ) . '&#160;';
		$form .= Xml::submitButton(
			$this->msg( 'export-addns' )->text(),
			[ 'name' => 'addns' ]
			) . '<br />';

		$form .= Xml::element(
			'textarea',
			[ 'name' => 'pages', 'cols' => 40, 'rows' => 10 ],
			$pages,
			false
		);
		$form .= '<br />';

		$form .= Xml::checkLabel(
			$this->msg( 'export-templates' )->text(),
			'templates',
			'wpPushTemplates',
			$req->wasPosted() ? $req->getCheck( 'templates' ) : $egPushIncTemplates
		) . '<br />';

		if ( $this->getUser()->isAllowed( 'filepush' ) ) {
			$form .= Xml::checkLabel(
				$this->msg( 'push-special-inc-files' )->text(),
				'files',
				'wpPushFiles',
				$req->wasPosted() ? $req->getCheck( 'files' ) : $egPushIncFiles
			) . '<br />';
		}

		if ( count( $egPushTargets ) == 1 ) {
			$names = array_keys( $egPushTargets );
			$form .= '<b>' . $this->msg( 'push-special-target-is', $names[0] )->parse() . '</b><br />';
		} else {
			$form .= '<b>' . $this->msg( 'push-special-select-targets' )->escaped() . '</b><br />';

			foreach ( $egPushTargets as $targetName => $targetUrl ) {
				$checkName = str_replace( ' ', '_', $targetName );
				$checked = $req->wasPosted() ? $req->getCheck( $checkName ) : true;
				$form .= Xml::checkLabel( $targetName, $checkName, $targetName, $checked ) . '<br />';
			}
		}

		$form .= Xml::submitButton(
			$this->msg( 'push-special-button-text' )->text(),
			[ 'style' => 'width: 125px; height: 30px' ]
		);
		$form .= Xml::closeElement( 'form' );

		$this->getOutput()->addHTML( $form );
	}

	/**
	 * Returns all pages for a category (up to 5000).
	 *
	 * @since 0.2
	 *
	 * @param Title $title
	 *
	 * @return array
	 */
	protected function getPagesFromCategory( Title $title ) {
		global $wgContLang;

		$name = $title->getDBkey();

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			[ 'page', 'categorylinks' ],
			[ 'page_namespace', 'page_title' ],
			[ 'cl_from=page_id', 'cl_to' => $name ],
			__METHOD__,
			[ 'LIMIT' => '5000' ]
		);

		$pages = [];

		foreach ( $res as $row ) {
			$n = $row->page_title;
			if ( $row->page_namespace ) {
				$ns = $wgContLang->getNsText( $row->page_namespace );
				$n = $ns . ':' . $n;
			}

			$pages[] = $n;
		}
		return $pages;
	}

	/**
	 * Returns all pages for a namespace (up to 5000).
	 *
	 * @since 0.2
	 *
	 * @param int $nsindex
	 *
	 * @return array
	 */
	protected function getPagesFromNamespace( $nsindex ) {
		global $wgContLang;

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			'page',
			[ 'page_namespace', 'page_title' ],
			[ 'page_namespace' => $nsindex ],
			__METHOD__,
			[ 'LIMIT' => '5000' ]
		);

		$pages = [];

		foreach ( $res as $row ) {
			$n = $row->page_title;

			if ( $row->page_namespace ) {
				$ns = $wgContLang->getNsText( $row->page_namespace );
				$n = $ns . ':' . $n;
			}

			$pages[] = $n;
		}
		return $pages;
	}

	/**
	 * get GroupName : pagetools
	 *
	 * @return string
	 */
	protected function getGroupName() {
		return 'pagetools';
	}
}
