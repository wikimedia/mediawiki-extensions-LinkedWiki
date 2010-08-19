<?php
/**
 * @version 0.1.0.0
 * @package Bourdercloud/4store-PHP
 * @copyright (c) 2010 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>

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

require_once(dirname(__FILE__) . '/../arc/ARC2.php');

require_once("FourStore_SPARQLParser.php");
require_once("FourStore_Store.php");

/**
 * Sparql HTTP Client for 4Store simpler but less efficient
 * than FourStore_Store.
 * This class uses the parser of ARC(lib RDF Classes for PHP)
 * to parse the query and the response of server.
 */
class FourStore_StorePlus {

	private $_endpoint;
	private $_arc2_RemoteStore;
	private $_arc2_Reader;
	private $_config;

	/**
	 * in the constructor set debug to true in order to get usefull output
	 * @access private
	 * @var string
	 */
	private $_debug;

	/**
	 * Constructor of FourStore_StorePlus
	 * @param string $endpoint : url of endpoint, example : http://localhost:8080/sparql
	 * @param boolean $readOnly : true by default, put false to write in the triplestore
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
		  'remote_store_endpoint' => $endpoint,
		);

		$this->_arc2_RemoteStore = @ARC2::getRemoteStore($this->_config);
	}

	/**
	 * Check, send a request SPARQL and parse the response. Example of using :
	$sp_readonly = new FourStore_StorePlus($endpoint);
	
	echo "\nPrint :";
    $q = "select * where { GRAPH <http://example.com> {?x ?y ?z.}} ";
    $rows = $sp_readonly->query($q, 'rows');
    $err = $sp_readonly->getErrors();
    if ($err) {
	    print_r($err);
	    throw new Exception(print_r($err,true));
	}
	var_dump($rows);
	
	echo "\nASK  :";
    $q = 	"PREFIX a: <http://example.com/test/a/>
			PREFIX b: <http://example.com/test/b/> 
			ask where { GRAPH <http://example.com> {a:A b:Name \"Test3\" .}} ";
    $res = $sp_readonly->query($q, 'raw');
    $err = $sp_readonly->getErrors();
    if ($err) {
	    print_r($err);
	    throw new Exception(print_r($err,true));
	}
	var_dump($res);
	
	 * @param string $query : Query Sparql
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
		$p = new FourStore_SPARQLParser('', $this);
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
	function getErrors() {
		return $this->_arc2_RemoteStore->getErrors();
	}

	/*
	 * Execute the query for 4Store
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
				$s = new FourStore_Store($ep,$this->_debug);
				$r = $s->queryUpdate($q );
				if(! $r){
					$errmsg = "Error unknown.";
					if(Net::ping($ep) == -1)
						$errmsg = "Could not connect to ".$ep;
						
					return $this->_arc2_RemoteStore->addError($errmsg );
				}
			}
		}else{
			$s = new FourStore_Store($ep,$this->_debug);
			$resp = $s->queryRead($q );
			
			if($resp == ""){
					$errmsg = "Error unknown.";
					if(Net::ping($ep) == -1)
						$errmsg = "Could not connect to ".$ep;
						
					return $this->_arc2_RemoteStore->addError($errmsg );
			}

			if(preg_match_all('%<!--(.*error.*)-->%m',$resp,$errorResponse)){
				$message4s = $errorResponse[1][0];
				return $this->_arc2_RemoteStore->addError("4Store message : ".$message4s ."\n query :\n".$q );
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


	 
}
