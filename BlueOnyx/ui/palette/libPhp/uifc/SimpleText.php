<?php
// 			Very simple in-style text block widget
//
// Author: Eric Braswell	
//
// Copyright 2001, Sun Microsystems.  All rights reserved.
// $Id: SimpleText.php 1050 2008-01-23 11:45:43Z mstauber $

global $isSimpleTextDefined;
if($isSimpleTextDefined)
  return;
$isSimpleTextDefined = true;

class SimpleText {

	var $style;
	var $text;
	// public methods

	// description: constructor
	// param: text: a text string. 
	// param: style: a style object. Optional

	function SimpleText($text, $style="") {
		if (false) {
			/*
			 * FIXME:  this is fundamentally broken, because
			 * it creates a new CCE connection for every
			 * SimpleText object created
			 */
			include_once("uifc/Stylist.php");
			include_once("ServerScriptHelper.php");
			$crackaddict = new ServerScriptHelper();
			$stylist = $crackaddict->getStylist();
			$this->style = $stylist->getStyle("SimpleText"); // try this
			
			if($this->style->getPropertyNumber()) { 
				$this->style = $stylist->getStyle("Page"); // default
			}
				
		} else {
			$this->style = $style;
		}		
		$this->text = $text;
	}


	function toHtml() {
		//return("<FONT" . (is_object($this->style) ? " STYLE=\"" . $this->style->toTextStyle() . "\"" : "") . ">" . $this->text . "</FONT>");
		return("<FONT" . " STYLE=\"font-size:12px\"" . ">" . $this->text . "</FONT>");

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
