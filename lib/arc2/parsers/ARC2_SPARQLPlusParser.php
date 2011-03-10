<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 SPARQL+ Parser (SPARQL + Aggregates + LOAD + INSERT + DELETE)
author:   Benjamin Nowack
version:  2010-11-16

author:   Karima Rafes
homepage: http://www.bordercloud.com
version:  2011-03-06
Change INSERT and DELETE

*/

ARC2::inc('SPARQLParser');

class ARC2_SPARQLPlusParser extends ARC2_SPARQLParser {

  function __construct($a, &$caller) {
    parent::__construct($a, $caller);
  }
  
  function __init() {
    parent::__init();
  }

  /* +1 */
  
  function xQuery($v) {
    list($r, $v) = $this->xPrologue($v);
    foreach (array('Select', 'Construct', 'Describe', 'Ask', 'Insert', 'Delete', 'Load') as $type) {
      $m = 'x' . $type . 'Query';
      if ((list($r, $v) = $this->$m($v)) && $r) {
        return array($r, $v);
      }
    }
    return array(0, $v);
  }

  /* +3 */
  
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

  /* +4 */
 
  function xLoadQuery($v) {
    if ($sub_r = $this->x('LOAD\s+', $v)) {
      $sub_v = $sub_r[1];
      if ((list($sub_r, $sub_v) = $this->xIRIref($sub_v)) && $sub_r) {
        $r = array('type' => 'load', 'url' => $sub_r, 'target_graph' => '');
        if ($sub_r = $this->x('INTO\s+', $sub_v)) {
          $sub_v = $sub_r[1];
          if ((list($sub_r, $sub_v) = $this->xIRIref($sub_v)) && $sub_r) {
            $r['target_graph'] = $sub_r;
          }
        }
        return array($r, $sub_v);
      }
    }
    return array(0, $v);
  }
  
  /* +5 */
  
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

  /* +6 */
  
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
  
  /* +7 */
  
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

  /* +8 */

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
