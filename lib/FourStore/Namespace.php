<?php
/**
 * @version 0.1.0.0
 * @package Bourdercloud/4store-PHP
 * @copyright (c) 2010 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * 
 * This file is a fork of project  : http://github.com/moustaki/4store-php
 * @author Yves Raimond

 Copyright (c) 2010 Bourdercloud.com

 Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:

 The above copyright notice and this permission notice shall be included in
 all copies or substantial portions of the Software.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 THE SOFTWARE.
 */

class FourStore_Namespace {

    protected static $_namespaces = array();
    
    public static function addW3CNamespace()
    {
    	self::add('rdf', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
    	self::add('rdfs', 'http://www.w3.org/2000/01/rdf-schema#');
    	self::add('xsd', 'http://www.w3.org/2001/XMLSchema#');
    }

    public static function add($short, $long)
    {
        self::$_namespaces[$short] = $long;
    }

    public static function get($short)
    {
        return self::$_namespaces[$short];
    }

    public static function to_sparql() {
        $sparql = "";
        foreach(self::$_namespaces as $short => $long) {
            $sparql .= "PREFIX $short: <$long>\n";
        }
        return $sparql;
    }

    public static function to_turtle() {
        $turtle = "";
        foreach(self::$_namespaces as $short => $long) {
            $turtle .= "@prefix $short: <$long> .\n";
        }
        return $turtle;
    }


}

?>
