<?php
// Author: Patrick Bose 
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: BarGraph.php 995 2007-05-05 07:44:27Z shibuya $

global $isBarGraphDefined;
if ( $isBarGraphDefined )
	return;
$isBarGraphDefined = true;

include_once("uifc/Graph.php");


class BarGraph extends Graph {

	var $ylabel;

	//
	// public methods
	//

	// constructor
	function BarGraph( $page, $data ) {
		$this->Graph( $page, $data );
	}

	function setYLabel( $label ) {
		$this->ylabel = $label;
	}

	function toHtml( $style = "" ) {
		$generatorPipe = $this->getImagePipe();
		$this->setGraphStyleOptions( $style );
		$this->setBarGraphStyleOptions( $style );
		$this->writeImageData( $generatorPipe );
		pclose( $generatorPipe );

		$filename = $this->getFilename();
		return "<TABLE><TR><TD>
	<TABLE><TR><TD WIDTH=\"50\"><CENTER>$this->ylabel</CENTER></TD><TD WIDTH=\"400\"><IMG SRC=\"$filename\"></TD></TR></TR></TABLE>
	</TD></TR><TR><TD>
	</TD></TR></TABLE>";
	}

	function getDefaultStyle( $stylist ) {
		return $stylist->getStyle("BarGraph");
	}	

	//	
	// protected methods
	//

	function setBarGraphStyleOptions( $style = "" ) {
		if ( $style == null || $style->getPropertyNumber() == 0 ) {
			$page = $this->getPage();
			$stylist = $page->getStylist();
			$style = $stylist->getStyle("BarGraph");
		}

		$barSpacing = $style->getProperty("bar_spacing");
		if ( $barSpacing ) {
			$this->setOption( "bar_spacing", $barSpacing );
		}
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
