<?php

use MediaWiki\EditPage\EditPage;
use MediaWiki\Language\RawMessage;
use MediaWiki\MediaWikiServices;

/**
 * @copyright (c) 2021 Bordercloud.com
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
		if ( ExtensionRegistry::getInstance()->isLoaded( 'SyntaxHighlight_GesShi' ) ) {
			// print RDF with the extension : SyntaxHighlight_GesShi
			$output = $parser->recursiveTagParse(
				"<syntaxhighlight lang=\"sparql\">" . $input . "</syntaxhighlight>",
				$frame
			);
		} else {
			$output = $parser->recursiveTagParse( "<pre>" . $input . "</pre>", $frame );
		}

		$parserOutput = $parser->getOutput();
		if ( method_exists( $parserOutput, 'setPageProperty' ) ) {
			// MW 1.38
			$parserOutput->setPageProperty( LinkedWikiStatus::PAGEPROP_WRITER_TAG, true );
		} else {
			$parserOutput->setProperty( LinkedWikiStatus::PAGEPROP_WRITER_TAG, true );
		}
		$parser->addTrackingCategory( 'linkedwiki-category-rdf-page' );
		$constraint = isset( $args['constraint'] ) ? strtolower( $args['constraint'] ) : '';
		if ( $constraint == "shacl" ) {
			if ( method_exists( $parserOutput, 'setPageProperty' ) ) {
				// MW 1.38
				$parserOutput->setPageProperty( LinkedWikiStatus::PAGEPROP_SHACL, true );
			} else {
				$parserOutput->setProperty( LinkedWikiStatus::PAGEPROP_SHACL, true );
			}
			$parser->addTrackingCategory( 'linkedwiki-category-rdf-schema' );
		}

		// push a job to load the data in the default database
		if ( method_exists( MediaWikiServices::class, 'getJobQueueGroup' ) ) {
			// MW 1.37+
			$queueJob = MediaWikiServices::getInstance()->getJobQueueGroup();
		} else {
			$queueJob = JobQueueGroup::singleton();
		}
		$jobLoadData = new LoadRDFJob( $parser->getTitle(), [] );
		$queueJob->push( $jobLoadData );
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

		// todo Clean ?
		$parameters = [ "?subject", "?type", "?property" ];
		$iri = "<" . $IRISource . ">";
		$values = [ $iri, $iri, $iri ];
		$text = str_replace( $parameters,
			$values,
			$textTemp );

		if ( preg_match( "/BASE\s</i", $text ) ) {
			return $text;
		} else {
			return "BASE <" . $IRISource . ">\n" . $text;
		}
	}

	/**
	 * @param string &$rawAction
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
	 * @param IContextSource $context
	 * @param string $content
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

		$badChar = [ ".", "/", " " ];
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
	 * @param IContextSource $context
	 * @param Content $content
	 * @param Status $status
	 * @param string $summary
	 * @param User $user
	 * @param bool $minoredit
	 * @return bool
	 */
	public static function onEditFilterMergedContent(
		$context, $content, $status, $summary, $user, $minoredit ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wgLinkedWiki' );
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
				// @todo Remove this line after this extension do not support mediawiki version 1.36 and before
				$status->value = EditPage::AS_HOOK_ERROR_EXPECTED;
				return false;
			}
		}
	}
}
