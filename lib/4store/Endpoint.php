<?php
/**
 * @version 0.4.0.0
 * @package Bourdercloud/4store-PHP
 * @copyright (c) 2011 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>

 Copyright (c) 2011 Bourdercloud.com

 Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:

 The above copyright notice and this permission notice shall be included in
 all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 THE SOFTWARE.
 */


require_once(dirname(__FILE__) . '/../arc2/ARC2.php');
require_once("Curl.php");
require_once("Net.php");

/**
 * Sparql HTTP Client for BorderCloud's Endpoint around basic php function.
 */
class Endpoint {
	/**
	 * URL of Endpoint to read
	 * @access private
	 * @var string
	 */
	private $_endpoint;
		
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
	
	/** For Arc2 **/
	private $_arc2_RemoteStore;
	private $_arc2_Reader;
	private $_config;

	/**
	 * Constructor of Graph
	 * @param string $endpoint : url of endpoint, example : http://lod.bordercloud.com/sparql
	 * @param boolean $debug : false by default, set debug to true in order to get usefull output
	 * @access public
	 */
	public function __construct($endpoint,$readOnly = true,$debug = false)
	{
		$this->_debug = $debug;
		$this->_endpoint = $endpoint;
		$this->_readOnly = $readOnly;
		
		$this->_config = array(
		/* remote endpoint */
		  'remote_store_endpoint' => $this->_endpoint."sparql/",
		);

		$this->_arc2_RemoteStore = ARC2::getRemoteStore($this->_config);
		
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
	 * Create or replace the data in a graph.
	 * @param string $graph : name of the graph
	 * @param string $turtle : list of the triples
	 * @return boolean : true if it did
	 * @access public
	 */
	public function set($graph, $turtle) {
		if($this->_readOnly){
				return $this->_arc2_RemoteStore->addError('No right to write in the triplestore.');
		}
		
		$client = new Curl();

		$headers = array( 'Content-Type: application/x-turtle' );
		$sUri    = $this->_endpoint. "data/" . $graph;

		$response = $client->send_put_data($sUri,$headers, $turtle);
		$code = $client->get_http_response_code();

		if($code == 201)
		{
			return true;
		}
		else
		{
			$datastr = print_r($turtle, true);
			$headerstr = print_r($headers, true);
			$this->errorLog("Set:\nHeader :".$headerstr."\n Data:".$datastr,$sUri,$code,$response);
			return false;
		}
	}

	/**
	 * Add new data in a graph.
	 * @param string $graph : name of the graph
	 * @param string $turtle : list of the triples
	 * @return boolean : true if it did
	 * @access public
	 */
	public function add($graph, $turtle) {
		if($this->_readOnly){
				return $this->_arc2_RemoteStore->addError('No right to write in the triplestore.');
		}
		
		$data = array( "graph" => $graph, "data" => $turtle , "mime-type" => 'application/x-turtle' );
		$sUri    = $this->_endpoint. "data/";

		$client = new Curl();
		$response = $client->send_post_data($sUri, $data);
		$code = $client->get_http_response_code();

		if($code == 200)
		{
			return true;
		}
		else
		{
			$datastr = print_r($data, true);
			$this->errorLog("Add:\n".$datastr,$sUri,$code,$response);
			return false;
		}
	}

	/**
	 * Delete a graph with its data.
	 * @param string $graph : name of the graph
	 * @return boolean : true if it did
	 * @access public
	 */
	public function delete($graph) {	
		if($this->_readOnly){
				return $this->_arc2_RemoteStore->addError('No right to write in the triplestore.');
		}
		
		$client = new Curl();
		$sUri    = $this->_endpoint. "data/". $graph ;
		$response = $client->send_delete($sUri);
		$code = $client->get_http_response_code();

		if($code == 200)
		{
			return true;
		}
		else
		{
			$this->errorLog("DELETE:<".$graph.">",$sUri,$code,$response);
			return false;
		}
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

	/**
	 * Count the number of triples in a graph or in the endpoint.
	 * @param string $graph : put name of the graph or nothing to count all triples in the endpoint
	 * @return number
	 * @access public
	 */
	public function count($graph= null ) {
		$r="";
		$count = 0;
		if($graph != null){
			//FIXME count(*) doesn't work
			$r = $this->queryReadTabSeparated("SELECT (count(?a) AS ?count) WHERE  { GRAPH <".$graph."> {?a ?b ?c}}");
		}else{
			$r = $this->queryReadTabSeparated("SELECT (count(?a) AS ?count) WHERE {?a ?b ?c}");
		}

		if(preg_match_all('%\?count\n"([0-9]+)"\^\^<http://www.w3.org/2001/XMLSchema#integer>%m',$r,$countResponse))
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
	public function queryRead($query,$typeOutput=null ) {
		$client = new Curl();
		$sUri    = $this->_endpoint."sparql/";
		
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
		$sUri  =   $this->_endpoint . "update/";
		$data =array("update" =>    $query) ;

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
			if($this->_readOnly){
				return $this->_arc2_RemoteStore->addError('No right to write in the triplestore.');
			}else{
				$r = $this->queryUpdate($q);
				if(! $r){
					$errmsg = "Error unknown.";
					if(Net::ping($ep) == -1)
						$errmsg = "Could not connect to ".$ep;
												
					return $this->_arc2_RemoteStore->addError($errmsg );
				}
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
