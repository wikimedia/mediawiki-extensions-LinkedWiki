<?php
/**
 * @version 0.1.0.0
 * @package Bourdercloud/linkedwiki
 * @copyright (c) 2010 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link http://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-nc-sa V3.0
 
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

class SpecialSparqlEndpoint extends SpecialPage {

	public function __construct() {
		parent::__construct( 'SpecialSparqlEndpoint' );
		wfLoadExtensionMessages('SpecialSparqlEndpoint');
	}

	public function execute($par = null) {
		global $wgOut,$wgLinkedWikiLocalEndPoint,$wgLinkedWikiEndPoint,$wgLinkedWikiGraphWiki;
		$queryWithoutPrefix = isset($_REQUEST["queryWithoutPrefix"])?stripslashes($_REQUEST["queryWithoutPrefix"]):"";
		$query = isset($_REQUEST["query"])?stripslashes($_REQUEST["query"]):"";
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
				$wgOut->addHTML("<h2>".wfMsg('specialsparqlendpoint_error_title_exists_yet')."</h2> \n");
				$wgOut->addHTML();
			}else{
				$this->savePage($titleRequest, $this->template($query,$description));
				$wgOut->addWikiText(wfMsg('specialsparqlendpoint_your_query_saved_here')."[[$titleRequest]]");
			}
		}
			
		if ( $query == "" || $output == "save" || $output == "wiki" ) {
			$wgOut->addWikiText(wfMsg('specialsparqlendpoint_mainpage'));
			$wgOut->addHTML("<pre>".htmlentities($this->prefix(), ENT_QUOTES, 'UTF-8')."</pre>");
			$wgOut->addHTML("<form method='post' name='formQuery'>");
			$wgOut->addHTML("<input type='hidden' name='output' value='wiki'>");
			$wgOut->addHTML("<input type='hidden' name='prefix' value='".htmlentities($this->prefix(), ENT_QUOTES, 'UTF-8')." '>");
			$wgOut->addHTML("<input type='hidden' name='query' >");
			$wgOut->addHTML("<textarea name='queryWithoutPrefix' cols='25' rows='15'>");
			$strQuery = $queryWithoutPrefix != "" ? $queryWithoutPrefix : $this->exampleSparql(0) ;
			$wgOut->addHTML($strQuery);
			$wgOut->addHTML("</textarea>");
			$wgOut->addHTML("<br/>");
			$wgOut->addHTML("<SCRIPT>
<!-- 
function validAndSendQuery(){
	var query = document.formQuery.prefix.value + ' ' + document.formQuery.queryWithoutPrefix.value;
	if(! document.formQuery.toXML.checked){
		document.formQuery.query.value= query;
		document.formQuery.submit();
	}else{		
		window.open('".$wgLinkedWikiLocalEndPoint."?query=' + escape(query.replace('\\n','')));
	}
}
//-->
</SCRIPT>");
			$wgOut->addHTML("<input type='button' value='".wfMsg('specialsparqlendpoint_sendquery')."'  onClick='validAndSendQuery();' />");
			$wgOut->addHTML("   Xml : <input type='checkbox'  name='toXML' />");
			$wgOut->addHTML("</form>");

			if ( $queryWithoutPrefix != ""){
				$sp = new FourStore_StorePlus($wgLinkedWikiEndPoint);
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
							$wgOut->addHTML("<pre>$err.</pre> \n");
						}
					}
				}else{
					//						//efSparqlParserFunction_simple( $querySparqlWiki,$endpoint ,$classHeaders = '',$headers = '', $debug = null)
					$arr = efSparqlParserFunction_simple( $query, $wgLinkedWikiEndPoint,  '',  '',   null );
					$wgOut->addWikiText($arr[0]);

					$wgOut->addWikiText("==".wfMsg('specialsparqlendpoint_usethisquery')."==");
					$wgOut->addWikiText(wfMsg('specialsparqlendpoint_usethisquery_tutorial'));
					$wgOut->addHTML("<pre>{{#sparql:".htmlentities($query, ENT_QUOTES, 'UTF-8')."}}</pre>");
				}
				$wgOut->addWikiText("==".wfMsg('specialsparqlendpoint_sharethisquery')."==");
				$wgOut->addHTML("<form method='post' name='formSave'>");
				$wgOut->addHTML("<input type='hidden' name='output' value='save'>");
				$wgOut->addHTML("Votre requête a besoin d'un titre pour être enregistrée (exemple : Comment obtenir les films de science-fictions en cours de diffusion ?)<br/>");
				$wgOut->addHTML("<input type='text' name='titleRequest' size='100' value='".htmlentities($titleRequest, ENT_QUOTES, 'UTF-8')."'/>");
				$wgOut->addHTML("<textarea name='description' >$description</textarea>");
				$wgOut->addHTML("<input type='hidden' name='prefix' value='".htmlentities($this->prefix(), ENT_QUOTES, 'UTF-8')." '>");
				$wgOut->addHTML("<input type='hidden' name='queryWithoutPrefix' value='".htmlentities($queryWithoutPrefix, ENT_QUOTES, 'UTF-8')." ' >");
				$wgOut->addHTML("<input type='hidden' name='query' value='".htmlentities($query, ENT_QUOTES, 'UTF-8')." ' >");
				$wgOut->addHTML("<input type='submit' value='Envoyer' />");
				$wgOut->addHTML("</form>");
					
				$wgOut->addWikiText("==".wfMsg('specialsparqlendpoint_linkxml')."==");
				$queryurl= $wgLinkedWikiLocalEndPoint."?query=".urlencode( str_replace('\n','',$query));
				$wgOut->addHTML("<a href='$queryurl'>".htmlentities( $queryurl, ENT_QUOTES, 'UTF-8')."</a>");
			}
		}else {
			//$wgOut->addHTML(print_r($request,true));

			$wgOut->disable();
			header("content-type: application/xml");
			$s = new FourStore_Store($wgLinkedWikiEndPoint);
			echo $s->queryRead($query);
		}
		$this->setHeaders();
	}

	function exampleSparql($index = 0){
		return "select * { ?x ?y ?z . } LIMIT 5";
	}

	function prefix(){
		global $wgContLang;
		$resolver = Title::makeTitle( NS_SPECIAL, 'URIResolver' );
		$smwgNamespace = $resolver->getFullURL() . '/';
		return	"PREFIX xsd:<http://www.w3.org/2001/XMLSchema#>   \n".
				"PREFIX rdfs:<http://www.w3.org/2000/01/rdf-schema#>  \n".
				"PREFIX rdf:<http://www.w3.org/1999/02/22-rdf-syntax-ns#>  \n".
				"PREFIX a:<".SparqlTools::decodeURItoIRI($smwgNamespace).">  \n".
				"PREFIX cat:<".SparqlTools::decodeURItoIRI($smwgNamespace.SMWExporter::encodeURI($wgContLang->getNsText( NS_CATEGORY ).":")).">  \n".
				"PREFIX prop:<".SparqlTools::decodeURItoIRI($smwgNamespace.SMWExporter::encodeURI($wgContLang->getNsText( SMW_NS_PROPERTY ).":")).">  \n";
	}


	function pageExists($title){
		$articleTitle = Title::newFromText($title);
		$ex = false;
		if($articleTitle instanceof Title ){
			$ex = $articleTitle->exists();
		}
		return $ex;
	}

	function savePage($title, $content, $doNotUpdate = true, $summary = "New request SPARQL."){
		global  $wgUser;

		$flags = EDIT_NEW;
		$titleObj = Title::newFromtext($title);

		$article = new Article($titleObj);
		$flags = $flags|EDIT_DEFER_UPDATES | EDIT_AUTOSUMMARY;
		$status = $article->doEdit( $content, $summary, $flags,false,$wgUser);
		$result = true;
		if(!$status->isOK()){
			$result = $status->getErrorsArray();
		}
		return $result;
	}
		
	function template($query,$description){
		$str ="=".wfMsg('specialsparqlendpoint_querydescription')."=\n";
		$str .="$description \n\n";
		$str .="=".wfMsg('specialsparqlendpoint_querysparql')."= \n";	
		$str .="<pre>".htmlentities($query)."</pre>\n\n";
		$str .="=".wfMsg('specialsparqlendpoint_queryresult')."=\n";
		$str .="{{#sparql: $query }}\n";
		$str .="=".wfMsg('specialsparqlendpoint_querytutorial')."=\n";
		$str .= wfMsg('specialsparqlendpoint_querytutorial_description');
		$str .="<pre>".htmlentities("{{#sparql: $query }}", ENT_QUOTES, 'UTF-8')."</pre>\n\n";
			
		$str .= "=".wfMsg('specialsparqlendpoint_querySeemore')."=\n";
		$str .= wfMsg('specialsparqlendpoint_querySeemore_link')."\n\n\n";
		$str .="[[Category:".wfMsg('specialsparqlendpoint_categorysparqlquery')."]]";
		return $str;
	}
}
