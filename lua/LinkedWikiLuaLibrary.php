<?php
/**
 * @package Bourdercloud/linkedwiki
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

// Libraries
//require_once( dirname(__FILE__) ."/../LinkedWikiConfig.php");

class LinkedWikiLuaLibrary extends Scribunto_LuaLibraryBase {
	/**
	 * @var luaBindings|null
	 */
	private $objConfig = null;
	private $subject = null;
	private $lastQuery = null;

	////PUBLIC FUNCTION

	/**
	 * @return mixed
	 */
	public function register() {
		// These functions will be exposed to the Lua module.
		// They are member functions on a Lua table which is private to the module, thus
		// these can't be called from user code, unless explicitly exposed in Lua.
		$lib = [
			'setConfig' => [ $this, 'setConfig' ],
			'setEndpoint' => [ $this, 'setEndpoint' ],
			'setDebug' => [ $this, 'setDebug' ],
			'isDebug' => [ $this, 'isDebug' ],
			'setGraph' => [ $this, 'setGraph' ],
			'setSubject' => [ $this, 'setSubject' ],
			'setLang' => [ $this, 'setLang' ],
			'getLang' => [ $this, 'getLang' ],
			'getValue' => [ $this, 'getValue' ],
			'getString' => [ $this, 'getString' ],
			'getConfig' => [ $this, 'getConfig' ],

			'getDefaultConfig' => [ $this, 'getDefaultConfig' ],
			// 'getDefaultLang' => array($this, 'getDefaultLang'),

			'getLastQuery' => [ $this, 'getLastQuery' ],

			'addPropertyWithIri' => [ $this, 'addPropertyWithIri' ],
			'addPropertyWithLitteral' => [ $this, 'addPropertyWithLitteral' ],
			'removeSubject' => [ $this, 'removeSubject' ],
			'loadData' => [ $this, 'loadData' ],
		];
		return $this->getEngine()->registerInterface(
			__DIR__ . '/LinkedWiki.lua', $lib, []
		);
	}

	/**
	 * @return array
	 */
	public function getLastQuery() {
		return [ $this->lastQuery ];
	}

	/**
	 * @return LinkedWikiConfig|luaBindings|null
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
	 * @param null|string $urlConfig
	 * @return array
	 */
	public function setConfig( $urlConfig = null ) {
		try{
			if ( empty( $this->objConfig ) ) {
				$this->objConfig = new LinkedWikiConfig( $urlConfig );
			} elseif ( $this->objConfig->getConfigEndpoint() !== $urlConfig ) {
				$this->objConfig->setConfigEndpoint( $urlConfig );
			}
		} catch ( Exception $e ) {
			return [ false,"ERROR : " . $e->getMessage() ];
		}
		return [ true ];
	}

	/**
	 * @return array
	 */
	public function getConfig() {
		return [ $this->getInstanceConfig()->getConfigEndpoint() ];
	}

	/**
	 * @return array
	 */
	public function getDefaultConfig() {
		return [ $this->getInstanceConfig()->getDefaultConfigEndpoint() ];
	}

	/**
	 * @param string $debug
	 */
	public function setDebug( $debug ) {
		$this->checkType( 'setDebug', 1, $debug, 'boolean' );
		$this->getInstanceConfig()->setDebug( $debug );
	}

	/**
	 * @return array
	 */
	public function isDebug() {
		return [ $this->getInstanceConfig()->isDebug() ];
	}

	/**
	 * @return bool
	 */
	public function isPreviewOrHistory() {
		if ( array_key_exists( "wpPreview", $_REQUEST )
			|| ( array_key_exists( "oldid", $_REQUEST ) && $_REQUEST["oldid"] > 0 )
		) {
			return true;
		} else {
			return false;
		}
	}

	// public function setGraph($graphNamed)
	//    {
	//        $graph = trim($graphNamed);
	//        $this->checkType('setGraph', 1, $graph, 'string');
	//        $this->getInstanceConfig()->setGraph($graph);
	//    }

	/**
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
	 * @param string $iriSubject
	 */
	public function setSubject( $iriSubject ) {
		$this->checkType( 'setSubject', 1, trim( $iriSubject ), 'string' );
		$this->subject = trim( $iriSubject );
	}

	/**
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
	 * @return array
	 */
	public function getLang() {
		return [ $this->getInstanceConfig()->getLang() ];
	}

	// public function geDefaultLang()
	//    {
	//        return array($this->getInstanceConfig()->getLang());
	//    }

	/**
	 * Find the value for a property
	 *
	 * @param string $iriProperty : IRI of the property
	 * @param string|null $tagLang : by default uses the lang by default
	 * in the configuration. if null, it will search the value without tag lang.
	 * @param string|null $iriSubject : Optional, IRI of the subject
	 * @return array
	 */
	public function getString( $iriProperty, $tagLang = null, $iriSubject = null ) {
		if ( $iriSubject === null && $this->subject === null ) {
			return [ "ERROR : Subject unknown (Use the parameter iriSubject or the function setSubject." ];
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
			$message = $this->getInstanceConfig()->isDebug() ?
				print_r( $err, true )
				: "ERROR SPARQL (see details in mode debug)";
			return [ "ERROR : " . $message ];
		}

		$result = [];
		foreach ( $rows["result"]["rows"] as $row ) {

			$result[] = $row["value"];
		}

		return [ implode( ";", $result ) ];
	}

	/**
	 * @param string $iriProperty
	 * @param null $iriSubject
	 * @return array
	 */
	public function getValue( $iriProperty, $iriSubject = null ) {
		if ( $iriSubject === null && $this->subject === null ) {
			return [ "ERROR : Subject unknown (Use the parameter iriSubject or the function setSubject." ];
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
			$message = $this->getInstanceConfig()->isDebug() ?
				print_r( $err, true )
				: "ERROR SPARQL (see details in mode debug)";
			return [ "ERROR : " . $message ];
		}

		$result = [];
		foreach ( $rows["result"]["rows"] as $row ) {
			$result[] = $row["value"];
		}

		return [ implode( ";", $result ) ];
	}

	/**
	 * @param string $iriProperty
	 * @param string $iriValue
	 * @param null|string $iriSubject
	 * @return array
	 */
	public function addPropertyWithIri(
		$iriProperty, $iriValue, $iriSubject = null ) {
		if ( $iriSubject === null && $this->subject === null ) {
			return [ "ERROR : Subject unknown (Use the parameter iriSubject or the function setSubject." ];
		}

		if ( preg_match( "/(\"\"\"|''')/i", $iriValue ) ) {
			return [ "ERROR : Bad value" ];
		}
		if ( preg_match( "/(\"\"\"|'''| )/i", trim( $iriProperty ) ) ) {
			return [ "ERROR : Bad property" ];
		}
		if ( preg_match( "/(\"\"\"|'''| )/i", trim( $iriSubject ) ) ) {
			return [ "ERROR : Bad subject" ];
		}
		if ( $this->isPreviewOrHistory() ) {
			return [ "Mode PreviewOrHistory detected: data will not save" ];
		}

		$this->checkType( 'addPropertyWithIri', 1, $iriProperty, 'string' );
		$this->checkType( 'addPropertyWithIri', 2, $iriValue, 'string' );
		$this->checkTypeOptional( 'addPropertyWithIri', 3, $iriSubject, 'string', $this->subject );
		// $this->checkType( 'getValue', 2, $iriSubject, 'string', $this->subject  );

		$subject = ( $iriSubject === null ) ?
			"<" . $this->subject . ">"
			: "<" . trim( $iriSubject ) . ">";
		$property = "<" . trim( $iriProperty ) . ">";
		$value = "<" . trim( $iriValue ) . ">";

		$parameters = [ "?subject", "?property", "?value" ];
		$values = [ $subject, $property, $value ];
		$q = str_replace( $parameters,
			$values,
			$this->getInstanceConfig()->getQueryInsertValue() );

		// for debug
		$this->setLastQuery( $q );

		$endpoint = $this->getInstanceEndpoint();
		$response = $endpoint->query( $q, 'raw' );
		$err = $endpoint->getErrors();
		if ( $err ) {
			$message = $this->getInstanceConfig()->isDebug() ?
				$response . print_r( $err, true )
				: "ERROR SPARQL (see details in mode debug)";
			return [ "ERROR : " . $message ];
		}

		return [ $response ];
	}

	/**
	 * @param string $iriProperty
	 * @param number $value
	 * @param null $type
	 * @param null $tagLang
	 * @param null $iriSubject
	 * @return array
	 */
	public function addPropertyWithLitteral(
		$iriProperty, $value, $type = null, $tagLang = null, $iriSubject = null ) {
		if ( $iriSubject === null && $this->subject === null ) {
			return [ "ERROR : Subject unknown (Use the parameter iriSubject or the function setSubject." ];
		}
		if ( ( empty( $value ) && !is_numeric( $value ) ) || preg_match( "/(\"\"\"|''')/i", $value ) ) {
			return [ "ERROR : Bad value" ];
		}
		if ( empty( $iriProperty ) || preg_match( "/(\"\"\"|'''| )/i", trim( $iriProperty ) ) ) {
			return [ "ERROR : Bad property" ];
		}
		if ( preg_match( "/(\"\"\"|'''| )/i", trim( $iriSubject ) ) ) {
			return [ "ERROR : Bad subject" ];
		}
		if ( $this->isPreviewOrHistory() ) {
			return [ "Mode PreviewOrHistory detected: data will not save." ];
		}
		// $this->checkType( 'addPropertyWithLitteral', 1, $iriProperty, 'string' );
		// $this->checkType( 'addPropertyWithLitteral', 2, $value, 'number or string' );
		// $this->checkType( 'addPropertyWithLitteral', 3, $type, 'string',null );
		// $this->checkTypeOptional( 'addPropertyWithLitteral', 4, $tagLang, 'string' ,
		// $this->getInstanceConfig()->getLang() );
		// $this->checkTypeOptional( 'addPropertyWithLitteral', 5, $iriSubject,
		// 'string',$this->subject  );
		// $this->checkType( 'getValue', 2, $iriSubject, 'string', $this->subject  );

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
			if ( $tagLang === null ) {
				$strValue .= "@" . $this->getInstanceConfig()->getLang();
			} elseif ( $tagLang === "" ) {
				// do nothing;
			} else {
				$strValue .= "@" . trim( $tagLang );
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
			$message = $this->getInstanceConfig()->isDebug() ?
				$response . print_r( $err, true )
				: "ERROR SPARQL (see details in mode debug)";
			return [ "ERROR : " . $message ];
		}

		return [ $response ];
	}

	/**
	 * @param null $iriSubject
	 * @return array
	 */
	public function removeSubject( $iriSubject = null ) {
		if ( $iriSubject === null && $this->subject === null ) {
			return [ "ERROR : Subject unknown (Use the parameter iriSubject or the function setSubject." ];
		}

		if ( preg_match( "/(\"\"\"|'''| )/i", trim( $iriSubject ) ) ) {
			return [ "ERROR : Bad subject" ];
		}
		if ( $this->isPreviewOrHistory() ) {
			return [ "Mode PreviewOrHistory detected: data will not save." ];
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
			$message = $this->getInstanceConfig()->isDebug() ?
				$response . print_r( $err, true )
				: "ERROR SPARQL (see details in mode debug)";
			return [ "ERROR : " . $message ];
		}

		return [ $response ];
	}

	/**
	 * @param array $titles
	 * @return array
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
			$message = $this->getInstanceConfig()->isDebug() ?
				$response . print_r( $err, true )
				: "ERROR SPARQL (see details in mode debug)";
			return [ "ERROR : " . $message ];
		}
		return [ $response ];
	}

	////PRIVATE FUNCTION

	/**
	 * @param string $query
	 */
	private function setLastQuery( $query ) {
		$this->lastQuery = $query;
	}
}
