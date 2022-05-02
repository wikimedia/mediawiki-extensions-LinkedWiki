<?php

use MediaWiki\MediaWikiServices;

/**
 * @copyright (c) 2021 Bordercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

class SparqlParser {
	/**
	 * @param Parser $parser
	 * @return array|string|null
	 */
	public static function render( $parser ) {
		$out = $parser->getOutput();
		$out->addModuleStyles( "ext.LinkedWiki.common" );
		$out->addModules( 'ext.LinkedWiki.table2CSV' );
		$out->addModules( 'ext.LinkedWiki.SparqlParser' );

		$configFactory = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wgLinkedWiki' );
		$configDefault = $configFactory->get( "SPARQLServiceByDefault" );

		$result = null;

		$args = func_get_args();
		$countArgs = count( $args );
		$query = isset( $args[1] ) ? urldecode( $args[1] ) : "";
		$vars = [];
		for ( $i = 2; $i < $countArgs; $i++ ) {
			if ( preg_match_all( '#^([^= ]+) *= *(.*)$#i', $args[$i], $match ) ) {
				$vars[$match[1][0]] = $match[2][0];
			}
		}

		if ( $query != "" ) {

			$query = ToolsParser::parserQuery( $query, $parser );

			$config = isset( $vars["config"] ) ? $vars["config"] : $configDefault;
			$endpoint = isset( $vars["endpoint"] ) ? $vars["endpoint"] : null;
			$class = isset( $vars["class"] ) ? $vars["class"] : 'wikitable sortable';
			$classHeaders = isset( $vars["classHeaders"] ) ? $vars["classHeaders"] : '';
			$headers = isset( $vars["headers"] ) ? $vars["headers"] : '';
			$templates = isset( $vars["templates"] ) ? $vars["templates"] : '';
			$template = isset( $vars["template"] ) ? $vars["template"] : '';
			$debug = isset( $vars["debug"] ) ? $vars["debug"] : null;
			$cache = isset( $vars["cache"] ) ? $vars["cache"] : "yes";
			$templateBare = isset( $vars["templateBare"] ) ? $vars["templateBare"] : '';
			$footer = isset( $vars["footer"] ) ? $vars["footer"] : '';
			$preview = isset( $vars["preview"] ) ? $vars["preview"] : '';
			$intro = isset( $vars["intro"] ) ? $vars["intro"] : '';
			$outro = isset( $vars["outro"] ) ? $vars["outro"] : '';
			$introtemplate = isset( $vars["introtemplate"] ) ? $vars["introtemplate"] : '';
			$outrotemplate = isset( $vars["outrotemplate"] ) ? $vars["outrotemplate"] : '';
			$userparam = isset( $vars["userparam"] ) ? $vars["userparam"] : '';

			$chart = isset( $vars["chart"] ) ? $vars["chart"] : '';
			$options = isset( $vars["options"] ) ? $vars["options"] : '';
			$log = isset( $vars["log"] ) ? $vars["log"] : 1;

			$noResultMsg = isset( $vars["default"] ) ? $vars["default"] : '';

			$title = $parser->getTitle();

			$parser->addTrackingCategory( 'linkedwiki-category-query' );

			if ( !empty( $chart ) ) {
				// renderer with sgvizler2
				$parserOutput = $parser->getOutput();
				if ( method_exists( $parserOutput, 'setPageProperty' ) ) {
					// MW 1.38
					$parserOutput->setPageProperty( LinkedWikiStatus::PAGEPROP_READER_QUERY, true );
				} else {
					$parserOutput->setProperty( LinkedWikiStatus::PAGEPROP_READER_QUERY, true );
				}
				return self::sgvizler2Container(
					$parser,
					$query,
					$config,
					$endpoint,
					$chart,
					$options,
					$log,
					$debug );
			} else {
				// renderer with php
				$parserOutput = $parser->getOutput();
				if ( $cache == "no" ) {
					$parserOutput->updateCacheExpiry( 0 );
					if ( method_exists( $parserOutput, 'setPageProperty' ) ) {
						// MW 1.38
						$parserOutput->setPageProperty( LinkedWikiStatus::PAGEPROP_READER_QUERY, true );
					} else {
						$parserOutput->setProperty( LinkedWikiStatus::PAGEPROP_READER_QUERY, true );
					}
				} else {
					if ( method_exists( $parserOutput, 'setPageProperty' ) ) {
						// MW 1.38
						$parserOutput->setPageProperty( LinkedWikiStatus::PAGEPROP_READER_QUERY_CACHED, true );
					} else {
						$parserOutput->setProperty( LinkedWikiStatus::PAGEPROP_READER_QUERY_CACHED, true );
					}
				}
				if ( $templateBare == "tableCell" ) {
					return self::tableCell(
						$parser,
						$query,
						$config,
						$endpoint,
						$debug,
						$log );
				} else {
					if ( $template != "" ) {
						return self::simpleHTMLWithRowTemplate(
							$parser,
							$query,
							$config,
							$endpoint,
							$template,
							$intro,
							$outro,
							$introtemplate,
							$outrotemplate,
							$footer,
							$preview,
							$debug,
							$log,
							$noResultMsg,
							$userparam
						);
					}
					if ( $templates != "" ) {
						return self::simpleHTMLWithTemplate(
							$parser,
							$query,
							$config,
							$endpoint,
							$class,
							$classHeaders,
							$headers,
							$templates,
							$footer,
							$preview,
							$debug,
							$log,
							$noResultMsg,
							$userparam
						);
					} else {
						return self::simpleHTML(
							$parser,
							$query,
							$config,
							$endpoint,
							$class,
							$classHeaders,
							$headers,
							$footer,
							$preview,
							$debug,
							$log,
							$noResultMsg );
					}
				}
			}
		} else {
			$parser->getOutput()->updateCacheExpiry( 0 );
			$result = "'''Error #sparql: "
				. "Incorrect argument (usage : #sparql: SELECT * WHERE {?a ?b ?c .} )'''";

			$parser->addTrackingCategory( 'linkedwiki-category-query-error' );
		}

		return $result;
	}

	/**
	 * Build the sgvizler2 container
	 *
	 * @param Parser $parser
	 * @param string $querySparqlWiki
	 * @param string $config
	 * @param string $endpoint
	 * @param string $chart
	 * @param string $options
	 * @param null|string $log
	 * @param null|string $debug
	 * @return array
	 */
	public static function sgvizler2Container(
		$parser,
		$querySparqlWiki,
		$config,
		$endpoint,
		$chart,
		$options = '',
		$log = '',
		$debug = null ) {
		$methodSg = "";
		$parameterSg = "";
		$endpointSg = "";
		$logSg = $log;

		if ( empty( $config ) && empty( $endpoint ) ) {
			return self::printMessageErrorDebug( $parser, 1, wfMessage( 'linkedwiki-error-endpoint-empty' )->text() );
		} elseif ( !empty( $endpoint ) ) {
			$endpointSg = $endpoint;
		} elseif ( !empty( $config ) ) {
			$configuration = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wgLinkedWiki' );
			$configs = $configuration->get( "ConfigSPARQLServices" );
			$configEndpoint = isset( $configs[$config] ) ? $configs[$config] : null;
			if ( !empty( $configEndpoint ) ) {
				$endpointSg = $configEndpoint["endpointRead"];
				$methodSg = isset( $configEndpoint["HTTPMethodForRead"] ) ?
					$configEndpoint["HTTPMethodForRead"]
					: "GET";
				$parameterSg = isset( $configEndpoint["nameParameterRead"] ) ?
					$configEndpoint["nameParameterRead"]
					: "query";
			}
		}

		if ( self::isDebug( $debug ) ) {
			$logSg = 2;
		}

		$uniqId = "ID" . uniqid();
		$str = "<div id='" . $uniqId . "' ";

		$str .= "data-sgvizler-query='" . htmlentities( $querySparqlWiki, ENT_QUOTES, "UTF-8" ) . "' \n" .

			"data-sgvizler-endpoint=\"" . $endpointSg . "\" \n" .
			"data-sgvizler-chart=\"" . $chart . "\" \n";

		if ( !empty( $options ) ) {
			$str .= "data-sgvizler-chart-options=\"" . $options . "\" \n";
		}
		if ( !empty( $logSg ) ) {
			$str .= "data-sgvizler-log=\"" . $logSg . "\" \n";
		}
		if ( !empty( $methodSg ) && ( $methodSg == "GET" || $methodSg == "POST" ) ) {
			$str .= "data-sgvizler-method=\"" . $methodSg . "\" \n";
		}
		if ( !empty( $parameterSg ) && $parameterSg != "query" ) {
			$str .= "data-sgvizler-endpoint-query-parameter=\"" . $parameterSg . "\" \n";
		}

		// insert api keys
		$configFactory = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'wgLinkedWiki' );
		if ( $configFactory->has( "GoogleApiKey" ) ) {
			$str .= "data-googleapikey=\"" . $configFactory->get( "GoogleApiKey" ) . "\" \n";
		}
		if ( $configFactory->has( "OSMAccessToken" ) ) {
			$str .= "data-osmaccesstoken=\"" . $configFactory->get( "OSMAccessToken" ) . "\" \n";
		}

		$str .= "></div>";

		return [ $str, 'isChildObj' => true ];
	}

	public static function simpleHTMLWithRowTemplate(
		$parser,
		$querySparqlWiki,
		$config,
		$endpoint,
		$template,
		$intro,
		$outro,
		$introtemplate,
		$outrotemplate,
		$footer = '',
		$preview = '',
		$debug = null,
		$log = '',
		$noResultMsg = '',
		$userparam = '' ) {
		$isDebug = self::isDebug( $debug );
		$specialC = [ "&#39;" ];
		$replaceC = [ "'" ];
		$querySparql = str_replace( $specialC, $replaceC, $querySparqlWiki );

		$arrEndpoint = ToolsParser::newEndpoint( $config, $endpoint );
		if ( $arrEndpoint["endpoint"] == null ) {
			return self::printMessageErrorDebug(
				$parser,
				$log,
				wfMessage( 'linkedwiki-error-endpoint-init' )->text(),
				$arrEndpoint["errorMessage"]
			);
		}
		$sp = $arrEndpoint["endpoint"];

		$rs = $sp->query( $querySparqlWiki );
		$errs = $sp->getErrors();
		if ( $errs ) {
			$strerr = "";
			foreach ( $errs as $err ) {
				$strerr .= "'''Error #sparql :" . $err . "'''";
			}
			return self::printMessageErrorDebug(
				$parser,
				$log,
				wfMessage( 'linkedwiki-error-server' )->text(),
				$strerr
			);
		}
		$variables = $rs['result']['variables'];

		$str = '';
		if ( empty( $rs['result']['rows'] ) && !empty( $noResultMsg ) ) {
			$str = $noResultMsg;
		} else {
			if ($intro != '') {
				$str = $intro;
			}
			else if ($introtemplate != '') {
				$str = '{{' . $introtemplate . ($userparam != '' ? '|userparam=' . $userparam : '') . '}}';
			}
			$arrayParameters = [];
			$nbRows = 0;
			$limitRow = is_numeric( $preview ) ? 0 + $preview : -1;
			foreach ( $rs['result']['rows'] as $row ) {
				if ( $limitRow > 0 && $nbRows >= $limitRow ) {
					break;
				}
				unset( $arrayParameters );
				foreach ( $variables as $variable ) {
					// START ADD BY DOUG to support optional variables in query
					if ( !isset( $row[$variable] ) ) {
						continue;
					}
					// END ADD BY DOUG
					if ( isset( $row[$variable . " type"] ) && $row[$variable . " type"] == "uri" ) {
						$arrayParameters[] = $variable . " = " . self::uri2Link( $row[$variable], true );
					} else {
						if ( isset( $variable ) ) {
							$arrayParameters[] = $variable . " = " . $row[$variable];
						}
					}
				}
				if ($userparam != '') {
					$arrayParameters['userparam'] = $userparam;
				}
				$str .= "{{" . $template
					. "|" . implode("|", $arrayParameters)
					. "}}";
				$nbRows++;
			}
			if ($outro != '') {
				$str .= $outro;
			}
			else if ($outrotemplate != '') {
				$str .= '{{' . $outrotemplate . ($userparam != '' ? '|userparam=' . $userparam : '') . '}}';
			}

			if ($footer != "NO" && $footer != "no") {
				$str .= "\n<span style=\"font-size:80%\" align=\"right\">";
				$str .= self::footer($rs['query_time'], $querySparqlWiki, $config, $endpoint, '', '')
					. "</span>\n";
			}
		}

		if ($isDebug) {
			$str .= "INPUT WIKI : " . $querySparqlWiki . "\n";
			$str .= "Query : " . $querySparql . "\n";
			$str .= print_r($rs, true);
			return self::printMessageErrorDebug(
				$parser, 2, "Debug messages", $str );
		}
		return [ $str, 'noparse' => false, 'isHTML' => false ];
	}

	/**
	 * @param Parser $parser
	 * @param string $querySparqlWiki
	 * @param string $config
	 * @param string $endpoint
	 * @param string $class
	 * @param string $classHeaders
	 * @param string $headers
	 * @param string $templates
	 * @param string $footer
	 * @param string $preview
	 * @param null|string $debug
	 * @param null|string $log
	 * @param string $noResultMsg
	 * @return array
	 */
	public static function simpleHTMLWithTemplate(
		$parser,
		$querySparqlWiki,
		$config,
		$endpoint,
		$class,
		$classHeaders = '',
		$headers = '',
		$templates = '',
		$footer = '',
		$preview = '',
		$debug = null,
		$log = '',
		$noResultMsg = '',
		$userparam = '' ) {
		$isDebug = self::isDebug( $debug );
		$specialC = [ "&#39;" ];
		$replaceC = [ "'" ];
		$querySparql = str_replace( $specialC, $replaceC, $querySparqlWiki );

		$arrEndpoint = ToolsParser::newEndpoint( $config, $endpoint );
		if ( $arrEndpoint["endpoint"] == null ) {
			return self::printMessageErrorDebug(
				$parser,
				$log,
				wfMessage( 'linkedwiki-error-endpoint-init' )->text(),
				$arrEndpoint["errorMessage"]
			);
		}
		$sp = $arrEndpoint["endpoint"];

		$rs = $sp->query( $querySparqlWiki );
		$errs = $sp->getErrors();
		if ( $errs ) {
			$strerr = "";
			foreach ( $errs as $err ) {
				$strerr .= "'''Error #sparql :" . $err . "'''";
			}
			return self::printMessageErrorDebug(
				$parser,
				$log,
				wfMessage( 'linkedwiki-error-server' )->text(),
				$strerr
			);
		}
		$variables = $rs['result']['variables'];
		$TableFormatTemplates = explode( ",", $templates );

		if ( empty( $rs['result']['rows'] ) && !empty( $noResultMsg ) ) {
			$str = $noResultMsg;
		} else {
			$lignegrise = false;
			$str = "{| class=\"" . $class . "\" \n";
			if ( $headers != '' ) {
				$TableTitleHeaders = explode( ",", $headers );
				$TableClassHeaders = explode( ",", $classHeaders );
				for ( $i = 0; $i < count( $TableClassHeaders ); $i++ ) {
					if ( !isset( $TableClassHeaders[$i] ) || $TableClassHeaders[$i] == "" ) {
						$classStr = "";

					} else {
						$classStr = $TableClassHeaders[$i] . "|";
					}
					$TableTitleHeaders[$i] = $classStr . $TableTitleHeaders[$i];
				}

				$str .= "|- \n";
				$str .= "!" . implode( "!!", $TableTitleHeaders );
				$str .= "\n";
			}

			$arrayParameters = [];
			$nbRows = 0;
			$limitRow = is_numeric( $preview ) ? 0 + $preview : -1;
			foreach ( $rs['result']['rows'] as $row ) {
				$str .= "|- ";
				if ( $lignegrise ) {
					$str .= "bgcolor=\"#f5f5f5\"";
				}
				if ( $limitRow > 0 && $nbRows >= $limitRow ) {
					$str .= ' style="display:none" ';
				}
				$lignegrise = !$lignegrise;
				$str .= "\n";
				$separateur = "|";
				unset( $arrayParameters );
				foreach ( $variables as $variable ) {
					// START ADD BY DOUG to support optional variables in query
					if ( !isset( $row[$variable] ) ) {
						continue;
					}
					// END ADD BY DOUG
					if ( isset( $row[$variable . " type"] ) && $row[$variable . " type"] == "uri" ) {
						$arrayParameters[] = $variable . " = " . self::uri2Link( $row[$variable], true );
					} else {
						if ( isset( $variable ) ) {
							$arrayParameters[] = $variable . " = " . $row[$variable];
						}
					}
				}
				if ($userparam != '') {
					$arrayParameters['userparam'] = $userparam;
				}
				foreach ( $TableFormatTemplates as $key => $TableFormatTemplate ) {
					if ( empty( $TableFormatTemplate ) ) {
						$str .= $separateur;
						$str .= isset( $row[$variables[$key]] ) && !empty( $row[$variables[$key]] ) ?
							$row[$variables[$key]] : "";
					} else {
						$str .= $separateur
							. "{{" . $TableFormatTemplate
							. "|" . implode( "|", $arrayParameters )
							. "}}";
					}
					$separateur = "||";
				}
				$str .= "\n";
				$nbRows++;
			}

			if ( $footer != "NO" && $footer != "no" ) {
				$str .= "|- style=\"font-size:80%\" align=\"right\"\n";
				$str .= "| colspan=\"" . count( $TableFormatTemplates ) . "\"|" .
					self::footer( $rs['query_time'], $querySparqlWiki, $config, $endpoint, $classHeaders, $headers )
					. "\n";
			}
			$str .= "|}\n";
		}

		if ( $isDebug ) {
			$str .= "INPUT WIKI : " . $querySparqlWiki . "\n";
			$str .= "Query : " . $querySparql . "\n";
			$str .= print_r( $arrayParameters, true );
			$str .= print_r( $rs, true );
			return self::printMessageErrorDebug(
				$parser, 2, "Debug messages", $str );
		}
		return [ $str, 'noparse' => false, 'isHTML' => false ];
	}

	/**
	 * @param Parser $parser
	 * @param string $querySparqlWiki
	 * @param string $config
	 * @param string $endpoint
	 * @param string $class
	 * @param string $classHeaders
	 * @param string $headers
	 * @param string $footer
	 * @param string $preview
	 * @param null|string $debug
	 * @param null|string $log
	 * @param string $noResultMsg
	 * @return array
	 */
	public static function simpleHTML(
		$parser,
		$querySparqlWiki,
		$config,
		$endpoint,
		$class,
		$classHeaders = '',
		$headers = '',
		$footer = '',
		$preview = '',
		$debug = null,
		$log = '',
		$noResultMsg = '' ) {
		$isDebug = self::isDebug( $debug );
		$specialC = [ "&#39;" ];
		$replaceC = [ "'" ];
		$querySparql = str_replace( $specialC, $replaceC, $querySparqlWiki );

		$arrEndpoint = ToolsParser::newEndpoint( $config, $endpoint );
		if ( $arrEndpoint["endpoint"] == null ) {
			return self::printMessageErrorDebug(
				$parser,
				$log,
				wfMessage( 'linkedwiki-error-endpoint-init' )->text(),
				$arrEndpoint["errorMessage"]
			);
		}
		$sp = $arrEndpoint["endpoint"];

		$rs = $sp->query( $querySparqlWiki );
		$errs = $sp->getErrors();
		if ( $errs ) {
			$strerr = "";
			foreach ( $errs as $err ) {
				$strerr .= "'''Error #sparql :" . $err . "'''";
			}
			return self::printMessageErrorDebug(
				$parser,
				$log,
				wfMessage( 'linkedwiki-error-server' )->text(),
				$strerr
			);
		}

		$lignegrise = false;
		$variables = $rs['result']['variables'];

		if ( empty( $rs['result']['rows'] ) && !empty( $noResultMsg ) ) {
			$str = $noResultMsg;
		} else {
			$str = "<table class='" . $class . "'>\n";
			if ( $headers != '' ) {
				$TableTitleHeaders = explode( ",", $headers );
				$TableClassHeaders = explode( ",", $classHeaders );
				for ( $i = 0; $i < count( $TableTitleHeaders ); $i++ ) {
					if ( !isset( $TableClassHeaders[$i] ) || $TableClassHeaders[$i] == "" ) {
						$classStr = "";
					} else {
						$classStr = " class=\"" . $TableClassHeaders[$i] . "\"";
					}
					$TableTitleHeaders[$i] = "<th" . $classStr . ">" . $TableTitleHeaders[$i] . "</th>";
				}
				$str .= "<tr>";
				$str .= implode( "\n", $TableTitleHeaders );
				$str .= "</tr>\n";
			} else {
				$TableClassHeaders = explode( ",", $classHeaders );
				for ( $i = 0; $i < count( $variables ); $i++ ) {
					if ( !isset( $TableClassHeaders[$i] ) || $TableClassHeaders[$i] == "" ) {
						$classStr = "";

					} else {
						$classStr = " class=\"" . $TableClassHeaders[$i] . "\"";
					}
					$TableTitleHeaders[$i] = "<th" . $classStr . ">" . $variables[$i] . "</th>";
				}

				$str .= "<tr>\n";
				$str .= implode( "\n", $TableTitleHeaders );
				$str .= "</tr>\n";
			}

			$nbRows = 0;
			$limitRow = is_numeric( $preview ) ? 0 + $preview : -1;
			foreach ( $rs['result']['rows'] as $row ) {
				$str .= "<tr";
				if ( $lignegrise ) {
					$str .= " bgcolor=\"#f5f5f5\" ";
				}
				if ( $limitRow > 0 && $nbRows >= $limitRow ) {
					$str .= ' style="display:none" ';
				}
				$str .= ">\n";
				$lignegrise = !$lignegrise;

				foreach ( $variables as $variable ) {
					$str .= "<td>";
					$value = isset( $row[$variable] ) && !empty( $row[$variable] ) ?
						$row[$variable] : "";

					if ( isset( $row[$variable . " type"] ) && $row[$variable . " type"] == "uri" ) {
						$str .= "<a href='" . $value . "'>" . $value . "</a>";
					} else {
						// T227845
						$str .= empty( $value ) ? "&nbsp;" : htmlentities( $value );
					}
					$str .= "</td>\n";
				}
				$str .= "</tr>\n";
				$nbRows++;
			}

			if ( $footer != "NO" && $footer != "no" ) {
				$str .= "<tr style=\"font-size:80%\" align=\"right\">\n";
				$str .= "<td colspan=\"" . count( $variables ) . "\">"
					. self::footerHTML(
						$parser,
						$rs['query_time'],
						$querySparqlWiki,
						$config,
						$endpoint,
						$classHeaders,
						$headers
					) . "</td>\n";
				$str .= "</tr>\n";
			}

			$str .= "</table>\n";
		}

		if ( $isDebug ) {
			$str .= "INPUT WIKI: \n" . $querySparqlWiki . "\n";
			$str .= "QUERY : " . $querySparql . "\n";
			$str .= print_r( $rs, true );
			return self::printMessageErrorDebug(
				$parser,
				2,
				"Debug messages",
				$str
			);
		}
		return [ $str, 'noparse' => false, 'isHTML' => true ];
	}

	/**
	 * @param Parser $parser
	 * @param string $querySparqlWiki
	 * @param string $config
	 * @param string $endpoint
	 * @param null|string $debug
	 * @param null|string $log
	 * @return array
	 */
	public static function tableCell(
		$parser,
		$querySparqlWiki,
		$config,
		$endpoint,
		$debug = null,
		$log = '' ) {
		$isDebug = self::isDebug( $debug );
		$specialC = [ "&#39;" ];
		$replaceC = [ "'" ];
		$querySparql = str_replace( $specialC, $replaceC, $querySparqlWiki );

		$arrEndpoint = ToolsParser::newEndpoint( $config, $endpoint );
		if ( $arrEndpoint["endpoint"] == null ) {
			return self::printMessageErrorDebug(
				$parser,
				$log,
				wfMessage( 'linkedwiki-error-endpoint-init' )->text(),
				$arrEndpoint["errorMessage"]
			);
		}
		$sp = $arrEndpoint["endpoint"];
		$rs = $sp->query( $querySparqlWiki );
		$errs = $sp->getErrors();
		if ( $errs ) {
			$strerr = "";
			foreach ( $errs as $err ) {
				$strerr .= "'''Error #sparql :" . $err . "'''";
			}
			return self::printMessageErrorDebug(
				$parser,
				$log,
				wfMessage( 'linkedwiki-error-server' )->text(),
				$strerr
			);
		}

		$variables = $rs['result']['variables'];
		$str = "";
		foreach ( $rs['result']['rows'] as $row ) {
			$str .= "\n";
			$separateur = "| ";
			foreach ( $variables as $variable ) {
				// START ADD BY DOUG to support optional variables in query
				if ( !isset( $row[$variable] ) ) {
					continue;
				}
				// END ADD BY DOUG
				if ( isset( $row[$variable . " type"] ) && $row[$variable . " type"] == "uri" ) {
					$str .= $separateur . self::uri2Link( $row[$variable] );
				} else {
					$str .= $separateur . $row[$variable];
				}
				$separateur = " || ";
			}
			$str .= "\n|- \n";
		}

		if ( $isDebug ) {
			$str .= "INPUT WIKI: \n" . $querySparqlWiki . "\n";
			$str .= "QUERY : " . $querySparql . "\n";
			$str .= print_r( $rs, true );
			return self::printMessageErrorDebug(
				$parser, 2, "Debug messages", $str );
		}

		return [ $str, 'noparse' => false, 'isHTML' => false ];
	}

	/**
	 * @param int $duration
	 * @param string $querySparqlWiki
	 * @param string $config
	 * @param string $endpoint
	 * @param string $classHeaders
	 * @param string $headers
	 * @return string
	 */
	public static function footer(
		$duration,
		$querySparqlWiki,
		$config,
		$endpoint,
		$classHeaders = '',
		$headers = '' ) {
		$today = date( wfMessage( 'linkedwiki-date' )->text() );
		return $today . " -- [{{fullurl:{{FULLPAGENAME}}|action=purge}} "
			. wfMessage( 'linkedwiki-refresh' )->text() . "] -- "
			. wfMessage( 'linkedwiki-duration' )->text() . " :"
			. round( $duration, 3 ) . "s";
	}

	/**
	 * @param Parser|null $parser
	 * @param int $duration
	 * @param string $querySparqlWiki
	 * @param string $config
	 * @param string $endpoint
	 * @param string $classHeaders
	 * @param string $headers
	 * @return string
	 */
	public static function footerHTML(
		$parser,
		$duration,
		$querySparqlWiki,
		$config,
		$endpoint,
		$classHeaders = '',
		$headers = '' ) {
		// error Exception caught: Request URL not set when push a page with a query
		try{
			$today = date( wfMessage( 'linkedwiki-date' )->text() );
			if ( empty( $parser ) ) {
				return $today . " -- " .
					wfMessage( 'linkedwiki-duration' )->text() . " :"
					. round( $duration, 3 )
					. "s -- <a class=\"csv\" style=\"cursor: pointer;\" >CSV</a>";
			} else {
				$url = $parser->getTitle()->getLocalURL( "action=purge" );
				return $today . " -- <a href=\"" . $url . "\">"
					. wfMessage( 'linkedwiki-refresh' )->text() . "</a> -- " .
					wfMessage( 'linkedwiki-duration' )->text() . " :"
					. round( $duration, 3 )
					. "s -- <a class=\"csv\" style=\"cursor: pointer;\" >CSV</a>";
			}
		} catch ( Exception $e ) {
			return "";
		}
	}

	/**
	 * @param string $uri
	 * @param bool $nowiki
	 * @return mixed
	 */
	public static function uri2Link( $uri, $nowiki = false ) {
		// TODO : $title ??? CLEAN ?
		$result = str_replace( "=", "{{equal}}", $uri );
		return $result;
	}

	private static function isDebug( $debugParam ) {
		return $debugParam != null
			&& (
				$debugParam == "YES"
				|| $debugParam == "yes"
				|| $debugParam == "1"
			);
	}

	/**
	 * @param Parser $parser
	 * @param int $logLevel
	 * @param string $messageName
	 * @param string $details
	 * @param bool $keepSpecialChars
	 * @return array
	 */
	private static function printMessageErrorDebug(
		$parser,
		$logLevel = 0,
		$messageName = "",
		$details = "",
		$keepSpecialChars = true
	) {
		$html = "";
		$messageNameHtml = $keepSpecialChars ? $messageName : htmlspecialchars( $messageName );
		$detailsHtml = $keepSpecialChars ? $details : htmlspecialchars( $details );
		// debug
		if ( $logLevel == 2 ) {
			$html .= "<p style='color:red'>" . $messageNameHtml . "</p>";
			$html .= "<pre>" . $detailsHtml . "</pre>";
		} elseif ( $logLevel == 1 ) {
			$html .= "<p style='color:red'>" . $messageNameHtml . "</p>";
		}

		if ( !empty( $parser ) && !empty( $parser->getTitle() ) ) {
			// deprecated ?
			$parser->addTrackingCategory( 'linkedwiki-category-query-error' );
			$parserOutput = $parser->getOutput();
			if ( method_exists( $parserOutput, 'setPageProperty' ) ) {
				// MW 1.38
				$parserOutput->setPageProperty( LinkedWikiStatus::PAGEPROP_ERROR_MESSAGE, $details );
			} else {
				$parserOutput->setProperty( LinkedWikiStatus::PAGEPROP_ERROR_MESSAGE, $details );
			}
		}
		// $logLevel == 0 // Print nothing
		return [ $html, 'noparse' => true, 'isHTML' => false ];
	}
}
