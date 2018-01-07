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

class SparqlParser
{
    public static function render($parser)
    {
        global $wgOut;

        $configFactory = ConfigFactory::getDefaultInstance()->makeConfig('ext-conf-linkedwiki');
        $configDefault = $configFactory->get("endpointDefault");

        $result = null;

        $wgOut->addModules('ext.LinkedWiki.table2CSV');

        $args = func_get_args(); // $parser, $param1 = '', $param2 = ''
        $countArgs = count($args);
        $query = isset($args[1]) ? urldecode($args[1]) :"";
        $vars = array();
        for ($i = 2; $i < $countArgs; $i++) {
            if (preg_match_all('#^([^= ]+) *= *(.*)$#i', $args[$i], $match)) {
                $vars[$match[1][0]] = $match[2][0];
            }
        }

        if ($query != "") {

            $query = ToolsParser::parserQuery($query, $parser);

            $config = isset($vars["config"]) ? $vars["config"] :$configDefault;
            $endpoint = isset($vars["endpoint"]) ? $vars["endpoint"] :null;
            $classHeaders = isset($vars["classHeaders"]) ? $vars["classHeaders"] :'';
            $headers = isset($vars["headers"]) ? $vars["headers"] :'';
            $templates = isset($vars["templates"]) ? $vars["templates"] :'';
            $debug = isset($vars["debug"]) ? $vars["debug"] :null;
            $cache = isset($vars["cache"]) ? $vars["cache"] :"yes";
            $templateBare = isset($vars["templateBare"]) ? $vars["templateBare"] :'';
            $footer = isset($vars["footer"]) ? $vars["footer"] :'';

            $chart = isset($vars["chart"]) ? $vars["chart"] : '';
            $options = isset($vars["options"]) ? $vars["options"] : '';
            $log = isset($vars["log"]) ? $vars["log"] : '';


            if(!EMPTY($chart)){
                // renderer with sgvizler2
                return SparqlParser::sgvizler2Container(
                    $query,
                    $config,
                    $endpoint,
                    $chart,
                    $options,
                    $log,
                    $debug);
            }else{
                // renderer with php
                if ($cache == "no") {
                    $parser->disableCache();
                }
                if ($templateBare == "tableCell") {
                   return SparqlParser::tableCell($query, $config, $endpoint, $debug);
                } else {
                    if ($templates != "") {
                        return SparqlParser::simpleHTMLWithTemplate($query, $config, $endpoint,
                            $classHeaders, $headers, $templates, $footer, $debug);
                    } else {
                        return SparqlParser::simpleHTML($query, $config, $endpoint,
                            $classHeaders, $headers, $footer, $debug);
                    }
                }
            }
        } else {
            $parser->disableCache();
            $result = "'''Error #sparql : Incorrect argument (usage : #sparql: SELECT * WHERE {?a ?b ?c .} )'''";
        }

        return $result;
    }

    public static function sgvizler2Container(
        $querySparqlWiki,
        $config,
        $endpoint,
        $chart,
        $options = '',
        $log = '',
        $debug = null)
    {
        global $wgOut ;
        $str = "";

        $methodSg = "";
        $parameterSg = "";
        $endpointSg = "";
        $logSg = $log;

//        $specialC = array("&#39;","&nbsp;");
//        $replaceC = array("'", " ");
//        $querySparql = str_replace($specialC, $replaceC, $querySparqlWiki);

        if(EMPTY($config) && EMPTY($endpoint)){
            return array("<pre>Where is the Endpoint or the configuration ?</pre>", 'noparse' => true, 'isHTML' =>
                false);

        }else if (!EMPTY($endpoint)){
            $endpointSg = $endpoint;
        }else if (!EMPTY($config)){
            $configuration = ConfigFactory::getDefaultInstance()->makeConfig('ext-conf-linkedwiki');
            $configs = $configuration->get("endpoint");
            $configEndpoint = isset($configs[$config])? $configs[$config] : null;
            if(!EMPTY($configEndpoint)){
                $endpointSg = $configEndpoint["endpointRead"];
                $methodSg = isset($configEndpoint["HTTPMethodForRead"])? $configEndpoint["HTTPMethodForRead"] : "GET";
                $parameterSg = isset($configEndpoint["nameParameterRead"])? $configEndpoint["nameParameterRead"] : "query";
            }
        }

        if($debug){
            $logSg = 2;
        }

        //echo $querySparql;
        $uniqId = "ID". uniqid();
        $str = "<div id='".$uniqId."' " ;

// Affiche : Un 'apostrophe' en &lt;strong&gt;gras&lt;/strong&gt;

        $str .="data-sgvizler-query='" .  htmlentities($querySparqlWiki, ENT_QUOTES, "UTF-8") . "' \n" .

        "data-sgvizler-endpoint=\"" . $endpointSg . "\" \n" .
        "data-sgvizler-chart=\"" . $chart . "\" \n" ;

        if(!EMPTY($options)){
            $str .= "data-sgvizler-chart-options=\"" . $options . "\" \n";
        }
        if(!EMPTY($logSg)){
            $str .= "data-sgvizler-log=\"" . $logSg . "\" \n";
        }
        if(!EMPTY($methodSg) && ($methodSg == "GET" || $methodSg == "POST") ){
            $str .= "data-sgvizler-method=\"" .  $methodSg . "\" \n";
        }
        if(!EMPTY($parameterSg) && $parameterSg != "query" ){
            $str .= "data-sgvizler-endpoint-query-parameter=\"" . $parameterSg . "\" \n";
        }
        $str .=  "></div>";
        //echo $str;

        return array($str, 'isChildObj' => true);
    }

    public static function simpleHTMLWithTemplate(
        $querySparqlWiki,
        $config,
        $endpoint,
        $classHeaders = '',
        $headers = '',
        $templates = '',
        $footer = '',
        $debug = null)
    {
        $specialC = array("&#39;");
        $replaceC = array("'");
        $querySparql = str_replace($specialC, $replaceC, $querySparqlWiki);

        $arrEndpoint = ToolsParser::newEndpoint($config, $endpoint);
        if ($arrEndpoint["endpoint"] == null) {
            return array("<pre>" . $arrEndpoint["errorMessage"] . "</pre>", 'noparse' => true, 'isHTML' => false);
        }
        $sp = $arrEndpoint["endpoint"];

        $rs = $sp->query($querySparqlWiki);
        $errs = $sp->getErrors();
        if ($errs) {
            $strerr = "";
            foreach ($errs as $err) {
                $strerr .= "'''Error #sparql :" . $err . "'''<br/>";
            }
            return $strerr;
        }
        $variables = $rs['result']['variables'];
        $TableFormatTemplates = explode(",", $templates);

        $lignegrise = false;
        $str = "{| class=\"wikitable sortable\" \n";
        if ($headers != '') {
            $TableTitleHeaders = explode(",", $headers);
            $TableClassHeaders = explode(",", $classHeaders);
            for ($i = 0; $i < count($TableClassHeaders); $i++) {
                if (!isset($TableClassHeaders[$i]) || $TableClassHeaders[$i] == "")
                    $classStr = "";
                else
                    $classStr = $TableClassHeaders[$i] . "|";
                $TableTitleHeaders[$i] = $classStr . $TableTitleHeaders[$i];
            }

            $str .= "|- \n";
            $str .= "!" . implode("!!", $TableTitleHeaders);
            $str .= "\n";
        }

        $arrayParameters = array();
        foreach ($rs['result']['rows'] as $row) {
            $str .= "|- ";
            if ($lignegrise)
                $str .= "bgcolor=\"#f5f5f5\"";
            $lignegrise = !$lignegrise;
            $str .= "\n";
            $separateur = "|";
            unset($arrayParameters);
            foreach ($variables as $variable) {
                // START ADD BY DOUG to support optional variables in query
                if (!isset($row[$variable])) {
                    continue;
                }
                //END ADD BY DOUG
                if ($row[$variable . " type"] == "uri") {
                    $arrayParameters[] = $variable . " = " . SparqlParser::uri2Link($row[$variable], true);
                } else {
                    if (isset($variable)) {
                        $arrayParameters[] = $variable . " = " . $row[$variable];
                    }
                }
            }
            foreach ($TableFormatTemplates as $key => $TableFormatTemplate) {
                if (empty($TableFormatTemplate)) {
                    $str .= $separateur . $row[$variables[$key]];
                } else {
                    $str .= $separateur . "{{" . $TableFormatTemplate . "|" . implode("|", $arrayParameters) . "}}";
                }
                $separateur = "||";
            }
            $str .= "\n";
        }

        if ($footer != "NO"  && $footer != "no") {
            $str .= "|- style=\"font-size:80%\" align=\"right\"\n";
            $str .= "| colspan=\"" . count($TableFormatTemplates) . "\"|" . SparqlParser::footer($rs['query_time'], $querySparqlWiki, $config, $endpoint, $classHeaders, $headers) . "\n";
        }

        $str .= "|}\n";

        if ($debug != null && ($debug == "YES" || $debug == "yes")) {
            $str .= "INPUT WIKI : " . $querySparqlWiki . "\n";
            $str .= "Query : " . $querySparql . "\n";
            $str .= print_r($arrayParameters, true);
            $str .= print_r($rs, true);
            return array("<pre>" . $str . "</pre>", 'noparse' => true, 'isHTML' => true);
        }
        return array($str, 'noparse' => false, 'isHTML' => false);
    }

    public static function simpleHTML(
        $querySparqlWiki,
        $config, $endpoint,
        $classHeaders = '',
        $headers = '',
        $footer = '',
        $debug = null)
    {
        $specialC = array("&#39;");
        $replaceC = array("'");
        $querySparql = str_replace($specialC, $replaceC, $querySparqlWiki);

        $arrEndpoint = ToolsParser::newEndpoint($config, $endpoint);
        if ($arrEndpoint["endpoint"] == null) {
            return array("<pre>" . $arrEndpoint["errorMessage"] . "</pre>", 'noparse' => true, 'isHTML' => false);
        }
        $sp = $arrEndpoint["endpoint"];

        $rs = $sp->query($querySparqlWiki);
        $errs = $sp->getErrors();
        if ($errs) {
            $strerr = "";
            foreach ($errs as $err) {
                $strerr .= "'''Error #sparql :" . $err . "'''<br/>";
            }
            return $strerr;
        }

        $lignegrise = false;
        $variables = $rs['result']['variables'];
        $str = "<table class='wikitable sortable'>\n";
        if ($headers != '') {
            $TableTitleHeaders = explode(",", $headers);
            $TableClassHeaders = explode(",", $classHeaders);
            for ($i = 0; $i < count($TableTitleHeaders); $i++) {
                if (!isset($TableClassHeaders[$i]) || $TableClassHeaders[$i] == "") {
                    $classStr = "";
                } else {
                    $classStr = " class=\"" . $TableClassHeaders[$i] . "\"";
                }
                $TableTitleHeaders[$i] = "<th" . $classStr . ">" . $TableTitleHeaders[$i] . "</th>";
            }
            $str .= "<tr>";
            $str .= implode("\n", $TableTitleHeaders);
            $str .= "</tr>\n";
        } else {
            $TableClassHeaders = explode(",", $classHeaders);
            for ($i = 0; $i < count($variables); $i++) {
                if (!isset($TableClassHeaders[$i]) || $TableClassHeaders[$i] == "")
                    $classStr = "";
                else
                    $classStr = " class=\"" . $TableClassHeaders[$i] . "\"";
                $TableTitleHeaders[$i] = "<th" . $classStr . ">" . $variables[$i] . "</th>";
            }

            $str .= "<tr>\n";
            $str .= implode("\n", $TableTitleHeaders);
            $str .= "</tr>\n";
        }

        foreach ($rs['result']['rows'] as $row) {

            $str .= "<tr";
            if ($lignegrise)
                $str .= " bgcolor=\"#f5f5f5\" ";
            $str .= ">\n";
            $lignegrise = !$lignegrise;


            foreach ($variables as $variable) {
                $str .= "<td>";

                if ($row[$variable . " type"] == "uri") {
                    $str .= "<a href='" . $row[$variable] . "'>" . $row[$variable] . "</a>";
                } else {
                    $str .= $row[$variable];
                }
                $str .= "</td>\n";
            }
            $str .= "</tr>\n";
        }

        if ($footer != "NO" && $footer != "no") {
            $str .= "<tr style=\"font-size:80%\" align=\"right\">\n";
            $str .= "<td colspan=\"" . count($variables) . "\">" . SparqlParser::footerHTML($rs['query_time'], $querySparqlWiki, $config, $endpoint, $classHeaders, $headers) . "</td>\n";
            $str .= "</tr>\n";
        }

        $str .= "</table>\n";

        if ($debug != null && ($debug == "YES" || $debug == "yes" || $debug == "1")) {
            $str .= "INPUT WIKI: \n" . $querySparqlWiki . "\n";
            $str .= "QUERY : " . $querySparql . "\n";
            $str .= print_r($rs, true);
            return array("<pre>" . htmlspecialchars($str) . "</pre>", 'noparse' => true, 'isHTML' => false);
        }

        return array($str, 'noparse' => false, 'isHTML' => true);
    }

    public static function tableCell($querySparqlWiki, $config, $endpoint, $debug = null)
    {
        $specialC = array("&#39;");
        $replaceC = array("'");
        $querySparql = str_replace($specialC, $replaceC, $querySparqlWiki);

        $arrEndpoint = ToolsParser::newEndpoint($config, $endpoint);
        if ($arrEndpoint["endpoint"] == null) {
            return array("<pre>" . $arrEndpoint["errorMessage"] . "</pre>", 'noparse' => true, 'isHTML' => false);
        }
        $sp = $arrEndpoint["endpoint"];
        $rs = $sp->query($querySparqlWiki);
        $errs = $sp->getErrors();
        if ($errs) {
            $strerr = "";
            foreach ($errs as $err) {
                $strerr .= "'''Error #sparql :" . $err . "'''<br/>";
            }
            return $strerr;
        }

        $variables = $rs['result']['variables'];
        $str = "";
        foreach ($rs['result']['rows'] as $row) {
            $str .= "\n";
            $separateur = "| ";
            foreach ($variables as $variable) {
                // START ADD BY DOUG to support optional variables in query
                if (!isset($row[$variable])) {
                    continue;
                }
                //END ADD BY DOUG
                if ($row[$variable . " type"] == "uri") {
                    $str .= $separateur . SparqlParser::uri2Link($row[$variable]);
                } else {
                    $str .= $separateur . $row[$variable];
                }
                $separateur = " || ";
            }
            $str .= "\n|- \n";
        }

        if ($debug != null && ($debug == "YES" || $debug == "yes" || $debug == "1")) {
            $str .= "INPUT WIKI: \n" . $querySparqlWiki . "\n";
            $str .= "QUERY : " . $querySparql . "\n";
            $str .= print_r($rs, true);
            return array("<pre>" . htmlspecialchars($str) . "</pre>", 'noparse' => true, 'isHTML' => false);
        }

        return array($str, 'noparse' => false, 'isHTML' => false);
    }

    public static function footer(
        $duration,
        $querySparqlWiki,
        $config,
        $endpoint,
        $classHeaders = '',
        $headers = '')
    {
        $today = date(wfMessage('linkedwiki-date')->text());
        return $today . " -- [{{fullurl:{{FULLPAGENAME}}|action=purge}} " . wfMessage('linkedwiki-refresh')->text() . "] -- " .
        wfMessage('linkedwiki-duration')->text() . " :" .
        round($duration, 3) . "s";
    }

    public static function footerHTML(
        $duration,
        $querySparqlWiki,
        $config,
        $endpoint,
        $classHeaders = '',
        $headers = '')
    {
        global $wgRequest;
        $today = date(wfMessage('linkedwiki-date')->text());

        $subject = $wgRequest->getRequestURL();
        $url = "";
        $pattern = '/\?.*(title=[^&]*).*$/';
        if (preg_match($pattern, $subject) == 1) {
            $url = preg_replace($pattern, "?\${1}&action=purge", $subject);
        } else {
            $url = $subject . "?action=purge";
        }

        return $today . " -- <a href=\"" . $url . "\">" . wfMessage('linkedwiki-refresh')->text() . "</a> -- " .
        wfMessage('linkedwiki-duration')->text() . " :" .
        round($duration, 3) . "s -- <a class=\"csv\" style=\"cursor: pointer;\" >CSV</a>";
    }

    public static function uri2Link($uri, $nowiki = false)
    {
        //TODO : $title ??? CLEAN ?
        global $wgServer;
        $result = str_replace("=", "{{equal}}", $uri);
        return $result;
    }

}
