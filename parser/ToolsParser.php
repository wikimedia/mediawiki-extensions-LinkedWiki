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
		// remove comments
		$array = explode( "\n", $res );
		$output = [];
		foreach ( $array as $line ) {
			if ( preg_match( "/^ *#/", $line ) ) {
				// do nothing
			} elseif ( preg_match( "/#[^<>]*$/", $line ) ) {
				$output[] = preg_replace( "/#[^<>]*$/", "", $line );
			} else {
				$output[] = $line;
			}
		}
		$res = implode( "\n", $output );
		return $res;
	}

	/**
	 * @param Parser &$parser
	 * @return string
	 */
	public static function pageIri( &$parser ) {
		// $resolverurl = $parser->getTitle()->getFullURL();
		$resolverurl = urldecode(
			MediaWikiServices::getInstance()->getNamespaceInfo()->getSubjectPage( $parser->getTitle() )->getFullURL()
		);
		return $resolverurl;
	}

	/**
	 * @param Parser &$parser
	 * @return string
	 */
	public static function dataIri( &$parser ) {
		// $resolverurl = $parser->getTitle()->getFullURL();
		$target = MediaWikiServices::getInstance()->getNamespaceInfo()->getSubjectPage( $parser->getTitle() );
		$currentNamespace = $target->getNamespace();
		$resolverurl = "";
		if ( $currentNamespace === 2 ) {
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
