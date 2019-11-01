<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

class WSparqlParser {

	/**
	 * @param object $parser
	 * @return array|string
	 */
	public static function render( $parser ) {
		$configFactory = ConfigFactory::getDefaultInstance()->makeConfig( 'wgLinkedWiki' );
		$configDefault = $configFactory->get( "SPARQLServiceByDefault" );
		// TODO OPTIMIZE
		$parser->disableCache();

		$args = func_get_args();
		$countArgs = count( $args );
		$query = "";
		$debug = null;
		$cache = "yes";
		$config = $configDefault;
		$endpoint = null;
		$namewidget = isset( $args[1] ) ? $args[1] : "";
		$vars = [];
		for ( $i = 2; $i < $countArgs; $i++ ) {
			// FIX bug : Newline breaks query
			if ( preg_match_all( '#^([^= ]+)=((.|\n)*)$#i', $args[$i], $match ) ) {
				if ( $match[1][0] == "query" ) {
					$query = urldecode( $match[2][0] );
				} elseif ( $match[1][0] == "debug" ) {
					$debug = $match[2][0];
				} elseif ( $match[1][0] == "endpoint" ) {
					$endpoint = $match[2][0];
				} elseif ( $match[1][0] == "config" ) {
					$config = $match[2][0];
				} elseif ( $match[1][0] == "cache" ) {
					$cache = $match[2][0];
				} else {
					$vars[] = $args[$i];
				}
			} else {
				$vars[] = $args[$i];
			}
		}

		if ( $cache == "no" ) {
			$parser->disableCache();
		}

		if ( $query != "" && $namewidget != "" ) {

			$query = ToolsParser::parserQuery( $query, $parser );

			return self::widget( $namewidget, $query, $config, $endpoint, $debug, $vars );
		} else {
			$parser->disableCache();
			return "'''Error #sparql: "
				. "Argument incorrect (usage : #wsparql:namewidget|query=SELECT * WHERE {?a ?b ?c .} )'''";
		}
	}

	/**
	 * @param string $namewidget
	 * @param string $querySparqlWiki
	 * @param string $config
	 * @param string $endpoint
	 * @param string $debug
	 * @param string $vars
	 * @return array|string
	 */
	public static function widget(
		$namewidget, $querySparqlWiki, $config, $endpoint, $debug, $vars ) {
		$arrEndpoint = ToolsParser::newEndpoint( $config, $endpoint );
		if ( $arrEndpoint["endpoint"] == null ) {
			return [
				"<pre>" . $arrEndpoint["errorMessage"] . "</pre>",
				'noparse' => true,
				'isHTML' => false
			];
		}
		$sp = $arrEndpoint["endpoint"];

		$rs = $sp->query( $querySparqlWiki );
		$errs = $sp->getErrors();
		if ( $errs ) {
			$strerr = "";
			foreach ( $errs as $err ) {
				$strerr .= "'''Error #sparql :" . $err . "'''<br/>";
			}
			return $strerr;
		}

		$res_rows = [];
		$i = 0;

		$variables = $rs['result']['variables'];
		foreach ( $rs['result']['rows'] as $row ) {
			$res_row = [];
			foreach ( $variables as $variable ) {
				if ( !isset( $row[$variable] ) ) {
					continue;
				}
				$res_row[] = "rows." . $i . "." . $variable . "=" . $row[$variable];
			}
			$res_rows[] = implode( " | ", $res_row );
			$i++;
		}

		$str = "{{#widget:" . $namewidget
			. "|" . implode( " | ", $vars )
			. "|" . implode( " | ", $res_rows )
			. "}}";
		if ( $debug != null ) {
			$str .= "\n" . print_r( $vars, true );
			$str .= print_r( $rs, true );
			return [
				"<pre>" . $str . "</pre>",
				'noparse' => true,
				'isHTML' => true
			];
		}

		return [ $str, 'noparse' => false ];
	}
}
