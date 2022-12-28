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
		$out->addModuleStyles( [ 'ext.LinkedWiki.common' ] );
		$out->addModules( [ 'ext.LinkedWiki.table2CSV', 'ext.LinkedWiki.SparqlParser' ] );

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

			$debug = isset( $vars["debug"] ) ? $vars["debug"] : null;
			$cache = isset( $vars["cache"] ) ? $vars["cache"] : "yes";
			$log = isset( $vars["log"] ) ? $vars["log"] : 1;

			$noResultMsg = isset( $vars["default"] ) ? $vars["default"] : '';

			$table = isset( $vars["table"] ) ? $vars["table"] : $configFactory->get( "SPARQLParserTableByDefault" );
			if ( $table !== "wiki" && $table !== "html" ) {
				$parser->getOutput()->updateCacheExpiry( 0 );
				$result = "'''Error #sparql: "
					. "Parameter table is incorrect. Possible values: '''wiki''' or '''html'''.";

				$parser->addTrackingCategory( 'linkedwiki-category-query-error' );
			}

			// Parameters with only cells
			$templateBare = isset( $vars["templateBare"] ) ? $vars["templateBare"] : '';

			// Parameters with a chart
			$chart = isset( $vars["chart"] ) ? $vars["chart"] : '';
			$options = isset( $vars["options"] ) ? $vars["options"] : '';

			// Parameters with a wiki/html table
			$class = isset( $vars["class"] ) ? $vars["class"] : 'wikitable sortable';
			$classHeaders = isset( $vars["classHeaders"] ) ? $vars["classHeaders"] : '';
			$headers = isset( $vars["headers"] ) ? $vars["headers"] : '';
			$footer = isset( $vars["footer"] ) ? $vars["footer"] : '';
			$preview = isset( $vars["preview"] ) ? $vars["preview"] : '';
			// one template by cell
			$templatesInCells = isset( $vars["templates"] ) ? $vars["templates"] : '';
			// one template in one cell with colspan
			$templatesInSingleCell = isset( $vars["templatesInSingleCell"] ) ? $vars["templatesInSingleCell"] : '';

			// Parameters with a grid
			$gridIntro = isset( $vars["gridIntro"] ) ? $vars["gridIntro"] : '';
			$gridOutro = isset( $vars["gridOutro"] ) ? $vars["gridOutro"] : '';
			$gridIntroTemplate = isset( $vars["gridIntroTemplate"] ) ? $vars["gridIntroTemplate"] : '';
			$gridOutroTemplate = isset( $vars["gridOutroTemplate"] ) ? $vars["gridOutroTemplate"] : '';
			// one template by row
			$gridRowTemplate = isset( $vars["gridRowTemplate"] ) ? $vars["gridRowTemplate"] : '';

			// Parameter with all templates
			$userparam = isset( $vars["userparam"] ) ? $vars["userparam"] : '';

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
				} elseif ( $gridRowTemplate != '' ) {
					return self::grid(
						$parser,
						$query,
						$config,
						$endpoint,
						$gridRowTemplate,
						$gridIntro,
						$gridOutro,
						$gridIntroTemplate,
						$gridOutroTemplate,
						$userparam,
						$footer,
						$preview,
						$debug,
						$log,
						$noResultMsg );
				} else {
					if ( $table == "wiki" ) {
						return self::tableWiki(
							$parser,
							$query,
							$config,
							$endpoint,
							$class,
							$classHeaders,
							$headers,
							$templatesInCells,
							$templatesInSingleCell,
							$userparam,
							$footer,
							$preview,
							$debug,
							$log,
							$noResultMsg );
					} elseif ( $table == "html" ) {
						return self::tableHTML(
							$parser,
							$query,
							$config,
							$endpoint,
							$class,
							$classHeaders,
							$headers,
							$templatesInCells,
							$templatesInSingleCell,
							$userparam,
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

	/**
	 * @param Parser $parser
	 * @param string $querySparqlWiki
	 * @param string $config
	 * @param string $endpoint
	 * @param string $gridRowTemplate
	 * @param string $gridIntro
	 * @param string $gridOutro
	 * @param string $gridIntroTemplate
	 * @param string $gridOutroTemplate
	 * @param string $userparam
	 * @param string $footer
	 * @param string $preview
	 * @param null|string $debug
	 * @param null|string $log
	 * @param string $noResultMsg
	 * @return array
	 */
	public static function grid(
		$parser,
		$querySparqlWiki,
		$config,
		$endpoint,
		$gridRowTemplate,
		$gridIntro = '',
		$gridOutro = '',
		$gridIntroTemplate = '',
		$gridOutroTemplate = '',
		$userparam = '',
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
		$variables = $rs['result']['variables'];

		$str = '';
		if ( empty( $rs['result']['rows'] ) && !empty( $noResultMsg ) ) {
			$str = $noResultMsg;
		} else {
			if ( $gridIntro != '' ) {
				$str = $gridIntro;
			} elseif ( $gridIntroTemplate != '' ) {
				$str = '{{' . $gridIntroTemplate . ( $userparam != '' ? '|userparam=' . $userparam : '' ) . '}}';
			}
			$nbRows = 0;
			$limitRow = is_numeric( $preview ) ? 0 + $preview : -1;
			foreach ( $rs['result']['rows'] as $row ) {
				if ( $limitRow > 0 && $nbRows >= $limitRow ) {
					break;
				}
				$str .= self::valueTemplate( $gridRowTemplate, $variables, $row, $userparam );
				$nbRows++;
			}
			if ( $gridOutro != '' ) {
				$str .= $gridOutro;
			} elseif ( $gridOutroTemplate != '' ) {
				$str .= '{{' . $gridOutroTemplate . ( $userparam != '' ? '|userparam=' . $userparam : '' ) . '}}';
			}

			if ( $footer != "NO" && $footer != "no" ) {
				$str .= "<br/><span>\n";
				$str .= self::footerHTML(
						$parser,
						$rs['query_time'],
						$querySparqlWiki,
						$config,
						$endpoint,
						'',
						''
					);
				$str .= "</span>\n";
			}
		}
		if ( $isDebug ) {
			$str .= "INPUT WIKI: " . $querySparqlWiki . "\n";
			$str .= "Query: " . $querySparql . "\n";
			$str .= print_r( $rs, true );
			return self::printMessageErrorDebug(
				$parser, 2, "Debug messages", $str );
		}
		return [ $str, 'noparse' => false, 'isHTML' => true ];
	}

	/**
	 * @param Parser $parser
	 * @param string $querySparqlWiki
	 * @param string $config
	 * @param string $endpoint
	 * @param string $class
	 * @param string $classHeaders
	 * @param string $headers
	 * @param string $templatesInCells
	 * @param string $templatesInSingleCell
	 * @param string $userparam
	 * @param string $footer
	 * @param string $preview
	 * @param null|string $debug
	 * @param null|string $log
	 * @param string $noResultMsg
	 * @return array
	 */
	public static function tableWiki(
		$parser,
		$querySparqlWiki,
		$config,
		$endpoint,
		$class,
		$classHeaders = '',
		$headers = '',
		$templatesInCells = '',
		$templatesInSingleCell = '',
		$userparam = '',
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

		$variables = $rs['result']['variables'];
		$isSingleCell = !empty( $templatesInSingleCell );
		$nbColumn = 0;

		if ( empty( $rs['result']['rows'] ) && !empty( $noResultMsg ) ) {
			$str = $noResultMsg;
		} else {
			$lineGrey  = false;
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
			} elseif ( $isSingleCell ) {
				// no headers by default
			} else {
				$TableClassHeaders = explode( ",", $classHeaders );
				for ( $i = 0; $i < count( $variables ); $i++ ) {
					if ( !isset( $TableClassHeaders[$i] ) || $TableClassHeaders[$i] == "" ) {
						$classStr = "";
					} else {
						$classStr = $TableClassHeaders[$i] . "|";
					}
					$TableTitleHeaders[$i] = $classStr . $variables[$i];
				}

				$str .= "|- \n";
				$str .= "!" . implode( "!!", $TableTitleHeaders );
				$str .= "\n";
			}

			$nbRows = 0;
			$limitRow = is_numeric( $preview ) ? 0 + $preview : -1;
			foreach ( $rs['result']['rows'] as $row ) {
				$str .= "|- ";
				if ( $lineGrey ) {
					$str .= "bgcolor=\"#f5f5f5\"";
				}
				if ( $limitRow > 0 && $nbRows >= $limitRow ) {
					$str .= ' style="display:none" ';
				}
				$lineGrey = !$lineGrey;
				$str .= "\n";
				$separator = "|";

				if ( $isSingleCell ) {
					$tableFormatTemplatesInSingleCell = explode( ",", $templatesInSingleCell );
					$nbColumn = count( $tableFormatTemplatesInSingleCell );
					$str .= $separator;
					foreach ( $tableFormatTemplatesInSingleCell as $key => $tableFormatTemplate ) {
						$str .= self::valueTemplate( $tableFormatTemplate, $variables, $row, $userparam );
					}
				} else {
					$tableFormatTemplatesInCells = explode( ",", $templatesInCells );
					if ( $headers != '' ) {
						$TableTitleHeaders = explode( ",", $headers );

						$nbColumn = count( $TableTitleHeaders );
						foreach ( $TableTitleHeaders as $key => $title ) {
							$str .= $separator;
							if ( isset( $tableFormatTemplatesInCells[$key] )
								&& !empty( $tableFormatTemplatesInCells[$key] ) ) {
								$str .= self::valueTemplate(
									$tableFormatTemplatesInCells[$key],
									$variables,
									$row,
									$userparam );
							} elseif ( isset( $variables[$key] ) ) {
								$str .= self::valueWiki( $row, $variables[$key] );
							}
							$separator = !empty( $templatesInSingleCell ) ? "" : "||";
						}
					} else {
						$nbColumn = count( $variables );
						foreach ( $variables as $key => $variable ) {
							$str .= $separator;
							if ( isset( $tableFormatTemplatesInCells[$key] )
								&& !empty( $tableFormatTemplatesInCells[$key] ) ) {
								$str .= self::valueTemplate(
									$tableFormatTemplatesInCells[$key],
									$variables,
									$row,
									$userparam );
							} else {
								$str .= self::valueWiki( $row, $variable );
							}
							$separator = !empty( $templatesInSingleCell ) ? "" : "||";
						}
					}
				}
				$str .= "\n";
				$nbRows++;
			}

			if ( $footer != "NO" && $footer != "no" ) {
				$str .= "|- style=\"font-size:80%\" align=\"right\"\n";
				$str .= "| colspan=\"" . $nbColumn . "\"|" .
					self::footerWiki( $rs['query_time'], $querySparqlWiki, $config, $endpoint, $classHeaders, $headers )
					. "\n";
			}
			$str .= "|}\n";
		}

		if ( $isDebug ) {
			$str .= "INPUT WIKI: " . $querySparqlWiki . "\n";
			$str .= "Query: " . $querySparql . "\n";
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
	 * @param string $templatesInCells
	 * @param string $templatesInSingleCell
	 * @param string $userparam
	 * @param string $footer
	 * @param string $preview
	 * @param null|string $debug
	 * @param null|string $log
	 * @param string $noResultMsg
	 * @return array
	 */
	public static function tableHTML(
		$parser,
		$querySparqlWiki,
		$config,
		$endpoint,
		$class,
		$classHeaders = '',
		$headers = '',
		$templatesInCells = '',
		$templatesInSingleCell = '',
		$userparam = '',
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

		$variables = $rs['result']['variables'];
		$isOneColumnByVariable = empty( $templatesInSingleCell );
		$isSingleCell = !empty( $templatesInSingleCell );
		$nbColumn = 0;

		if ( empty( $rs['result']['rows'] ) && !empty( $noResultMsg ) ) {
			$str = $noResultMsg;
		} else {
			$lineGrey  = false;
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
			} elseif ( $isSingleCell ) {
				// no headers by default
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
				if ( $lineGrey ) {
					$str .= " bgcolor=\"#f5f5f5\" ";
				}
				if ( $limitRow > 0 && $nbRows >= $limitRow ) {
					$str .= ' style="display:none" ';
				}
				$str .= ">\n";
				$lineGrey = !$lineGrey;

				if ( $isSingleCell ) {
					$tableFormatTemplatesInSingleCell = explode( ",", $templatesInSingleCell );
					$nbColumn = count( $tableFormatTemplatesInSingleCell );
					$str .= "<td>";
					foreach ( $tableFormatTemplatesInSingleCell as $key => $tableFormatTemplate ) {
						$str .= self::valueTemplate(
							$tableFormatTemplate,
							$variables,
							$row,
							$userparam );
					}
					$str .= "</td>";
				} else {
					$tableFormatTemplatesInCells = explode( ",", $templatesInCells );

					if ( $headers != '' ) {
						$TableTitleHeaders = explode( ",", $headers );

						$nbColumn = count( $TableTitleHeaders );
						foreach ( $TableTitleHeaders as $key => $title ) {
							$str .= "<td>";
							if ( isset( $tableFormatTemplatesInCells[$key] )
								&& !empty( $tableFormatTemplatesInCells[$key] ) ) {
								$str .= self::valueTemplate(
									$tableFormatTemplatesInCells[$key],
									$variables,
									$row,
									$userparam );
							} elseif ( isset( $variables[$key] ) ) {
								$str .= self::valueHTML( $row, $variables[$key] );
							}
							$str .= "</td>\n";
						}
					} else {
						$nbColumn = count( $variables );
						foreach ( $variables as $key => $variable ) {
							$str .= "<td>";
							if ( isset( $tableFormatTemplatesInCells[$key] )
								&& !empty( $tableFormatTemplatesInCells[$key] ) ) {
								$str .= self::valueTemplate(
									$tableFormatTemplatesInCells[$key],
									$variables,
									$row,
									$userparam );
							} else {
								$str .= self::valueHTML( $row, $variable );
							}
							$str .= "</td>\n";
						}
					}
				}
				$str .= "</tr>\n";
				$nbRows++;
			}

			if ( $footer != "NO" && $footer != "no" ) {
				$str .= "<tr style=\"font-size:80%\" align=\"right\">\n";
				$str .= "<td colspan=\"" . $nbColumn . "\">"
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
			$str .= "QUERY: " . $querySparql . "\n";
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
			$separator = "| ";
			foreach ( $variables as $variable ) {
				$str .= $separator . self::valueWiki( $row, $variable );
				$separator = " || ";
			}
			$str .= "\n|- \n";
		}

		if ( $isDebug ) {
			$str .= "INPUT WIKI: \n" . $querySparqlWiki . "\n";
			$str .= "QUERY: " . $querySparql . "\n";
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
	public static function footerWiki(
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
	 * @param array $row
	 * @param string $variable
	 * @return string
	 */
	private static function valueWiki( $row, $variable ) {
		$str = '';
		$value = isset( $row[$variable] ) && !empty( $row[$variable] ) ?
			$row[$variable] : "";

		if ( isset( $row[$variable . " type"] ) && $row[$variable . " type"] == "uri" ) {
			$str .= str_replace( "=", "{{equal}}", $value );
		} else {
			$str .= empty( $value ) ? "" : $value;
		}
		return $str;
	}

	/**
	 * @param array $row
	 * @param string $variable
	 * @return string
	 */
	private static function valueHTML( $row, $variable ) {
		$str = '';
		$value = isset( $row[$variable] ) && !empty( $row[$variable] ) ?
			$row[$variable] : "";

		if ( isset( $row[$variable . " type"] ) && $row[$variable . " type"] == "uri" ) {
			$str .= "<a href='" . $value . "'>" . $value . "</a>";
		} else {
			$str .= empty( $value ) ? "&nbsp;" : htmlentities( $value );
		}
		return $str;
	}

	/**
	 * @param string $templateName
	 * @param array $variables
	 * @param array $row
	 * @param string $userparam
	 * @return string
	 */
	private static function valueTemplate( $templateName, $variables, $row, $userparam = '' ) {
		$str = '';
		$arrayParameters = [];
		foreach ( $variables as $variable ) {
			// support optional variables
			if ( isset( $row[$variable] ) ) {
				$arrayParameters[] = $variable . " = " . self::valueWiki( $row, $variable );
			}
		}
		if ( !empty( $userparam ) ) {
			$arrayParameters[] = 'userparam=' . $userparam;
		}

		$str .= "{{" . $templateName
			. "|" . implode( "|", $arrayParameters )
			. "}}";
		return $str;
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
