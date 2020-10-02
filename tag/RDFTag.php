<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

class RDFTag {

	/**
	 * @param string $input
	 * @param array $args
	 * @param Parser $parser
	 * @param PPFrame $frame
	 * @return array
	 */
	public static function render( $input, array $args, Parser $parser, PPFrame $frame ) {
		if ( class_exists( SyntaxHighlight::class ) ) {
			// print RDF with the extension : SyntaxHighlight_GesShi
			$output = $parser->recursiveTagParse(
				"<syntaxhighlight lang=\"sparql\">" . $input . "</syntaxhighlight>",
				$frame
			);
		} else {
			$output = $parser->recursiveTagParse( "<pre>" . $input . "</pre>", $frame );
		}

		$parser->addTrackingCategory( 'linkedwiki-category-rdf-page' );

		$constraint = isset( $args['constraint'] ) ? strtolower( $args['constraint'] ) : '';
		if ( $constraint == "shacl" ) {
			$parser->addTrackingCategory( 'linkedwiki-category-rdf-schema' );
		}

		return [ $output, 'isHTML' => true ];
	}

	/**
	 * @param string $wikicode
	 * @param string $IRISource
	 * @return mixed
	 */
	public static function convertWikiCode2Turtle( $wikicode, $IRISource ) {
		$textTemp = "";
		preg_match_all(
			'#<rdf.*?>(.*?)</rdf>#is',
			$wikicode,
			$matches
		);
		foreach ( $matches[1] as $source ) {
			$textTemp .= $source;
		}
		$parameters = [ "?subject","?type","?property" ];
		$iri = "<" . $IRISource . ">";
		$values = [ $iri,$iri,$iri ];
		$text = str_replace( $parameters,
			$values,
			$textTemp );

		return $text;
	}

	/**
	 * @param object &$rawAction
	 * @param string &$text
	 * @return bool
	 */
	public static function rawRDFSource( &$rawAction, &$text ) {
		// test with ?action=raw&export=rdf

		if ( $rawAction != '' && isset( $_REQUEST["export"] ) ) {
			$tag = $_REQUEST["export"];
			// default : turtle
			if ( $tag == "rdf" ) {
				header( "Content-type: text/turtle" );
				header( "Expires: 0" );
				header( "Pragma: no-cache" );
				header( "Cache-Control: no-store" );

				$text = self::convertWikiCode2Turtle(
					$text, $rawAction->getTitle()->getFullURL()
				);
				// print_r($rawAction,true);
				return true;
			}
		}
		return false;
	}

	/**
	 * @param object $context
	 * @param object $content
	 * @return mixed|string
	 */
	public static function checkErrorWithRapper( $context, $content ) {
		$error = "";

		$fullURL = $context->getTitle()->getFullURL();
		// old code : delete ?
		// $tag = "rdf";
		// $format = "";
		// $shaclSchemasArray = [];
		// $shaclSchemasArrayIri = [];
		// str_replace($wikiPage->getTitle()->getBaseText()

		$badChar = [ ".","/"," " ];
		$filename = '/tmp/' . str_replace( $badChar, "", $context->getTitle()->getDBKey() ) . '.ttl';
		$commandRDFUnit = "rapper -i turtle \"" . $filename . "\"  ";
		// check RDF
		$turtle = self::convertWikiCode2Turtle( $content->getWikitextForTransclusion(), $fullURL );

		$out = fopen( $filename, "w" );
		fwrite( $out, $turtle );
		fclose( $out );

		exec( $commandRDFUnit . " 2>&1", $retval );
		$textRetval = print_r( $retval, true );
		if ( preg_match( "#URI .*:(.*) - (.*)#", $textRetval, $matches ) ) {
			$error = "Rapper detected an error in this RDF page : " . $matches[2] . "\n";
			// Write message
			$arrayTurtle = explode( "\n", $turtle );
			for ( $i = 1; $i < count( $arrayTurtle ); $i++ ) {
				$space = " ";
				if ( $i == $matches[1] ) {
					$space = "*";
				}
				$error .= sprintf( " %'{$space}5d : %s\n", $i, $arrayTurtle[$i] );
			}

		} elseif ( preg_match( "#.*Error.*#", $textRetval ) ) {
			$error = $textRetval;
		}

		unlink( $filename );
		return $error;
	}

	/**
	 * @param object $context
	 * @param object $content
	 * @param object $status
	 * @param object $summary
	 * @param object $user
	 * @param object $minoredit
	 */
	public static function onEditFilterMergedContent(
		$context, $content, $status, $summary, $user, $minoredit ) {
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'wgLinkedWiki' );
		if ( $config->has( "CheckRDFPage" ) && $config->get( "CheckRDFPage" ) &&
			preg_match( '#<rdf.*?>#is', $content->getWikitextForTransclusion(), $matches ) ) {

			$error = self::checkErrorWithRapper( $context, $content );
			if ( !empty( $error ) ) {
				$status->fatal(
					new RawMessage(
						"<div style='color: red'>"
						. htmlspecialchars( $error ) . "</div>"
					)
				);
			}
		}
	}
}
