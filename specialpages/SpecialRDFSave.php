<?php
/**
 * @copyright (c) 2017 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link http://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-nc-sa V3.0
 *
 *  Last version : http://github.com/BorderCloud/LinkedWiki
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
if (!defined('MEDIAWIKI')) die();

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

        //Default config for saving the shemas
        $config = ConfigFactory::getDefaultInstance()->makeConfig('ext-conf-linkedwiki');
        if(!$config->has("endpointSaveDataOfWiki")){
            $wgOut->addHTML("Database by default for the Wiki is not precised in the extension.json of the LinkedWiki extension.(parameter endpointSaveDataOfWiki)");
            return;
        }

        $configDefaultSaveData = $config->get("endpointSaveDataOfWiki");

        $configSaveData = new LinkedWikiConfig($configDefaultSaveData);


        $request = $this->getRequest();
        $refreshData = $request->getText('refreshData');

        $wgOut->addHTML("<a href='?refreshData=true' class=\"mw-htmlform-submit mw-ui-button mw-ui-primary mw-ui-progressive\">Import all
RDF data in the database</a>");


        if(!EMPTY($refreshData)){
            $query = $this->querySaveData($configSaveData);

            $wgOut->addHTML("<br/>Query executed : <pre>".htmlentities($query)."</pre>");

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

        $wgOut->addHTML("<br/>You can need to clean the database before with this query :");
        $wgOut->addHTML("<pre>".htmlentities("CLEAR GRAPH <".$configDefaultSaveData.">")."</pre>");
        $this->setHeaders();
    }

    public function querySaveData($config)
    {
        global $wgOut;

        $category = "RDF_page";

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