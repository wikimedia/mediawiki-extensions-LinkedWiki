<?php

use BorderCloud\SPARQL\ParserSparql;
use MediaWiki\MediaWikiServices;

/**
 * @package Bourdercloud/linkedwiki
 * @copyright (c) 2021 Bordercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

// Libraries
//require_once( dirname(__FILE__) ."/../LinkedWikiConfig.php");

class LinkedWikiLuaLibrary extends Scribunto_LuaLibraryBase {
	/**
	 * Current configuration
	 * @var LinkedWikiConfig
	 */
	private $objConfig = null;

	/**
	 * Default subject of triples
	 * @var string IRI
	 */
	private $subject = null;

	/**
	 * Last SPARQL query used (enable the mode debug with setDebug(true) to see the last query in error)
	 * @var string
	 */
	private $lastQuery = null;

	////PUBLIC FUNCTION

	/**
	 * These functions will be exposed to the Lua module.
	 * They are member functions on a Lua table which is private to the module, thus
	 * these can't be called from user code, unless explicitly exposed in Lua.
	 * @return mixed
	 */
	public function register() {
		$lib = [
			'setConfig' => [ $this, 'setConfig' ],
			'getConfig' => [ $this, 'getConfig' ],
			'getDefaultConfig' => [ $this, 'getDefaultConfig' ],

			'setLang' => [ $this, 'setLang' ],
			'getLang' => [ $this, 'getLang' ],

			'setSubject' => [ $this, 'setSubject' ],
			'removeSubject' => [ $this, 'removeSubject' ],

			'setDebug' => [ $this, 'setDebug' ],
			'isDebug' => [ $this, 'isDebug' ],
			'getLastQuery' => [ $this, 'getLastQuery' ],

			'addPropertyWithIri' => [ $this, 'addPropertyWithIri' ],
			'addPropertyWithLiteral' => [ $this, 'addPropertyWithLiteral' ],
			'getValue' => [ $this, 'getValue' ],
			'getString' => [ $this, 'getString' ],

			'setEndpoint' => [ $this, 'setEndpoint' ],
			'query' => [ $this, 'query' ],
			'loadData' => [ $this, 'loadData' ],

			'getProtocol' => [ $this, 'getProtocol' ],
			'loadStyles' => [ $this, 'loadStyles' ]

		];
		return $this->getEngine()->registerInterface(
			__DIR__ . '/LinkedWiki.lua', $lib, []
		);
	}

	/**
	 * Load the css ressources used in infoboxes
	 */
	public function loadStyles() {
		$this->getParser()->getOutput()->addModules( [ 'ext.LinkedWiki.Lua' ] );
	}

	/**
	 * Get the last SPARQL query used by this class
	 *
	 * @return array [
	 *     the last SPARQL query
	 * ]
	 */
	public function getLastQuery() {
		return [ $this->lastQuery ];
	}

	/**
	 * Get the current protocol of wiki. It's useful when we need to build a correct url
	 * to print an image in a HTML page.
	 *
	 * @return array luaBindings [
	 *     the current protocol of wiki
	 * ]
	 */
	public function getProtocol() {
		return [ WebRequest::detectProtocol() ];
	}

	/**
	 * Set the default SPARQL endpoint configuration to use
	 * (see https://www.mediawiki.org/wiki/Extension:LinkedWiki/Configuration )
	 *
	 * @param null|string $urlConfig
	 * @return array luaBindings [
	 *     bool True, if the configuration has been changed else false
	 *     string A error message when the configuration has not been changed
	 * ]
	 */
	public function setConfig( $urlConfig = null ) {
		try {
			if ( empty( $this->objConfig ) ) {
				$this->objConfig = new LinkedWikiConfig( $urlConfig );
			} elseif ( $this->objConfig->getConfigEndpoint() !== $urlConfig ) {
				$this->objConfig->setConfigEndpoint( $urlConfig );
			}
		} catch ( Exception $e ) {
			return [ false, $e->getMessage() ];
		}
		return [ true ];
	}

	/**
	 * Get the current SPARQL endpoint configuration to use
	 * (see https://www.mediawiki.org/wiki/Extension:LinkedWiki/Configuration )
	 *
	 * @return array luaBindings [
	 *     string Uri of the configuration
	 * ]
	 */
	public function getConfig() {
		return [ $this->getInstanceConfig()->getConfigEndpoint() ];
	}

	/**
	 * Get the default SPARQL endpoint configuration for this wiki
	 * (see https://www.mediawiki.org/wiki/Extension:LinkedWiki/Configuration )
	 *
	 * @return array luaBindings [
	 *     string Uri of the default configuration of wiki
	 * ]
	 */
	public function getDefaultConfig() {
		return [ $this->getInstanceConfig()->getDefaultConfigEndpoint() ];
	}

	/**
	 * Enable or disable the mode debug
	 * With the mode debug, the error message are more verbose.
	 *
	 * @param string $debug
	 */
	public function setDebug( $debug ) {
		$this->checkType( 'setDebug', 1, $debug, 'boolean' );
		$this->getInstanceConfig()->setDebug( $debug );
	}

	/**
	 * Get information about the debug mode
	 *
	 * @return array luaBindings [
	 *     bool state of debug mode
	 * ]
	 */
	public function isDebug() {
		return [ $this->getInstanceConfig()->isDebug() ];
	}

	/**
	 * Change the read access SPARQL endpoint
	 *
	 * @param string $urlEndpoint
	 */
	public function setEndpoint( $urlEndpoint ) {
		$this->checkType( 'setEndpoint', 1, $urlEndpoint, 'string' );
		$errorMessage = null;
		// default config
		$keyConfigByDefault = "http://www.example.org";
		$this->objConfig = new LinkedWikiConfig( $keyConfigByDefault );
		// $this->objConfig->setEndpoint($urlEndpoint);
		$this->objConfig->setEndpointRead( $urlEndpoint );
	}

	/**
	 * Change the subject of triple by default
	 *
	 * @param string $iriSubject
	 */
	public function setSubject( $iriSubject ) {
		$this->checkType( 'setSubject', 1, trim( $iriSubject ), 'string' );
		$this->subject = trim( $iriSubject );
	}

	/**
	 * Change the tag lang by default (for example: fr, en, etc.)
	 *
	 * @param string $tagLang
	 */
	public function setLang( $tagLang ) {
		$langTrim = trim( $tagLang );
		$this->checkType( 'setLang', 1, $langTrim, 'string' );
		if ( empty( $this->objConfig ) && $this->objConfig->getLang() != $langTrim ) {
			$this->getInstanceConfig()->setLang( $langTrim );
		}
	}

	/**
	 * Get the tag lang by default
	 *
	 * @return array luaBindings [
	 *     string tag lang
	 * ]
	 */
	public function getLang() {
		return [ $this->getInstanceConfig()->getLang() ];
	}

	/**
	 * Find the literal of type string for a property with the subject by default
	 * and the tag lang by default.
	 * You can also change the subject and the tag lang
	 * with optional parameters.
	 *
	 * The result is a list of string because a subject can have several triples with
	 * the same property and different values.
	 *
	 * @param string $iriProperty : IRI of the property
	 * @param string|null $tagLang : replace the lang by default. if null,
	 *                               it will search the value without tag lang.
	 * @param string|null $iriSubject : Optional, IRI of subject
	 * @return array luaBindings [
	 *     string list of results with separator ';'
	 *     string Error message
	 * ]
	 */
	public function getString( $iriProperty, $tagLang = null, $iriSubject = null ) {
		if ( $iriSubject === null && $this->subject === null ) {
			return [
				null,
				wfMessage( "linkedwiki-lua-param-error-subject-unknown" )->plain()
			];
		}

		$result = "";
		$this->checkType( 'getString', 1, $iriProperty, 'string' );
		$this->checkTypeOptional(
			'getString', 2, $tagLang, 'string', $this->getInstanceConfig()->getLang()
		);
		$this->checkTypeOptional( 'getString', 3, $iriSubject, 'string', $this->subject );
		$subject = ( $iriSubject === null ) ?
			"<" . $this->subject . ">"
			: "<" . trim( $iriSubject ) . ">";
		$property = "<" . trim( $iriProperty ) . ">";

		$q = "";
		if ( $tagLang === null ) {
			$parameters = [ "?subject", "?property", "?lang" ];
			$values = [ $subject, $property, "\"" . $this->getInstanceConfig()->getLang() . "\"" ];
			$q = str_replace( $parameters,
				$values,
				$this->getInstanceConfig()->getQueryReadStringWithTagLang() );
		} elseif ( $tagLang === "" ) {
			$parameters = [ "?subject", "?property" ];
			$values = [ $subject, $property ];
			$q = str_replace( $parameters,
				$values,
				$this->getInstanceConfig()->getQueryReadStringWithoutTagLang() );
		} else {
			$parameters = [ "?subject", "?property", "?lang" ];
			$values = [ $subject, $property, "\"" . trim( $tagLang ) . "\"" ];
			$q = str_replace( $parameters,
				$values,
				$this->getInstanceConfig()->getQueryReadStringWithTagLang() );
		}

		// for debug
		$this->setLastQuery( $q );

		$endpoint = $this->getInstanceEndpoint();
		$rows = $endpoint->query( $q, 'rows' );
		$err = $endpoint->getErrors();
		if ( $err ) {
			return $this->manageError( "", $err );
		} else {
			$this->doAfterReading();
		}

		$result = [];
		foreach ( $rows["result"]["rows"] as $row ) {
			$result[] = $row["value"];
		}

		return [ implode( ";", $result ) ];
	}

	/**
	 * Find the literal for a property with the subject by default.
	 * You can also change the subject with an optional parameter.
	 *
	 * The result is a list of value of all types because a subject
	 * can have several triples with the same property and different values.
	 * If you need to select triples by the tag lang, you can use
	 * the function getString in this class.
	 *
	 * @param string $iriProperty : IRI of the property
	 * @param string|null $iriSubject : Optional, IRI of subject
	 * @return array luaBindings [
	 *     string list of results with separator ';'
	 *     string Error message
	 * ]
	 */
	public function getValue( $iriProperty, $iriSubject = null ) {
		if ( $iriSubject === null && $this->subject === null ) {
			return [
				null,
				wfMessage( "linkedwiki-lua-param-error-subject-unknown" )->plain()
			];
		}
		$this->checkType( 'getValue', 1, $iriProperty, 'string' );
		$this->checkTypeOptional( 'getValue', 2, $iriSubject, 'string', $this->subject );
		// $this->checkType( 'getValue', 2, $iriSubject, 'string', $this->subject  );

		$subject = ( $iriSubject === null ) ?
			"<" . $this->subject . ">"
			: "<" . trim( $iriSubject ) . ">";
		$property = "<" . trim( $iriProperty ) . ">";

		$parameters = [ "?subject", "?property" ];
		$values = [ $subject, $property ];
		$q = str_replace( $parameters,
			$values,
			$this->getInstanceConfig()->getQueryReadValue() );

		// for debug
		$this->setLastQuery( $q );

		$endpoint = $this->getInstanceEndpoint();
		$rows = $endpoint->query( $q, 'rows' );
		$err = $endpoint->getErrors();
		if ( $err ) {
			return $this->manageError( "", $err );
		} else {
			$this->doAfterReading();
		}

		$result = [];
		foreach ( $rows["result"]["rows"] as $row ) {
			$result[] = $row["value"];
		}

		return [ implode( ";", $result ) ];
	}

	/**
	 * Execute a SPARQL query only in read access.
	 *
	 * @param string $q SPARQL query
	 * @return array luaBindings [
	 *     array [
	 *             variables => array variable names of rows
	 *             rows => array of rows
	 *           ]
	 *     string Error message
	 * ]
	 */
	public function query( $q ) {
		if ( ParserSparql::isUpdateQuery( $q ) ) {
			return $this->manageError(
				"",
				wfMessage( "linkedwiki-lua-query-error-not-allow-to-write" )->plain()
			);
		}

		// for debug
		$this->setLastQuery( $q );
		$endpoint = clone $this->getInstanceEndpoint();
		// disable update
		$endpoint->setEndpointWrite( "" );
		$result = $endpoint->query( $q );
		$err = $endpoint->getErrors();
		if ( $err ) {
			return $this->manageError( "", $err );
		} else {
			$this->doAfterReading();
		}
		return [ $result ];
	}

	/**
	 * Add a triple of type Subject(IRI) Property(IRI) Object(IRI).
	 *
	 * @param string $iriProperty : IRI of the property
	 * @param string $iriObject : IRI of the object
	 * @param string|null $iriSubject : Optional, IRI of subject
	 * @return array luaBindings [
	 *     string response of server
	 *     string Error message
	 * ]
	 */
	public function addPropertyWithIri(
		$iriProperty, $iriObject, $iriSubject = null ) {
		if ( $iriSubject === null && $this->subject === null ) {
			return [
				null,
				wfMessage( "linkedwiki-lua-param-error-subject-unknown" )->plain()
			];
		}

		if ( preg_match( "/(\"\"\"|''')/i", $iriObject ) ) {
			return [ null, "Bad value" ];
		}
		if ( preg_match( "/(\"\"\"|'''| )/i", trim( $iriProperty ) ) ) {
			return [ null, "Bad property" ];
		}
		if ( preg_match( "/(\"\"\"|'''| )/i", trim( $iriSubject ) ) ) {
			return [ null, "Bad subject" ];
		}
		if ( $this->isPreviewOrHistory() ) {
			// it is not an error. When it's a preview page or an archive, to do nothing.
			return [
				wfMessage( "linkedwiki-lua-message-preview-or-history" )->plain()
			];
		}

		$this->checkType( 'addPropertyWithIri', 1, $iriProperty, 'string' );
		$this->checkType( 'addPropertyWithIri', 2, $iriObject, 'string' );
		$this->checkTypeOptional( 'addPropertyWithIri', 3,
			$iriSubject, 'string', $this->subject );

		$subject = ( $iriSubject === null ) ?
			"<" . $this->subject . ">"
			: "<" . trim( $iriSubject ) . ">";
		$property = "<" . trim( $iriProperty ) . ">";
		$object = "<" . trim( $iriObject ) . ">";

		$parameters = [ "?subject", "?property", "?value" ];
		$values = [ $subject, $property, $object ];
		$q = str_replace( $parameters,
			$values,
			$this->getInstanceConfig()->getQueryInsertValue() );

		// for debug
		$this->setLastQuery( $q );

		$endpoint = $this->getInstanceEndpoint();
		$response = $endpoint->query( $q, 'raw' );
		$err = $endpoint->getErrors();
		if ( $err ) {
			return $this->manageError( $response, $err );
		} else {
			$this->doAfterWriting();
		}
		return [ $response ];
	}

	/**
	 * Add a triple of type Subject(IRI) Property(IRI) value(Literal).
	 *
	 * @param string $iriProperty : IRI of the property
	 * @param number|string $value : Literal is a number or a string
	 * @param null|string $type : datatype IRI of literal
	 * @param null|string $tagLang : tag lang of string literal.
	 *                               If null, it will use the default tag lang.
	 *                               If empty (""), the tag lang will not save.
	 * @param null|string $iriSubject : Optional, IRI of subject
	 * @return array luaBindings [
	 *     string response of server
	 *     string Error message
	 * ]
	 */
	public function addPropertyWithLiteral(
		$iriProperty, $value, $type = null, $tagLang = null, $iriSubject = null ) {
		if ( $iriSubject === null && $this->subject === null ) {
			return [
				null,
				wfMessage( "linkedwiki-lua-param-error-subject-unknown" )->plain()
			];
		}
		if ( ( empty( $value ) && !is_numeric( $value ) ) || preg_match( "/(\"\"\"|''')/i", $value ) ) {
			return [ null, "Bad value" ];
		}
		if ( $iriProperty === null || empty( $iriProperty )
			|| preg_match( "/(\"\"\"|'''| )/i", trim( $iriProperty ) ) ) {
			return [ null, "Bad property" ];
		}
		if ( preg_match( "/(\"\"\"|'''| )/i", trim( $iriSubject ) ) ) {
			return [ null, "Bad subject" ];
		}
		if ( $this->isPreviewOrHistory() ) {
			// it is not an error. When it's a preview page or an archive, to do nothing.
			return [
				wfMessage( "linkedwiki-lua-message-preview-or-history" )->plain()
			];
		}
		if ( !empty( $type ) ) {
			switch ( $type ) {
				case "http://www.w3.org/2001/XMLSchema#date":
					/**
					 * RegExp to test a string for a ISO 8601 Date spec
					 *  YYYY
					 *  YYYY-MM
					 *  YYYY-MM-DD
					 *  YYYY-MM-DDThh:mmTZD
					 *  YYYY-MM-DDThh:mm:ssTZD
					 *  YYYY-MM-DDThh:mm:ss.sTZD
					 * @see: https://www.w3.org/TR/NOTE-datetime
					 * @type {RegExp}
					 */
					if (
						!preg_match(
							"/^\d{4}(-\d\d(-\d\d(T\d\d:\d\d(:\d\d)?(\.\d+)?(([+-]\d\d:\d\d)|Z)?)?)?)?$/i",
							trim( $value )
						)
					) {
						return [ null, wfMessage( "linkedwiki-lua-type-error-xsddate", $value )->plain() ];
					}
					break;
				case "http://www.w3.org/2001/XMLSchema#dateTime":
					/**
					 * RegExp to test a string for a full ISO 8601 Date
					 * Does not do any sort of date validation,
					 * only checks if the string is according to the ISO 8601 spec.
					 *  YYYY-MM-DDThh:mm:ss
					 *  YYYY-MM-DDThh:mm:ssTZD
					 *  YYYY-MM-DDThh:mm:ss.sTZD
					 * @see: https://www.w3.org/TR/NOTE-datetime
					 * @type {RegExp}
					 */
					if (
						!preg_match(
							"/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(\.\d+)?(([+-]\d\d:\d\d)|Z)?$/i",
							trim( $value )
						)
					) {
						return [ null, wfMessage( "linkedwiki-lua-type-error-xsddatetime", $value )->plain() ];
					}
					break;
				case "http://www.w3.org/2001/XMLSchema#long":
				case "http://www.w3.org/2001/XMLSchema#short":
				case "http://www.w3.org/2001/XMLSchema#int":
					if ( !is_numeric( $value ) || !is_int( 0 + $value ) ) {
						return [ null, wfMessage( "linkedwiki-lua-type-error-xsdint", $value )->plain() ];
					}
					break;
				case "http://www.w3.org/2001/XMLSchema#float":
				case "http://www.w3.org/2001/XMLSchema#decimal":
					if ( !is_numeric( $value ) || ( !is_float( 0 + $value ) && !is_int( 0 + $value ) ) ) {
						return [ null, wfMessage( "linkedwiki-lua-type-error-xsddecimal", $value )->plain() ];
					}
					break;
			}
		}

		$subject = ( $iriSubject === null ) ?
			"<" . $this->subject . ">"
			: "<" . trim( $iriSubject ) . ">";
		$property = "<" . trim( $iriProperty ) . ">";

		$strValue = "";
		if ( is_string( $value ) ) {
			$strValue = "\"\"\"" . trim( $value ) . "\"\"\"";

			if ( $type != null ) {
				$strValue .= "^^" . "<" . trim( $type ) . ">";
			}
			switch ( trim( $type ) ) {
				case "http://www.w3.org/2001/XMLSchema#date":
				case "http://www.w3.org/2001/XMLSchema#dateTime":
				case "http://www.w3.org/2001/XMLSchema#long":
				case "http://www.w3.org/2001/XMLSchema#short":
				case "http://www.w3.org/2001/XMLSchema#int":
				case "http://www.w3.org/2001/XMLSchema#float":
				case "http://www.w3.org/2001/XMLSchema#decimal":
					break;
				default:
					if ( $tagLang === null ) {
						$strValue .= "@" . $this->getInstanceConfig()->getLang();
					} elseif ( $tagLang === "" ) {
						// do nothing;
					} else {
						$strValue .= "@" . trim( $tagLang );
					}
			}
		} else {
			$strValue = strval( $value );
			if ( $type != null ) {
				$strValue = "\"" . $strValue . "\"" . "^^" . "<" . trim( $type ) . ">";
			}
		}

		$parameters = [ "?subject", "?property", "?value" ];
		$values = [ $subject, $property, $strValue ];
		$q = str_replace( $parameters,
			$values,
			$this->getInstanceConfig()->getQueryInsertValue() );

		// for debug
		$this->setLastQuery( $q );

		$endpoint = $this->getInstanceEndpoint();
		$response = $endpoint->query( $q, 'raw' );
		$err = $endpoint->getErrors();
		if ( $err ) {
			return $this->manageError( $response, $err );
		} else {
			$this->doAfterWriting();
		}

		return [ $response ];
	}

	/**
	 * Delete all triples with the subject by default or an other subject.
	 *
	 * @param null|string $iriSubject if null, it uses the subject by default
	 * @return array luaBindings [
	 *     string response of server
	 *     string Error message
	 * ]
	 */
	public function removeSubject( $iriSubject = null ) {
		if ( $iriSubject === null && $this->subject === null ) {
			return [
				null,
				wfMessage( "linkedwiki-lua-param-error-subject-unknown" )->plain()
			];
		}
		if ( preg_match( "/(\"\"\"|'''| )/i", trim( $iriSubject ) ) ) {
			return [ null, "ERROR : Bad subject" ];
		}
		if ( $this->isPreviewOrHistory() ) {
			// it is not an error. When it's a preview page or an archive, to do nothing.
			return [
				wfMessage( "linkedwiki-lua-message-preview-or-history" )->plain()
			];
		}

		$subject = ( $iriSubject === null ) ?
			"<" . $this->subject . ">"
			: "<" . trim( $iriSubject ) . ">";

		$parameters = [ "?subject" ];
		$values = [ $subject ];
		$q = str_replace( $parameters,
			$values,
			$this->getInstanceConfig()->getQueryDeleteSubject() );

		// for debug
		$this->setLastQuery( $q );

		$endpoint = $this->getInstanceEndpoint();
		$response = $endpoint->query( $q, 'raw' );
		$err = $endpoint->getErrors();
		if ( $err ) {
			return $this->manageError( $response, $err );
		} else {
			$this->doAfterWriting();
		}
		return [ $response ];
	}

	/**
	 * Load the RDF of wiki pages in the database.
	 *
	 * @param string $titles list of page titles in the wiki with comma separator
	 * @return array luaBindings [
	 *     string response of server
	 *     string Error message
	 * ]
	 */
	public function loadData( $titles ) {
		$listTitle = explode( ",", $titles );
		$q = "";
		foreach ( $listTitle as $title ) {
			$titleObject = Title::newFromText( trim( $title ) );
			if ( $titleObject->exists() ) {
				$q .= $this->getInstanceConfig()->getQueryLoadData(
						$titleObject->getFullURL() . "?action=raw&export=rdf"
					) . ' ; ';
			}
		}

		// for debug
		$this->setLastQuery( $q );

		$endpoint = $this->getInstanceEndpoint();
		$response = $endpoint->query( $q, 'raw' );
		$err = $endpoint->getErrors();
		if ( $err ) {
			return $this->manageError( $response, $err );
		} else {
			$this->doAfterWriting();
		}

		return [ $response ];
	}

	////PRIVATE FUNCTION

	/**
	 * @return LinkedWikiConfig
	 */
	private function getInstanceConfig() {
		if ( $this->objConfig === null ) {
			$this->objConfig = new LinkedWikiConfig();
		}
		return $this->objConfig;
	}

	/**
	 * @return \BorderCloud\SPARQL\SparqlClient|null
	 */
	private function getInstanceEndpoint() {
		return $this->getInstanceConfig()->getInstanceEndpoint();
	}

	/**
	 * Detect if the current page with this module is only a preview or an archive.
	 * This function helps to disable the save in the database.
	 *
	 * @return bool
	 */
	private function isPreviewOrHistory() {
		if ( array_key_exists( "wpPreview", $_REQUEST )
			|| ( array_key_exists( "oldid", $_REQUEST ) && $_REQUEST["oldid"] > 0 )
		) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Save the last query.
	 *
	 * @param string $query
	 */
	private function setLastQuery( $query ) {
		$this->lastQuery = $query;
	}

	/**
	 * For the functions with SPARQL query, this function encapsule
	 * a SPARQL error in a lua error.
	 *
	 * @param string $response
	 * @param string $err
	 * @return array luaBindings [
	 *     string response of server
	 *     string Error message in function of debug mode
	 * ]
	 */
	private function manageError( $response, $err ) {
		$messageError = print_r( $err, true );
		$p = $this->getParser()->getOutput();
		if ( method_exists( $p, 'setPageProperty' ) ) {
			// MW 1.38
			$p->setPageProperty( LinkedWikiStatus::PAGEPROP_ERROR_MESSAGE, $messageError );
		} else {
			$p->setProperty( LinkedWikiStatus::PAGEPROP_ERROR_MESSAGE, $messageError );
		}
		$message = $this->getInstanceConfig()->isDebug() ?
			$messageError
			: wfMessage( "linkedwiki-lua-query-error-unknown" )->plain();
		return [ $response, $message ];
	}

	/**
	 * Attach a property with the wiki page that use this lua module
	 * in order to write in a RDF database.
	 * Save the job to refresh wiki pages with SPARQL queries when this module modifies
	 * a RDF database.
	 */
	private function doAfterWriting() {
		$p = $this->getParser()->getOutput();
		if ( method_exists( $p, 'setPageProperty' ) ) {
			// MW 1.38
			$p->setPageProperty( LinkedWikiStatus::PAGEPROP_WRITER_MODULE, true );
		} else {
			$p->setProperty( LinkedWikiStatus::PAGEPROP_WRITER_MODULE, true );
		}
		// push a job to refresh old queries (also in the modules) in the wiki if it is not a job
		if ( !RequestContext::getMain()->getRequest() instanceof FauxRequest ) {
			if ( method_exists( $p, 'setPageProperty' ) ) {
				// MW 1.38
				$p->setPageProperty( LinkedWikiStatus::PAGEPROP_DB_TOUCHED, time() );
			} else {
				$p->setProperty( LinkedWikiStatus::PAGEPROP_DB_TOUCHED, time() );
			}
			$job = new InvalidatePageWithQueryJob();
			if ( method_exists( MediaWikiServices::class, 'getJobQueueGroup' ) ) {
				// MW 1.37+
				MediaWikiServices::getInstance()->getJobQueueGroup()->lazyPush( $job );
			} else {
				JobQueueGroup::singleton()->lazyPush( $job );
			}
		}
	}

	/**
	 * Attach a property with the wiki page that use this lua module
	 * in order to read a RDF database.
	 */
	private function doAfterReading() {
		$p = $this->getParser()->getOutput();
		if ( method_exists( $p, 'setPageProperty' ) ) {
			// MW 1.38
			$p->setPageProperty( LinkedWikiStatus::PAGEPROP_READER_MODULE, true );
		} else {
			$p->setProperty( LinkedWikiStatus::PAGEPROP_READER_MODULE, true );
		}
	}
}
