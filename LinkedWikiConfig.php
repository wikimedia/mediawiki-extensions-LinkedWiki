<?php
/**
 * @copyright (c) 2018 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * @link https://www.mediawiki.org/wiki/Extension:LinkedWiki
 * @license CC-by-nc-sa V4.0
 *
 *  Last version : https://github.com/BorderCloud/LinkedWiki
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

use BorderCloud\SPARQL\SparqlClient;

class LinkedWikiConfig
{
    //region private variables
    /*
     * Object with all the configurations of the extension
     */
    private $config = null;

    /*
     * Object with the endpoint's configurations
     */
    private $configEndpoints = null;

    /*
     * Config of the current endpoint
     */
    private $configEndpoint = null;

    /*
     * Id of Endpoint by default
     */
    private $idEndpointByDefault = "";

    /*
     * Id of current Endpoint (iri)
     */
    private $idEndpoint = "";

    /*
     * Instance of Endpoint with this current config
     */
    private $objEndpoint = null;


    private $lang = "en";

    private $debug = FALSE;
    private $isReadOnly = TRUE;
    //private $graphNamed = "";
//    private $endpoint = "";
    private $endpointRead = "";
    private $endpointUpdate = "";
    private $login = "";
    private $password = "";
    private $typeRDFDatabase = "virtuoso";
    private $HTTPMethodForRead = "POST";
    private $HTTPMethodForWrite = "POST";
    private $nameParameterRead = "query";
    private $nameParameterWrite = "update";

    private $storageMethod = null;

    private $proxyHost = null;
    private $proxyPort = null;
    //endregion

    //region private functions

    private function setEndpointWrite($endpointWrite)
    {
        $this->endpointWrite = $endpointWrite;
        $this->resetInstanceEndpoint();
    }

    private function getEndpointWrite()
    {
        return $this->endpointWrite;
    }

    private function setLogin($login)
    {
        $this->login = $login;
        $this->resetInstanceEndpoint();
    }

    private function getLogin()
    {
        return $this->login;
    }

    private function setPassword($password)
    {
        $this->password = $password;
        $this->resetInstanceEndpoint();
    }

    private function getPassword()
    {
        if (!EMPTY($this->password)) {
            return "*****";
        } else {
            return "";
        }
    }

    private function setTypeRDFDatabase($typeRDFDatabase)
    {
        $this->typeRDFDatabase = $typeRDFDatabase;
        $this->resetInstanceEndpoint();
    }

    private function getTypeRDFDatabase()
    {
        return $this->typeRDFDatabase;
    }

    private function setProxyHost($proxyHost)
    {
        $this->proxyHost = $proxyHost;
        $this->resetInstanceEndpoint();
    }

    private function getProxyHost()
    {
        return $this->proxyHost;
    }

    private function setProxyPort($proxyPort)
    {
        $this->proxyPort = $proxyPort;
        $this->resetInstanceEndpoint();
    }

    private function getProxyPort()
    {
        return $this->proxyPort;
    }

    private function setStorageMethod($storageMethod)
    {
        $this->storageMethod = $storageMethod;
    }

    private function getStorageMethod()
    {
        return $this->storageMethod;
    }


    private function resetInstanceEndpoint()
    {
        $this->objEndpoint = null;
    }

    private function newInstanceEndpoint()
    {
        $objEndpoint = null;
        $objEndpoint = new SparqlClient($this->debug);
        $objEndpoint->setEndpointRead($this->endpointRead);

//        $objEndpoint->setNameParameterQueryRead($nameParameterQuery);
//        $objEndpoint->setNameParameterQueryWrite($nameParameterWrite);

        if (!EMPTY($this->proxyHost)) {
            $objEndpoint->setproxyHost($this->proxyHost);
        }
        if (!EMPTY($this->proxyPort)) {
            $objEndpoint->setproxyPort($this->proxyPort);
        }
        if (!EMPTY($this->endpointRead)) {
            $objEndpoint->setEndpointRead($this->endpointRead);
        }
        if (!EMPTY($this->HTTPMethodForRead)) {
            $objEndpoint->setMethodHTTPRead($this->HTTPMethodForRead);
        }
        if (!EMPTY($this->nameParameterRead)) {
            $objEndpoint->setNameParameterQueryRead($this->nameParameterRead);
        }
        if (!$this->isReadOnly) {
            if (!EMPTY($this->endpointWrite)) {
                $objEndpoint->setEndpointWrite($this->endpointWrite);
            }else{
                $objEndpoint->setEndpointWrite($this->endpoint);
            }
            if (!EMPTY($this->HTTPMethodForRead)) {
                $objEndpoint->setMethodHTTPWrite($this->HTTPMethodForRead);
            }
            if (!EMPTY($this->nameParameterWrite)) {
                $objEndpoint->setNameParameterQueryWrite($this->nameParameterWrite);
            }
        }
        if (!EMPTY($this->login)) {
            $objEndpoint->setLogin($this->login);
        }
        if (!EMPTY($this->password)) {
            $objEndpoint->setPassword($this->password);
        }
        return $objEndpoint;
    }

    private function setNameParameterWrite($nameParameterWrite)
    {
        $this->nameParameterWrite = $nameParameterWrite;
        $this->resetInstanceEndpoint();
    }

    private function getNameParameterWrite()
    {
        return $this->nameParameterWrite;
    }

    private function setMethodForWrite($HTTPMethodForWrite)
    {
        $this->HTTPMethodForWrite = $HTTPMethodForWrite;
        $this->resetInstanceEndpoint();
    }

    private function getMethodForWrite()
    {
        return $this->HTTPMethodForWrite;
    }
    //endregion

    //region public functions

//    public function setEndpoint($urlEndpoint)
//    {
//        $this->endpoint = $urlEndpoint;
//        $this->resetInstanceEndpoint();
//    }
//
//    public function getEndpoint()
//    {
//        return $this->endpoint;
//    }

    public function setEndpointRead($endpointRead)
    {
        $this->endpointRead = $endpointRead;
        $this->resetInstanceEndpoint();
    }

    public function getEndpointRead()
    {
        return $this->endpointRead;
    }


    public function setDebug($debug)
    {
        $this->debug = $debug;
        $this->resetInstanceEndpoint();
    }

    public function isDebug()
    {
        return $this->debug;
    }

    public function setReadOnly($isReadOnly)
    {
        $this->isReadOnly = $isReadOnly;
        $this->resetInstanceEndpoint();
    }

    public function isReadOnly()
    {
        return $this->isReadOnly;
    }

    public function getConfig()
    {
        return $this->config;
    }


    public function getDefaultConfigEndpoint()
    {
        return $this->idEndpointByDefault;
    }

    public function getConfigEndpoint()
    {
        return $this->idEndpoint;
    }

    public function setConfigEndpoint($urlEndpointConfig = null)
    {
        if (EMPTY($urlEndpointConfig)) {
            $urlEndpointConfig = $this->idEndpointByDefault;
        }

        //reset
        if ($urlEndpointConfig != 'http://www.example.org') {
            $this->setConfigEndpoint('http://www.example.org');
        }

        if (!array_key_exists($urlEndpointConfig, $this->configEndpoints)) {
            throw new Exception("The configuration of " . $urlEndpointConfig . " is not found in the file LinkedWiki/extension.json.");
        }

        $this->idEndpoint = $urlEndpointConfig;
        $this->configEndpoint = $this->configEndpoints[$this->idEndpoint];

        if (isset($this->configEndpoint["storageMethodClass"])) {
            $storageMethodClass = $this->configEndpoint["storageMethodClass"];
            $this->storageMethod = new $storageMethodClass;
        }

        if (isset($this->configEndpoint["debug"]))
            $this->setDebug($this->configEndpoint["debug"]);

        if (isset($this->configEndpoint["isReadOnly"]))
            $this->setReadOnly($this->configEndpoint["isReadOnly"]);

        if (isset($this->configEndpoint["proxyHost"]))
            $this->setProxyHost($this->configEndpoint["proxyHost"]);

        if (isset($this->configEndpoint["proxyPort"]))
            $this->setProxyPort($this->configEndpoint["proxyPort"]);

        if (isset($this->configEndpoint["lang"]))
            $this->setLang($this->configEndpoint["lang"]);

//        if (isset($this->configEndpoint["endpoint"]))
//            $this->setEndpoint($this->configEndpoint["endpoint"]);

        if (isset($this->configEndpoint["endpointRead"]))
            $this->setEndpointRead($this->configEndpoint["endpointRead"]);

        if (isset($this->configEndpoint["endpointWrite"]))
            $this->setEndpointWrite($this->configEndpoint["endpointWrite"]);

        if (isset($this->configEndpoint["login"]))
            $this->setLogin($this->configEndpoint["login"]);

        if (isset($this->configEndpoint["password"]))
            $this->setPassword($this->configEndpoint["password"]);

        if (isset($this->configEndpoint["typeRDFDatabase"]))
            $this->setTypeRDFDatabase($this->configEndpoint["typeRDFDatabase"]);

        if (isset($this->configEndpoint["HTTPMethodForRead"]))
            $this->setMethodForRead($this->configEndpoint["HTTPMethodForRead"]);

        if (isset($this->configEndpoint["HTTPMethodForWrite"]))
            $this->setMethodForWrite($this->configEndpoint["HTTPMethodForWrite"]);

        if (isset($this->configEndpoint["nameParameterRead"]))
            $this->setNameParameterRead($this->configEndpoint["nameParameterRead"]);

        if (isset($this->configEndpoint["nameParameterWrite"]))
            $this->setNameParameterWrite($this->configEndpoint["nameParameterWrite"]);

        $this->resetInstanceEndpoint();
    }


    public function __construct($urlEndpointConfig = null)
    {
        $this->config = ConfigFactory::getDefaultInstance()->makeConfig('ext-conf-linkedwiki');
        $this->configEndpoints = $this->config->get("endpoint");
        $this->idEndpointByDefault = $this->config->get("endpointDefault");

        $this->setConfigEndpoint($urlEndpointConfig);
    }

    public function getInstanceEndpoint()
    {
        if ($this->objEndpoint === null) {
            $this->objEndpoint = $this->newInstanceEndpoint();
        }
        return $this->objEndpoint;
    }

    public function getQueryReadValue()
    {
        return $this->storageMethod->getQueryReadValue();
    }

    public function getQueryReadStringWithTagLang()
    {
        return $this->storageMethod->getQueryReadStringWithTagLang();
    }

    public function getQueryReadStringWithoutTagLang()
    {
        return $this->storageMethod->getQueryReadStringWithoutTagLang();
    }

    public function getQueryInsertValue()
    {
        return $this->storageMethod->getQueryInsertValue();
    }

    public function getQueryDeleteSubject()
    {
        return $this->storageMethod->getQueryDeleteSubject();
    }

    public function getQueryLoadData($url)
    {
        return $this->storageMethod->getQueryLoadData($url);
    }

    public function setSubject($iriSubject)
    {
        $this->subject = trim($iriSubject);
    }

    public function setLang($tagLang)
    {
        $this->lang = trim($tagLang);
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function setNameParameterRead($nameParameterRead)
    {
        $this->nameParameterRead = $nameParameterRead;
        $this->resetInstanceEndpoint();
    }

    public function getNameParameterRead()
    {
        return $this->nameParameterRead;
    }

    public function setMethodForRead($HTTPMethodForRead)
    {
        $this->HTTPMethodForRead = $HTTPMethodForRead;
        $this->resetInstanceEndpoint();
    }

    public function getMethodForRead()
    {
        return $this->HTTPMethodForRead;
    }
    //endregion

    //region static public functions
    static function info()
    {
        //global $wgLinkedWikiConfigDefault, $wgLinkedWikiAccessEndpoint;
        $config = ConfigFactory::getDefaultInstance()->makeConfig('ext-conf-linkedwiki');
        $configDefault = $config->get("endpointDefault");
        $configs = $config->get("endpoint");

        $html = "";
        foreach ($configs as $key => $value) {
            $objConfig = null;

            $title = "";
            if ($key === "http://www.example.org") {
                $title = "Configuration by default with the parameter \"Endpoint\"";
            } elseif ($key === $configDefault) {
                $title = "Configuration by default: " . $configDefault;
            } else {
                $title = "Configuration: " . $key;
            }

            if ($key === $configDefault) {
                $objConfig = new LinkedWikiConfig();
            } else {
                $objConfig = new LinkedWikiConfig($key);
            }
            $html .= "<h3>$title</h3>";

            $html .= "<table class='wikitable'>";

            $html .= "<tr>";
            $html .= "<th>";
            $html .= "Key";
            $html .= "</th>";
            $html .= "<th>";
            $html .= "Value";
            $html .= "</th>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "debug";
            $html .= "</td>";
            $html .= "<td>";
            $html .= $objConfig->isDebug() ? "Enable" :"Disable";
            $html .= "</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "isReadOnly";
            $html .= "</td>";
            $html .= "<td>";
            $html .= $objConfig->isReadOnly() ? "Enable" :"Disable";
            $html .= "</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "proxyHost";
            $html .= "</td>";
            $html .= "<td>";
            $html .= $objConfig->getProxyHost();
            $html .= "</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "proxyPort";
            $html .= "</td>";
            $html .= "<td>";
            $html .= $objConfig->getProxyPort();
            $html .= "</td>";
            $html .= "</tr>";

//            $html .= "<tr>";
//            $html .= "<td>";
//            $html .= "endpoint";
//            $html .= "</td>";
//            $html .= "<td>";
//            $html .= $objConfig->getEndpoint();
//            $html .= "</td>";
//            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "endpointRead";
            $html .= "</td>";
            $html .= "<td>";
            $html .= $objConfig->getEndpointRead();
            $html .= "</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "endpointWrite";
            $html .= "</td>";
            $html .= "<td>";
            $html .= $objConfig->getEndpointWrite();
            $html .= "</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "login";
            $html .= "</td>";
            $html .= "<td>";
            $html .= "see file: extension.json";//$objConfig->getLogin();
            $html .= "</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "password";
            $html .= "</td>";
            $html .= "<td>";
            $html .= "see file: extension.json";//$objConfig->getPassword();
            $html .= "</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "getTypeRDFDatabase";
            $html .= "</td>";
            $html .= "<td>";
            $html .= $objConfig->getTypeRDFDatabase();
            $html .= "</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "HTTPMethodForRead";
            $html .= "</td>";
            $html .= "<td>";
            $html .= $objConfig->getMethodForRead();
            $html .= "</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "HTTPMethodForWrite";
            $html .= "</td>";
            $html .= "<td>";
            $html .= $objConfig->getMethodForWrite();
            $html .= "</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "nameParameterRead";
            $html .= "</td>";
            $html .= "<td>";
            $html .= $objConfig->getNameParameterRead();
            $html .= "</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "nameParameterWrite";
            $html .= "</td>";
            $html .= "<td>";
            $html .= $objConfig->getNameParameterWrite();
            $html .= "</td>";
            $html .= "</tr>";

            $method = $objConfig->getStorageMethod();
            $html .= "<tr>";
            $html .= "<td>";
            $html .= "storageMethodClass";
            $html .= "</td>";
            $html .= "<td>";
            $html .= get_class($method);
            $html .= "</td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "getQueryDeleteSubject";
            $html .= "</td>";
            $html .= "<td><pre>";
            $html .= htmlentities($method->getQueryDeleteSubject());
            $html .= "</pre></td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "getQueryInsertValue";
            $html .= "</td>";
            $html .= "<td><pre>";
            $html .= htmlentities($method->getQueryInsertValue());
            $html .= "</pre></td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "getQueryReadValue";
            $html .= "</td>";
            $html .= "<td><pre>";
            $html .= htmlentities($method->getQueryReadValue());
            $html .= "</pre></td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "getQueryReadStringWithTagLang";
            $html .= "</td>";
            $html .= "<td><pre>";
            $html .= htmlentities($method->getQueryReadStringWithTagLang());
            $html .= "</pre></td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "getQueryReadStringWithoutTagLang";
            $html .= "</td>";
            $html .= "<td><pre>";
            $html .= htmlentities($method->getQueryReadStringWithoutTagLang());
            $html .= "</pre></td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "getQueryLoadData";
            $html .= "</td>";
            $html .= "<td><pre>";
            $html .= htmlentities($method->getQueryLoadData('http://example.org/file.ttl'));
            $html .= "</pre></td>";
            $html .= "</tr>";

            $html .= "<tr>";
            $html .= "<td>";
            $html .= "lang";
            $html .= "</td>";
            $html .= "<td>";
            $html .= $objConfig->getLang();
            $html .= "</td>";
            $html .= "</tr>";

            $html .= "</table>";
//            //$wgOut->addHTML("CHECK :".$objScribunto_LuaLinkedWikiLibrary->check());
//
//            if ($objConfig->isReadOnly()) {
//                //test read
//                $html .= "\n*  Test endpoint for reading : ";
//                /* $endpoint = $objConfig->getInstanceEndpoint();
//                $rows = $endpoint->query($objConfig->getQueryReadValue(). " LIMIT 5", 'rows');
//                $err = $endpoint->getErrors();
//                if ($err) {
//                     $html = "\n<pre>";
//                     $html .=  "Error".print_r($err,true) ;
//                     $html .= "</pre>";
//                }else{
//                    $html .=  "\n** Read 5 lines :" ;
//                   // $html .=  $objConfig->printTable($rows) ;
//                }
//                */
//                $arr = SparqlParser::simpleHTML(
//                    $objConfig->getQueryReadValue() . " LIMIT 5", $key,
//                    '', '', '', null);
//                $html .= $arr[0];
//            }
//            if (!$objConfig->isReadOnly) {
//                //test write
//                $html .= "\n*  Test endpoint for writing (only in mode debug): ";
//                if ($objConfig->debug) {
//                    $endpoint = $objConfig->getInstanceEndpoint();
//
//                    $parametersDeleteSubject = array("?subject");
//                    $replaceparametersDeleteSubject = array("<http://linkedwiki.org/test/1>");
//                    $qDeleteSubject = str_replace($parametersDeleteSubject,
//                        $replaceparametersDeleteSubject,
//                        $objConfig->getQueryDeleteSubject());
//
//                    $endpoint->ResetErrors();
//                    $message = $endpoint->query($qDeleteSubject, "raw");
//                    $err = $endpoint->getErrors();
//                    if ($err) {
//                        $html .= "\n<pre>Error" . print_r($err, true) . "</pre>";
//                    } else {
//                        $html .= "\n<pre>" . $message . "</pre>";
//                    }
//
//                    $parametersInsertValue = array("?subject", "?property", "?value");
//                    $replaceparametersInsertValue = array("<http://linkedwiki.org/test/1>", "<http://linkedwiki.org/test/2>", "<http://linkedwiki.org/test/3>");
//
//                    $qInsert = str_replace($parametersInsertValue,
//                        $replaceparametersInsertValue,
//                        $objConfig->getQueryInsertValue());
//
//                    $endpoint->ResetErrors();
//                    $message = $endpoint->query($qInsert, "raw");
//                    $err = $endpoint->getErrors();
//                    if ($err) {
//                        $html .= "\n<pre>Error" . print_r($err, true) . "</pre>";
//                    } else {
//                        $html .= "\n<pre>" . $message . "</pre>";
//                    }
//
//                    $parametersQueryValue = array("?subject", "?property");
//                    $replaceparametersQueryValue = array("<http://linkedwiki.org/test/1>", "<http://linkedwiki.org/test/2>");
//                    $qQueryValue = str_replace($parametersQueryValue,
//                        $replaceparametersQueryValue,
//                        $objConfig->getQueryReadValue());
//
//                    /*$endpoint->ResetErrors();
//                    $rows = $endpoint->query($qQueryValue, 'rows');
//                    $err = $endpoint->getErrors();
//                    if ($err) {
//                        $html .= "\n<pre>Error".print_r($err,true)."</pre>";
//                    }else{
//                        $html .=  "\nIn theory will find  <http://linkedwiki.org/test/3> : " ;
//                        $html .=  $objConfig->printTable($rows) ;
//
//                    }    */
//                    $arr = efSparqlParserFunction_simpleHTML(
//                        $qQueryValue, $key, '', '', '', null);
//                    $html .= $arr[0];
//
//                    $parametersDeleteSubject = array("?subject");
//                    $replaceparametersDeleteSubject = array("<http://linkedwiki.org/test/1>");
//                    $qDeleteSubject = str_replace($parametersDeleteSubject,
//                        $replaceparametersDeleteSubject,
//                        $objConfig->getQueryDeleteSubject());
//
//                    $endpoint->ResetErrors();
//                    $message = $endpoint->query($qDeleteSubject, "raw");
//                    $err = $endpoint->getErrors();
//                    if ($err) {
//                        $html .= "\n<pre>Error" . print_r($err, true) . "</pre>";
//                    } else {
//                        $html .= "\n<pre>" . $message . "</pre>";
//                    }
//
//                }
//            }
//
        }
        //$wgOut->addWikiText(wfMessage('linkedwiki-specialsparqlquery_mainpage')->text());
        return $html;
    }


    /*
    private function printTable($rows){
        $html = "\n<pre>";
         foreach($rows["result"]["variables"] as $variable){
            $html .= sprintf("%-60.60s",$variable);
            $html .= ' | ';
         }
         $html .= "\n";

         foreach ($rows["result"]["rows"] as $row){
            foreach($rows["result"]["variables"] as $variable){
                $html .= sprintf("%-60.60s",$row[$variable]);
                $html .= ' | ';
            }
         $html .="\n";
        }

        $html .= "</pre>";
        return $html;
    }*/
    //endregion
}
