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

class SpecialRDFUnit extends SpecialPage
{

    public function __construct()
    {
        parent::__construct('linkedwiki-specialrdfunit',"data-edit");
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

        if(!file_exists ("/RDFUnit")){
            $wgOut->addHTML("RDFUnit is not installed.");
            return;
        }

        $config = ConfigFactory::getDefaultInstance()->makeConfig('ext-conf-linkedwiki');
        if(!$config->has("endpointSaveDataOfWiki")){
            $wgOut->addHTML("Database by default for the Wiki is not precised in the extension.json of the LinkedWiki extension.(parameter endpointSaveDataOfWiki)");
            return;
        }

        $configDefaultSaveData = $config->get("endpointSaveDataOfWiki");
        $configSaveData = new LinkedWikiConfig($configDefaultSaveData);

        $request = $this->getRequest();
        $refresh = $request->getText('refresh');

        $uriOfDataset = $configDefaultSaveData;
        $graphOfDataset = $configDefaultSaveData;
        $graphOfDatasetFileForRDFUnit = "";
        if (preg_match("#//(.*)#", $graphOfDataset, $matches)) {
            $graphOfDatasetFileForRDFUnit = str_replace("/","_",$matches[1]);
        }

        $resultTestCase = "/RDFUnit/data/results/".$graphOfDatasetFileForRDFUnit.".shaclFullTestCaseResult.html";
        $dbTestCase = "/RDFUnit/cache/sparql/".$graphOfDatasetFileForRDFUnit.".mv.db";
        $endpointOfDataset = $configSaveData->getInstanceEndpoint()->getEndpointQuery();

        $category = "RDF_schema";


        $wgOut->addHTML("<a href='?refresh=true' class=\"mw-htmlform-submit mw-ui-button mw-ui-primary mw-ui-progressive\">Refresh test cases</a>");

        $wgOut->addWikiText("== RDF schemas in the Wiki ==");


        $wgOut->addWikiText("You can add a new RDF schema with the tag rdf with attribut contraint='shacl'.");
        $wgOut->addHTML("For example : ".htmlentities("<rdf contraint='shacl'>"));

        //make the list of schema
        $wgOut->addWikiText("List of RDF schema uses during the tests:");

        $dbr = wfGetDB(DB_SLAVE);
        $sql = "SELECT  p.page_id AS pid, p.page_title AS title, t.old_text as text FROM page p 
INNER JOIN revision r ON p.page_latest = r.rev_id
INNER JOIN text t ON r.rev_text_id = t.old_id 
INNER JOIN categorylinks c ON c.cl_from = p.page_id 
INNER JOIN searchindex s ON s.si_page = p.page_id 
 WHERE c.cl_to='".$category."' ORDER BY p.page_title ASC";
        $res = $dbr->query($sql, __METHOD__);

        $schemas = array();
        $schemasStr = array();
        while($row = $dbr->fetchObject($res))
        {
            $schemas[] = $row;
            $schemasStr[] = '"'.Title::newFromID( $row->pid )->getFullURL().'?action=raw&export=rdf"';
            $wgOut->addWikiText("* [[".$row->title."]] ");
        }

        //return $list;

        $wgOut->addWikiText("== RDFUnit command ==");

        $command = 'rdfunit -d "'.$uriOfDataset.'" -r shacl -e "'.$endpointOfDataset.'" -g "'.$graphOfDataset.'" -v -s '.implode(',',$schemasStr);


        $wgOut->addHTML("<pre>".$command);

        $wgOut->addHTML("</pre>");

        $wgOut->addWikiText("== Results ==");
        if(!EMPTY($refresh)){
            $wgOut->addHTML(self::refreshAndPrintTests($command,$dbTestCase));
        }else{
            $wgOut->addHTML(self::printTests($resultTestCase));
        }

        $this->setHeaders();
    }

    public static function refreshAndPrintTests($command,$dbTestCase)
    {
        $result = "";
        //$commandRDFUnit = "whoami";
        unlink($dbTestCase);

        $commandRDFUnit =  "bin/".$command ;
        chdir('/RDFUnit');
        $file = exec($commandRDFUnit ." 2>&1", $retval);
        $textRetval = print_r($retval,true);

        if (preg_match("#\[INFO  ValidateCLI\] Results stored in: (.*)\.\*#", $textRetval, $matches)) {
            $result = self::printTests("/RDFUnit/" . $matches[1] . ".html");
        }elseif (preg_match("#.*ERROR.*#", $textRetval)) {
            $result = "<pre>".$textRetval."</pre>";
        }else{
            $result = "<pre>".$textRetval."</pre>";
        }

        return $result;
    }

    public static function   printTests( $file)
    {
        $result ="NO RESULTS";
        if(file_exists($file)){
            if (preg_match("#<body>(.*)</body>#s", file_get_contents($file), $matchesbody)) {
                $result = $matchesbody[1];
            }
        }
        return $result;
    }
}