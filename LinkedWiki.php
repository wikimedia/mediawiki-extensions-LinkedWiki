<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
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
	 * @param object &$parser
	 * @return bool
	 */
	public static function parserFirstCallInit( &$parser ) {
		global $wgOut;
		$wgOut->addModules( 'ext.LinkedWiki.table2CSV' );
		$wgOut->addModules( 'ext.LinkedWiki.flowchart' );

		$wgOut->addModules( 'ext.LinkedWiki.SparqlParser' );

		$parser->setHook( 'lwgraph', 'LwgraphTag::render' );
		$parser->setFunctionHook( 'sparql', 'SparqlParser::render' );
		$parser->setFunctionHook( 'wsparql', 'WSparqlParser::render' );

		$parser->setHook( 'rdf', 'RDFTag::render' );
		return true;
	}

	/**
	 * @param object $engine
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
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'wgLinkedWiki' );
		if ( !$config->has( "SPARQLServiceSaveDataOfWiki" ) ) {
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
			$output->addWikiText( "ERROR : " . $message );
		} else {
			$output->addWikiText( "Data deleted." );
		}
		return true;
	}

	/**
	 * @param Title &$title old Title
	 * @param Title &$newTitle new Title
	 * @param User &$user User who did the move
	 * @param int $oldid database page_id of the page that's been moved
	 * @param int $newid database page_id of the created redirect,
	 * or 0 if the redirect was suppressed.
	 * @param string $reason reason for the move
	 * @param Revision $revision revision created by the move
	 * @return bool
	 * @throws Exception
	 */
	public static function onTitleMoveComplete(
		Title &$title, Title &$newTitle, User &$user,
		$oldid, $newid, $reason, Revision $revision ) {
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'wgLinkedWiki' );
		if ( !$config->has( "SPARQLServiceSaveDataOfWiki" ) ) {
			// $output->addHTML("Database by default for the Wiki is not precised
			// in the extension.json of the LinkedWiki extension.
			// (parameter SPARQLServiceSaveDataOfWiki) : no data deleted.");
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
		return true;
	}

	/**
	 * @param OutputPage &$out
	 * @param Skin &$skin
	 * @return bool
	 */
	public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
		$out->addModules( "ext.LinkedWiki.common" );

		if ( $out->getTitle()->isSpecial( 'linkedwiki-specialsparqlquery' ) ) {
			$out->addModules( 'ext.LinkedWiki.SpecialSparqlQuery' );
		}

		// Human or machine request ?
		$accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? $_SERVER['HTTP_ACCEPT'] : "text/turtle";
		if ( strpos( $accept, "text/turtle" ) !== false ) {
			// for machine but we check if there are RDF in the page
			$keyCategoryRDFPage = "Category:"
				. Title::newFromText(
					wfMessage( 'linkedwiki-category-rdf-page' )->inContentLanguage()->parse()
				)->getDBKey();
			$listCategories = $out->getTitle()->getParentCategories();
			if ( ( isset( $listCategories[$keyCategoryRDFPage] ) ) ) {
				header(
					'Location: ' . $out->getTitle()->getFullURL() . "?action=raw&export=rdf"
				);
				exit;
			}
		}
		return true;
	}
}
