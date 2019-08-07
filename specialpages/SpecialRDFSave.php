<?php
/**
 * @copyright (c) 2018 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
 *
 *  Last version: https://github.com/BorderCloud/LinkedWiki
 *
 *
 * This work is licensed under the Creative Commons
 * Attribution-NonCommercial-ShareAlike 3.0
 * Unported License. To view a copy of this license,
 * visit http://creativecommons.org/licenses/by-nc-sa/3.0/
 * or send a letter to Creative Commons,
 * 171 Second Street, Suite 300, San Francisco,
 * California, 94105, USA.
 */

class SpecialRDFSave extends SpecialPage
{

    public function __construct()
    {
        parent::__construct('linkedwiki-specialrdfsave',"data-edit");
    }

    function getGroupName() {
        return 'linkedwiki_group';
    }

    public function execute($par = null)
    {
        global $wgOut;

        if ( !$this->userCanExecute( $this->getUser() ) ) {
            $this->displayRestrictionError();
            return;
        }

        //Default config for saving the schemas
        $config = ConfigFactory::getDefaultInstance()->makeConfig('ext-conf-linkedwiki');
        if(!$config->has("endpointSaveDataOfWiki")){
            $wgOut->addHTML("Database by default for the Wiki is not precised in the extension.json of the LinkedWiki extension.(parameter endpointSaveDataOfWiki)");
            return;
        }

        $configDefaultSaveData = $config->get("endpointSaveDataOfWiki");

        $configSaveData = new LinkedWikiConfig($configDefaultSaveData);

        $request = $this->getRequest();
        $refreshDataPage = $request->getText('refreshDataPage');
        $refreshLuaPage = $request->getText('refreshLuaPage');

        $wgOut->addHTML("<a href='?refreshDataPage=true' class=\"mw-htmlform-submit mw-ui-button mw-ui-primary mw-ui-progressive\">Import all
RDF data pages in the database</a><br/><br/>");

        $wgOut->addHTML("<a href='?refreshLuaPage=true' class=\"mw-htmlform-submit mw-ui-button mw-ui-primary mw-ui-progressive\">Refresh all
pages with lua in the database</a><br/>");

        if(!EMPTY($refreshDataPage)){
            $query = $this->querySaveRDFData($configSaveData);
            $wgOut->addHTML("<br/>Query executed : <pre>".htmlentities($query)."</pre>");
            if(! EMPTY($query)){
                $endpoint = $configSaveData->getInstanceEndpoint();
                $response = $endpoint->query($query, 'raw');
                $err = $endpoint->getErrors();
                if ($err) {
                    //$message = $config->isDebug() ? $response . print_r($err, true) :"ERROR SPARQL (see details in mode debug)";
                    $wgOut->addHTML("<br/>Error : <pre>".htmlentities(print_r($err, true))."</pre>");
                }else{
                    $wgOut->addHTML("<br/>Result : <pre>".htmlentities($response)."</pre>");
                }
            }
        }

        $dbr =wfGetDB(DB_SLAVE);
        $titleArray = TitleArray::newFromResult(
            $dbr->select( 'page',
                [ 'page_id', 'page_namespace', 'page_title' ]
            ));
        $nbPage = $titleArray->count();
        $wgOut->addHTML("<br/>Nb pages in this wiki : ".$nbPage);

        $jobQueue = JobQueueGroup::singleton()->get("SynchroniseThreadArticleLinkedDataJob");

        if(!EMPTY($refreshLuaPage)){
            while($jobQueue->pop()){}

            $wgOut->addHTML("<br/>List of pages : <br/><pre>");
            if ($nbPage) {
                foreach ( $titleArray as $title ) {
                    $jobParams = array();
                    $job = new SynchroniseThreadArticleLinkedDataJob(  $title, $jobParams );

                    $wgOut->addHTML( $title->getText()."\n" );
                    JobQueueGroup::singleton()->push( $job );
                }
            }
            $wgOut->addHTML("</pre>");
            $wgOut->addHTML("<br/>Nb Job : ".$jobQueue->getSize());
        }else{
            $wgOut->addHTML("<br/>Nb Job : ".$jobQueue->getSize());
        }

        $wgOut->addHTML("<br/><br/><br/><br/><br/>You can need to clean the database before with this query :");
        $wgOut->addHTML("<pre>".htmlentities("CLEAR GRAPH <".$configDefaultSaveData.">")."</pre>");
        $this->setHeaders();
    }

    public function querySaveRDFData($config)
    {
        $category = Title::newFromText(wfMessage( 'linkedwiki-category-rdf-page' )->inContentLanguage()->parse() )->getDBKey();

        $dbr =wfGetDB(DB_SLAVE);
        $sql = "SELECT  p.page_id AS pid, p.page_title AS title, t.old_text as text FROM page p 
INNER JOIN revision r ON p.page_latest = r.rev_id
INNER JOIN text t ON r.rev_text_id = t.old_id 
INNER JOIN categorylinks c ON c.cl_from = p.page_id 
INNER JOIN searchindex s ON s.si_page = p.page_id 
 WHERE c.cl_to='".$category."' ORDER BY p.page_title ASC";
        $res = $dbr->query($sql, __METHOD__);

        $q = "";
        while($row = $dbr->fetchObject($res)){
            $q .= $config->getQueryLoadData(Title::newFromID( $row->pid )->getFullURL().'?action=raw&export=rdf') .' ; ';
        }

        return $q;
    }
}
