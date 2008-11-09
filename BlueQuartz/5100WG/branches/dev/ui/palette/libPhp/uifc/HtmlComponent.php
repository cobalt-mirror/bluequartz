<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: HtmlComponent.php 201 2003-07-18 19:11:07Z will $

global $isHtmlComponentDefined;
if($isHtmlComponentDefined)
  return;
$isHtmlComponentDefined = true;

include("uifc/Stylish.php");

// also implements Collatable
class HtmlComponent extends Stylish {
  //
  // private variables
  //

  var $page;
  
  // style object with which to render the component
  var $style; 
  var $styleTarget;
  
  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this HTML component is in
  function HtmlComponent(&$page) {
    $this->setPage($page);
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
       $stylist = $this->page->getStylist();
       $style = $stylist->getStyle($style);
    }
    $this->style = $style;
  }
  
    // Get the style object with which this component will be rendered
  function getStyle() {
    return($this->style);
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

