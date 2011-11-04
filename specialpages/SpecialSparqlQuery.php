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
 
 Description : http://www.mediawiki.org/wiki/Extension:LinkedWiki
 
 Copyright (c) 2010 Bourdercloud.com

	This work is licensed under the Creative Commons 
	Attribution-NonCommercial-ShareAlike 3.0 
	Unported License. To view a copy of this license, 
	visit http://creativecommons.org/licenses/by-nc-sa/3.0/ 
	or send a letter to Creative Commons,
	171 Second Street, Suite 300, San Francisco, 
	California, 94105, USA.

 */
if (!defined('MEDIAWIKI')) die();

class specialsparqlquery extends SpecialPage {

	public function __construct() {
		parent::__construct( 'specialsparqlquery' );
		wfLoadExtensionMessages('specialsparqlquery');
	}

	public function execute($par = null) {
		global $wgOut,$wgScriptPath;
		$queryWithoutPrefix = isset($_REQUEST["queryWithoutPrefix"])?stripslashes($_REQUEST["queryWithoutPrefix"]):"";
		$query = isset($_REQUEST["query"])?stripslashes($_REQUEST["query"]):"";
		$endpoint = isset($_REQUEST["endpoint"])?stripslashes(trim($_REQUEST["endpoint"])):"http://dbpedia.org/sparql";
		$output = isset($_REQUEST["output"])?stripslashes($_REQUEST["output"]):"";
		$titleRequest = isset($_REQUEST["titleRequest"])?stripslashes($_REQUEST["titleRequest"]):"";
		$description = isset($_REQUEST["description"])?stripslashes($_REQUEST["description"]):"";
			
		// $wgOut->addHTML( isset($_REQUEST["query"])?stripslashes($_REQUEST["query"]):"Vide");
		// $wgOut->addHTML(print_r($_REQUEST,true));
		// $wgOut->addHTML(print_r($queryWithoutPrefix,true));
		// $wgOut->addHTML(print_r($query,true));
		// $wgOut->addHTML(print_r($output,true));
			
		if ( $query != "" && $output == "save" && $titleRequest != ""  ) {

			if($this->pageExists($titleRequest)){
				$wgOut->addHTML("<h2>".wfMsg('specialsparqlquery_error_title_exists_yet')."</h2> \n");
				$wgOut->addHTML();
			}else{
				$this->savePage($titleRequest, $this->template($query,$description));
				$wgOut->addWikiText(wfMsg('specialsparqlquery_your_query_saved_here')."[[$titleRequest]]");
			}
		}
			
		if ( $query == "" || $output == "save" || $output == "wiki" ) {
			$wgOut->addWikiText(wfMsg('specialsparqlquery_mainpage'));
			$wgOut->addHTML("<pre>".htmlentities($this->prefix(), ENT_QUOTES, 'UTF-8')."</pre>");
			$wgOut->addHTML("<form method='post' name='formQuery'>");
			$wgOut->addHTML("<input type='hidden' name='output' value='wiki'>");
			$wgOut->addHTML("<input type='hidden' name='prefix' value='".htmlentities($this->prefix(), ENT_QUOTES, 'UTF-8')." '>");
			$wgOut->addHTML("<input type='hidden' name='query' >");
			$wgOut->addHTML(wfMsg('specialsparqlquery_endpointsparql')." : <input type='text' name='endpoint' size='50' value='".$endpoint." '>");
			$wgOut->addHTML("<textarea name='queryWithoutPrefix' cols='25' rows='15'>");
			$strQuery = $queryWithoutPrefix != "" ? $queryWithoutPrefix : $this->exampleSparql(0) ;
			$wgOut->addHTML($strQuery);
			$wgOut->addHTML("</textarea>");
			$wgOut->addHTML("<br/>");
			$wgOut->addHTML("<script language='javascript' type='text/javascript' src='".$wgScriptPath."/extensions/LinkedWiki/js/bordercloud.js'></script>");
			$wgOut->addHTML("<SCRIPT>
<!-- 
function validAndSendQuery(){
	var query = document.formQuery.prefix.value + ' ' + document.formQuery.queryWithoutPrefix.value;
	if(! document.formQuery.toXML.checked){
		document.formQuery.query.value= query;
		document.formQuery.submit();
	}else{		
		window.open('".$endpoint."?query=' + escape(query.replace('\\n','')));
	}
}
function validWithJS(){
	var query = document.formQuery.prefix.value + ' ' + document.formQuery.queryWithoutPrefix.value;

	bcValidateSPARQL('".$endpoint."',query);
}
//-->
</SCRIPT>");
			$wgOut->addHTML("<input type='button' value='".wfMsg('specialsparqlquery_sendquery')."'  onClick='validAndSendQuery();' />");
			$wgOut->addHTML("<input type='button' value='(R&D) Validation (js)'  onClick='validWithJS();' />");
			$wgOut->addHTML("   Xml : <input type='checkbox'  name='toXML' />");
			$wgOut->addHTML(" </form>");
			
 			$wgOut->addHTML("<div  id='bc_div'></div>");
			$wgOut->addHTML("<div style='display: none;'>");
			$wgOut->addHTML("<img id='canvas-image-wait' src='".$wgScriptPath."/extensions/LinkedWiki/js/wait.png'></img>");
			$wgOut->addHTML("</div>");

			if ( $queryWithoutPrefix != ""){
				$sp = new Endpoint($endpoint);
				$rs = $sp->query($query);
				$errs = $sp->getErrors();
				if ($errs) {
					$wgOut->addHTML("<h1>ERROR(s)</h1>\n");
					foreach ($errs as $err) {
						if(is_array($err)){
							foreach ($err as $suberr) {
								$wgOut->addHTML("<pre>$suberr.</pre> \n");
							}
						}else{		
							if (preg_match("/bcjeton/i", $err) && ( preg_match("/insert/i", $strQuery) || preg_match("/delete/i",$strQuery))) { 
							    $wgOut->addHTML("<pre>You have not the right to write in the dataset.</pre> \n");
							}else{		
								$wgOut->addHTML("<pre>$err.</pre> \n");
							}
						}
					}
				}else{
					//						//efSparqlParserFunction_simple( $querySparqlWiki,$endpoint ,$classHeaders = '',$headers = '', $debug = null)
					$arr = efSparqlParserFunction_simple( $query, $endpoint,  '',  '',   null );
					$wgOut->addWikiText($arr[0]);

					$wgOut->addWikiText("==".wfMsg('specialsparqlquery_usethisquery')."==");
					$wgOut->addWikiText(wfMsg('specialsparqlquery_usethisquery_tutorial'));
					$wgOut->addHTML("<pre>{{#sparql:".htmlentities($query, ENT_QUOTES, 'UTF-8')."\n|endpoint=".htmlentities($endpoint, ENT_QUOTES, 'UTF-8')."}}</pre>");
				}
				
				//$wgOut->addWikiText("==".wfMsg('specialsparqlquery_linkxml')."==");				
				//$queryurl= $endpoint."?query=".urlencode( str_replace('\n','',$query));
				//$wgOut->addHTML("<a href='$queryurl'>".htmlentities( $queryurl, ENT_QUOTES, 'UTF-8')."</a>");
			}
		}
		$this->setHeaders();
	}

	function exampleSparql(){
		return "select * where { ?x ?y ?z . } LIMIT 5";
	}

	function prefix(){
		return	"PREFIX xsd:<http://www.w3.org/2001/XMLSchema#>   \n".
				"PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#>  \n".
				"PREFIX rdf:<http://www.w3.org/1999/02/22-rdf-syntax-ns#>  \n".
				"PREFIX owl:<http://www.w3.org/2002/07/owl#>  \n";
				
	}

}
