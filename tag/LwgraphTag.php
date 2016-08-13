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

class LwgraphTag
{

    public static function render($input, array $args, Parser $parser, PPFrame $frame)
    {
        //global $wgOut;
        $html = "";
        $width = isset($args["width"]) ? $args["width"] :"100%";
        $height = isset($args["height"]) ? $args["height"] :"150px";
        $border = isset($args["border"]) && $args["border"] > 0 ? "border:" . $args["border"] . "px solid #000000;" :"";

        if (isset($args["debug"]) && $args["debug"] == "true") {
            $attr = array();
            foreach ($args as $name => $value)
                $attr[] = $name . ' = ' . $value;

            $html .= "<div><b>lwgraph DEBUG :</b><br/>" . implode('<br/>', $attr) . "</div>";
            $html .= "<pre>" . htmlspecialchars($input) . "</pre>";

        }

        if (isset($args["type"]) && $args["type"] == "flow") {
            // I put this addModules in efSparqlParserFunction_Setup
            //$wgOut->addModules('ext.LinkedWiki.flowchart');


            preg_match_all("/\[\[([^\]\|]*)(?:\|[^\]]*)?\]\]/U", $input, $out);
            $arrayTitle = array_unique($out[1]);

            $textGraph = $input;
            foreach ($arrayTitle as $title) {
                $titleObject = Title::newFromText($title);
                if (!$titleObject->exists())
                    $textGraph = str_replace("[[" . $title, "~[[" . $title, $textGraph);
                $textGraph = str_replace("~~", "~", $textGraph);
            }
            $html .= "<canvas  class=\"lwgraph-flow\" style=\"" . $border . "width: " . $width . ";height:" . $height . "\">" . $textGraph . "</canvas>";
        }
        return array($html, 'isHTML' => true);
    }
}