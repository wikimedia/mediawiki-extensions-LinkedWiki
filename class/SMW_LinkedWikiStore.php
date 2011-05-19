<?php
/**
 * @version 1.0.0.0
 * @package Bourdercloud/linkedwiki
 * @copyright (c) 2011 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link http://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-nc-sa V3.0
 *
 * Last version : http://github.com/BorderCloud/LinkedWiki

	This work is licensed under the Creative Commons 
	Attribution-NonCommercial-ShareAlike 3.0 
	Unported License. To view a copy of this license, 
	visit http://creativecommons.org/licenses/by-nc-sa/3.0/ 
	or send a letter to Creative Commons,
	171 Second Street, Suite 300, San Francisco, 
	California, 94105, USA.

 */
if ( !defined( 'MEDIAWIKI' ) ) {
    echo "This file is not a valid entry point.";
    exit( 1 );
}
/**
 * This is an implementation of the SMW store that still uses the new
 * SMW SQL 2 Store for everything SMW does, but it decorates all edits to
 * the store with calls to a 4Store, so it keeps in parallel a second
 * store with all the semantic data. This allows for a SPARQL endpoint. 
 */
class SMW_LinkedWikiStore extends SMWSQLStore2 {
	
	public function SMW_LinkedWikiStore() {
	}
	
//	private function updatePageLinkedWikiStore(Title $title,$uri) {
//		global $wgLinkedWikiGraphWiki,$wgLinkedWikiEndPoint; 
//		try{
//			SparqlTools::insertRDF($uri,$wgLinkedWikiGraphWiki,$wgLinkedWikiEndPoint,$wgLinkedWikiBorderCloudJeton);
//		}catch (Exception $e){
//			LinkedWikiJob::doJob($title,
//								$uri,
//								"update",
//								$wgLinkedWikiGraphWiki,
//								$wgLinkedWikiEndPoint
//								);
//		}
//	}
	
	private function deletePageLinkedWikiStore(Title $title,$uri) {
		global $wgLinkedWikiGraphWiki,$wgLinkedWikiEndPoint,$wgLinkedWikiBorderCloudJeton; 
//		try{
			SparqlTools::deleteTriples($uri,$wgLinkedWikiGraphWiki,$wgLinkedWikiEndPoint,$wgLinkedWikiBorderCloudJeton);
//		}catch (Exception $e){			
//			LinkedWikiJob::doJob($title,
//								$uri,
//								"delete",
//								$wgLinkedWikiGraphWiki,
//								$wgLinkedWikiEndPoint
//								);
//		}
	}

///// Writing methods /////

	function deleteSubject( Title $subject ) {
		$res = parent::deleteSubject( $subject );
		$iri = $this->getIRI( $subject ) ;
		$this->deletePageLinkedWikiStore($subject,$iri);
		return $res;
	}

	function updateData( SMWSemanticData $data ) {
		$res = parent::updateData( $data );
		$this->_update( $data );
		return $res;
	}
	
	function _update( SMWSemanticData $data) {
		global $wgLinkedWikiGraphWiki,$wgLinkedWikiEndPoint,$wgLinkedWikiBorderCloudJeton; 
		$ed = SMWExporter::makeExportData( $data ); // ExpData
		$iri = $this->getIRI( $ed->getSubject()->getName());
		//first solution but the rdf is not update in real time //FIXME
//		$this->updatePageLinkedWikiStore($data->getSubject()->getTitle(),$uri);

		//SECOND SOLUTION
		SparqlTools::deleteTriples($iri,$wgLinkedWikiGraphWiki,$wgLinkedWikiEndPoint,$wgLinkedWikiBorderCloudJeton);
		$triples = array();				
		$tl = $ed->getTripleList(); // list of tenary arrays

		foreach ($tl as $triple) {
			$s = $triple[0];	// Subject
			$p = $triple[1];	// Predicate
			$o = $triple[2]; // Object

            $s_str = $this->buildStringTriple($s);
            $p_str = $this->buildStringTriple($p);
            $o_str =  $this->buildStringTriple($o);

            if($s_str != "" && $p_str != "" && $o_str != "" ){
               $triples[] = array(
                              $s_str,
                              $p_str,
                              $o_str);
            }else{
            	$this->logError('Error insert triple', 
            	$s_str."(".$s->getName().")"." " . 
            	$p_str."(".$p->getName().")" ." ".
            	$o_str."(".$o->getName().")");
            }
				
		}
		SparqlTools::insert($this->implodeTriples($triples),$wgLinkedWikiGraphWiki,$wgLinkedWikiEndPoint,$wgLinkedWikiBorderCloudJeton);
	}
	
	
	function changeTitle( Title $oldtitle, Title $newtitle, $pageid, $redirid = 0 ) {
		// Save it in parent store now!
		// We need that so we get all information correctly!
		$result = parent::changeTitle( $oldtitle, $newtitle, $pageid, $redirid );
		
//		error_log("Error ".$oldtitle ."as the same id = ".$this->getSMWPageID( $oldtitle->getDBkey(), $oldtitle->getNamespace(), $oldtitle->getInterwiki() ));
//		error_log("Error ".$newtitle ."as the same id = ".$this->getSMWPageID( $newtitle->getDBkey(), $newtitle->getNamespace(), $newtitle->getInterwiki() ));
		
		//first solution but the rdf is not update in real time //FIXME
//		$oldIri= $this->getIRI( $oldtitle ) ;
//		$newIri= $this->getIRI( $newtitle ) ;
		//$this->deletePageLinkedWikiStore($oldtitle,$oldIri);
		//$this->updatePageLinkedWikiStore($newtitle,$newIri);
		
		//erase cache //FIXME https://bugzilla.wikimedia.org/show_bug.cgi?id=24856
		$this->m_semdata = array();
		//second solution
		$newpage = SMWDataValueFactory::newTypeIDValue( '_wpg' );
		$newpage->setValues( $newtitle->getDBkey(), $newtitle->getNamespace(), $pageid );
		$semdata = $this->getSemanticData( $newpage );
		$this->_update($semdata);
		
		//erase cache
		$this->m_semdata = array();
		// Save the old page
		$oldpage = SMWDataValueFactory::newTypeIDValue( '_wpg' );
		$oldpage->setValues( $oldtitle->getDBkey(), $oldtitle->getNamespace(), $redirid );
		$semdata = $this->getSemanticData( $oldpage );
		$this->_update( $semdata);
		
		return $result;
	}

///// Setup store /////

	/**
	 * Setup all storage structures properly for using the store. This function performs tasks like
	 * creation of database tables. It is called upon installation as well as on upgrade: hence it
	 * must be able to upgrade existing storage structures if needed. It should return "true" if
	 * successful and return a meaningful string error message otherwise.
	 *
	 * The parameter $verbose determines whether the procedure is allowed to report on its progress.
	 * This is doen by just using print and possibly ob_flush/flush. This is also relevant for preventing
	 * timeouts during long operations. All output must be valid XHTML, but should preferrably be plain
	 * text, possibly with some linebreaks and weak markup.
	 */
	function setup( $verbose = true ) {
		 $res = parent::setup( $verbose );
		
		//TEST if the wiki can connect to endpoint and if the endpoint is compatible.
		global $wgLinkedWikiGraphWiki,$wgLinkedWikiEndPoint,$wgLinkedWikiBorderCloudJeton; 
		$this->reportProgress( "\nLinkedWiki : Checking parameters in your LocalSettings.php \n", $verbose );		
		$this->reportProgress( 'LinkedWiki :         $wgLinkedWikiEndPoint : Endpoint\'s address = ' .$wgLinkedWikiEndPoint."\n", $verbose );
		$this->reportProgress( 'LinkedWiki :         $wgLinkedWikiGraphWiki : Graph  = ' .$wgLinkedWikiGraphWiki."\n", $verbose );
		$this->reportProgress( "LinkedWiki : checking connection to Endpoint :...", $verbose );
		$s = new Endpoint($wgLinkedWikiEndPoint);
		if(! $s->check())
			$this->reportProgress( " KO. the server is down "."\n", $verbose );
		else{
			$this->reportProgress( " OK "."\n", $verbose );
			
			$this->reportProgress( "LinkedWiki : checking the compatibility of this Endpoint :...\n", $verbose );
			
			$this->reportProgress( "LinkedWiki :        query INSERT DATA (SPARQL 1.1 Update) : ", $verbose );
			if($wgLinkedWikiBorderCloudJeton == null){
				$sp_write = new Endpoint($wgLinkedWikiEndPoint,false );
			}else{
				$sp_write = new Endpoint($wgLinkedWikiEndPoint,$wgLinkedWikiBorderCloudJeton );
			}
			$q = " 	PREFIX a: <http://example.com/test/a/>
					PREFIX b: <http://example.com/test/b/> 
					INSERT DATA {  
						GRAPH <".$wgLinkedWikiGraphWiki."> {    
						a:A b:Name \"Test1\" .   
						a:A b:Name \"Test2\" .   
						a:A b:Name \"Test3\" .  
		    		}}";
			$res = $sp_write->query($q,'raw');
			$err = $sp_write->getErrors();
		    if ($err || !$res) {
			   $this->reportProgress( " KO \n", $verbose  . print_r($err,true). print_r($res,true));
			}else{
			   $this->reportProgress( " OK \n", $verbose );
			}
			
			$this->reportProgress( "LinkedWiki :        query DELETE DATA (SPARQL 1.1 Update) : ", $verbose );
			$q = " 
					PREFIX a: <http://example.com/test/a/>
					PREFIX b: <http://example.com/test/b/> 
					DELETE DATA {  
						GRAPH <".$wgLinkedWikiGraphWiki."> {    
						a:A b:Name \"Test1\" .   
						a:A b:Name \"Test2\" .   
						a:A b:Name \"Test3\" . 
		    		}}";
			
			$res = $sp_write->query($q,'raw');
			$err = $sp_write->getErrors();
			if ($err || !$res) {
			   $this->reportProgress( " KO \n" . print_r($err,true). print_r($res,true), $verbose );
			}else{
			   $this->reportProgress( " OK \n", $verbose );
			}
			
			$this->reportProgress( "LinkedWiki :        query SELECT (SPARQL 1.0) : ", $verbose );
			$sp_readonly = new Endpoint($wgLinkedWikiEndPoint);
		    $q = "select * where { GRAPH <".$wgLinkedWikiGraphWiki."> {?x ?y ?z.}} ";
		    $rows = $sp_readonly->query($q, 'rows');
		    $err = $sp_readonly->getErrors();		    
			if ($err || !$res) {
			   $this->reportProgress( " KO \n" . print_r($err,true). print_r($res,true), $verbose);
			}else{
			   $this->reportProgress( " OK \n", $verbose );
			}
		}
		return  $res;
	}

	function drop( $verbose = true ) {		
		global $wgLinkedWikiGraphWiki,$wgLinkedWikiEndPoint,$wgLinkedWikiBorderCloudJeton; 
		$res = parent::drop();
		
		$this->reportProgress( "LinkedWiki : Delete the graph <".$wgLinkedWikiGraphWiki."> in the endpoint ". $wgLinkedWikiEndPoint." : ", $verbose );		
		try{
		   SparqlTools::deleteGraph($wgLinkedWikiGraphWiki,$wgLinkedWikiBorderCloudJeton);
		   LinkedWikiJob::cleanJobs();
		   $this->reportProgress( " OK \n", $verbose );
		}catch (Exception $e){
		   $this->reportProgress( " KO \n", $verbose );
		   $res = false; // maybe ?
		}
		return $res;
	}

	/**
	 * Having a title of a page or a object title, what is the IRI that is described by that page?
	 */
	private function getIRI( $title ) {
		$iri = "";
		if ( $title instanceof Title ) {
			$dv = SMWDataValueFactory::newTypeIDValue( '_wpg' );
			$dv->setTitle( $title );
			$exp = $dv->getExportData();
			$name = $exp->getSubject()->getName();
			$iri = SparqlTools::decodeURItoIRI(SMWExporter::expandURI($name));
		} else {
			$iri = SparqlTools::decodeURItoIRI(SMWExporter::expandURI($title));
		}

		return $iri; 
	}
	
    private function buildStringTriple($node) {
		$res = "";
		$name = $node->getName();
    	if ( $node instanceof SMWExpResource  ) {
				$iri = SparqlTools::decodeURItoIRI(SMWExporter::expandURI($name));				
				$res = "<".$iri.">";
		}elseif ( $node instanceof SMWExpLiteral ) {
			$res = "\"".addcslashes($name,"\t\n\r\f\"\'\\")."\"";
				
			//type
			$type = $node->getDatatype(); 
			// bug of 4Store so I add a condition  $type != "http://www.w3.org/2001/XMLSchema#string"
			if ($type != '' && $type != "http://www.w3.org/2001/XMLSchema#string" ) {
         		$res .= "^^<".$type.">";
			}else{
                  //tag lang
                  global $wgLinkedWikiLanguageTag;
                  if($wgLinkedWikiLanguageTag != null){
                       $res .= "@".$wgLinkedWikiLanguageTag;
                  }
            }
		}else{
			if (preg_match('#^_[0-9]*$#i', $name)) {//namespace ? blank ?
				$res = "";
			} else { 	
				$this->logError('Error unknown node', $node->getName() ." " . get_class($node) ." ". print_r($node,true) ." " . $res);
				$res ="";
			}
		}
        return $res;
    }

    private function implodeTriples($triples) {
        $result = "";
        foreach($triples as $t) {
            $result .= implode(" ", $t);
            $result .= ".\n ";
        }
        return $result;
    }
    
    private function logError($message,$data){
		$datastr = print_r($data, true); 
		error_log($message."\n".$datastr);
	}

}

