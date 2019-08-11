<?php
/**
 * @copyright (c) 2019 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
 */

class LwgraphTag
{
    public static function render($input, array $args, Parser $parser, PPFrame $frame)
    {
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
