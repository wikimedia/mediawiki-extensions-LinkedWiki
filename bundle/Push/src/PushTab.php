<?php
/**
 * Static class with methods to create and handle the push tab.
 *
 * @since 0.1
 *
 * @file Push_Tab.php
 * @ingroup Push
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Karima Rafes < karima.rafes@gmail.com >
 */
final class PushTab {

	/**
	 * Adds an "action" (i.e., a tab) to allow pushing the current article.
	 * @param Object $obj
	 * @param array &$content_actions
	 * @return bool
	 */
	public static function displayTab( $obj, &$content_actions ) {
		global $wgUser;

		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'egPush' );
		$egPushTargets = [];
		if ( !$config->has( "Targets" ) ) {
			// throw new MWException( "$egPushTargets is not precised in the localsettings." );
		} else {
			$egPushTargets = $config->get( "Targets" );
		}

		/**
		 * Make sure that this is not a special page, the page has contents, and the user can push.
		 *
		 * @var Title $title
		 */
		$title = $obj->getTitle();
		if (
			$title->getNamespace() !== NS_SPECIAL
			&& $title->exists()
			&& $wgUser->isAllowed( 'push' )
			&& count( $egPushTargets ) > 0 ) {

			global $wgRequest;

			$content_actions['push'] = [
				'text' => wfMessage( 'push-tab-text' )->text(),
				'class' => $wgRequest->getVal( 'action' ) == 'push' ? 'selected' : '',
				'href' => $title->getLocalURL( 'action=push' )
			];
		}

		return true;
	}

	/**
	 * Function currently called only for the 'Vector' skin, added in
	 * MW 1.16 - will possibly be called for additional skins later
	 *
	 * @param Object $obj
	 * @param array &$links
	 * @return bool
	 */
	public static function displayTab2( $obj, &$links ) {
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'egPush' );
		$egPushShowTab = false;
		if ( !$config->has( "ShowTab" ) ) {
			// throw new MWException(
			// "$egPushShowTab is not precised in the localsettings." );
		} else {
			$egPushShowTab = $config->get( "ShowTab" );
		}

		// The old '$content_actions' array is thankfully just a sub-array of this one
		$views_links = $links[$egPushShowTab ? 'views' : 'actions'];
		self::displayTab( $obj, $views_links );
		$links[$egPushShowTab ? 'views' : 'actions'] = $views_links;

		return true;
	}

	/**
	 * Handle actions not known to MediaWiki. If the action is push,
	 * display the push page by calling the displayPushPage method.
	 *
	 * @param string $action
	 * @param Article $article
	 *
	 * @return true
	 */
	public static function onUnknownAction( $action, Article $article ) {
		if ( $action !== 'push' ) {
			return true;
		}
	}

	/**
	 * Todo
	 *
	 * @param Object $output
	 * @param Object $article
	 * @param Object $title
	 * @param Object $user
	 * @param Object $request
	 * @param Object $mediaWiki
	 * @return bool
	 */
	public static function  onMediaWikiPerformAction(
		$output, $article, $title, $user, $request, $mediaWiki ) {
		if ( $mediaWiki->getAction() === 'nosuchaction' ) {
			if ( $request->getText( 'action' ) === 'push' ) {
				return self::displayPushPage( $article );
			}
		}
		return true;
	}

	/**
	 * The function called if we're in index.php (as opposed to one of the
	 * special pages)
	 *
	 * @since 0.1
	 * @param Article $article
	 * @return bool
	 * @throws PermissionsError
	 */
	public static function displayPushPage( Article $article ) {
		global $wgOut, $wgUser, $wgSitename, $wgRequest;
		$wgTitle = Title::newFromText( $wgRequest->getVal( 'title' ) );

		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'egPush' );
		$egPushTargets = [];
		if ( !$config->has( "Targets" ) ) {
			// throw new MWException(
			// "$egPushTargets is not precised in the localsettings." );
		} else {
			$egPushTargets = $config->get( "Targets" );
		}

		$wgOut->setPageTitle( wfMessage( 'push-tab-title', $article->getTitle()->getText() )->parse() );

		if ( !$wgUser->isAllowed( 'push' ) ) {
			throw new PermissionsError( 'push' );
		}

		$wgOut->addHTML( '<p>' . wfMessage( 'push-tab-desc' )->escaped() . '</p>' );

		if ( count( $egPushTargets ) == 0 ) {
			$wgOut->addHTML( '<p>' . wfMessage( 'push-tab-no-targets' )->escaped() . '</p>' );
			return false;
		}

		$wgOut->addModules( 'ext.push.tab' );

		$wgOut->addHTML(
			Html::hidden( 'pageName', $wgTitle->getFullText(), [ 'id' => 'pageName' ] ) .
			Html::hidden( 'siteName', $wgSitename, [ 'id' => 'siteName' ] )
		);

		self::displayPushList();

		self::displayPushOptions();

		return false;
	}

	/**
	 * Displays a list with all targets to which can be pushed.
	 *
	 * @since 0.1
	 */
	protected static function displayPushList() {
		global $wgOut;

		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'egPush' );
		$egPushTargets = [];
		if ( !$config->has( "Targets" ) ) {
			// throw new MWException( "$egPushTargets is not precised in the localsettings." );
		} else {
			$egPushTargets = $config->get( "Targets" );
		}

		$items = [
			Html::rawElement(
				'tr',
				[],
				Html::element(
					'th',
					[ 'width' => '200px' ],
					wfMessage( 'push-targets' )->text()
				) .
				Html::element(
					'th',
					[ 'style' => 'min-width:400px;' ],
					wfMessage( 'push-remote-pages' )->text()
				) .
				Html::element(
					'th',
					[ 'width' => '125px' ],
					''
				)
			)
		];

		foreach ( $egPushTargets as $name => $url ) {
			$items[] = self::getPushItem( $name, $url );
		}

		// If there is more then one item, display the 'push all' row.
		if ( count( $egPushTargets ) > 1 ) {
			$items[] = Html::rawElement(
				'tr',
				[],
				Html::element(
					'th',
					[ 'colspan' => 2, 'style' => 'text-align: left' ],
					wfMessage( 'push-targets-total' )->numParams( count( $egPushTargets ) )->parse()
				) .
				Html::rawElement(
					'th',
					[ 'width' => '125px' ],
					Html::element(
						'button',
						[
							'id' => 'push-all-button',
							'style' => 'width: 125px; height: 30px',
						],
						wfMessage( 'push-button-all' )->text()
					)
				)
			);
		}

		$wgOut->addHTML(
			Html::rawElement(
				'table',
				[ 'class' => 'wikitable', 'width' => '50%' ],
				implode( "\n", $items )
			)
		);
	}

	/**
	 * Returns the HTML for a single push target.
	 *
	 * @since 0.1
	 *
	 * @param string $name
	 * @param string $url
	 *
	 * @return string
	 */
	protected static function getPushItem( $name, $url ) {
		global $wgRequest;
		$wgTitle = Title::newFromText( $wgRequest->getVal( 'title' ) );

		static $targetId = 0;
		$targetId++;

		return Html::rawElement(
			'tr',
			[],
			Html::element(
				'td',
				[],
				$name
			) .
			Html::rawElement(
				'td',
				[ 'height' => '45px' ],
				Html::element(
					'a',
					[
						'href' => $url . '/index.php?title=' . $wgTitle->getFullText(),
						'rel' => 'nofollow',
						'id' => 'targetlink' . $targetId
					],
					wfMessage( 'push-remote-page-link', $wgTitle->getFullText(), $name )->parse()
				) .
				Html::element(
					'div',
					[
						'id' => 'targetinfo' . $targetId,
						'style' => 'display:none; color:darkgray'
					]
				) .
				Html::element(
					'div',
					[
						'id' => 'targettemplateconflicts' . $targetId,
						'style' => 'display:none; color:darkgray'
					]
				) .
				Html::element(
					'div',
					[
						'id' => 'targetfileconflicts' . $targetId,
						'style' => 'display:none; color:darkgray'
					]
				) .
				Html::element(
					'div',
					[
						'id' => 'targeterrors' . $targetId,
						'style' => 'display:none; color:darkred'
					]
				)
			) .
			Html::rawElement(
				'td',
				[],
				Html::element(
					'button',
					[
						'class' => 'push-button',
						'pushtarget' => $url,
						'style' => 'width: 125px; height: 30px',
						'targetid' => $targetId,
						'targetname' => $name
					],
					wfMessage( 'push-button-text' )->text()
				)
			)
		);
	}

	/**
	 * Outputs the HTML for the push options.
	 *
	 * @since 0.4
	 */
	protected static function displayPushOptions() {
		global $wgOut, $wgUser, $wgRequest ,$wgScript;
		$wgTitle = Title::newFromText( $wgRequest->getVal( 'title' ) );

		$wgOut->addHTML( '<h3>' . wfMessage( 'push-tab-push-options' )->escaped() . '</h3>' );

		$allpages = PushFunctions::getSubpages(
			[ $wgTitle->getFullText() ],
			[ $wgTitle->getFullText() => true ]
		);

		$subpages = array_keys( $allpages );
		// Get rid of the page itself.
		array_shift( $subpages );

		$templates =
			array_keys(
				PushFunctions::getTemplates(
					[ $wgTitle->getFullText() ],
					[ $wgTitle->getFullText() => true ]
				)
			);
		// Get rid of the page itself.
		array_shift( $templates );

		$wgOut->addInlineScript(
			'var wgPushTemplates = ' . FormatJson::encode( $templates ) . ';'
		);

		$subpagesTemplates =
			array_values( array_diff( array_keys(
				PushFunctions::getTemplates(
					array_keys( $allpages ),
					$allpages
				)
			), $templates, $subpages, [ $wgTitle->getFullText() ] ) );

		$pageFiles = PushFunctions::getImages( [ $wgTitle->getFullText() ] );
		$templateFiles = PushFunctions::getImages( $templates );

		$wgOut->addInlineScript(
			'var wgPushPageFiles = ' . FormatJson::encode( $pageFiles ) . ';' .
			'var wgPushTemplateFiles = ' . FormatJson::encode( $templateFiles ) . ';' .
			'var wgPushIndexPath = ' . FormatJson::encode( $wgScript )
		);

		$subpageFiles = PushFunctions::getImages( array_merge( $subpages, $subpagesTemplates ) );
		$wgOut->addInlineScript(
			'var wgPushSubpages = ' . FormatJson::encode( $subpages ) . ';' . "\n" .
			'var wgPushSubpagesTemplates = ' . FormatJson::encode( $subpagesTemplates ) . ';' . "\n" .
			'var wgPushSubpagesFiles = ' . FormatJson::encode( $subpageFiles ) . ';' . "\n"
		);

		self::displayIncSubpagesOption( $subpages );
		self::displayIncTemplatesOption( $templates, $subpagesTemplates );

		if ( $wgUser->isAllowed( 'filepush' ) ) {
			self::displayIncFilesOption();
		}
	}

	/**
	 * Outputs the HTML for the "include templates" option.
	 *
	 * @since 0.4
	 *
	 * @param array $templates
	 * @param array $templatesSubpage
	 */
	protected static function displayIncTemplatesOption( array $templates, array $templatesSubpage ) {
		global $wgOut;
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'egPush' );
		$egPushIncTemplates = false;
		if ( !$config->has( "IncTemplates" ) ) {
			// throw new MWException( "$egPushIncTemplates is not precised in the localsettings." );
		} else {
			$egPushIncTemplates = $config->get( "IncTemplates" );
		}

		foreach ( $templates as &$template ) {
			$template = "[[$template]]";
		}
		foreach ( $templatesSubpage as &$template ) {
			$template = "[[$template]]";
		}

		$wgOut->addHTML(
			Html::rawElement(
				'div',
				[ 'id' => 'divIncTemplates', 'style' => 'display: table-row' ],
				Xml::check( 'checkIncTemplates', $egPushIncTemplates, [ 'id' => 'checkIncTemplates' ] ) .
				Html::element(
					'label',
					[ 'id' => 'lblIncTemplates', 'for' => 'checkIncTemplates' ],
					wfMessage( 'push-tab-inc-templates' )->text()
				) .
				'&#160;' .
				Html::rawElement(
					'div',
					[ 'style' => 'display:none; opacity:0', 'id' => 'txtTemplateList' ],
					''
				)
			)
		);
	}

	/**
	 * Outputs the HTML for the "include subpages" option.
	 *
	 * @since 1.4.0
	 *
	 * @param array $subpages
	 */
	protected static function displayIncSubpagesOption( array $subpages ) {
		global $wgOut, $wgLang;
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'egPush' );
		$egPushIncSubpages = false;
		if ( !$config->has( "IncSubpages" ) ) {
			// throw new MWException( "$egPushIncSubpages is not precised in the localsettings." );
		} else {
			$egPushIncSubpages = $config->get( "IncSubpages" );
		}

		foreach ( $subpages as &$subpage ) {
			$subpage = "[[$subpage]]";
		}

		$wgOut->addHTML(
			Html::rawElement(
				'div',
				[ 'id' => 'divIncSubpages', 'style' => 'display: table-row' ],
				Xml::check( 'checkIncSubpages', $egPushIncSubpages, [ 'id' => 'checkIncSubpages' ] ) .
				Html::element(
					'label',
					[ 'id' => 'lblIncSubpages', 'for' => 'checkIncSubpages' ],
					wfMessage( 'push-tab-inc-subpages' )->text()
				) .
				'&#160;' .
				Html::rawElement(
					'div',
					[ 'style' => 'display:none; opacity:0', 'id' => 'txtSubpageList' ],
					count( $subpages ) > 0 ?
						wfMessage( 'push-tab-used-subpages',
							$wgLang->listToText( $subpages ), count( $subpages ) )->parse() :
						wfMessage( 'push-tab-no-used-subpages' )->escaped()
				)
			)
		);
	}

	/**
	 * Outputs the HTML for the "include files" option.
	 *
	 * @since 0.4
	 *
	 */
	protected static function displayIncFilesOption() {
		global $wgOut;
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'egPush' );
		$egPushIncFiles = false;
		if ( !$config->has( "IncFiles" ) ) {
			// throw new MWException( "$egPushIncFiles is not precised in the localsettings." );
		} else {
			$egPushIncFiles = $config->get( "IncFiles" );
		}

		$wgOut->addHTML(
			Html::rawElement(
				'div',
				[ 'id' => 'divIncFiles', 'style' => 'display: table-row' ],
				Xml::check( 'checkIncFiles', $egPushIncFiles, [ 'id' => 'checkIncFiles' ] ) .
				Html::element(
					'label',
					[ 'id' => 'lblIncFiles', 'for' => 'checkIncFiles' ],
					wfMessage( 'push-tab-inc-files' )->text()
				) .
				'&#160;' .
				Html::rawElement(
					'div',
					[ 'style' => 'display:none; opacity:0', 'id' => 'txtFileList' ],
					''
				)
			)
		);
	}

}
