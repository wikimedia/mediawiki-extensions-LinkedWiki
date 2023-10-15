<?php

use MediaWiki\MediaWikiServices;

/**
 * @copyright (c) 2021 Bordercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

class LinkedWiki {
	/**
	 * @return GlobalVarConfig
	 */
	public static function makeConfig() {
		return new GlobalVarConfig( 'wgLinkedWiki' );
	}

	/**
	 * @param Parser &$parser
	 * @return bool
	 */
	public static function parserFirstCallInit( &$parser ) {
		$parser->setFunctionHook( 'sparql', 'SparqlParser::render' );
		$parser->setHook( 'rdf', 'RDFTag::render' );
		return true;
	}

	/**
	 * @param Parser &$parser
	 * @return bool
	 */
	public static function onParserClearState( &$parser ) {
		LinkedWikiStatus::clearPagePropertiesViaParser( $parser );
	}

	/**
	 * @param string $engine
	 * @param array &$extraLibraries
	 * @return bool
	 */
	public static function scribuntoExternalLibraries( $engine, array &$extraLibraries ) {
		if ( $engine !== 'lua' ) {
			return true;
		}
		// Lua extension Doc :  https://www.mediawiki.org/wiki/Extension:Scribunto/Example_extension
		$extraLibraries['linkedwiki'] = 'LinkedWikiLuaLibrary';
		return true;
	}

	/**
	 * @param Title $title
	 * @param OutputPage $output
	 * @return bool
	 * @throws Exception
	 */
	public static function onArticleDeleteAfterSuccess( Title $title, OutputPage $output ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wgLinkedWiki' );
		if ( !$config->has( "SPARQLServiceSaveDataOfWiki" )
			|| empty( $config->get( "SPARQLServiceSaveDataOfWiki" ) ) ) {
			$output->addHTML( "Database by default for the Wiki is not precised
			in the extension.json of the LinkedWiki extension.
			(parameter SPARQLServiceSaveDataOfWiki) : no data deleted." );
			return true;
		}

		$configDefaultSaveData = $config->get( "SPARQLServiceSaveDataOfWiki" );
		$configSaveData = new LinkedWikiConfig( $configDefaultSaveData );

		$subject = "<" . urldecode( $title->getFullURL() ) . ">";

		$parameters = [ "?subject" ];
		$values = [ $subject ];
		$q = str_replace( $parameters,
			$values,
			$configSaveData->getQueryDeleteSubject() );

		$endpoint = $configSaveData->getInstanceEndpoint();
		$response = $endpoint->query( $q, 'raw' );

		$err = $endpoint->getErrors();
		if ( $err ) {
			$message = $configSaveData->isDebug() ?
				$response . print_r( $err, true )
				: "ERROR SPARQL (see details in mode debug)";
			$output->addWikiTextAsInterface( "ERROR : " . $message );
		} else {
			$output->addWikiTextAsInterface( "Data deleted." );
		}
		return true;
	}

	/**
	 * Occurs immediately before a file or other page is moved
	 *
	 * @param Title &$oldtitle Title object of the old article (moved from)
	 * @param Title &$newtitle Title object of the new article (moved to)
	 * @param User &$user
	 * @return bool
	 * @throws Exception
	 */
	public static function onTitleMove( Title &$oldtitle, Title &$newtitle, User &$user ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wgLinkedWiki' );
		if ( !$config->has( "SPARQLServiceSaveDataOfWiki" )
			|| empty( $config->get( "SPARQLServiceSaveDataOfWiki" ) ) ) {
// $form->getOutput()->addHTML("Database by default for the Wiki is not precised
//			 in the extension.json of the LinkedWiki extension.
//			 (parameter SPARQLServiceSaveDataOfWiki) : no data deleted.");
			return true;
		}

		$configDefaultSaveData = $config->get( "SPARQLServiceSaveDataOfWiki" );
		$configSaveData = new LinkedWikiConfig( $configDefaultSaveData );
		$subject = "<" . urldecode( $oldtitle->getFullURL() ) . ">";

		$parameters = [ "?subject" ];
		$values = [ $subject ];
		$q = str_replace( $parameters,
			$values,
			$configSaveData->getQueryDeleteSubject() );

		$endpoint = $configSaveData->getInstanceEndpoint();
		$response = $endpoint->query( $q, 'raw' );
		return true;
	}

	/**
	 * @param OutputPage &$out
	 * @param Skin &$skin
	 * @return bool
	 */
	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		$title = $out->getTitle();

		if ( $title->isSpecial( 'linkedwiki-specialsparqlquery' ) ) {
			$out->addModules( [ 'ext.LinkedWiki.SpecialSparqlQuery' ] );
		}

		// Human or machine request ?
		$accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? $_SERVER['HTTP_ACCEPT'] : "text/turtle";
		if ( strpos( $accept, "text/turtle" ) !== false ) {
			if ( LinkedWikiStatus::isPageWithRDF( $title ) ) {
				header(
					'Location: ' . $out->getTitle()->getFullURL() . "?action=raw&export=rdf"
				);
				exit;
			}
		}
		return true;
	}

	/**
	 * Adds an "action" (i.e., a tab) to allow the purge of the current page.
	 *
	 * @param SkinTemplate $sktemplate
	 * @param array &$links
	 */
	public static function onSkinTemplateNavigationUniversal( SkinTemplate $sktemplate, array &$links ) {
		$title = $sktemplate->getTitle();
		$request = $sktemplate->getRequest();
		$links['actions']['linkedwiki-purge'] = [
			'text' => wfMessage( 'purge' )->text(),
			'class' => $request->getVal( 'action' ) == 'purge' ? 'selected' : '',
			'href' => $title->getLocalURL( 'action=purge' )
		];
		if ( LinkedWikiStatus::isPageWithRDF( $title ) ) {
			$links['actions']['linkedwiki-turtle'] = [
				'text' => 'Turtle',
				'class' => $request->getVal( 'action' ) == 'raw'
							&& $request->getVal( 'export' ) == 'rdf'
							? 'selected' : '',
				'href' => $title->getLocalURL( 'action=raw&export=rdf' )
			];
		}
	}
}
