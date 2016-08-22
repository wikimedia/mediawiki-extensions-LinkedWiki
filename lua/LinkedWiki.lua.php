<?php
/**
 * @package Bourdercloud/linkedwiki
 * @copyright (c) 2011 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link http://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-nc-sa V3.0
 *
 * Last version : http://github.com/BorderCloud/LinkedWiki
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

//Libraries
//require_once( dirname(__FILE__) ."/../LinkedWikiConfig.php");

class Scribunto_LuaLinkedWikiLibrary extends Scribunto_LuaLibraryBase
{
    /**
     * @var luaBindings|null
     */
    private $objConfig = null;
    private $subject = null;
    private $lastQuery = null;

    ////PUBLIC FUNCTION
    function register()
    {
        // These functions will be exposed to the Lua module.
        // They are member functions on a Lua table which is private to the module, thus
        // these can't be called from user code, unless explicitly exposed in Lua.
        $lib = array(
            'setConfig' => array($this, 'setConfig'),
            'setEndpoint' => array($this, 'setEndpoint'),
            'setDebug' => array($this, 'setDebug'),
            'isDebug' => array($this, 'isDebug'),
            'setGraph' => array($this, 'setGraph'),
            'setSubject' => array($this, 'setSubject'),
            'setLang' => array($this, 'setLang'),
            'getLang' => array($this, 'getLang'),
            'getValue' => array($this, 'getValue'),
            'getString' => array($this, 'getString'),
            'getConfig' => array($this, 'getConfig'),

            'getDefaultConfig' => array($this, 'getDefaultConfig'),
            //'getDefaultLang' => array($this, 'getDefaultLang'),

            'getLastQuery' => array($this, 'getLastQuery'),


            'addPropertyWithIri' => array($this, 'addPropertyWithIri'),
            'addPropertyWithLitteral' => array($this, 'addPropertyWithLitteral'),
            'removeSubject' => array($this, 'removeSubject'),
        );
        return $this->getEngine()->registerInterface(
            __DIR__ . '/LinkedWiki.lua', $lib, array()
        );
    }

    ////PRIVATE FUNCTION

    private function setLastQuery($query)
    {
        $this->lastQuery = $query;
    }

    public function getLastQuery()
    {
        return array($this->lastQuery);
    }

    private function getInstanceConfig()
    {
        if ($this->objConfig === null) {
            $this->objConfig = new LinkedWikiConfig();
        }
        return $this->objConfig;
    }

    private function getInstanceEndpoint()
    {
        return $this->getInstanceConfig()->getInstanceEndpoint();
    }

    public function setConfig($urlConfig = null)
    {
        if(EMPTY($this->objConfig) ){
            $this->objConfig = new LinkedWikiConfig($urlConfig);
        }elseif( $this->objConfig->getConfigEndpoint() !== $urlConfig){
            $this->objConfig->setConfigEndpoint($urlConfig);
        }
    }
    public function getConfig()
    {
        return array($this->getInstanceConfig()->getConfigEndpoint());
    }
    public function getDefaultConfig()
    {
        return array($this->getInstanceConfig()->getDefaultConfigEndpoint());
    }

    public function setDebug($debug)
    {
        $this->checkType('setDebug', 1, $debug, 'boolean');
        $this->getInstanceConfig()->setDebug($debug);
    }
    public function isDebug()
    {
        return array($this->getInstanceConfig()->isDebug());
    }

//    public function setGraph($graphNamed)
//    {
//        $graph = trim($graphNamed);
//        $this->checkType('setGraph', 1, $graph, 'string');
//        $this->getInstanceConfig()->setGraph($graph);
//    }

    public function setEndpoint($urlEndpoint)
    {
        $this->checkType('setEndpoint', 1, $urlEndpoint, 'string');
        $errorMessage = null;
        $keyConfigByDefault = "http://www.example.org"; //default config 
        $this->objConfig = new LinkedWikiConfig($keyConfigByDefault);
        $this->objConfig->setEndpoint($urlEndpoint);
        $this->objConfig->setEndpointQueryOnly($urlEndpoint);
    }

    public function setSubject($iriSubject)
    {
        $this->checkType('setSubject', 1, trim($iriSubject), 'string');
        $this->subject = trim($iriSubject);
    }

    public function setLang($tagLang)
    {
        $langTrim = trim($tagLang);
        $this->checkType('setLang', 1, $langTrim, 'string');
        if(EMPTY($this->objConfig) && $this->objConfig->getLang() != $langTrim){
            $this->getInstanceConfig()->setLang($langTrim);
        }
    }

    public function getLang()
    {
        return array($this->getInstanceConfig()->getLang());
    }
//    public function geDefaultLang()
//    {
//        return array($this->getInstanceConfig()->getLang());
//    }

    /**
     * Find the value for a property
     * @param string $iriProperty : IRI of the property
     * @param string or null $tagLang : by default uses the lang by default in the configuration. if null, it will search the value without tag lang.
     * @param string $iriSubject : Optional, IRI of the subject
     * @access public
     */
    public function getString($iriProperty, $tagLang = null, $iriSubject = null)
    {
        if ($iriSubject === null && $this->subject ===null) {
            return array("ERROR : Subject unknown (Use the parameter iriSubject or the function setSubject.");
        }

        $result = "";
        $this->checkType('getString', 1, $iriProperty, 'string');
        $this->checkTypeOptional('getString', 2, $tagLang, 'string', $this->getInstanceConfig()->getLang());
        $this->checkTypeOptional('getString', 3, $iriSubject, 'string', $this->subject);
        $subject = ($iriSubject === null) ? "<" . $this->subject . ">" :"<" . trim($iriSubject) . ">";
        $property = "<" . trim($iriProperty) . ">";

        $q = "";
        if ($tagLang === null) {
            $parameters = array("?subject", "?property", "?lang");
            $values = array($subject, $property, "\"" . $this->getInstanceConfig()->getLang() . "\"");
            $q = str_replace($parameters,
                $values,
                $this->getInstanceConfig()->getQueryReadStringWithTagLang());
        } elseif ($tagLang === "") {
            $parameters = array("?subject", "?property");
            $values = array($subject, $property);
            $q = str_replace($parameters,
                $values,
                $this->getInstanceConfig()->getQueryReadStringWithoutTagLang());
        } else {
            $parameters = array("?subject", "?property", "?lang");
            $values = array($subject, $property, "\"" . trim($tagLang) . "\"");
            $q = str_replace($parameters,
                $values,
                $this->getInstanceConfig()->getQueryReadStringWithTagLang());
        }

        $this->setLastQuery($q);//for debug
        $endpoint = $this->getInstanceEndpoint();
        $rows = $endpoint->query($q, 'rows');
        $err = $endpoint->getErrors();
        if ($err) {
            $message = $this->getInstanceConfig()->isDebug() ? print_r($err, true) :"ERROR SPARQL (see details in mode debug)";
            return array("ERROR : " . $message);
        }

        $result = array();
        foreach ($rows["result"]["rows"] as $row) {

            $result[] = $row["value"];
        }

        return array(implode(";", $result));
    }

    public function getValue($iriProperty, $iriSubject = null)
    {
        if ($iriSubject === null && $this->subject ===null) {
            return array("ERROR : Subject unknown (Use the parameter iriSubject or the function setSubject.");
        }
        $this->checkType('getValue', 1, $iriProperty, 'string');
        $this->checkTypeOptional('getValue', 2, $iriSubject, 'string', $this->subject);
        //$this->checkType( 'getValue', 2, $iriSubject, 'string', $this->subject  );        

        $subject = ($iriSubject === null) ? "<" . $this->subject . ">" :"<" . trim($iriSubject) . ">";
        $property = "<" . trim($iriProperty) . ">";

        $parameters = array("?subject", "?property");
        $values = array($subject, $property);
        $q = str_replace($parameters,
            $values,
            $this->getInstanceConfig()->getQueryReadValue());


        $this->setLastQuery($q);//for debug
        $endpoint = $this->getInstanceEndpoint();
        $rows = $endpoint->query($q, 'rows');
        $err = $endpoint->getErrors();
        if ($err) {
            $message = $this->getInstanceConfig()->isDebug() ? print_r($err, true) :"ERROR SPARQL (see details in mode debug)";
            return array("ERROR : " . $message);
        }

        $result = array();
        foreach ($rows["result"]["rows"] as $row) {
            $result[] = $row["value"];
        }

        return array(implode(";", $result));
    }

    public function addPropertyWithIri($iriProperty, $iriValue, $iriSubject = null)
    {
        if ($iriSubject === null && $this->subject ===null) {
            return array("ERROR : Subject unknown (Use the parameter iriSubject or the function setSubject.");
        }

        if (preg_match("/(\"\"\"|''')/i", $iriValue)) {
            return array("ERROR : Bad value");
        }
        if (preg_match("/(\"\"\"|'''| )/i", trim($iriProperty))) {
            return array("ERROR : Bad property");
        }
        if (preg_match("/(\"\"\"|'''| )/i", trim($iriSubject))) {
            return array("ERROR : Bad subject");
        }

        $this->checkType('addPropertyWithIri', 1, $iriProperty, 'string');
        $this->checkType('addPropertyWithIri', 2, $iriValue, 'string');
        $this->checkTypeOptional('addPropertyWithIri', 3, $iriSubject, 'string', $this->subject);
        //$this->checkType( 'getValue', 2, $iriSubject, 'string', $this->subject  );        

        $subject = ($iriSubject === null) ? "<" . $this->subject . ">" :"<" . trim($iriSubject) . ">";
        $property = "<" . trim($iriProperty) . ">";
        $value = "<" . trim($iriValue) . ">";

        $parameters = array("?subject", "?property", "?value");
        $values = array($subject, $property, $value);
        $q = str_replace($parameters,
            $values,
            $this->getInstanceConfig()->getQueryInsertValue());

        $this->setLastQuery($q);//for debug
        $endpoint = $this->getInstanceEndpoint();
        $response = $endpoint->query($q, 'raw');
        $err = $endpoint->getErrors();
        if ($err) {
            $message = $this->getInstanceConfig()->isDebug() ? $response . print_r($err, true) :"ERROR SPARQL (see details in mode debug)";
            return array("ERROR : " . $message);
        }

        return array($response);
    }

    public function addPropertyWithLitteral($iriProperty, $value, $type = null, $tagLang = null, $iriSubject = null)
    {
        if ($iriSubject === null && $this->subject ===null) {
            return array("ERROR : Subject unknown (Use the parameter iriSubject or the function setSubject.");
        }
        if (EMPTY($value) || preg_match("/(\"\"\"|''')/i", $value)) {
            return array("ERROR : Bad value");
        }
        if (EMPTY($iriProperty) || preg_match("/(\"\"\"|'''| )/i", trim($iriProperty))) {
            return array("ERROR : Bad property");
        }
        if (preg_match("/(\"\"\"|'''| )/i", trim($iriSubject))) {
            return array("ERROR : Bad subject");
        }
        // $this->checkType( 'addPropertyWithLitteral', 1, $iriProperty, 'string' );
        // $this->checkType( 'addPropertyWithLitteral', 2, $value, 'number or string' );
        // $this->checkType( 'addPropertyWithLitteral', 3, $type, 'string',null );
        // $this->checkTypeOptional( 'addPropertyWithLitteral', 4, $tagLang, 'string' ,$this->getInstanceConfig()->getLang() );
        //$this->checkTypeOptional( 'addPropertyWithLitteral', 5, $iriSubject, 'string',$this->subject  );
        //$this->checkType( 'getValue', 2, $iriSubject, 'string', $this->subject  );        

        $subject = ($iriSubject === null) ? "<" . $this->subject . ">" :"<" . trim($iriSubject) . ">";
        $property = "<" . trim($iriProperty) . ">";

        $strValue = "";
        if (is_string($value)) {
            $strValue = "\"\"\"" . trim($value) . "\"\"\"";

            if ($type != null) {
                $strValue .= "^^" . "<" . trim($type) . ">";
            }
            if ($tagLang === null) {
                $strValue .= "@" . $this->getInstanceConfig()->getLang();
            } elseif ($tagLang === "") {
                //do nothing;
            } else {
                $strValue .= "@" . trim($tagLang);
            }
        } else {
            $strValue = strval($value);
            if ($type != null) {
                $strValue = "\"".$strValue."\"". "^^" . "<" . trim($type) . ">";
            }
        }

        //return array($strValue) ;


        $parameters = array("?subject", "?property", "?value");
        $values = array($subject, $property, $strValue);
        $q = str_replace($parameters,
            $values,
            $this->getInstanceConfig()->getQueryInsertValue());

        $this->setLastQuery($q);//for debug
        $endpoint = $this->getInstanceEndpoint();
        $response = $endpoint->query($q, 'raw');
        $err = $endpoint->getErrors();
        if ($err) {
            $message = $this->getInstanceConfig()->isDebug() ? $response . print_r($err, true) :"ERROR SPARQL (see details in mode debug)";
            return array("ERROR : " . $message);
        }

        return array($response);
    }


    public function removeSubject($iriSubject = null)
    {
        if ($iriSubject === null && $this->subject ===null) {
            return array("ERROR : Subject unknown (Use the parameter iriSubject or the function setSubject.");
        }

        if (preg_match("/(\"\"\"|'''| )/i", trim($iriSubject))) {
            return array("ERROR : Bad subject");
        }

        $subject = ($iriSubject === null) ? "<" . $this->subject . ">" :"<" . trim($iriSubject) . ">";

        $parameters = array("?subject");
        $values = array($subject);
        $q = str_replace($parameters,
            $values,
            $this->getInstanceConfig()->getQueryDeleteSubject());

        $this->setLastQuery($q);//for debug
        $endpoint = $this->getInstanceEndpoint();
        $response = $endpoint->query($q, 'raw');
        $err = $endpoint->getErrors();
        if ($err) {
            $message = $this->getInstanceConfig()->isDebug() ? $response . print_r($err, true) :"ERROR SPARQL (see details in mode debug)";
            return array("ERROR : " . $message);
        }

        return array($response);
    }
}
