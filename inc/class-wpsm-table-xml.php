<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Array_XML {
	
	private $writer;
	private $version = '1.0';
	private $encoding = 'UTF-8';
	private $rootName = 'wpsm';
	
	function __construct() {
		$this->writer = new XMLWriter();
	}
	
	public function convert($data) {
		$this->writer->openMemory();
		$this->writer->startDocument($this->version, $this->encoding);
		$this->writer->startElement($this->rootName);
		if (is_array($data)) {
			$this->getXML($data);
		}
		$this->writer->endElement();
		return $this->writer->outputMemory();
	}

	public function setVersion($version) {
		$this->version = $version;
	}
	
	public function setEncoding($encoding) {
		$this->encoding = $encoding;
	}
	
	public function setRootName($rootName) {
		$this->rootName = $rootName;
	}
	
	private function getXML($data) {
		foreach ($data as $key => $val) {
			if (!is_array($val) && $val == '') {
				continue;
			} else {
				if (is_numeric($key)) {
					$key = 'key'.$key;
				}
				if (is_array($val)) {
					$this->writer->startElement($key);
					$this->getXML($val);
					$this->writer->endElement();
				}
				else {
					$this->writer->writeElement($key, $val);
				}
			}
		}
	}
}

function xml2array($xml) {
	$json = json_encode($xml);
	$array = json_decode($json,TRUE);
	$tvalues = $array['tvalues'];

	foreach($tvalues as $_key0=>$_v0) {
	  $key1 = str_ireplace('key', '', $_key0);

	  foreach($_v0 as $_key1=>$_val) {
		$key2 = str_ireplace('key', '', $_key1);
		$_v[$key1][$key2] = $_val;
	  }
	}
	$array['tvalues'] = $_v;
	unset($_key0, $_v0, $_key, $_key1, $_val, $_v);
	
	if(isset($array['id']) ) {
		unset( $array['id'] );
	}
	return $array;
}

function csv2array($handle, $delimiter) { 
	
		$row = 0; 
		while (($line_array = fgetcsv($handle, 4000, $delimiter)) !== FALSE) { 
			$cols = count($line_array);
				for ($j=0; $j<$cols; $j++) {
					$col = $j + 1;
					if (!is_array($line_array[$j]) && $line_array[$j] == '') {
						continue;
					} else {
						$table_array[$row][$col] = $line_array[$j];
					}
				} 
			$row++; 
		} 
		

	$rows = $row - 1;
	return $data = array( 'trows' => $rows, 'tcols' => $cols, 'tvalues' => $table_array ); 
}