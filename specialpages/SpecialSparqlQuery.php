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
    public function getGroupName() {
        return 'linkedwiki_group';
    }

    public function execute($par = null)
    {
        //https://www.mediawiki.org/wiki/OOjs_UI/Using_OOjs_UI_in_MediaWiki
        global $wgOut, $wgScriptPath;


        $configFactory = ConfigFactory::getDefaultInstance()->makeConfig('ext-conf-linkedwiki');
        $querySparqlInSpecialPage = $configFactory->get("querySparqlInSpecialPage");
        $configDefault = $configFactory->get("endpointDefault");

        $query = isset($_REQUEST["query"]) ? stripslashes($_REQUEST["query"]) :"";
        $endpoint = isset($_REQUEST["endpoint"]) ? trim($_REQUEST["endpoint"]) :'';
        $config = isset($_REQUEST["config"]) ? trim($_REQUEST["config"]) :"";
        $idConfig = !EMPTY($config) && $_REQUEST["config"] != "Other" ? $config :"";
        $radioCache = isset($_REQUEST["radio"]) ? trim($_REQUEST["radio"]) :'sgvizler2';

//         $wgOut->addHTML( isset($_REQUEST["query"])?stripslashes($_REQUEST["query"]):"Vide");
//         $wgOut->addHTML(print_r($_REQUEST,true));
//         $wgOut->addHTML(print_r($queryWithoutPrefix,true));
//         $wgOut->addHTML(print_r($query,true));
//         $wgOut->addHTML(print_r($config,true));
//         $wgOut->addHTML(print_r($output,true));

        // Module by name
        //mw.loader.load( 'jquery' );

        $wgOut->addModules('ext.LinkedWiki.SpecialSparqlQuery');
        //test
        //$wgOut->addHTML(file_get_contents(__dir__ . "/../js/SparqlEditor/form.html"));

        /////////////////////////////
        $wgOut->addWikiText(wfMessage('linkedwiki-specialsparqlquery_mainpage')->text());
        //$wgOut->addHTML("<pre>" . htmlentities($this->prefix(), ENT_QUOTES, 'UTF-8') . "</pre>");
        $wgOut->addHTML("<form method='post' name='formQuery' id='formSparqlQuery'>");


        $wgOut->addHTML("<div class=\"form-group row\">
            <label for=\"endpoint\" class=\"col-2 col-form-label\">".wfMessage('linkedwiki-specialsparqlquery_chooseaconfiguration')->text()."</label>
            <div class=\"col-10\">");
        $wgOut->addHTML($this->printSelectConfig($config));
        $wgOut->addHTML("</div>
        </div>");

        $wgOut->addHTML("<div class=\"form-group row\" id='fieldEndpoint' ");

        if (EMPTY($endpoint))
            $wgOut->addHTML("style='display: none;'");

        $wgOut->addHTML(">
            <label for=\"endpointOther\" class=\"col-2 col-form-label\">");
        $wgOut->addHTML(wfMessage('linkedwiki-specialsparqlquery_endpointsparql')->text() . "</label>
            <div class=\"col-10\">
                <input class=\"form-control\" type=\"url\" value=\"https://query.wikidata.org/sparql\" id=\"endpointOther\"/>
            </div>
        </div>");

        $wgOut->addHTML("
        <div class=\"form-group row\">
            <label for=\"query\" class=\"col-2 col-form-label\">Query</label>
            <div class=\"col-10\">
        <textarea class=\"form-control\" id=\"query\" name='query'  rows=\"8\"  lang=\"sparql\">");

        $strQuery = $query != "" ? $query :$querySparqlInSpecialPage;
        $wgOut->addHTML($strQuery);

        $wgOut->addHTML("</textarea>
            </div>
        </div>
        ");

        $checkedPhp = $radioCache == "php" ? "checked" : "";
        $checkedSgvizler= $radioCache == "sgvizler2" ? "checked" : "";
        $wgOut->addHTML("<div class=\"form-group row\">
            <label for=\"endpoint\" class=\"col-2 col-form-label\"></label>
            <div class=\"col-10\">
                <label class=\"custom-control custom-radio\">
                    <input id=\"radio1\" type=\"radio\"  name=\"radio\" 
                    aria-label=\"Charts of Sgvizler2 (wihtout cache and only for public data)\"
                           class=\"custom-control-input\"
                           value='sgvizler2' ". $checkedSgvizler .">
                    <span class=\"custom-control-indicator\"></span>
                    <span class=\"custom-control-description\">Javascript charts of 
                        <a href=\"https://bordercloud.github.io/sgvizler2\">Svizgler2</a> (wihtout cache and only for public data)</span>
                </label>
                <label class=\"custom-control custom-radio\">
                    <input id=\"radio2\" type=\"radio\" name=\"radio\" aria-label=\"With cache, table only (PHP)\"
                           class=\"custom-control-input\"
                           value='php' ". $checkedPhp .">
                    <span class=\"custom-control-indicator\"></span>
                    <span class=\"custom-control-description\">Table only with cache and for public data</span>
                </label>
            </div>
        </div>
        <div id=\"sgvizlerInputsForm\" ");

        if ($checkedPhp == "php")
            $wgOut->addHTML("style='display: none;'");

        $wgOut->addHTML(">
            <div class=\"form-group row\">
                <label for=\"options\" class=\"col-2 col-form-label\">Options</label>
                <div class=\"col-10\">
                    <input class=\"form-control\" type=\"input\" id=\"options\" value='width=100%!height=500px'>
                </div>
            </div>
            <div class=\"form-group row\">
                <label for=\"logsLevel\" class=\"col-2 col-form-label\">Logs level</label>
                <div class=\"col-10\">
                    <select class=\"custom-select\" id=\"logsLevel\">
                        <option value=\"0\">0</option>
                        <option value=\"1\">1</option>
                        <option value=\"2\" selected>2</option>
                    </select>
                </div>
            </div>
            <div class=\"form-group row\">
                <label for=\"logsLevel\" class=\"col-2 col-form-label\">Charts</label>
                <div class=\"col-10\">
                    <select class=\"selectpicker selectchart\" id=\"chart\"></select>
                    <button id=\"seeDoc\" type=\"button\" class=\"btn btn-secondary secondary\">See the doc</button>
                </div>
            </div>
        </div>");

        $wgOut->addHTML("
        <div style=\"height: 50px;\">
            <div class=\"pull-right\" >
                <button id=\"execQuery\" type=\"button\" class=\"btn btn-primary\">" . wfMessage('linkedwiki-specialsparqlquery_sendquery')->text() . "</button>
            </div>
        </div>");

//        $wgOut->addHTML(wfMessage('linkedwiki-specialsparqlquery_endpointsparql')->text() . " : <input class=\"form-control\" type=\"url\" value=\"https://query.wikidata.org/sparql\" id='endpoint' name='endpoint' size='50' value='" . $endpoint . " '></div>");
//        $wgOut->addHTML("<textarea name='query' cols='25' rows='15'>");
//        $wgOut->addHTML("</textarea>");
//        $wgOut->addHTML("<br/>");
//        $wgOut->addHTML("<script language='javascript' type='text/javascript' src='" . $wgScriptPath . "/extensions/LinkedWiki/js/bordercloud.js'></script>");
//        $wgOut->addHTML("<input type='submit' value='" . wfMessage('linkedwiki-specialsparqlquery_sendquery')->text() . "'   />");


        $wgOut->addHTML(" </form>
 <ul class=\"nav nav-tabs\" role=\"tablist\" id='tabSparqlQuery'>
    <li class=\"nav-item\">
        <a class=\"nav-link active\" data-toggle=\"tab\" href=\"#resultTab\" role=\"tab\">Result</a>
    </li>
    <li class=\"nav-item\">
        <a class=\"nav-link\" data-toggle=\"tab\" href=\"#htmlTab\" role=\"tab\">" . wfMessage('linkedwiki-specialsparqlquery_usethisquery')->text() . "</a>
    </li>
</ul>
 <div class=\"tab-content\">
    <div class=\"tab-pane active\" id=\"resultTab\" role=\"tabpanel\">
        <div id=\"example\" style=\"padding: 25px;\"><div id=\"result\">");

        if (!EMPTY($query)) {
            $arr = SparqlParser::simpleHTML($query, $idConfig, $endpoint, '', '', null);
            $wgOut->addHTML($arr[0]);
        }
        $wgOut->addHTML("</div></div>
    </div>
    <div class=\"tab-pane\" id=\"htmlTab\" role=\"tabpanel\">
        <div class=\"bg-faded\" style=\"padding: 25px;\">");
        $wgOut->addWikiText(wfMessage('linkedwiki-specialsparqlquery_usethisquery_tutorial')->text());

        $wgOut->addHTML("<pre lang=\"html\" id=\"consoleWiki\">");
        if (!EMPTY($query)) {
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
                $wgOut->addHTML($template);
            } else {
                $wgOut->addHTML($errorMessage);
            }
        }
        $wgOut->addHTML("</pre></div>
    </div>
</div>");

        $this->setHeaders();
    }

    protected function printSelectConfig($configIri)
    {
        //global $wgLinkedWikiConfigDefault,$wgLinkedWikiAccessEndpoint;
        $html = "";
        // In PHP, whenever you want your config object
        $config = ConfigFactory::getDefaultInstance()->makeConfig('ext-conf-linkedwiki');

        $configs = $config->get("endpoint");

        $html .= "<select id='config' name='config'  class=\"form-control\">";
        foreach ($configs as $key => $value) {

            if ($key === "http://www.example.org") {
                continue;
            }

            $html .= '<option value="' . $key . '" ';
            if ($key === $configIri) {
                $html .= "selected='selected' ";
            }
            if (isset($value["login"])){
                $html .= "credential='true' ";
            }else{
                $html .= "credential='false' ";
                if (isset($value["endpointRead"])){
                    $html .= "endpoint='" . $value["endpointRead"] . "' ";
                }

                if (isset($value["HTTPMethodForRead"])){
                    $html .= "method='" . $value["HTTPMethodForRead"] . "' ";
                }

                if (isset($value["nameParameterRead"])){
                    $html .= "parameter='" . $value["nameParameterRead"] . "' ";
                }
            }
            //print_r($value);
            $html .= ">";
            $html .= $key;
            $html .= "</option>";
            //print_r($html);
        }
        $html .= '<option value="other" >Other</option>';
        $html .= "</select>";
        return $html;
    }
}
