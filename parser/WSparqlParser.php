<?php
/**
 * @copyright (c) 2018 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link http://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
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

class WSparqlParser
{

    public static function render($parser)
    {
        $configFactory = ConfigFactory::getDefaultInstance()->makeConfig( 'ext-conf-linkedwiki' );
        $configDefault = $configFactory->get("endpointDefault");

        $parser->disableCache(); //TODO OPTIMIZE

        $args = func_get_args(); // $parser, $param1 = '', $param2 = ''
        $countArgs = count($args);
        $query = "";
        $debug = null;
        $cache = "yes";
        $config= $configDefault;
        $endpoint = null;
        $namewidget = isset($args[1]) ? $args[1] :"";
        $vars = array();
        for ($i = 2; $i < $countArgs; $i++) {
            // FIX bug : Newline breaks query
            if (preg_match_all('#^([^= ]+)=((.|\n)*)$#i', $args[$i], $match)) {
                if ($match[1][0] == "query") {
                    $query = urldecode($match[2][0]);
                } elseif ($match[1][0] == "debug") {
                    $debug = $match[2][0];
                } elseif ($match[1][0] == "endpoint") {
                    $endpoint = $match[2][0];
                }elseif($match[1][0] == "config"){
                    $config = $match[2][0];
                } elseif ($match[1][0] == "cache") {
                    $cache = $match[2][0];
                } else {
                    $vars[] = $args[$i];
                }
            } else {
                $vars[] = $args[$i];
            }
        }

        if ($cache == "no") {
            $parser->disableCache();
        }

        if ($query != "" && $namewidget != "") {

            $query = ToolsParser::parserQuery($query, $parser);

            return WSparqlParser::widget($namewidget, $query, $config,$endpoint, $debug, $vars);
        } else {
            $parser->disableCache();
            return "'''Error #sparql : Argument incorrect (usage : #wsparql:namewidget|query=SELECT * WHERE {?a ?b ?c .} )'''";
        }
    }

    public static function widget($namewidget, $querySparqlWiki, $config,$endpoint, $debug, $vars)
    {
        $arrEndpoint = ToolsParser::newEndpoint($config,$endpoint);
        if($arrEndpoint["endpoint"] == null){
            return  array("<pre>".$arrEndpoint["errorMessage"]."</pre>",'noparse' => true, 'isHTML' => false);
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

        $res_rows = array();
        $i = 0;

        $variables = $rs['result']['variables'];
        foreach ($rs['result']['rows'] as $row) {
            $res_row = array();
            foreach ($variables as $variable) {
                if (!isset($row[$variable])) {
                    continue;
                }
                $res_row[] = "rows." . $i . "." . $variable . "=" . $row[$variable];
            }
            $res_rows[] = implode(" | ", $res_row);
            $i++;
        }

        $str = "{{#widget:" . $namewidget . "|" . implode(" | ", $vars) . "|" . implode(" | ", $res_rows) . "}}";
        if ($debug != null) {
            $str .= "\n" . print_r($vars, true);
            $str .= print_r($rs, true);
            return array("<pre>" . $str . "</pre>", 'noparse' => true, 'isHTML' => true);
        }

        return array($str, 'noparse' => false);
    }
}
