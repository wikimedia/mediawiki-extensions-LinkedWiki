<?php

use MediaWiki\MediaWikiServices;

/**
 * @copyright (c) 2021 Bordercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

class ToolsParser {
	/**
	 * @param string $query
	 * @param Parser $parser
	 * @return string
	 */
	public static function parserQuery( $query, $parser ) {
		$res = $query;
		if ( preg_match( "/<PAGEIRI>/i", $res ) ) {
			$uri  = "<" . self::pageIri( $parser ) . ">";
			$res  = str_replace( "<PAGEIRI>", $uri, $res );
		}
		if ( preg_match( "/<DATAIRI>/i", $res ) ) {
			$uri  = "<" . self::dataIri( $parser ) . ">";
			$res  = str_replace( "<DATAIRI>", $uri, $res );
		}
		// remove comments because the mediawiki parser removes \n of the query in the html and the # break the query
		// Comments in SPARQL queries take the form of '#', outside an IRI or string, and continue to the end of line
		//(marked by characters 0x0D or 0x0A) or end of file if there is no end of line after the comment marker.
		// Comments are treated as white space.
		$re = '/((([^"\'<#])*("[^"]*"){0,1}(\'[^\']*\'){0,1}(<[^>]*>){0,1})*)(?:#.*)?/m';
		preg_match_all( $re, $res, $matches, PREG_SET_ORDER, 0 );
		$result = "";
		foreach ( $matches as $group ) {
			$result .= $group[1];
		}
		return $result;
	}

	/**
	 * @param Parser &$parser
	 * @return string
	 */
	public static function pageIri( &$parser ) {
		$target = MediaWikiServices::getInstance()->getNamespaceInfo()->getSubjectPage( $parser->getTitle() );
		$currentNamespace = $target->getNamespace();
		$base = $parser->getTitle()->getBaseText();
		$resolverurl = "";
		if ( $currentNamespace === 2 || $currentNamespace === 3 || $currentNamespace === 10002 ) {
			$resolverurl = urldecode(
				Title::makeTitle( 2, $base )->getFullURL()
			);
		} else {
			$resolverurl = urldecode(
				Title::makeTitle( 0, $base )->getFullURL()
			);
		}
		return $resolverurl;
	}

	/**
	 * @param Parser &$parser
	 * @return string
	 */
	public static function dataIri( &$parser ) {
		$target = MediaWikiServices::getInstance()->getNamespaceInfo()->getSubjectPage( $parser->getTitle() );
		$currentNamespace = $target->getNamespace();
		$resolverurl = "";
		if ( $currentNamespace === 2 || $currentNamespace === 3 ) {
			$resolverurl = urldecode(
				Title::makeTitle( 10002, $parser->getTitle()->getBaseText() )->getFullURL()
			);
		} else {
			$resolverurl = urldecode(
				Title::makeTitle( 10000, $parser->getTitle()->getBaseText() )->getFullURL()
			);
		}
		return $resolverurl;
	}

	/**
	 * @param string $config
	 * @param string $endpoint
	 * @return array
	 */
	public static function newEndpoint( $config, $endpoint ) {
		$errorMessage = null;
		$objConfig = null;

		$errorMessage = "";

		try {
			if ( !empty( $endpoint ) ) {
				$objConfig = new LinkedWikiConfig();
				// $objConfig->setEndpoint($endpoint);
				$objConfig->setEndpointRead( $endpoint );
			} elseif ( !empty( $config ) ) {
				$objConfig = new LinkedWikiConfig( $config );
			} else {
				$objConfig = new LinkedWikiConfig();
			}
		} catch ( Exception $e ) {
			$errorMessage = $e->getMessage();
			return [ 'endpoint' => $endpoint, 'errorMessage' => $errorMessage ];
		}

		$objConfig->setReadOnly( true );
		$endpoint = $objConfig->getInstanceEndpoint();

		return [ 'endpoint' => $endpoint, 'errorMessage' => $errorMessage ];
	}
}
