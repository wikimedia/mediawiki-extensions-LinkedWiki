<?php
/**
 * @version 1.2.0.0
 * @package bordercloud/client
 * @copyright (c) 2011 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 *
 All rights reserved Copyright (c) 2011 Bourdercloud.com

 */

require_once(dirname(__FILE__) . '/../arc2/ARC2.php');
require_once("Curl.php");
require_once("Net.php");

/**
 * Sparql HTTP Client for BorderCloud's Endpoint around basic php function.
 */
class Endpoint {
	/**
	 * URL  sparql to read
	 * @access private
	 * @var string
	 */
	private $_endpoint;
	
	/**
	 * Name of the graph in the endpoint
	 * @access private
	 * @var string
	 */
	private $_graph;
	
	/**
	 * URL  sparql to write
	 * @access private
	 * @var string
	 */
	private $_endpoint_write;
	
	/**
	 * URL refresh graph's info in the dataset
	 * @access private
	 * @var string
	 */
	private $_endpoint_refresh;
	
	/**
	 * URL to upload file in the dataset
	 * @access private
	 * @var string
	 */
	private $_endpoint_upload;
	
	/**
	 * URL to save the graph in a file
	 * @access private
	 * @var string
	 */
	private $_endpoint_export;
	
	/**
	 * URL to reset the graph (delete all the triples in this graph)
	 * @access private
	 * @var string
	 */
	private $_endpoint_reset;
		
	/**
	 * Code BorderCloud
	 * @access private
	 * @var string
	 */
	private $_jeton;
	
	/**
	 * in the constructor set debug to true in order to get usefull output
	 * @access private
	 * @var string
	 */
	private $_debug;
	
	/** For Arc2 **/
	private $_arc2_RemoteStore;
	private $_arc2_Reader;
	private $_config;

	/**
	 * Constructor of Graph
	 * @param string $endpoint : url of endpoint, example : http://lod.bordercloud.com/sparql
	 * @param string $jeton : number give by Bordercloud if you are hosting your graph in the cloud
	 * @param string $graph : name of the graph in the cloud by default uses in the class
	 * @param boolean $debug : false by default, set debug to true in order to get usefull output
	 * @access public
	 */
	public function __construct($endpoint,$jeton = null,$graph = null,$debug = false)
	{
		$this->_debug = $debug;
		$this->_endpoint = $endpoint."sparql/";
		$this->_jeton = $jeton;
		$this->_graph = $graph;
		$this->_endpoint_write = $endpoint."update/";
		$this->_endpoint_refresh = $endpoint."refresh/";
		$this->_endpoint_reset = $endpoint."reset/";
		$this->_endpoint_upload = $endpoint."upload/";
		$this->_endpoint_export = $endpoint."export/";	
		
		$this->_config = array(
		/* remote endpoint */
		  'remote_store_endpoint' => $this->_endpoint."/sparql",
		);

		$this->_arc2_RemoteStore = @ARC2::getRemoteStore($this->_config);
	}
	
	/**
	 * Check if the server is up.
	 * @return boolean true if the triplestore is up.
	 * @access public
	 */
	public function check() {
		return Net::ping($this->_endpoint) != -1;
	}
	
	
	/* @param string $query : Query Sparql
	 * @param $q Query SPARQL 
	 * @param  $result_format Optional, 
	 * rows to return array of results or 
	 * row to return array of first result or 
	 * raw to return boolean for request ask, insert and delete
	 * @return array|boolean in function of parameter $result_format
	 * @access public
	 */
	public function query($q, $result_format = '') {
		if($this->_debug){
			print date('Y-m-d\TH:i:s\Z', time()) . ' : ' . $q . '' . "\n\n";
		}

		$p =  ARC2::getSPARQLPlusParser();		
		$p->parse($q);
		$infos = $p->getQueryInfos();
		$t1 = ARC2::mtime();		
		if (!$errs = $p->getErrors()) {
			$qt = $infos['query']['type'];
			$r = array('query_type' => $qt, 'result' => $this->runQuery($q, $qt, $infos));
		}
		else {
			$r = array('result' => '');		
			if($this->_debug){
				print date('Y-m-d\TH:i:s\Z', time()) . ' : ERROR ' . $q . '' . "\n\n";
				print_r($errs);
			}
			return $this->_arc2_RemoteStore->addError($p->getErrors() );
		}
		$t2 = ARC2::mtime();
		$r['query_time'] = $t2 - $t1;
	  
		/* query result */
		if ($result_format == 'raw') {
			return $r['result'];
		}
		if ($result_format == 'rows') {
			return $this->_arc2_RemoteStore->v('rows', array(), $r['result']);
		}
		if ($result_format == 'row') {
			if (!isset($r['result']['rows'])) return array();
			return $r['result']['rows'] ? $r['result']['rows'][0] : array();
		}
		return $r;
	}
		
	/*
	 * Give the errors 
	 * @return array
	 * @access public
	 */
	public function getErrors() {
		return $this->_arc2_RemoteStore->getErrors();
	}

	/*
	 * Refresh  the information of this graph in the graph http://www.bordercloud.com/dataset
	 * @return array
	 * @access public
	 */
	public function refreshInfo($graph = null ) {
		if($graph == null)
			$graph = $this->_graph;
		$client = new Curl();
		$sUri    = $this->_endpoint_refresh;
		$data = array(	
			"bcjeton" => $this->_jeton,	
			"graph" => $graph);

		$response = $client->send_post_data($sUri,$data);
		$code = $client->get_http_response_code();
		if($code != 200 || $response != "REFRESHED"){
			$this->_arc2_RemoteStore->addError("Cannot refreshInfo : ".$response );
		}
	}	
	
	/*
	 * Reset the graph, delete all the triples in the graph
	 * @return array
	 * @access public
	 */
	public function resetGraph($graph = null ) {
		if($graph == null)
			$graph = $this->_graph;
		$client = new Curl();
		$sUri    = $this->_endpoint_reset;
		$data = array(	
			"bcjeton" => $this->_jeton,	
			"graph" => $graph);

		$response = $client->send_post_data($sUri,$data);
		$code = $client->get_http_response_code();
		if($code != 200){
			$this->_arc2_RemoteStore->addError("Cannot reset the graph : ".$response );
		}
	}	
	
	/**
	 * Give the number of triples in a graph
	 * @param string $graph : put name of the graph or nothing for the default graph
	 * @return number
	 * @access public
	 */
	public function count($graph = null ) {
		if($graph == null)
			$graph = $this->_graph;
		$r="";
		$count = 0;

		$prefix = "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> ";
		$prefix .= "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> ";
		$prefix .= "PREFIX xsd: <http://www.w3.org/2001/XMLSchema#> ";		
		$prefix .= "PREFIX scovo: <http://purl.org/NET/scovo#> ";
		$prefix .= "PREFIX void: <http://rdfs.org/ns/void#> ";
	
		$query = "<".$graph."> void:sparqlEndpoint <".substr($this->_endpoint,0,-1)."> ;";
		$query .= " void:statItem ?nodeNBTriple.";
        $query .= " ?nodeNBTriple scovo:dimension void:numberOfTriples ; ";
        $query .= " rdf:value ?nbTriple ." ;
		
        $sparql = $prefix." SELECT ?nbTriple WHERE { GRAPH <http://www.bordercloud.com/dataset> {".$query."}}";
		
		$r = $this->queryReadTabSeparated($sparql);
		
		if(preg_match_all('%\$nbTriple\n"([0-9]+)"\^\^<http://www.w3.org/2001/XMLSchema#integer>%m',$r,$countResponse))
			$count = $countResponse[1][0];

		return $count;
	}
	
	/**
	 * Give the size of graph (Bytes)
	 * @param string $graph : put name of the graph or nothing for the default graph
	 * @return number
	 * @access public
	 */
	public function size($graph = null ) {
		if($graph == null)
			$graph = $this->_graph;
		$r="";
		$count = 0;

		$prefix = "PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> ";
		$prefix .= "PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> ";
		$prefix .= "PREFIX xsd: <http://www.w3.org/2001/XMLSchema#> ";		
		$prefix .= "PREFIX scovo: <http://purl.org/NET/scovo#> ";
		$prefix .= "PREFIX void: <http://rdfs.org/ns/void#> ";
	
		$query = "<".$graph."> void:sparqlEndpoint <".substr($this->_endpoint,0,-1)."> ;";
        $query .= " void:statItem ?nodesize .";
        $query .= " ?nodesize scovo:dimension void:numberOfBytes ; ";
        $query .= " rdf:value ?size ." ;
        
		$sparql = $prefix." SELECT ?size WHERE { GRAPH <http://www.bordercloud.com/dataset> {".$query."}}";
		
		$r = $this->queryReadTabSeparated($sparql);
		if(preg_match_all('%\$size\n"([0-9]+)"\^\^<http://www.w3.org/2001/XMLSchema#integer>%m',$r,$countResponse))
			$count = $countResponse[1][0];

		return $count;
	}
	
	/**
	 * Send a request SPARQL of type select or ask to endpoint directly and output the response
	 * of server. If you want parse the result of this function, it's better and simpler
	 * to use the function query().
	 * @param string $query : Query Sparql
	 * @param string $typeOutput by default "application/sparql-results+xml"
	 *  if you want use another format, you can use directly the function queryReadJSON and queryReadTabSeparated
	 * @return string response of server or false if error (to do getErrors())
	 * @access public
	 */
	public function queryRead($query,$typeOutput="application/sparql-results+xml" ) {
		$client = new Curl();
		$sUri    = $this->_endpoint;
		$data = array("query" =>   $query,"output" => $typeOutput);

		$this->debugLog($query,$sUri);
		
//		if($typeOutput == "application/sparql-results+xml" )
//			$response = $client->send_post_data($sUri,$data);
//		else
			$response = $client->send_post_data($sUri,$data);

		$code = $client->get_http_response_code();
			
		$this->debugLog($query,$sUri,$code,$response);

		if($code != 200)
		{
			$error = $this->errorLog($query,$data,$sUri,$code,$response);
			$this->_arc2_RemoteStore->addError($error);
			return false;
		}
		return $response;
	}

	/**
	 * Send a request SPARQL of type select or ask to endpoint directly and output the response
	 * of server in the format JSON
	 * @param string $query : Query Sparql
	 * @return string response of server in the format JSON
	 * @access public
	 */
	public function queryReadJSON($query ){
		return $this->queryRead($query,"application/sparql-results+json" );
	}

	/**
	 * Send a request SPARQL of type select or ask to endpoint directly and output the response
	 * of server in the format TabSeparated
	 * @param string $query : Query Sparql
	 * @return string response of server in the format TabSeparated
	 * @access public
	 */
	public function queryReadTabSeparated ($query ){
		return $this->queryRead($query,"text" );
	}

	/**
	 * Send a request SPARQL of type insert data or delete data to endpoint directly.
	 * If you want check the query before to send, it's better to use the function query()
	 *  in the class StorePlus.
	 *  Example insert : PREFIX ex: <http://example.com/> INSERT DATA { GRAPH <http://mygraph> { ex:a ex:p 12 .}}
	 *  Example delete : PREFIX ex: <http://example.com/> DELETE DATA { GRAPH <http://mygraph> { ex:a ex:p 12 .}}
	 * @param string $query : Query Sparql of type insert data or delete data only
	 * @return boolean true if it did or false if error (to do getErrors())
	 * @access public
	 */
	public function queryUpdate($query) { 
		$post_endpoint =  $this->_endpoint_write;

		$sUri    = $post_endpoint;
		$data =array("query" =>    $query,"bcjeton" => $this->_jeton) ;

		$this->debugLog($query,$sUri);
			
		$client = new Curl();
		$response = $client->send_post_data($sUri, $data);
		$code = $client->get_http_response_code();

		$this->debugLog($query,$sUri,$code,$response);
			
		if($code == 200 )
		{
			return true;
		}
		else
		{
			$error = $this->errorLog($query,$data,$sUri,$code,$response);
			$this->_arc2_RemoteStore->addError($error);
			return false;
		}
	}
	
	/**
	 * Import a file of type rdfxml, ntriples or turtle
	 * @param string $filename 
	 * @param  $typefile Optional, 
	 * ntriples to import a file NTriples or 
	 * rdfxmlto import a file RDF or 
	 * turtle to import a file Turtle
	 * @param  $append Optional, 
	 * true if you want to append the new triples in the graph
	 * false if you want to erase the graph before to add the new triples
	 * @return the code of this file on the server if OK else return false (see errors with getErrors())
	 * @access public
	 */
	public function importFile($filename, $typefile = "rdfxml", $append = true) {
		$client = new Curl();
		$sUri    = $this->_endpoint_upload;
		
		if($append){
			$erase = 0;
		}else{
			$erase = 1;
		}
		
		$data = array(	
			"erase" => $erase,	
			"graph" => $this->_graph,	
			"typeFile" => $typefile,	
			"bcjeton" => $this->_jeton);

		$file = array("filename" =>  $filename );
		$response = $client->send_multipart_post_data($sUri,$data,$file);
		$code = $client->get_http_response_code();
		if($code == 200 && preg_match("#.*<a[^>]*>(.*)\n?</a>#i",$response,$matches)){
			return $matches[1];
		}else{
			$this->_arc2_RemoteStore->addError("Cannot importFile : ".$response ."(".$code.")");
			return false;
		}
	}
	
	/**
	 * Import a file of type rdfxml, ntriples or turtle
	 * @param string $filename 
	 * @param  $typefile Optional, 
	 * ntriples to import a file NTriples or 
	 * rdfxmlto import a file RDF or 
	 * turtle to import a file Turtle
	 * @return true if the file is waiting on the server else false (check if errors with getErrors())
	 * @access public
	 */
	public function isFileWaitToParse($codefile) {
		$client = new Curl();
		$sUri    = $this->_endpoint_upload;
		$data = array(	
			"codefile" => $codefile);

		$response = $client->send_post_data($sUri,$data);
		$code = $client->get_http_response_code();
		if($code != 200 ){
			$this->_arc2_RemoteStore->addError("Error importFile : ".$response ."(".$code.")");
			return false;
		}elseif(preg_match("#.*file yet parsed.*#i",$response,$matches)){
			return false;
		}elseif(preg_match("#.*Wait to parse.*#i",$response,$matches)){
			return true;
		}else{
			$this->_arc2_RemoteStore->addError("Cannot importFile : ".$response ."(".$code.")");
			return false;
		}
	}	
		
	/**
	 * Save the graph in a file
	 * @param string $file 
	 * @param  $typefile Optional, 
	 * ntriples to export a file NTriples (only for the moment)
	 * @return true if it's ok
	 * @access public
	 */
	public function saveAs($file, $typefile = "ntriples") {
		$res = false;
		$fp = fopen($file, 'w');
		$client = new Curl();
		$url   = $this->_endpoint_export."?bcjeton=".$this->_jeton."&typeFile=".$typefile."&graph=".$this->_graph;	
		$res =  $client->fetch_into_file($url, $fp, null,600);
		fclose($fp);
		return $res;
	}	
	
	/************************************************************************/
	//PRIVATE Function
	
	/*
	 * Execute the query 
	 * @access private
	 */
	private function runQuery($q, $qt = '', $infos = '') {

		/* ep */
		$ep = $this->_arc2_RemoteStore->v('remote_store_endpoint', 0, $this->_arc2_RemoteStore->a);
		if (!$ep) return $this->_arc2_RemoteStore->addError('No Endpoint defined.');
		/* prefixes */
		$q = $this->_arc2_RemoteStore->completeQuery($q);
		/* custom handling */
		$mthd = 'run' . $this->_arc2_RemoteStore->camelCase($qt) . 'Query';
		if (method_exists($this, $mthd)) {
			return $this->_arc2_RemoteStore->$mthd($q, $infos);
		}
		if(in_array($qt, array('insert', 'delete'))){
			$r = $this->queryUpdate($q);
			if(! $r){
				$errmsg = "Error unknown.";
				if(Net::ping($ep) == -1)
					$errmsg = "Could not connect to ".$ep;
					
				return $this->_arc2_RemoteStore->addError($errmsg );
			}
		}else{
			$resp = $this->queryRead($q );
			
			if($resp == ""){
					$errmsg = "Error unknown.";
					if(Net::ping($ep) == -1)
						$errmsg = "Could not connect to ".$ep;
						
					return $this->_arc2_RemoteStore->addError($errmsg );
			}

			if(preg_match_all('%<!--(.*error.*)-->%m',$resp,$errorResponse)){
				$message4s = $errorResponse[1][0];
				return $this->_arc2_RemoteStore->addError("5Store message : ".$message4s ."\n query :\n".$q );
			}

			$parser = @ARC2::getSPARQLXMLResultParser() ;
			$parser->parse('', $resp);
			$err = $parser->getErrors();
			if($err)
				return $this->_arc2_RemoteStore->addError($err);
			
			if ($qt == 'ask') {
				$bid = $parser->getBooleanInsertedDeleted();
				$r = $bid['boolean'];
			}
			/* select */
			elseif (($qt == 'select') && !method_exists($parser, 'getRows')) {
				$r = $resp;
			}
			elseif ($qt == 'select') {
				$r = array('rows' => $parser->getRows(), 'variables' => $parser->getVariables());
			}
			/* any other */
			else {
				$r = $parser->getSimpleIndex(0);
			}
			unset($parser);
		}
		return $r;
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
		$error = "";
		if($httpcode == 600){
			$error = 	"Error message of BorderCloud  : parameter query does not exist in your query http \n";
			$error .= 	"data  : ". print_r($data,true)."\n";			
		}else if($httpcode == 601){
			$error = 	"Error message of BorderCloud  : parameter bcjeton does not exist in your query http \n";
			$error .= 	"data  : ". print_r($data,true)."\n";			
		}else if($httpcode == 602){
			$error = 	"Error message of BorderCloud  : the number of you bcjeton is incorrect (after 3 incorrect connections, your IP will be in the blacklist) \n";	
		}else if($httpcode == 603){
			$error = 	"Error message of BorderCloud  :  IP address rejected. \n";	
		}else if($httpcode == 604){
			$error = 	"Error message of BorderCloud  :  You can not insert your data because the size is limited. \n";	
		}else{			
			$error = 	"Error query  : " .$query."\n" .
						"Error endpoint: " .$endPoint."\n" .
						"Error http_response_code: " .$httpcode."\n" .
						"Error message: " .$response."\n";
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

}
