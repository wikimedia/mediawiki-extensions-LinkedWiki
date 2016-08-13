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

class ToolsParser
{
    public static function parserQuery($query,$parser) {
        $res = $query;
        //TODO:
        // 	if (preg_match("/<PAGEIRI>/i",$res)) {
        // 			$uri  = "<".fSparqlParserFunction_pageiri($parser).">";
        // 		   $res  = str_replace("<PAGEIRI>",$uri , $res);
        // 	}
        // 	if (preg_match("/<WIKIGRAPH>/i",$res)) {
        // 			$uri  = "<".$wgLinkedWikiGraphWiki.">";
        // 		   $res  = str_replace("<WIKIGRAPH>",$uri , $res);
        // 	}
        return $res;
    }
}