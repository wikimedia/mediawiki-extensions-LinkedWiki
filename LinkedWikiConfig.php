<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-BY-SA-4.0
 */

use BorderCloud\SPARQL\SparqlClient;

class LinkedWikiConfig {
	// region private variables

	/**
	 * Object with all the configurations of the extension
	 */
	private $config = null;

	/**
	 * Object with the endpoint's configurations
	 */
	private $configEndpoints = null;

	/**
	 * Config of the current endpoint
	 */
	private $configEndpoint = null;

	/**
	 * Id of Endpoint by default
	 */
	private $idEndpointByDefault = "";

	/**
	 * Id of current Endpoint (iri)
	 */
	private $idEndpoint = "";

	/**
	 * Instance of Endpoint with this current config
	 */
	private $objEndpoint = null;

	private $lang = "en";

	private $debug = false;
	private $isReadOnly = true;

	// private $graphNamed = "";
	// private $endpoint = "";

	/**
	 * @var string
	 */
	private $endpointRead = "";
	private $endpointUpdate = "";
	private $login = "";
	private $password = "";
	private $typeRDFDatabase = "virtuoso";
	private $HTTPMethodForRead = "POST";
	private $HTTPMethodForWrite = "POST";
	private $nameParameterRead = "query";
	private $nameParameterWrite = "update";

	private $storageMethod = null;

	private $proxyHost = null;
	private $proxyPort = null;
	// endregion

	// region private functions

	/**
	 * @param string $endpointWrite
	 */
	private function setEndpointWrite( $endpointWrite ) {
		$this->endpointWrite = $endpointWrite;
		$this->resetInstanceEndpoint();
	}

	private function getEndpointWrite() {
		return $this->endpointWrite;
	}

	private function setLogin( $login ) {
		$this->login = $login;
		$this->resetInstanceEndpoint();
	}

	private function getLogin() {
		return $this->login;
	}

	private function setPassword( $password ) {
		$this->password = $password;
		$this->resetInstanceEndpoint();
	}

	private function getPassword() {
		if ( !empty( $this->password ) ) {
			return "*****";
		} else {
			return "";
		}
	}

	private function setTypeRDFDatabase( $typeRDFDatabase ) {
		$this->typeRDFDatabase = $typeRDFDatabase;
		$this->resetInstanceEndpoint();
	}

	private function getTypeRDFDatabase() {
		return $this->typeRDFDatabase;
	}

	private function setProxyHost( $proxyHost ) {
		$this->proxyHost = $proxyHost;
		$this->resetInstanceEndpoint();
	}

	private function getProxyHost() {
		return $this->proxyHost;
	}

	private function setProxyPort( $proxyPort ) {
		$this->proxyPort = $proxyPort;
		$this->resetInstanceEndpoint();
	}

	private function getProxyPort() {
		return $this->proxyPort;
	}

	private function setStorageMethod( $storageMethod ) {
		$this->storageMethod = $storageMethod;
	}

	private function getStorageMethod() {
		return $this->storageMethod;
	}

	private function resetInstanceEndpoint() {
		$this->objEndpoint = null;
	}

	private function newInstanceEndpoint() {
		$objEndpoint = null;
		$objEndpoint = new SparqlClient( $this->debug );
		$objEndpoint->setEndpointRead( $this->endpointRead );

// $objEndpoint->setNameParameterQueryRead($nameParameterQuery);
//        $objEndpoint->setNameParameterQueryWrite($nameParameterWrite);

		if ( !empty( $this->proxyHost ) ) {
			$objEndpoint->setproxyHost( $this->proxyHost );
		}
		if ( !empty( $this->proxyPort ) ) {
			$objEndpoint->setproxyPort( $this->proxyPort );
		}
		if ( !empty( $this->endpointRead ) ) {
			$objEndpoint->setEndpointRead( $this->endpointRead );
		}
		if ( !empty( $this->HTTPMethodForRead ) ) {
			$objEndpoint->setMethodHTTPRead( $this->HTTPMethodForRead );
		}
		if ( !empty( $this->nameParameterRead ) ) {
			$objEndpoint->setNameParameterQueryRead( $this->nameParameterRead );
		}
		if ( !$this->isReadOnly ) {
			if ( !empty( $this->endpointWrite ) ) {
				$objEndpoint->setEndpointWrite( $this->endpointWrite );
			} else {
				$objEndpoint->setEndpointWrite( $this->endpoint );
			}
			if ( !empty( $this->HTTPMethodForRead ) ) {
				$objEndpoint->setMethodHTTPWrite( $this->HTTPMethodForRead );
			}
			if ( !empty( $this->nameParameterWrite ) ) {
				$objEndpoint->setNameParameterQueryWrite( $this->nameParameterWrite );
			}
		}
		if ( !empty( $this->login ) ) {
			$objEndpoint->setLogin( $this->login );
		}
		if ( !empty( $this->password ) ) {
			$objEndpoint->setPassword( $this->password );
		}
		return $objEndpoint;
	}

	private function setNameParameterWrite( $nameParameterWrite ) {
		$this->nameParameterWrite = $nameParameterWrite;
		$this->resetInstanceEndpoint();
	}

	private function getNameParameterWrite() {
		return $this->nameParameterWrite;
	}

	private function setMethodForWrite( $HTTPMethodForWrite ) {
		$this->HTTPMethodForWrite = $HTTPMethodForWrite;
		$this->resetInstanceEndpoint();
	}

	private function getMethodForWrite() {
		return $this->HTTPMethodForWrite;
	}

	// endregion

	//region public functions

//    public function setEndpoint($urlEndpoint)
//    {
//        $this->endpoint = $urlEndpoint;
//        $this->resetInstanceEndpoint();
//    }
//
//    public function getEndpoint()
//    {
//        return $this->endpoint;
//    }

	/**
	 * @param string $endpointRead
	 */
	public function setEndpointRead( $endpointRead ) {
		$this->endpointRead = $endpointRead;
		$this->resetInstanceEndpoint();
	}

	/**
	 * @return string
	 */
	public function getEndpointRead() {
		return $this->endpointRead;
	}

	/**
	 * @param bool $debug
	 */
	public function setDebug( $debug ) {
		$this->debug = $debug;
		$this->resetInstanceEndpoint();
	}

	/**
	 * @return bool
	 */
	public function isDebug() {
		return $this->debug;
	}

	/**
	 * @param bool $isReadOnly
	 */
	public function setReadOnly( $isReadOnly ) {
		$this->isReadOnly = $isReadOnly;
		$this->resetInstanceEndpoint();
	}

	/**
	 * @return bool
	 */
	public function isReadOnly() {
		return $this->isReadOnly;
	}

	/**
	 * @return |null
	 */
	public function getConfig() {
		return $this->config;
	}

	/**
	 * @return string
	 */
	public function getDefaultConfigEndpoint() {
		return $this->idEndpointByDefault;
	}

	/**
	 * @return string
	 */
	public function getConfigEndpoint() {
		return $this->idEndpoint;
	}

	/**
	 * @param null $urlEndpointConfig
	 * @throws Exception
	 */
	public function setConfigEndpoint( $urlEndpointConfig = null ) {
		if ( empty( $urlEndpointConfig ) ) {
			$urlEndpointConfig = $this->idEndpointByDefault;
		}

		// reset
		if ( $urlEndpointConfig != 'http://www.example.org' ) {
			$this->setConfigEndpoint( 'http://www.example.org' );
		}

		if ( !array_key_exists( $urlEndpointConfig, $this->configEndpoints ) ) {
			throw new Exception(
				"The configuration of " . $urlEndpointConfig
				. " is not found in the file LinkedWiki/extension.json or in the LocalSettings.php."
			);
		}

		$this->idEndpoint = $urlEndpointConfig;
		$this->configEndpoint = $this->configEndpoints[$this->idEndpoint];

		if ( isset( $this->configEndpoint["storageMethodClass"] ) ) {
			$storageMethodClass = $this->configEndpoint["storageMethodClass"];
			$this->storageMethod = new $storageMethodClass;
		}

		if ( isset( $this->configEndpoint["debug"] ) ) {
			$this->setDebug( $this->configEndpoint["debug"] );
		}

		if ( isset( $this->configEndpoint["isReadOnly"] ) ) {
			$this->setReadOnly( $this->configEndpoint["isReadOnly"] );
		}

		if ( isset( $this->configEndpoint["proxyHost"] ) ) {
			$this->setProxyHost( $this->configEndpoint["proxyHost"] );
		}

		if ( isset( $this->configEndpoint["proxyPort"] ) ) {
			$this->setProxyPort( $this->configEndpoint["proxyPort"] );
		}

		if ( isset( $this->configEndpoint["lang"] ) ) {
			$this->setLang( $this->configEndpoint["lang"] );
		}

// if (isset($this->configEndpoint["endpoint"]))
//            $this->setEndpoint($this->configEndpoint["endpoint"]);

		if ( isset( $this->configEndpoint["endpointRead"] ) ) {
			$this->setEndpointRead( $this->configEndpoint["endpointRead"] );
		}

		if ( isset( $this->configEndpoint["endpointWrite"] ) ) {
			$this->setEndpointWrite( $this->configEndpoint["endpointWrite"] );
		}

		if ( isset( $this->configEndpoint["login"] ) ) {
			$this->setLogin( $this->configEndpoint["login"] );
		}

		if ( isset( $this->configEndpoint["password"] ) ) {
			$this->setPassword( $this->configEndpoint["password"] );
		}

		if ( isset( $this->configEndpoint["typeRDFDatabase"] ) ) {
			$this->setTypeRDFDatabase( $this->configEndpoint["typeRDFDatabase"] );
		}

		if ( isset( $this->configEndpoint["HTTPMethodForRead"] ) ) {
			$this->setMethodForRead( $this->configEndpoint["HTTPMethodForRead"] );
		}

		if ( isset( $this->configEndpoint["HTTPMethodForWrite"] ) ) {
			$this->setMethodForWrite( $this->configEndpoint["HTTPMethodForWrite"] );
		}

		if ( isset( $this->configEndpoint["nameParameterRead"] ) ) {
			$this->setNameParameterRead( $this->configEndpoint["nameParameterRead"] );
		}

		if ( isset( $this->configEndpoint["nameParameterWrite"] ) ) {
			$this->setNameParameterWrite( $this->configEndpoint["nameParameterWrite"] );
		}

		$this->resetInstanceEndpoint();
	}

	/**
	 * LinkedWikiConfig constructor.
	 *
	 * @param null|string $urlEndpointConfig
	 * @throws Exception
	 */
	public function __construct( $urlEndpointConfig = null ) {
		$this->config = ConfigFactory::getDefaultInstance()->makeConfig( 'wgLinkedWiki' );
		$this->configEndpoints = $this->config->get( "ConfigSPARQLServices" );
		$this->idEndpointByDefault = $this->config->get( "SPARQLServiceByDefault" );

		$this->setConfigEndpoint( $urlEndpointConfig );
	}

	/**
	 * @return SparqlClient|null
	 */
	public function getInstanceEndpoint() {
		if ( $this->objEndpoint === null ) {
			$this->objEndpoint = $this->newInstanceEndpoint();
		}
		return $this->objEndpoint;
	}

	/**
	 * @return mixed
	 */
	public function getQueryReadValue() {
		return $this->storageMethod->getQueryReadValue();
	}

	/**
	 * @return mixed
	 */
	public function getQueryReadStringWithTagLang() {
		return $this->storageMethod->getQueryReadStringWithTagLang();
	}

	/**
	 * @return mixed
	 */
	public function getQueryReadStringWithoutTagLang() {
		return $this->storageMethod->getQueryReadStringWithoutTagLang();
	}

	/**
	 * @return mixed
	 */
	public function getQueryInsertValue() {
		return $this->storageMethod->getQueryInsertValue();
	}

	/**
	 * @return mixed
	 */
	public function getQueryDeleteSubject() {
		return $this->storageMethod->getQueryDeleteSubject();
	}

	/**
	 * @param string $url
	 * @return mixed
	 */
	public function getQueryLoadData( $url ) {
		return $this->storageMethod->getQueryLoadData( $url );
	}

	/**
	 * @param string $iriSubject
	 */
	public function setSubject( $iriSubject ) {
		$this->subject = trim( $iriSubject );
	}

	/**
	 * @param string $tagLang
	 */
	public function setLang( $tagLang ) {
		$this->lang = trim( $tagLang );
	}

	/**
	 * @return string
	 */
	public function getLang() {
		return $this->lang;
	}

	/**
	 * @param string $nameParameterRead
	 */
	public function setNameParameterRead( $nameParameterRead ) {
		$this->nameParameterRead = $nameParameterRead;
		$this->resetInstanceEndpoint();
	}

	/**
	 * @return string
	 */
	public function getNameParameterRead() {
		return $this->nameParameterRead;
	}

	/**
	 * @param string $HTTPMethodForRead
	 */
	public function setMethodForRead( $HTTPMethodForRead ) {
		$this->HTTPMethodForRead = $HTTPMethodForRead;
		$this->resetInstanceEndpoint();
	}

	/**
	 * @return string
	 */
	public function getMethodForRead() {
		return $this->HTTPMethodForRead;
	}

	// endregion

	//region static public functions

	/**
	 * @return string
	 */
	public static function info() {
		// global $wgLinkedWikiConfigDefault, $wgLinkedWikiAccessEndpoint;
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'wgLinkedWiki' );
		$configDefault = $config->get( "SPARQLServiceByDefault" );
		$configs = $config->get( "ConfigSPARQLServices" );

		$html = "";

		foreach ( $configs as $key => $value ) {
			$objConfig = null;

			$title = "";
			if ( $key === "http://www.example.org" ) {
				$title = "Configuration by default with the parameter \"ConfigSPARQLServices\"";
			} elseif ( $key === $configDefault ) {
				$title = "Configuration by default: " . $configDefault;
			} else {
				$title = "Configuration: " . $key;
			}

			if ( $key === $configDefault ) {
				$objConfig = new LinkedWikiConfig();
			} else {
				$objConfig = new LinkedWikiConfig( $key );
			}
			$html .= "<h3>$title</h3>";

			$html .= "<table class='wikitable'>";

			$html .= "<tr>";
			$html .= "<th>";
			$html .= "Key";
			$html .= "</th>";
			$html .= "<th>";
			$html .= "Value";
			$html .= "</th>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "debug";
			$html .= "</td>";
			$html .= "<td>";
			$html .= $objConfig->isDebug() ? "Enable" : "Disable";
			$html .= "</td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "isReadOnly";
			$html .= "</td>";
			$html .= "<td>";
			$html .= $objConfig->isReadOnly() ? "Enable" : "Disable";
			$html .= "</td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "proxyHost";
			$html .= "</td>";
			$html .= "<td>";
			$html .= $objConfig->getProxyHost();
			$html .= "</td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "proxyPort";
			$html .= "</td>";
			$html .= "<td>";
			$html .= $objConfig->getProxyPort();
			$html .= "</td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "endpointRead";
			$html .= "</td>";
			$html .= "<td>";
			$html .= $objConfig->getEndpointRead();
			$html .= "</td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "endpointWrite";
			$html .= "</td>";
			$html .= "<td>";
			$html .= $objConfig->getEndpointWrite();
			$html .= "</td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "login";
			$html .= "</td>";
			$html .= "<td>";
			$html .= "see file: LocalSettings.php";
			$html .= "</td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "password";
			$html .= "</td>";
			$html .= "<td>";
			$html .= "see file: LocalSettings.php";
			$html .= "</td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "getTypeRDFDatabase";
			$html .= "</td>";
			$html .= "<td>";
			$html .= $objConfig->getTypeRDFDatabase();
			$html .= "</td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "HTTPMethodForRead";
			$html .= "</td>";
			$html .= "<td>";
			$html .= $objConfig->getMethodForRead();
			$html .= "</td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "HTTPMethodForWrite";
			$html .= "</td>";
			$html .= "<td>";
			$html .= $objConfig->getMethodForWrite();
			$html .= "</td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "nameParameterRead";
			$html .= "</td>";
			$html .= "<td>";
			$html .= $objConfig->getNameParameterRead();
			$html .= "</td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "nameParameterWrite";
			$html .= "</td>";
			$html .= "<td>";
			$html .= $objConfig->getNameParameterWrite();
			$html .= "</td>";
			$html .= "</tr>";

			$method = $objConfig->getStorageMethod();
			$html .= "<tr>";
			$html .= "<td>";
			$html .= "storageMethodClass";
			$html .= "</td>";
			$html .= "<td>";
			$html .= get_class( $method );
			$html .= "</td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "getQueryDeleteSubject";
			$html .= "</td>";
			$html .= "<td><pre>";
			$html .= htmlentities( $method->getQueryDeleteSubject() );
			$html .= "</pre></td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "getQueryInsertValue";
			$html .= "</td>";
			$html .= "<td><pre>";
			$html .= htmlentities( $method->getQueryInsertValue() );
			$html .= "</pre></td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "getQueryReadValue";
			$html .= "</td>";
			$html .= "<td><pre>";
			$html .= htmlentities( $method->getQueryReadValue() );
			$html .= "</pre></td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "getQueryReadStringWithTagLang";
			$html .= "</td>";
			$html .= "<td><pre>";
			$html .= htmlentities( $method->getQueryReadStringWithTagLang() );
			$html .= "</pre></td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "getQueryReadStringWithoutTagLang";
			$html .= "</td>";
			$html .= "<td><pre>";
			$html .= htmlentities( $method->getQueryReadStringWithoutTagLang() );
			$html .= "</pre></td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "getQueryLoadData";
			$html .= "</td>";
			$html .= "<td><pre>";
			$html .= htmlentities( $method->getQueryLoadData( 'http://example.org/file.ttl' ) );
			$html .= "</pre></td>";
			$html .= "</tr>";

			$html .= "<tr>";
			$html .= "<td>";
			$html .= "lang";
			$html .= "</td>";
			$html .= "<td>";
			$html .= $objConfig->getLang();
			$html .= "</td>";
			$html .= "</tr>";

			$html .= "</table>";
		}
		return $html;
	}

	public static function infoOtherOptions() {
		$config = ConfigFactory::getDefaultInstance()->makeConfig( 'wgLinkedWiki' );
		$varSPARQLServiceByDefault = $config->get( "SPARQLServiceByDefault" );
		$varSPARQLServiceSaveDataOfWiki = $config->get( "SPARQLServiceSaveDataOfWiki" );
		$varCheckRDFPage = $config->get( "CheckRDFPage" );
		$varQuerySparqlInSpecialPage = $config->get( "QuerySparqlInSpecialPage" );

		$html = "";

		$html .= "<table class='wikitable'>";

		$html .= "<tr>";
		$html .= "<th>";
		$html .= "Key";
		$html .= "</th>";
		$html .= "<th>";
		$html .= "Value";
		$html .= "</th>";
		$html .= "</tr>";

		$html .= "<tr>";
		$html .= "<th>";
		$html .= 'SPARQLServiceByDefault <br/>($wgLinkedWikiSPARQLServiceByDefault)';
		$html .= "</th>";
		$html .= "<td>";
		$html .= $varSPARQLServiceByDefault;
		$html .= "</td>";
		$html .= "</tr>";

		$html .= "<tr>";
		$html .= "<th>";
		$html .= 'SPARQLServiceSaveDataOfWiki <br/>($wgLinkedWikiSPARQLServiceSaveDataOfWiki)';
		$html .= "</th>";
		$html .= "<td>";
		$html .= $varSPARQLServiceSaveDataOfWiki;
		$html .= "</td>";
		$html .= "</tr>";

		$html .= "<tr>";
		$html .= "<th>";
		$html .= 'CheckRDFPage <br/>($wgLinkedWikiCheckRDFPage)';
		$html .= "</th>";
		$html .= "<td>";
		$html .= $varCheckRDFPage ? "Enable" : "Disable";
		$html .= "</td>";
		$html .= "</tr>";

		$html .= "<tr>";
		$html .= "<th>";
		$html .= 'QuerySparqlInSpecialPage <br/>($wgLinkedWikiQuerySparqlInSpecialPage)';
		$html .= "</th>";
		$html .= "<td><pre>";
		$html .= htmlentities( $varQuerySparqlInSpecialPage );
		$html .= "</pre></td>";
		$html .= "</tr>";

		$html .= "</table>";
		return $html;
	}

	/*
	private function printTable($rows){
		$html = "\n<pre>";
		 foreach($rows["result"]["variables"] as $variable){
			$html .= sprintf("%-60.60s",$variable);
			$html .= ' | ';
		 }
		 $html .= "\n";

		 foreach ($rows["result"]["rows"] as $row){
			foreach($rows["result"]["variables"] as $variable){
				$html .= sprintf("%-60.60s",$row[$variable]);
				$html .= ' | ';
			}
		 $html .="\n";
		}

		$html .= "</pre>";
		return $html;
	}*/
	//endregion
}
