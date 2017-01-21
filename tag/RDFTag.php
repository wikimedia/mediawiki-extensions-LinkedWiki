<?php
/**
 * @copyright (c) 2017 Bourdercloud.com
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

class RDFTag
{

    public static function render($input, array $args, Parser $parser, PPFrame $frame)
    {
        if ( class_exists( 'SyntaxHighlight_GeSHi' ) ) {
            //print RDF with the extension : SyntaxHighlight_GesShi
            $output = $parser->recursiveTagParse("<source lang=\"sparql\">". $input."</source>", $frame );
        }else{
            $output = $parser->recursiveTagParse("<pre>". $input."</pre>", $frame );
        }

        $parser->addTrackingCategory( 'linkedwiki-category-rdf-page' );

        $contraint = isset( $args['contraint'] ) ? strtolower($args['contraint']) : '';
        if($contraint == "shacl"){
            $parser->addTrackingCategory( 'linkedwiki-category-rdf-schema' );
        }

        return array($output, 'isHTML' => true);
    }

    public static function convertWikiCode2Turtle($wikicode, $IRISource) {
        $textTemp = "";
        preg_match_all(
            '#<rdf.*?>(.*?)</rdf>#is',
            $wikicode,
            $matches
        );
        foreach($matches[1] as $source){
            $textTemp.= $source;
        }
        $parameters = array("?subject","?type","?property");
        $iri = "<".$IRISource.">";
        $values = array($iri,$iri,$iri);
        $text = str_replace($parameters,
            $values,
            $textTemp);

        return $text;
    }

    public static function RawRDFSource(  &$rawAction, &$text) {
        //test with ?action=raw&export=rdf

        if($rawAction != '' && isset($_REQUEST["export"])){
            $tag = $_REQUEST["export"];
            if($tag == "rdf"){ //default : turtle
                header("Content-type: text/turtle");
                header("Expires: 0");
                header("Pragma: no-cache");
                header("Cache-Control: no-store");

                $text = RDFTag::convertWikiCode2Turtle($text, $rawAction->getTitle()->getFullURL());
                //print_r($rawAction,true);

                return true;
            }
        }
        return false;
    }

    public static function checkErrorWithRapper( $context, $content)
    {
        $error = "";

        $fullURL = $context->getTitle()->getFullURL();
        $tag = "rdf"; //by default
        $format = "";
        $shaclSchemasArray = array();
        $shaclSchemasArrayIri = array();
        //str_replace($wikiPage->getTitle()->getBaseText()

        $badChar = array(".","/"," ");
        $filename = '/tmp/'.str_replace($badChar,"",$context->getTitle()->getDBKey()).'.ttl';
        $commandRDFUnit = "rapper -i turtle \"".$filename."\"  " ;
        //check RDF
        $turtle = RDFTag::convertWikiCode2Turtle($content->getWikitextForTransclusion(),$fullURL );

        $out = fopen($filename, "w");
        fwrite($out, $turtle);
        fclose($out);

        exec($commandRDFUnit ." 2>&1", $retval);
        $textRetval = print_r($retval,true);
        if (preg_match("#URI .*:(.*) - (.*)#", $textRetval, $matches)) {
            $error = "Rapper detected an error in this RDF page : ".$matches[2]. "\n";
            //Write message
            $arrayTurtle = explode("\n", $turtle);
            for($i=1;$i<=count($arrayTurtle);$i++){
                $space =" ";
                if($i == $matches[1]){
                    $space ="*";
                }
                $error .= sprintf(" %'{$space}5d : %s\n", $i,$arrayTurtle[$i] );
            }

        } elseif (preg_match("#.*Error.*#", $textRetval)) {
            $error = $textRetval;
        }

        unlink($filename);
        return $error;
    }


    public static function onEditFilterMergedContent( $context, $content, $status, $summary, $user, $minoredit )
    {
        $config = ConfigFactory::getDefaultInstance()->makeConfig('ext-conf-linkedwiki');
        if($config->has("checkRDFPage") && $config->get("checkRDFPage") &&
            preg_match('#<rdf.*?>#is',$content->getWikitextForTransclusion(),$matches)){

            $error = RDFTag::checkErrorWithRapper($context,$content);
            if(! EMPTY($error)){
                $status->fatal(new RawMessage("<div style='color: red'>" . htmlspecialchars($error) . "</div>"));

            }
        }

    }
}