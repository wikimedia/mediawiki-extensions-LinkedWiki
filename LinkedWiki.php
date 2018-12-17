<?php
/**
 * @copyright (c) 2018 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-sa V4.0
 *
 *  Last version: https://github.com/BorderCloud/LinkedWiki
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

class LinkedWiki
{
    public static function makeConfig()
    {
        return new GlobalVarConfig( 'ext-conf-linkedwiki' );
    }

    public static function parserFirstCallInit(&$parser)
    {
        global $wgOut;
        $wgOut->addModules('ext.LinkedWiki.table2CSV');
        $wgOut->addModules('ext.LinkedWiki.flowchart');

        $wgOut->addModules('ext.LinkedWiki.SparqlParser');

        $parser->setHook('lwgraph', 'LwgraphTag::render');
        $parser->setFunctionHook('sparql', 'SparqlParser::render');
        $parser->setFunctionHook('wsparql', 'WSparqlParser::render');

        $parser->setHook('rdf', 'RDFTag::render');
        return true;
    }

    public static function scribuntoExternalLibraries( $engine, array &$extraLibraries ) {
        if ( $engine !== 'lua' ) {
            return true;
        }
        //Lua extension Doc :  https://www.mediawiki.org/wiki/Extension:Scribunto/Example_extension
        $extraLibraries['linkedwiki'] = 'Scribunto_LuaLinkedWikiLibrary';
        return true;
    }

    public static function onArticleDeleteComplete( &$article, User &$user, $reason, $id, Content $content = null, LogEntry $logEntry ) {
        $config = new LinkedWikiConfig();
        $subject = "<".urldecode($article->getTitle()->getFullURL()).">";

        $parameters = array("?subject");
        $values = array($subject);
        $q = str_replace($parameters,
            $values,
            $config->getQueryDeleteSubject());

        $endpoint = $config->getInstanceEndpoint();
        $response = $endpoint->query($q, 'raw');
        //$err = $endpoint->getErrors();
        /*if ($err) {
            $message = $config->isDebug() ? $response . print_r($err, true) :"ERROR SPARQL (see details in mode debug)";
            return array("ERROR : " . $message);
        }*/
        return true;
    }

    public static function onTitleMoveComplete( Title &$title, Title &$newTitle, User &$user, $oldid, $newid, $reason, Revision $revision ) {
        $config = new LinkedWikiConfig();
        $subject = "<".urldecode($title->getFullURL()).">";

        $parameters = array("?subject");
        $values = array($subject);
        $q = str_replace($parameters,
            $values,
            $config->getQueryDeleteSubject());

        $endpoint = $config->getInstanceEndpoint();
        $response = $endpoint->query($q, 'raw');
        return true;
    }

    public static function onBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
        if ( $out->getTitle()->isSpecial( 'linkedwiki-specialsparqlquery' ) ) {
            $out->addModules('ext.LinkedWiki.SpecialSparqlQuery');
        }
        return true;
    }
}
