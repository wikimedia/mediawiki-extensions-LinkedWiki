<?php
/**
 * @copyright (c) 2018 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
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
if (!defined('MEDIAWIKI')) {
    echo "This file is not a valid entry point.";
    exit(1);
}

class ToolsParser
{
    public static function parserQuery($query,$parser) {
        $res = $query;
        if (preg_match("/<PAGEIRI>/i",$res)) {
            $uri  = "<".ToolsParser::pageIri($parser).">";
            $res  = str_replace("<PAGEIRI>",$uri , $res);
        }
        return $res;
    }

    public static function  pageIri(&$parser) {
        //$resolverurl = $parser->getTitle()->getFullURL();
        $resolverurl = urldecode($parser->getTitle()->getSubjectPage()->getFullURL());
        return $resolverurl;
    }

    public static function  newEndpoint($config,$endpoint) {
       // GLOBAL $wgLinkedWikiAccessEndpoint,$wgLinkedWikiConfigDefault;
        $errorMessage = null;
        $objConfig = null;

        $errorMessage = "" ;

        try {
            if(! EMPTY($endpoint)){
                $objConfig = new LinkedWikiConfig();
                //$objConfig->setEndpoint($endpoint);
                $objConfig->setEndpointRead($endpoint);
            }elseif(! EMPTY($config)){
                $objConfig = new LinkedWikiConfig($config);
            }else{
                $objConfig = new LinkedWikiConfig();
            }
        } catch (Exception $e) {
            $errorMessage =  $e->getMessage();
            return array('endpoint'=> $endpoint, 'errorMessage' => $errorMessage);
        }

        $objConfig->setReadOnly(true);
        $endpoint =$objConfig->getInstanceEndpoint();

        return array('endpoint'=> $endpoint, 'errorMessage' => $errorMessage);
    }
}
