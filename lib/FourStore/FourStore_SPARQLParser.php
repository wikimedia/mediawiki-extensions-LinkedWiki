<?php
/**
 * @version 0.1.0.0
 * @package Bourdercloud/4store-PHP
 * @copyright (c) 2010 Bourdercloud.com
 * @author Karima Rafes <karima.rafes@bordercloud.com>
 * 
 * This file is a fork of ARC2_SPARQLPlusParser : 
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

ARC2::inc('SPARQLParser');

/**
 * Hack of the class ARC2_SPARQLPlusParser for 4Store's query syntax (source http://arc.semsol.org/)
 */
class FourStore_SPARQLParser extends ARC2_SPARQLParser {

	
  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_SPARQLPlusParser($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  
  function __init() {
    parent::__init();
  }
  
  function xQuery($v) {
    list($r, $v) = $this->xPrologue($v);
    foreach (array('Select', 'Construct', 'Describe', 'Ask', 'Insert', 'Delete') as $type) {
      $m = 'x' . $type . 'Query';
      if ((list($r, $v) = $this->$m($v)) && $r) {
        return array($r, $v);
      }
    }
    return array(0, $v);
  }

  function xResultVar($v) {
    $aggregate = '';
    /* aggregate */
    if ($sub_r = $this->x('\(?(AVG|COUNT|MAX|MIN|SUM)\s*\(\s*([^\)]+)\)\s+AS\s+([^\s\)]+)\)?', $v)) {
      $aggregate = $sub_r[1];
      $result_var = $sub_r[3];
      $v = $sub_r[2] . $sub_r[4];
    }
    if ($sub_r && (list($sub_r, $sub_v) = $this->xVar($result_var)) && $sub_r) {
      $result_var = $sub_r['value'];
    }
    /* * or var */
    if ((list($sub_r, $sub_v) = $this->x('\*', $v)) && $sub_r) {
      return array(array('var' => $sub_r['value'], 'aggregate' => $aggregate, 'alias' => $aggregate ? $result_var : ''), $sub_v);
    }
    if ((list($sub_r, $sub_v) = $this->xVar($v)) && $sub_r) {
      return array(array('var' => $sub_r['value'], 'aggregate' => $aggregate, 'alias' => $aggregate ? $result_var : ''), $sub_v);
    }
    return array(0, $v);
  }
  
  
  function xInsertQuery($v) {
	if ($sub_r = $this->x('INSERT\s+', $v)) {    	
	      $r = array(
	        'type' => 'insert'
	      );
	      $sub_v = $sub_r[1];
	      /* target */
	      if ($sub_r = $this->x('DATA\s+', $sub_v)) {
	        $sub_v = $sub_r[1];
	        if ((list($sub_r, $sub_v) = $this->xGroupGraphPattern($sub_v)) && $sub_r) {
		      	if( 'graph' != $sub_r['patterns'][0]['type'] ){
		            $this->addError("The graph didn't find Usage : \nPREFIX ex: <http://example.com/>  INSERT DATA { GRAPH ex:test {ax:a ex:b ex:c.}}");
		            return array(0, $v);
		          }
		          $r['into'] = $sub_r['patterns'][0]['uri'];
		      		$r['construct_triples'] = $sub_r['patterns'][0]['patterns'][0]['patterns'][0]['patterns'];
		      	 return array($r, $sub_v);
		    }
        }
    }
    return array(0, $v);
  }

  function xDeleteQuery($v) {
	if ($sub_r = $this->x('DELETE\s+', $v)) {    	
	      $r = array(
	        'type' => 'delete'
	      );
	      $sub_v = $sub_r[1];
	      /* target */
	      if ($sub_r = $this->x('DATA\s+', $sub_v)) {
	        $sub_v = $sub_r[1];
	        if ((list($sub_r, $sub_v) = $this->xGroupGraphPattern($sub_v)) && $sub_r) {
		      	if( 'graph' != $sub_r['patterns'][0]['type'] ){
		            $this->addError("The graph didn't find Usage : \nPREFIX ex: <http://example.com/>  DELETE DATA { GRAPH ex:test {ax:a ex:b ex:c.}}");
		            return array(0, $v);
		          }
		          $r['into'] = $sub_r['patterns'][0]['uri'];
		      		$r['construct_triples'] = $sub_r['patterns'][0]['patterns'][0]['patterns'][0]['patterns'];
		      	 return array($r, $sub_v);
		    }
        }
    }
    return array(0, $v);
  }
  
  
  function xSolutionModifier($v) {
    $r = array();
    if ((list($sub_r, $sub_v) = $this->xGroupClause($v)) && $sub_r) {
      $r['group_infos'] = $sub_r;
    }
    if ((list($sub_r, $sub_v) = $this->xOrderClause($sub_v)) && $sub_r) {
      $r['order_infos'] = $sub_r;
    }
    while ((list($sub_r, $sub_v) = $this->xLimitOrOffsetClause($sub_v)) && $sub_r) {
      $r = array_merge($r, $sub_r);
    }
    return ($v == $sub_v) ? array(0, $v) : array($r, $sub_v);
  }

  function xGroupClause($v) {
    if ($sub_r = $this->x('GROUP BY\s+', $v)) {
      $sub_v = $sub_r[1];
      $r = array();
      do {
        $proceed = 0;
        if ((list($sub_r, $sub_v) = $this->xVar($sub_v)) && $sub_r) {
          $r[] = $sub_r;
          $proceed = 1;
          if ($sub_r = $this->x('\,', $sub_v)) {
            $sub_v = $sub_r[1];
          }
        }
      } while ($proceed);
      if (count($r)) {
        return array($r, $sub_v);
      }
      else {
        $this->addError('No columns specified in GROUP BY clause.');
      }
    }
    return array(0, $v);
  }

}  
