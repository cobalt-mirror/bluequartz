<?php
// Author: Kevin K.M. Chiu
// $Id: HtmlComponent.php

global $isHtmlComponentDefined;
if($isHtmlComponentDefined)
  return;
$isHtmlComponentDefined = true;

include_once("uifc/Stylish.php");

// also implements Collatable
class HtmlComponent extends Stylish {
  //
  // private variables
  //

  var $page;
  
  // style object with which to render the component
  var $style; 
  var $styleTarget;

  var $Label = "";
  var $Description = "";

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this HTML component is in
  function HtmlComponent(&$page) {
    $this->setPage($page);
    $this->style = "none";
    $style = "none";
    $styleTarget = "none";
    $this->styleTarget = "none";
  }

  // description: collate 2 values
  //     From the Collatable interface
  // param: valueA: the first value
  // param: valueB: the second value
  // param: collator: a Collator object
  // returns: ">" if A > B, "<" if A < B, "=" if A == B
  function collate($valueA, $valueB, $collator) {
    return $collator->collateStrings($valueA, $valueB, $collator);
  }

  // description: get the value for collation
  //     From the Collatable interface
  // returns: a value of any type
  function getCollatableValue() {
    return "";
  }

  // description: get the Page object this HTML component is in
  // returns: a Page object
  // see: setPage()
  function &getPage() {
    return $this->page;
  }

  // description: set the Page object this HTML component is in
  // param: page: a Page object
  // see: getPage()
  function setPage(&$page) {
    $this->page =& $page;
  }
  
  // Set the style object with which this component will be rendered
  // style:  a style object or style name
  function setStyle($style) {
    if(!is_object($style)) {
       //$stylist = $this->page->getStylist();
       //$style = $stylist->getStyle($style);
       $style = "";
    }
    $this->style = $style;
  }
  
  // Get the style object with which this component will be rendered
  function getStyle() {
    return ($this->style);
  }
  
    function setStyleTarget($target) {
        $this->styleTarget=$target;
    }
    
    function getStyleTarget() {
        return(  $this->styleTarget );
    }
    
  // Set the style based on 
 // If a style object is passed in, use it,
 // otherwise use the style class variable
 // or the default style 
  function setRightStyle($style) {  
      $page = $this->getPage();
    if(is_object($style)) {
        $this->setStyle($style);
    } else {
        if(!is_object($this->getStyle())) {
           $this->setStyle($this->getDefaultStyle($page->getStylist()));
        }
    }
  }

  // description: translate into a HTML representation
  // param: style: the style of the representation in a Style object
  // returns: HTML
  function toHtml($style = "") {
    return "";
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