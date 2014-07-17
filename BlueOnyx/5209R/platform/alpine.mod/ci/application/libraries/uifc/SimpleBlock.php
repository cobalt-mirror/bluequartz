<?php
// Author: Kevin K.M. Chiu, Michael Stauber
// $Id: SimpleBlock.php

// description:
// SimpleBlock represents a block with a title and HtmlComponents in it.
//
// applicability:
// Use in places where when a title needs to be associated with HtmlComponents.
//
// usage:
// After instantiation, use addHtmlComponent() to add HTML components into the
// block. Use toHtml() to get HTML representation.

global $isSimpleBlockDefined;
if($isSimpleBlockDefined)
	return;
$isSimpleBlockDefined = true;

include_once("uifc/Block.php");

class SimpleBlock extends Block {
	//
	// private variables
	//

	var $components;
	var $dividers;
	var $dividerIndexes;

	//
	// public methods
	//

	// description: constructor
	// param: page: the Page object this block is in
	// param: label: a Label object for the block title. Optional
	function SimpleBlock($page, $label = "", $i18n = "") {
		// superclass constructor
		$this->Block($page, $label);

		$this->i18n = $i18n;

		$this->components = array();
		$this->dividers = array();
		$this->dividerIndexes = array();
	}

	function getDefaultStyle($stylist) {
		return '';
	}

	// description: get all the HTML components of the block
	// returns: an array of HtmlComponent objects
	// see: addHtmlComponent()
	function getHtmlComponents() {
		return $this->components;
	}

	// description: add a HTML component to this block
	// param: htmlComponent: a HtmlComponent object
	// see: getHtmlComponents()
	function addHtmlComponent($htmlComponent) {
		$this->components[] = $htmlComponent;
	}

	// description: get all dividers added to the block
	// returns: an array of Label objects
	// see: addDivider()
	function getDividers() {
		return $this->dividers;
	}

	// description: add a divider
	// param: label: a label object. Optional
	// see: getDividers()
	function addDivider($label = "") {
		$this->dividers[] = $label;

		// find the number of HTML components before the divider on the page
		$components = $this->getHtmlComponents();

		$this->dividerIndexes[] = count($components);
	}

	function toHtml($style = "") {
		$page = $this->getPage();
		if ($this->i18n == "") {
			$i18n = $page->getI18n();
		}
		else {
			$i18n = $this->i18n;
		}

		$form = $page->getForm();
		$formId = $form->getId();

		// there can be no title
		$titleLabel = $this->getLabel();
		$titleLabelHtml = is_object($titleLabel) ? $titleLabel->toHtml("TEST") : "";

		// find all HTML components
		$components = $this->getHtmlComponents();

		// find all dividers
		$dividers = $this->getDividers();
		$dividerIndexes = $this->dividerIndexes;

		$result = "";

		if($titleLabelHtml != "") {
			$result .= "";
		}

		for($i = 0; $i < count($components); $i++) {
			// get HTML
			$componentHtml = $components[$i]->toHtml();
			$result .= $componentHtml;
		}

		// make buttons
		$buttons = $this->getButtons();
		$allButtons = '';
		if(count($buttons) > 0) {
			for($i = 0; $i < count($buttons); $i++) {
				if($i > 0)
					$allButtons .= $buttons[$i]->toHtml();
				}
			}
			$result .= "\n";
			return $result;
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