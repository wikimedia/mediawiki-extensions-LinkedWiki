<?php
/**
 * @version 0.1.0.0
 * @package Bourdercloud/4store-PHP
 * @copyright (c) 2010 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 *
 * This file is a fork of project  : http://github.com/moustaki/4store-php
 * @author Yves Raimond

 Copyright (c) 2010 Bourdercloud.com

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


require_once("Net.php");
require_once("Curl_HTTP_Client.php");

/**
 * Sparql HTTP Client for 4Store around basic php function.
 */
class FourStore_Store {

	/**
	 * Endpoint sparql
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
	 * Constructor of FourStore_Store
	 * @param string $endpoint : url of endpoint, example : http://localhost:8080/sparql
	 * @param boolean $debug : false by default, set debug to true in order to get usefull output
	 * @access public
	 */
	public function __construct($endpoint,$debug = false)
	{
		$this->_debug = $debug;
		$this->_endpoint = $endpoint;
	}
	
//	/**
//	 * Check if the triplestore is up.
//	 * @return boolean true if the triplestore is up.
//	 * @access public
//	 */
//	public function checkEndpoint() {
//		$client = &new Curl_HTTP_Client();
//		$sUri    = $this->_endpoint;
//		$query =  "select * where {?x ?y ?z.} LIMIT 1";
//		$data = array("query" => $query);
//
//		$response = $client->send_post_data($sUri, $data	);
//		$code = $client->get_http_response_code();
//		
//		$this->debugLog($query,$sUri,$code,$response);
//		return $code == 200;
//	}

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
		$client = &new Curl_HTTP_Client();

		$headers = array( 'Content-Type: application/x-turtle' );
		$sUri    = $this->_endpoint . $graph;

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
		$post_endpoint = array_shift(explode("/sparql/", $this->_endpoint)) . "/data/";

		$data = array( "graph" => $graph, "data" => $turtle , "mime-type" => 'application/x-turtle' );
		$sUri    = $post_endpoint;

		$client = &new Curl_HTTP_Client();
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
		$client = &new Curl_HTTP_Client();
		$sUri    = $this->_endpoint . $graph ;
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

	/**
	 * Count the number of triples in a graph or in the endpoint.
	 * @param string $graph : put name of the graph or nothing to count all triples in the endpoint
	 * @return number
	 * @access public
	 */
	public function count($graph= null ) {
		$r="";
		$count = -1;
		if($graph != null){
			//FIXME count(*) doesn't work
			$r = $this->queryReadTabSeparated("SELECT count(?a) AS count WHERE  { GRAPH <".$graph."> {?a ?b ?c}}");
		}else{
			$r = $this->queryReadTabSeparated("SELECT count(?a) AS count WHERE {?a ?b ?c}");
		}

		if(preg_match_all('%\?count\n"([0-9]+)"\^\^<http://www.w3.org/2001/XMLSchema#integer>%m',$r,$countResponse))
		$count = $countResponse[1][0];

		return $count;
	}

	/**
	 * Send a request SPARQL of type select or ask to endpoint directly and output the response
	 * of server. If you want parse the result of this function, it's better and simpler
	 * to use the function query() in the class FourStore_StorePlus.
	 * @param string $query : Query Sparql
	 * @param string $typeOutput by default "application/sparql-results+xml"
	 *  if you want use another format, you can use directly the function queryReadJSON and queryReadTabSeparated
	 * @return string response of server
	 * @access public
	 */
	public function queryRead($query,$typeOutput="application/sparql-results+xml" ) {
		$client = &new Curl_HTTP_Client();
		$sUri    = $this->_endpoint;
		$data = array("query" =>   $query);
		$header = array("Accept:". $typeOutput);

		$this->debugLog($query,$sUri);

		$code = $client->get_http_response_code();

		if($typeOutput == "application/sparql-results+xml" )
			$response = $client->send_post_data($sUri,$data);
		else
			$response = $client->send_post_data($sUri,$data,$header);

		$code = $client->get_http_response_code();
			
		$this->debugLog($query,$sUri,$code,$response);

		if($code != 200)
		{
			$datastr = print_r($data, true);
			$headerstr = print_r($header, true);
			$this->errorLog("Set:\nHeader :".$headerstr."\n Data:".$datastr,$sUri,$code,$response);			
			return "";
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
		return $this->queryRead($query,"text/tab-separated-values" );
	}

	/**
	 * Send a request SPARQL of type insert data or delete data to endpoint directly.
	 * If you want check the query before to send, it's better to use the function query()
	 *  in the class FourStore_StorePlus.
	 *  Example insert : PREFIX ex: <http://example.com/> INSERT DATA { GRAPH ex:mygraph { ex:a ex:p 12 .}}
	 *  Example delete : PREFIX ex: <http://example.com/> DELETE DATA { GRAPH ex:mygraph { ex:a ex:p 12 .}}
	 * @param string $query : Query Sparql of type insert data or delete data only
	 * @return boolean true if it did
	 * @access public
	 */
	public function queryUpdate($query) {
		$post_endpoint = array_shift(explode("/sparql/", $this->_endpoint)) . "/update/";

		$sUri    = $post_endpoint;
		$data =array("update" =>    $query) ;

		$this->debugLog($query,$sUri);
			
		$client = &new Curl_HTTP_Client();
		$response = $client->send_post_data($sUri, $data);
		$code = $client->get_http_response_code();

		$this->debugLog($query,$sUri,$code,$response);
			
		//FIXME in 4Store : check in the next version || $code == 0 || $code == 100
		if($code == 200 ) //bug
		{
			return true;
		}
		else
		{
			$this->errorLog($query,$sUri,$code,$response);
			return false;
		}
	}

	/**
	 * Put error in the log
	 * @param string $query
	 * @param string $endPoint
	 * @param number $httpcode
	 * @param string $response
	 * @access private
	 */
	private function errorLog($query,$endPoint,$httpcode='',$response=''){
		$error = 	"Error query 4store : " .$query."\n" .
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
						"query 4store : " .$query."\n" .
                        "endpoint: " .$endPoint."\n" .
                        "http_response_code: " .$httpcode."\n" .
                        "message: " .$response.
                        "\n#######################\n";

			echo $error ;
		}
	}

}
