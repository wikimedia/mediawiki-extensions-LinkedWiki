<?php
/**
 * @copyright (c) 2016 Bourdercloud.com
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
if (!defined('MEDIAWIKI'))
    die();

class SpecialSparqlQuery extends SpecialPage
{

    public function __construct()
    {
        parent::__construct('linkedwiki-specialsparqlquery');
    }

    public function execute($par = null)
    {
        global $wgOut, $wgScriptPath;

        $configFactory = ConfigFactory::getDefaultInstance()->makeConfig('ext-conf-linkedwiki');
        $querySparqlInSpecialPage = $configFactory->get("querySparqlInSpecialPage");
        $configDefault = $configFactory->get("endpointDefault");

        $query = isset($_REQUEST["query"]) ? stripslashes($_REQUEST["query"]) :"";
        $endpoint = isset($_REQUEST["endpoint"]) ? trim($_REQUEST["endpoint"]) :'';
        $config = isset($_REQUEST["config"]) ? trim($_REQUEST["config"]) :"";
        $idConfig = !EMPTY($config) && $_REQUEST["config"] != "Other" ? $config :"";

//         $wgOut->addHTML( isset($_REQUEST["query"])?stripslashes($_REQUEST["query"]):"Vide");
//         $wgOut->addHTML(print_r($_REQUEST,true));
//         $wgOut->addHTML(print_r($queryWithoutPrefix,true));
//         $wgOut->addHTML(print_r($query,true));
//         $wgOut->addHTML(print_r($config,true));
//         $wgOut->addHTML(print_r($output,true));

        $wgOut->addWikiText(wfMessage('linkedwiki-specialsparqlquery_mainpage')->text());
        //$wgOut->addHTML("<pre>" . htmlentities($this->prefix(), ENT_QUOTES, 'UTF-8') . "</pre>");
        $wgOut->addHTML("<form method='post' name='formQuery'>");

        $wgOut->addHTML("Choose a configuration :" . $this->printSelectConfig($config));

        $wgOut->addHTML("<br/><div id='fieldEndpoint' ");
        if (EMPTY($endpoint))
            $wgOut->addHTML("style='display: none;'");
        $wgOut->addHTML(">");
        $wgOut->addHTML(wfMessage('linkedwiki-specialsparqlquery_endpointsparql')->text() . " : <input type='text' id='endpoint' name='endpoint' size='50' value='" . $endpoint . " '></div>");
        $wgOut->addHTML("<textarea name='query' cols='25' rows='15'>");
        $strQuery = $query != "" ? $query :$querySparqlInSpecialPage;
        $wgOut->addHTML($strQuery);
        $wgOut->addHTML("</textarea>");
        $wgOut->addHTML("<br/>");
        $wgOut->addHTML("<script language='javascript' type='text/javascript' src='" . $wgScriptPath . "/extensions/LinkedWiki/js/bordercloud.js'></script>");
        $wgOut->addHTML("<input type='submit' value='" . wfMessage('linkedwiki-specialsparqlquery_sendquery')->text() . "'   />");

        $wgOut->addHTML(" </form>");
        if (!EMPTY($query)) {
            $arr = SparqlParser::simpleHTML($query, $idConfig, $endpoint, '', '', null);
            $wgOut->addHTML($arr[0]);

            $wgOut->addWikiText("==" . wfMessage('linkedwiki-specialsparqlquery_usethisquery')->text() . "==");
            $wgOut->addWikiText(wfMessage('linkedwiki-specialsparqlquery_usethisquery_tutorial')->text());


            $template = "{{#sparql:\n" . htmlentities($query, ENT_QUOTES, 'UTF-8');
            $errorMessage = "";
            if ($config == "other" && !EMPTY($endpoint)) {
                $template .= "\n|endpoint=" . htmlentities($endpoint, ENT_QUOTES, 'UTF-8');
            } elseif (!EMPTY($config) && $config != $configDefault) {
                $template .= "\n|config=" . htmlentities($config, ENT_QUOTES, 'UTF-8');
            } elseif (!EMPTY($config)) {
                //do nothing
            } else {
                $errorMessage = "An endpoint Sparql or "
                    . "a configuration by default is not found.";
            }
            $template .= "\n}}";
            if (EMPTY($errorMessage)) {
                $wgOut->addHTML("<pre>" . $template . "</pre>");
            } else {
                $wgOut->addHTML("<pre>" . $errorMessage . "</pre>");
            }
        }

        $this->setHeaders();

    }

//    protected function getGroupName() {
//        return 'pagetools';
//    }

    protected function printSelectConfig($config)
    {
        //global $wgLinkedWikiConfigDefault,$wgLinkedWikiAccessEndpoint;
        $html = "";
        // In PHP, whenever you want your config object
        $config = ConfigFactory::getDefaultInstance()->makeConfig('ext-conf-linkedwiki');
        $configDefault = $config->get("endpointDefault");
        $configs = $config->get("endpoint");

        $html .= "<select id='config' name='config' onchange='eventChangeSelectConfig()'>";
        foreach ($configs as $key => $value) {

            if ($key === "http://www.example.org") {
                continue;
            }

            $html .= '<option value="' . $key . '" ';
            if ($key === $configDefault) {
                $html .= "selected='selected'";
            }
            $html .= ">";
            $html .= $key;
            $html .= "</option>";
        }
        $html .= '<option value="other" >Other</option>';
        $html .= "</select>";


        $html .= <<<EOF
<script type="text/javascript">
function eventChangeSelectConfig() {
    var selectConfig = document.getElementById('config');
    var divFieldEndpoint = document.getElementById('fieldEndpoint');
    var inputFieldEndpoint = document.getElementById('endpoint');
    var value = selectConfig.options[selectConfig.selectedIndex].value;
    if(value != "other"){
        inputFieldEndpoint.value = "";
        divFieldEndpoint.style.display = "none";
    }else{
        divFieldEndpoint.style.display = "initial";
    }
}
</script>
EOF;

        return $html;
    }
}
