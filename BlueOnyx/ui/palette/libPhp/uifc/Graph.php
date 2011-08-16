<?php
// Author: Patrick Bose 
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Graph.php 1050 2008-01-23 11:45:43Z mstauber $

global $isGraphDefined;
if ( $isGraphDefined ) 
	return;
$isGraphDefined = true;

include_once("uifc/HtmlComponent.php");

class Graph extends HtmlComponent {

	//
	// private variables
	//
	var $options;
	var $xlabels;
	var $data;
	var $filename;

	// 
	// public methods
	//

	// description: constructor
	function Graph( $page, $data ) {
		$this->HtmlComponent( $page );
		$this->setData( $data );
	}

	function setXLabels( $xlabels ) {
		$this->xlabels = $xlabels;
	}

	function setData( $data ) {
		$this->data = $data;
	}
	
	function setFilename( $filename ) {
		$this->filename = $filename;
	}

	function getData() { return $this->data; }
	function getXLabels() { return $this->xlabels; }
	function getFilename() { return $this->filename; }

	function getDefaultStyle( $stylist ) {
		return $stylist->getStyle("Graph");
	}	

	//	
	// protected functions
	//
	
	function setOption( $name, $value ) {
		$this->options[$name] = $value;
	}
	
	function setOptions( $options ) {
		$this->options = $options;
	}

	function setGraphStyleOptions( $style = "" ) {
		if ( $style == null || $style->getPropertyNumber() == 0 ) {
			$page = $this->getPage();
#			$style = $this->getDefaultStyle( $page->getStylist() );
			$stylist = $page->getStylist();
			$style = $stylist->getStyle("Graph");
		}

		$width = $style->getProperty("width");
		$height = $style->getProperty("height");
		$shadowDepth = $style->getProperty("shadow_depth");
		if ( $shadowDepth ) {
			$this->options["shadow_depth"] = $shadowDepth;
		}
		$shadowColor = $style->getProperty("shadowclr");
		if ( $shadowColor ) {
			$this->options["shadowclr"] = $shadowColor;
		}	
		$dataColors = $style->getProperty("dclrs");
		if ( $dataColors ) {
			$this->options["dclrs"] = $dataColors;
		}

	}

	function getOptions() { return $this->options; }

	function getImagePipe() {
		$cmd = "/usr/local/bin/generateGraph.pl";
		if ( $this->filename ) { $cmd .= " -f $this->filename"; }
		$generatorPipe = popen($cmd, "w");
		return $generatorPipe;
	}

	// writes to the data file information common to all graphs
	function writeImageData( $dataFile ) {
		$options = $this->getOptions();
		if($options > 0) {
			$keys = array_keys( $options );
		} else {
			$keys = array();
		}
		for ($i=0; $i<count($keys); $i++) {
			$value = $options[$keys[$i]];
			fwrite( $dataFile, "$keys[$i] $value\n");
			next($options);
		}

		fwrite( $dataFile, "dataset ");
		$xlabels = $this->getXLabels();
		for ($i=0; $i<count($xlabels); $i++) {
			fwrite( $dataFile, $xlabels[$i] . " ");
		}

		$data = $this->getData();
		for ($i=0; $i<count($data); $i++) {
			fwrite( $dataFile, ":" );
			for ($j=0; $j<count($data[$i]); $j++) {
				fwrite( $dataFile, $data[$i][$j] . " ");
			}
		}
		fwrite( $dataFile, ":\n" );	
	}

}
/*
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>
