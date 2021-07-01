<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

/**
 * Constants usable with http_build_url()
 * @link http://php.net/manual/en/http.constants.php#constant.http-url-replace
 */
defined( 'HTTP_URL_REPLACE' ) || define( 'HTTP_URL_REPLACE',        0 );
defined( 'HTTP_URL_JOIN_PATH' ) || define( 'HTTP_URL_JOIN_PATH',      1 );
defined( 'HTTP_URL_JOIN_QUERY' ) || define( 'HTTP_URL_JOIN_QUERY',     2 );
defined( 'HTTP_URL_STRIP_USER' ) || define( 'HTTP_URL_STRIP_USER',     4 );
defined( 'HTTP_URL_STRIP_PASS' ) || define( 'HTTP_URL_STRIP_PASS',     8 );
defined( 'HTTP_URL_STRIP_AUTH' ) || define( 'HTTP_URL_STRIP_AUTH',     12 );
defined( 'HTTP_URL_STRIP_PORT' ) || define( 'HTTP_URL_STRIP_PORT',     32 );
defined( 'HTTP_URL_STRIP_PATH' ) || define( 'HTTP_URL_STRIP_PATH',     64 );
defined( 'HTTP_URL_STRIP_QUERY' ) || define( 'HTTP_URL_STRIP_QUERY',    128 );
defined( 'HTTP_URL_STRIP_FRAGMENT' ) || define( 'HTTP_URL_STRIP_FRAGMENT', 256 );
defined( 'HTTP_URL_STRIP_ALL' ) || define( 'HTTP_URL_STRIP_ALL',      492 );
if ( !function_exists( 'http_build_str' ) ) :

	/**
	 * Build query string
	 * @link http://php.net/manual/en/function.http-build-str.php
	 * @param array $query associative array of query string parameters
	 * @param string $prefix top level prefix
	 * @param string|null $arg_separator argument separator to use
	 * (by default the INI setting arg_separator.output will be used,
	 * or "&" if neither is set)
	 * @return string Returns the built query as string on success or FALSE on failure.
	 */
	function http_build_str( array $query, $prefix = '', $arg_separator = null ) {
		if ( $arg_separator === null ) {
			$arg_separator = ini_get( 'arg_separator.output' );
		}
		$out = [];
		foreach ( $query as $k => $v ) {
			$key = $prefix ? "{$prefix}%5B{$k}%5D" : $k;
			if ( is_array( $v ) ) {
				$out[] = call_user_func(
					__FUNCTION__, $v, $key, $arg_separator
				);
			} else {
				$out[] = $key . '=' . urlencode( $v );
			}
		}
		return implode( $arg_separator, $out );
	}

endif;

if ( !function_exists( 'http_build_url' ) ) :

	/**
	 * Build a URL
	 * @link http://php.net/manual/en/function.http-build-url.php
	 * @param mixed $url (part(s) of) an URL in form of a string
	 * 			or associative array like parse_url() returns
	 * @param mixed $parts same as the first argument
	 * @param int $flags a bitmask of binary or'ed HTTP_URL constants; HTTP_URL_REPLACE is the default
	 * @param array|null &$new_url if set, it will be filled with the parts
	 * of the composed url like parse_url() would return
	 * @return string Returns the new URL as string on success or FALSE on failure.
	 */
	function http_build_url(
		$url = [], $parts = [], $flags = HTTP_URL_REPLACE, &$new_url = null ) {
		$defaults = [
			'scheme' => (
				empty( $_SERVER['HTTPS'] )
				|| strtolower( $_SERVER['HTTPS'] ) == 'off' ? 'http' : 'https'
			),
			'host'   => $_SERVER['HTTP_HOST'],
			'port'   => '',
			'user'   => '', 'pass' => '',
			'path'   => preg_replace( '`^([^\?]*).*$`', '$1', $_SERVER['REQUEST_URI'] ),
			'query'  => '', 'fragment' => '',
		];
		is_array( $url ) || $url = parse_url( $url );
		is_array( $parts ) || $parts = parse_url( $parts );
		$new_url = $parts + $url + $defaults;
		// Default flags ?
		$flags || $flags = ( HTTP_URL_JOIN_PATH );
		$JOIN_PATH      = ( ( $flags | HTTP_URL_JOIN_PATH ) == $flags );
		$JOIN_QUERY     = ( ( $flags | HTTP_URL_JOIN_QUERY ) == $flags );
		$STRIP_USER     = ( ( $flags | HTTP_URL_STRIP_USER ) == $flags );
		$STRIP_PASS     = ( ( $flags | HTTP_URL_STRIP_PASS ) == $flags );
		$STRIP_PATH     = ( ( $flags | HTTP_URL_STRIP_PATH ) == $flags );
		$STRIP_QUERY    = ( ( $flags | HTTP_URL_STRIP_QUERY ) == $flags );
		$STRIP_FRAGMENT = ( ( $flags | HTTP_URL_STRIP_FRAGMENT ) == $flags );
		// User
		if ( $STRIP_USER ) {
			$new_url['user'] = '';
		}
		// Pass
		if ( !$new_url['user'] || ( $new_url['pass'] && $STRIP_PASS ) ) {
			$new_url['pass'] = '';
		}
		// Port
		if ( $new_url['port'] && ( $flags | HTTP_URL_STRIP_PORT ) == $flags ) {
			$new_url['port'] = '';
		}
		// Path
		if ( $STRIP_PATH ) {
			$new_url['path'] = '';
		} else {
			$d_path = $defaults['path'];
			$u_path = ( isset( $url['path'] ) ? $url['path'] : '' );
			$p_path = ( isset( $parts['path'] ) ? $parts['path'] : '' );
			if ( $p_path ) { $u_path = '';
			}
			$path = $d_path;
			if ( isset( $url['host'] ) && !$p_path ) {
				$path = '/' . ltrim( $u_path, '/' );
			} elseif ( strpos( $u_path, '/' ) === 0 ) {
				$path = $u_path;
			} elseif ( $u_path ) {
				$path = pathinfo( $path . 'x', PATHINFO_DIRNAME ) . '/' . $u_path;
			}
			if ( isset( $parts['host'] ) ) {
				$path = '/' . ltrim( $p_path, '/' );
			} elseif ( strpos( $p_path, '/' ) === 0 ) {
				$path = $p_path;
			} elseif ( $p_path ) {
				$path = pathinfo( $path . 'x', PATHINFO_DIRNAME ) . '/' . $p_path;
			}
			$path = explode( '/', $path );
			$k_stack = [];
			foreach ( $path as $k => $v ) {
				if ( $v == '..' ) {
					if ( $k_stack ) {
						$k_parent = array_pop( $k_stack );
						unset( $path[$k_parent] );
					}
					unset( $path[$k] );
				} elseif ( $v == '.' ) {
					unset( $path[$k] );

				} else { $k_stack[] = $k;
				}
			}
			$path = implode( '/', $path );
			$new_url['path'] = $path;
		}
		$new_url['path'] = '/' . ltrim( $new_url['path'], '/' );
		// Query
		if ( $STRIP_QUERY ) {
			$new_url['query'] = '';
		} else {
			$u_query = isset( $url['query'] ) ? $url['query'] : '';
			$p_query = isset( $parts['query'] ) ? $parts['query'] : '';
			$query = $new_url['query'];
			if ( is_array( $p_query ) ) {
				$query = $u_query;
			} elseif ( $JOIN_QUERY ) {
				if ( !is_array( $u_query ) ) { parse_str( $u_query, $u_query );
				}
				if ( !is_array( $p_query ) ) { parse_str( $p_query, $p_query );
				}
				$u_query = http_build_str( $u_query );
				$p_query = http_build_str( $p_query );
				$u_query = str_replace( [ '[', '%5B' ], '{{{', $u_query );
				$u_query = str_replace( [ ']', '%5D' ], '}}}', $u_query );
				$p_query = str_replace( [ '[', '%5B' ], '{{{', $p_query );
				$p_query = str_replace( [ ']', '%5D' ], '}}}', $p_query );
				parse_str( $u_query, $u_query );
				parse_str( $p_query, $p_query );
				$query = http_build_str( array_merge( $u_query, $p_query ) );
				$query = str_replace( [ '{{{', '%7B%7B%7B' ], '%5B', $query );
				$query = str_replace( [ '}}}', '%7D%7D%7D' ], '%5D', $query );
				parse_str( $query, $query );
			}
			if ( is_array( $query ) ) {
				$query = http_build_str( $query );
			}
			$new_url['query'] = $query;
		}
		// Fragment
		if ( $STRIP_FRAGMENT ) {
			$new_url['fragment'] = '';
		}
		// Scheme
		$out = $new_url['scheme'] . '://';
		// User
		if ( $new_url['user'] ) {
			$out .= $new_url['user']
				. ( $new_url['pass'] ? ':' . $new_url['pass'] : '' )
				. '@';
		}
		// Host
		$out .= $new_url['host'];
		// Port
		if ( $new_url['port'] ) {
			$out .= ':' . $new_url['port'];
		}
		// Path
		$out .= $new_url['path'];
		// Query
		if ( $new_url['query'] ) {
			$out .= '?' . $new_url['query'];
		}
		// Fragment
		if ( $new_url['fragment'] ) {
			$out .= '#' . $new_url['fragment'];
		}
		$new_url = array_filter( $new_url );
		return $out;
	}

endif;

class SpecialRDFUnit extends SpecialPage {

	public function __construct() {
		parent::__construct( 'linkedwiki-specialrdfunit', "data-edit" );
	}

	/**
	 * @return string
	 */
	public function getGroupName() {
		return 'linkedwiki_group';
	}

	/**
	 * @param null $par
	 */
	public function execute( $par = null ) {
		$output = $this->getOutput();

		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
			return;
		}

		if ( !file_exists( "/RDFUnit" ) ) {
			$output->addHTML( "RDFUnit is not installed." );
			return;
		}

		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'wgLinkedWiki' );
		if ( !$config->has( "SPARQLServiceSaveDataOfWiki" ) ) {
			$output->addHTML(
				"Database by default for the Wiki is not precised "
				. "in the LocalSettings.php. "
				. '(parameter $wgLinkedWikiSPARQLServiceSaveDataOfWiki)'
			);
			return;
		}

		$configDefaultSaveData = $config->get( "SPARQLServiceSaveDataOfWiki" );
		$configSaveData = new LinkedWikiConfig( $configDefaultSaveData );

		$request = $this->getRequest();
		$refresh = $request->getText( 'refresh' );

		$uriOfDataset = $configDefaultSaveData;
		$graphOfDataset = $configDefaultSaveData;
		$graphOfDatasetFileForRDFUnit = "";
		if ( preg_match( "#//(.*)#", $graphOfDataset, $matches ) ) {
			$removeChars = [ "/", ":", "#" ];
			$graphOfDatasetFileForRDFUnit = str_replace( $removeChars, "_", $matches[1] );
		}

		$resultTestCase = "/RDFUnit/data/results/"
			. $graphOfDatasetFileForRDFUnit	. ".shaclTestCaseResult.html";
		$dbTestCase = "/RDFUnit/cache/sparql/"
			. $graphOfDatasetFileForRDFUnit . ".mv.db";
		$endpointOfDatasetPublic = $configSaveData->getInstanceEndpoint()->getEndpointRead();
		$endpointOfDataset = $configSaveData->getInstanceEndpoint()->getEndpointRead();
	if ( !empty( $configSaveData->getInstanceEndpoint()->getLogin() ) ) {
		$endpointOfDataset = http_build_url( $configSaveData->getInstanceEndpoint()->getEndpointRead(),
				[
					"user" => $configSaveData->getInstanceEndpoint()->getLogin(),
					"pass" => $configSaveData->getInstanceEndpoint()->getPassword()
				] );
	}

		$category = Title::newFromText(
			wfMessage( 'linkedwiki-category-rdf-schema' )->inContentLanguage()->parse()
		)->getDBKey();

		// make the list of schema
		// $output->addWikiTextAsInterface("List of RDF schema uses during the tests:");

		$dbr = wfGetDB( DB_REPLICA );
		$sql = "SELECT  p.page_id AS pid, p.page_title AS title FROM page p
INNER JOIN categorylinks c ON c.cl_from = p.page_id
 WHERE c.cl_to='" . $category . "' ORDER BY p.page_title ASC";
// echo  $sql;
		// phpcs:disable
		$res = $dbr->query( $sql, __METHOD__ );
		// phpcs:enable

		$schemas = [];
		$schemasStr = [];
		$lines = [];
		$row = $dbr->fetchObject( $res );
		while ( $row ) {
			$schemas[] = $row;
			$schemasStr[] = '"' . Title::newFromID( $row->pid )->getFullURL() . '?action=raw&export=rdf"';
			// $lines[] = "* [[".$row->title."]] ";
			$lines[] = "* [" . Title::newFromID( $row->pid )->getFullURL() . " " . $row->title . "] ";

			$row = $dbr->fetchObject( $res );
		}

		if ( count( $schemasStr ) == 0 ) {
			$output->addHTML(
				"<button class=\"mw-htmlform-submit mw-ui-button mw-ui-primary mw-ui-progressive\""
				. " disabled>Refresh test cases</button>"
				. " (No test cases for the moment in the wiki)"
			);
		} else {
			$output->addHTML(
				"<a href='?refresh=true' "
				. "class=\"mw-htmlform-submit mw-ui-button mw-ui-primary mw-ui-progressive\"> "
				. "Refresh test cases</a>"
			);
		}

		$output->addWikiTextAsInterface( "== RDF schemas in the wiki ==" );

		$output->addWikiTextAsInterface(
			"You can add a new RDF schema with the tag rdf with attribut constraint='shacl'."
		);
		$output->addHTML( "For example : " . htmlentities( "<rdf constraint='shacl'>" ) );

		$output->addWikiTextAsInterface( implode( "\n", $lines ) );

		// return $list;

		$output->addWikiTextAsInterface( "== RDFUnit command ==" );

		$commandPublic = 'rdfunit -d "' . $uriOfDataset
			. '" -r shacl -e "' . $endpointOfDatasetPublic
			. '" -g "' . $graphOfDataset
			. '" -v -s ' . implode( ',', $schemasStr );

		$command = 'rdfunit -d "' . $uriOfDataset
		. '" -r shacl -e "' . $endpointOfDataset
		. '" -g "' . $graphOfDataset
		. '" -v -s ' . implode( ',', $schemasStr );

		$output->addHTML( "<pre>" . $commandPublic );

		$output->addHTML( "</pre>" );

		$output->addWikiTextAsInterface( "== Results ==" );
		if ( !empty( $refresh ) ) {
			$output->addHTML( self::refreshAndPrintTests( $command, $dbTestCase ) );
		} else {
			$output->addHTML( self::printTests( $resultTestCase ) );
		}

		$this->setHeaders();
	}

	/**
	 * @param string $command
	 * @param string $dbTestCase
	 * @return mixed|string
	 */
	public static function refreshAndPrintTests( $command, $dbTestCase ) {
		$result = "";
		if ( file_exists( $dbTestCase ) ) {
			unlink( $dbTestCase );
		}

		// $commandRDFUnit = "ls -al";// "whoami";
		// $commandRDFUnit =  "mvn -pl rdfunit-validate -am clean install";
		$commandRDFUnit = "bin/" . $command;
		chdir( '/RDFUnit' );
		$file = exec( $commandRDFUnit . " 2>&1", $retval );
		$textRetval = print_r( $retval, true );

		if ( preg_match(
			"#\[INFO  ValidateCommand\] Results stored in: (.*)\.\*#",
			$textRetval,
			$matches
			)
		) {
			$result = self::printTests( "/RDFUnit/" . $matches[1] . ".html" );
		} elseif ( preg_match( "#.*ERROR.*#", $textRetval ) ) {
			$result = "<pre>" . $textRetval . "</pre>";
		} else {
			$result = "<pre>" . $textRetval . "</pre>";
		}

		return $result;
	}

	/**
	 * @param string $file
	 * @return mixed|string
	 */
	public static function printTests( $file ) {
		$result = "NO RESULTS";
		if ( file_exists( $file ) ) {
			if ( preg_match( "#<body>(.*)</body>#s", file_get_contents( $file ), $matchesbody ) ) {
				$result = $matchesbody[1];
			}
		}
		return $result;
	}

}
