<?php

class ParserSparqlResult extends Base {
   private $_result;
   private $_rowCurrent;
   private $_cellCurrent;
   private $_value;
   
   function __construct() {
   		parent::__construct();
   		$this->_result = array();
   }
   
   function getParser(){
	   	$objectParser = xml_parser_create();
	   	xml_set_object ($objectParser, $this);
	   	
	   	//Don't alter the case of the data
	   	xml_parser_set_option($objectParser, XML_OPTION_CASE_FOLDING, false);
	   	
	   	xml_set_element_handler($objectParser,"startElement","endElement");
	   	xml_set_character_data_handler($objectParser, "contentHandler");
	   	return $objectParser;
   }
   
   function getResult(){
   		return $this->_result;
   }

   //callback for the start of each element
   function startElement($parser_object, $elementname, $attribute) {
   //	echo $elementname."\n";
   	
   	if($elementname == "sparql"){   		
   		$this->_result['result'] =  array();
   	}else if($elementname == "head"){
   		$this->_result['result']['variables'] =  array();
   	}else if($elementname == "variable"){
   		$this->_result['result']['variables'][] = $attribute['name'];
   	}else if($elementname == "results"){
   		$this->_rowCurrent = -1;
   		$this->_result['result']['rows'] =  array();
   	}else if($elementname == "result"){
   		$this->_rowCurrent++;
   		$this->_result['result']['rows'][] =  array();   		
   	}else if($elementname == "binding"){
   		$this->_value = "";
   		$this->_cellCurrent = $attribute['name'];
   	}else if($this->_cellCurrent != null){
   		$this->_result['result']['rows'][$this->_rowCurrent][$this->_cellCurrent." type"] = $elementname;
   		
   		if(isset($attribute['xml:lang']))
   			$this->_result['result']['rows'][$this->_rowCurrent][$this->_cellCurrent." lang"] = $attribute['xml:lang'];
   	
   		if(isset($attribute['datatype']))
   			$this->_result['result']['rows'][$this->_rowCurrent][$this->_cellCurrent." datatype"] = $attribute['datatype'];
   	}
   }

   //callback for the end of each element
   function endElement($parser_object, $elementname) {
//    	echo $elementname."\n";
//    	if($elementname == "sparql"){
   		 
//    	}else if($elementname == "head"){
   		 
//    	}else if($elementname == "results"){
   		 
//    	}else if($elementname == "result"){
   		 
    	//}else 
    	if($elementname == "binding"){
    		$this->_result['result']['rows'][$this->_rowCurrent][$this->_cellCurrent] = $this->_value;
   			$this->_cellCurrent = null;
   			$this->_value = "";
   		}
   }

   //callback for the content within an element
   function contentHandler($parser_object,$data)
   {
	   	if($this->_cellCurrent != null){
		   	//echo "DATA". $data." - ".$this->_cellCurrent."\n";
		   	$this->_value .= $data;
	   	}
   }
	
	
	
}