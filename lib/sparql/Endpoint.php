<?php

require_once("Curl.php");
require_once("Net.php");
require_once("Base.php");
require_once("ParserSparqlResult.php");

class Endpoint extends Base {
	/**
	 * Root of the URL Endpoint
	 * @access private
	 * @var string
	 */	 
	private $_endpoint_root;
	
	/**
	 * URL of Endpoint to read
	 * @access private
	 * @var string
	 */
	private $_endpoint;
		
	/**
	 * URL  sparql to write
	 * @access private
	 * @var string
	 */
	private $_endpoint_write;
	
	/**
	 * in the constructor set debug to true in order to get usefull output
	 * @access private
	 * @var string
	 */
	private $_debug;
	
	/**
	 * in the constructor set the right to write or not in the store
	 * @access private
	 * @var string
	 */
	private $_readOnly;
	
	/**
	 * in the constructor set the proxy_host if necessary
	 * @access private
	 * @var string
	 */
	private $_proxy_host;
	
	/**
	 * in the constructor set the proxy_port if necessary
	 * @access private
	 * @var int
	 */
	private $_proxy_port;
	
	
	private $_parserSparqlResult;
	
	/** For Arc2 **/
// 	private $_arc2_RemoteStore;
// 	private $_arc2_Reader;
// 	private $_config;

	/**
	 * Constructor of Graph
	 * @param string $endpoint : url of endpoint, example : http://lod.bordercloud.com/sparql
	 * @param boolean $readOnly : true by default, if you allow the function query to write in the database
	 * @param boolean $debug : false by default, set debug to true in order to get usefull output
	 * @param string $proxy_host : null by default, IP of your proxy
	 * @param string $proxy_port : null by default, port of your proxy
	 * @access public
	 */
	public function __construct($endpoint,
								$readOnly = true,
								$debug = false,
								$proxy_host = null,
								$proxy_port = null)
	{				
		parent::__construct();
		
		if($readOnly){
			$this->_endpoint = $endpoint;
		}else{
			if (preg_match("|/sparql/?$|i", $endpoint)) {
				$this->_endpoint = $endpoint;
				$this->_endpoint_root = preg_replace("|^(.*/)sparql/?$|i", "$1", $endpoint);
			} else {
				$this->_endpoint_root = $endpoint;
				$this->_endpoint = 	$this->_endpoint_root."sparql/";
			}
		}
	
		$this->_debug = $debug;
		$this->_endpoint_write = $this->_endpoint_root."update/"; 
		$this->_readOnly = $readOnly;
		
		$this->_proxy_host = $proxy_host;
		$this->_proxy_port = $proxy_port;		
		
		if($this->_proxy_host != null && $this->_proxy_port != null){
			$this->_config = array(
				/* remote endpoint */
			  'remote_store_endpoint' => $this->_endpoint,
				  /* network */
			  'proxy_host' => $this->_proxy_host,
			  'proxy_port' => $this->_proxy_port,			
			);
		}else{
			$this->_config = array(
			/* remote endpoint */
			  'remote_store_endpoint' => $this->_endpoint,
			);			
		}

		//init parser
 		$this->_parserSparqlResult = new ParserSparqlResult(); 		
 		
	}
	
	/**
	 * Check if the server is up.
	 * @return boolean true if the triplestore is up.
	 * @access public
	 */
	public function check() {
		return Net::ping($this->_endpoint) != -1;
	}
	
	/**
	 * This function parse a SPARQL query, send the query and parse the SPARQL result in a array. 
	 * You can custom the result with the parameter $result_format : 
	 * <ul>
	 * <li>rows to return array of results
	 * <li>row to return array of first result
	 * <li>raw to return boolean for request ask, insert and delete
	 * </ul>
	 * @param string $q : Query SPARQL 
	 * @param string $result_format : Optional,  rows, row or raw
	 * @return array|boolean in function of parameter $result_format
	 * @access public
	 */
	public function query($q, $result_format = '') {	
		$t1 = Endpoint::mtime();
		$response = $this->queryRead($q);
		xml_parse($this->_parserSparqlResult->getParser(),$response, true);		
		$result = $this->_parserSparqlResult->getResult();
		$result['query_time'] =   Endpoint::mtime() - $t1 ;
		return $result;
	}
		

	
	/************************************************************************/
	//PRIVATE Function
	
	static function mtime(){
		list($msec, $sec) = explode(" ", microtime());
		return ((float)$msec + (float)$sec);
	}
	
	/**
	* Send a request SPARQL of type select or ask to endpoint directly and output the response
	* of server. If you want parse the result of this function, it's better and simpler
	* to use the function query().
	*
	* if you want use another format, you can use directly the function queryReadJSON and queryReadTabSeparated
	* @param string $query : Query Sparql
	* @param string $typeOutput by default "application/sparql-results+xml",
	* @return string response of server or false if error (to do getErrors())
	* @access public
	*/
	private function queryRead($query,$typeOutput=null ) {
		$client = $this->initCurl();
		$sUri    = $this->_endpoint;
	
		$data = array("query" =>   $query);
		if($typeOutput == null){
			$response = $client->send_post_data($sUri,$data);
		}else{
			$response = $client->fetch_url($sUri."?query=".$query."&output=".$typeOutput);
		}
	
		$code = $client->get_http_response_code();
			
		$this->debugLog($query,$sUri,$code,$response);
	
		if($code != 200)
		{
			$error = $this->errorLog($query,$data,$sUri,$code,$response);
			$this->addError($error);
			return false;
		}
		return $response;
	}

	/**
	 * write error for human
	 * @param string $query
	 * @param string $endPoint
	 * @param number $httpcode
	 * @param string $response
	 * @access private
	 */
	private function errorLog($query,$data,$endPoint,$httpcode=0,$response=''){
		$error = 	"Error query  : " .$query."\n" .
					"Error endpoint: " .$endPoint."\n" .
					"Error http_response_code: " .$httpcode."\n" .
					"Error message: " .$response."\n";			
		if($this->_debug)
		{
			echo '=========================>>>>>>'.$error ;
		}else{
			error_log($error);
		}
		return $error;
	}

	/**
	 * Print infos
	 * @param unknown_type $query
	 * @param unknown_type $endPoint
	 * @param unknown_type $httpcode
	 * @param unknown_type $response
	 * @access private
	 */
	private function debugLog($query,$endPoint,$httpcode='',$response=''){
		if($this->_debug)
		{
			$error = 	"\n#######################\n".
						"query				: " .$query."\n" .
                        "endpoint			: " .$endPoint."\n" .
                        "http_response_code	: " .$httpcode."\n" .
                        "message			: " .$response.
                        "\n#######################\n";

			echo $error ;
		}
	}
	
	/**
	 * Init an object Curl in function of proxy.
	 * @return an object of type Curl
	 * @access private
	 */
	private function initCurl(){
		$objCurl = new Curl();
		if($this->_proxy_host != null && $this->_proxy_port != null){
			$objCurl->set_proxy($this->_proxy_host.":".$this->_proxy_port);	
		}
		return $objCurl;
	}
}
