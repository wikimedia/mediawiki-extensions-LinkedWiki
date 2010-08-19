<?php
/**
 * @version 0.1.0.0
 * @package Bourdercloud/4store-PHP
 * @copyright (c) 2010 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * 
 * This file is a fork of ARC2__NTriplesSerializer : 
 * @author  Benjamin Nowack
 * @copyright http://arc.semsol.org/
 * @license http://arc.semsol.org/license

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
require_once(dirname(__FILE__) . '/../arc/ARC2.php');

ARC2::inc('RDFSerializer');

class FourStore_NTriplesSerializer extends ARC2_RDFSerializer {

  function __construct($a = '', &$caller = null) {
    parent::__construct($a, $caller);
  }
  
  function FourStore_NTriplesSerializer($a = '', &$caller  = null) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->esc_chars = array();
    $this->raw = 0;
  }

  function getTerm($v) {
    if (!is_array($v)) {
      if (preg_match('/^\_\:/', $v)) {
        return $v;
      }
      if (preg_match('/^[a-z0-9]+\:[^\s\"]*$/is', $v)) {
        return '<' . $v . '>';
      }
      return $this->getTerm(array('type' => 'literal', 'value' => $v));
    }
    if ($v['type'] != 'literal') {
      return $this->getTerm($v['value']);
    }
    /* literal */
    $quot = '"';
    $v['value'] = addcslashes($v['value'],"\t\n\r\b\f\"\'\\") ;
    $suffix = isset($v['lang']) && $v['lang'] ? '@' . $v['lang'] : '';
    $suffix = isset($v['datatype']) && $v['datatype'] != "" ? '^^' . $this->getTerm($v['datatype']) : $suffix;
 	 return $quot .$v['value'] . $quot . $suffix;
  }
  
  function getSerializedIndex($index, $raw = 0) {
    $this->raw = $raw;
    $r = '';
    $nl = "\n";
    foreach ($index as $s => $ps) {
      $s = $this->getTerm($s);
      foreach ($ps as $p => $os) {
        $p = $this->getTerm($p);
        if (!is_array($os)) {/* single literal o */
          $os = array(array('value' => $os, 'type' => 'literal'));
        }
        foreach ($os as $o) {
          $o = $this->getTerm($o);
          $r .= $r ? $nl : '';
          $r .= $s . ' ' . $p . ' ' . $o . ' .';
        }
      }
    }
    return $r . $nl;
  } 
}
