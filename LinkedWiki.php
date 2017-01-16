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

        $parser->setHook('lwgraph', 'LwgraphTag::render');
        $parser->setFunctionHook('sparql', 'SparqlParser::render');
        $parser->setFunctionHook('wsparql', 'WSparqlParser::render');

        $parser->setHook('rdf', 'RDFTag::render');
        return true;
    }

    public static function languageGetMagic(&$magicWords, $langCode)
    {
        # Add the magic word
        # The first array element is whether to be case sensitive, in this
        # case (0) it is not case sensitive, 1 would be sensitive
        # All remaining elements are synonyms for our parser function
        $magicWords['sparql'] = array(0, 'sparql');
        $magicWords['wsparql'] = array(0, 'wsparql');
        # unless we return true, other parser functions extensions won't get loaded.
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

}
