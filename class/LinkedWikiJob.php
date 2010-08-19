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
class LinkedWikiJob extends Job {

	static function doJob($title,$uri,$mode,$graph,$endpoint) {
		$params = array();
		$params["uri"]=$uri;
		$params["mode"]=$mode;
		$params["graph"]=$graph;
		$params["endpoint"]=$endpoint;	
		$job = new LinkedWikiJob($title,$params);
		$job->insert();
	}
	
	static function cleanJobs() {
		$dbw = wfGetDB( DB_MASTER );
		//$sqlSelect = "SELECT COUNT(*) as total FROM ".$dbw->tableName('job')."  WHERE `job_cmd` = 'LinkedWikiJob' ";
		$sqlDelete = "delete FROM ".$dbw->tableName('job')." WHERE `job_cmd` = 'LinkedWikiJob'";
		
//		$result = $dbw->query($sqlSelect);	
//		$total = $dbw->fetchObject( $result );		
//		$outHtml =  "<h2>Erasing " .$total->total." jobs</h2><br/>";

		$result = $dbw->query($sqlDelete);				
	}
	
	function __construct($title,$params) {
		parent::__construct( get_class($this), $title, $params);
	}

	/**
	 * Run job
	 * @return boolean success
	 */
	function run() {
		$success = true;
		try{
			if($this->params["mode"] == "update"){
				 SparqlTools::updateRDF($this->params["uri"],$this->params["mode"],$this->params["graph"],$this->params["endpoint"]);
			}elseif($this->params["mode"] == "delete"){
				 SparqlTools::deleteTriples($this->params["uri"],$this->params["mode"],$this->params["graph"],$this->params["endpoint"]);
			}
			
		}catch (Exception $e) {
			$success = false;
			$this->insert();
			$this->handleJobException($e);
		}
		return $success;
	}
	
	/*
	 * Called when the job run() crashes
	 *  Can be overriden
	 */
	function handleJobException($exception){
		error_log($exception->getMessage());
	}
	
}