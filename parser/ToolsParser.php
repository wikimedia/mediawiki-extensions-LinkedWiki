<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
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
	public static function  pageIri( &$parser ) {
		// $resolverurl = $parser->getTitle()->getFullURL();
		$resolverurl = urldecode( $parser->getTitle()->getSubjectPage()->getFullURL() );
		return $resolverurl;
	}

	/**
	 * @param string $config
	 * @param string $endpoint
	 * @return array
	 */
	public static function  newEndpoint( $config, $endpoint ) {
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
