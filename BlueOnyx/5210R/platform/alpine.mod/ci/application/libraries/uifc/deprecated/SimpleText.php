<?php
// 			Very simple in-style text block widget
//
// Author: Eric Braswell	
//
// $Id: SimpleText.php

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
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>