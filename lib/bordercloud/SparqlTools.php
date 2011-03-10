<?php
/**
 * @version 1.2.0.0
 * @package bordercloud/client
 * @copyright (c) 2011 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 *
 All rights reserved Copyright (c) 2011 Bourdercloud.com

 */

require_once("Endpoint.php");

class SparqlTools {
	
	/*
	 * CAREFUL : Erase your GRAPH
	 * @param string $graph :
	 * @param string $endpoint : url of endpoint, example : http://localhost:8080/sparql
	 * @access public
	 */
	static function deleteGraph($graph,$endpoint,$code){
		//FIXME Not sparql for 4store
		$s = new Endpoint($endpoint,$code);
		$r = $s->delete($graph);	
		if (!$r) {
			$msg = "Query delete Graph";
		    throw new Exception($msg);
		}
	} 
	
	/*
	 * Delete triples in the endpoint when the uri of subject is equal.
	 * @param string $iri : subject of triples to delete
	 * @param string $graph : graph of endpoint where it will delete the data
	 * @param string $endpoint : url of endpoint, example : http://localhost:8080/sparql
	 * @access public
	 */
	static function deleteTriples($iri,$graph,$endpoint,$code){
			/* Serializer instantiation */			
			$ser = ARC2::getNTriplesSerializer();
			$sp_readonly = new Endpoint($endpoint);	
			//DELETE OLD TRIPLES WITH THE SUBJECT $uri
			$q = "select * where {GRAPH <".$graph."> {<".$iri.">  ?p ?o.}} ";
			
	    	$oldTriples = $sp_readonly->query($q,'rows');
	    	$err = $sp_readonly->getErrors();
		    if ($err) {
			    throw new Exception(self::buildMessage($err));
			}
			if(count($oldTriples) > 0){				
			    for ($i = 0, $i_max = count($oldTriples); $i < $i_max; $i++) {
			    	$t = & $oldTriples[$i];
					 $t['s'] = $iri;
					 $t['s type'] = "uri";
				}
				/* Serialize a triples array */
				$docd = $ser->getSerializedTriples($oldTriples,1);
		    	$sp =  new Endpoint($endpoint,$code);
				$q = "DELETE DATA {  
							GRAPH <".$graph."> {    
							$docd 
			    		}}";
				$res = $sp->query($q,'raw' );
				$err = $sp->getErrors();
			    if ($err ) {
			    	throw new Exception(self::buildMessage($err));
				}
				
				if (!$res) {
					$msg = "Query delete old triples return: False without errors";
				    throw new Exception($msg);
				}
			}
	} 
	
	/*
	 * Insert triples in the endpoint
	 * @param string $turtle : list of triples like the format turtle without prefix
	 * @param string $graph : graph of endpoint where it will record the data
	 * @param string $endpoint : url of endpoint, example : http://localhost:8080/sparql
	 * @access public
	 */
	static function insert($turtle,$graph,$endpoint,$code){
	    	$sp_write = new Endpoint($endpoint,$code);
			$q = "INSERT DATA {  
						GRAPH <".$graph."> {    
						$turtle
		    		}}";
						
			$res = $sp_write->query($q,'raw');
			$err = $sp_write->getErrors();
		    if ($err) {
			    throw new Exception(self::buildMessage($err));
			}		
			if (!$res) {
				$msg = "Query insert new triples return: False without errors";
			    throw new Exception($msg);
			}
	} 
	
	//FIXME  ERROR IN SMW : this function replace decodeURI of SMW
	/*
	 * Decode URI of SMW to IRI for sparql endpoint
	 * @param string $uri 
	 * @return string IRI (utf8)
	 * @access public
	 */
	static public function decodeURItoIRI( $uri ) {
		$url = preg_replace("/-([0-9A-F]{2})/i","%\\1",$uri);
		return urldecode($url);
	}
	
	//FIXME AND TEST ME ...PROBLEM...
	/*
	 * Read a rdf and insert it in the endpoint
	 * @param string $uri : address web of RDF 
	 * @param string $graph : graph of endpoint where it will record the data
	 * @param string $endpoint : url of endpoint, example : http://localhost:8080/sparql
	 * @access public
	 */
	static function insertRDF($uri,$graph,$endpoint,$code){
		self::deleteTriples($uri,$graph,$endpoint,$code);
		
		//READ NEW TRIPLES WITH THE SUBJECT $uri
		$parser = ARC2::getRDFXMLParser();
		@$parser->parse($uri);
		$err = $parser->getErrors();
	    if ($err ) {
		    throw new Exception(self::buildMessage($err));
		}
		$newTriples = $parser->getTriples();
		for ($i = 0, $i_max = count($newTriples); $i < $i_max; $i++) {
			if($uri != $newTriples[$i]['s'])
		  		unset($newTriples[$i]);
		}
		/* Serializer instantiation */			
		$ser = new FourStore_NTriplesSerializer();
		/* Serialize a triples array */
		$doc = $ser->getSerializedTriples($newTriples,1);
		self::insert($doc,$graph,$endpoint,$code);
	} 
	
	static  function buildMessage($errors){
		$msg = "";
		$i = 0;
		foreach($errors as $error){
			$msg .= $i. "--".$error."\n";
			$i++;
		}
		return $msg;
	}
    
}