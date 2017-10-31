<?php
// Author: Kevin K.M. Chiu
// $Id: Form.php

// description:
// This class represents a HTML form
//
// applicability:
// Places where a HTML form is needed
//
// usage:
// Each Page contains a Form object that is accessible by the getForm() method
// of the Page object. Form objects have getId() methods to get its ID which is
// used as the NAME attribute of the HTML FORM tag. Each form has a Javascript
// onsubmit() handler associated with it. Because Javascript function
// form.submit() does not call the onsubmit() handler, please call onsubmit()
// explicitly if you submit the form through Javascript. If no action is
// supplied, environment variable REQUEST_URI is used as action. Otherwise,
// Javascript variable isActionAvailable for the Form object is set to true.

global $isFormDefined;
if($isFormDefined)
  return;
$isFormDefined = true;

include_once("uifc/HtmlComponent.php");

class Form extends HtmlComponent {
  //
  // private variables
  //

  var $NAME = "form";

  var $action;
  var $isActionAvailable;
  var $id;
  var $target;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this object lives in
  // param: action: the ACTION attribute of the FORM tag. Optional
  //     If no supplied, it is set to environment variable REQUEST_URI
  function Form($page, $action = "") {
    // superclass constructor
    $this->HtmlComponent($page);

    // form action supplied?
    $this->isActionAvailable = true;
    if($action == "") {
      $this->isActionAvailable = false;

      // set form action to REQUEST_URI
      global $REQUEST_URI;
      $action = $REQUEST_URI;
    }

    $this->setAction($action);
    $this->setTarget("");

    $this->id = $this->NAME;
  }

  // description: get the ACTION attribute
  // param: action: the ACTION attribute of the FORM tag
  // see: setAction()
  function getAction() {
    return $this->action;
  }

  // description: set the ACTION attribute
  // param: action: the ACTION attribute of the FORM tag
  // see: getAction()
  function setAction($action) {
    $this->action = $action;
  }

  // description: get the TARGET attribute
  // returns: the TARGET attribute of the FORM tag
  // see: setTarget()
  function getTarget() {
    return $this->target;
  }

  // description: set the TARGET attribute
  // param: action: the TARGET attribute of the FORM tag
  // see: getTarget()
  function setTarget($target) {
    $this->target = $target;
  }

  // description: get the ID of the form. It is also the NAME attribute
  // returns: a string
  // see: setId()
  function getId() {
    return $this->id;
  }

  // description: set the ID of the form. It is also the NAME attribute
  // param: id: a string
  // see: getId()
  function setId($id) {
    $this->id = $id;
  }

  // description: get the form action that is used to submit the form
  // returns: a string
  function getSubmitAction() {
    // get wait string

    $id = $this->getId();
    return "javascript: if(document.$id.onsubmit()) { top.code.info_show(document._form_".$id."_wait, 'wait'); document.$id._save.value = 1; document.$id.submit(); }";
  }

  // description: translate the header of the form into HTML representation
  // param: style: a Style object that defines the style of the representation
  //     Optional. If not supplied, default style is used
  // returns: HTML in string
  function toHeaderHtml($style = "") {
    $id = $this->getId();
    $handlerName = "_Form_submitHandler_".$id;
    $encType = "multipart/form-data";
//    $encType = "application/x-www/form-urlencoded";

    $action = $this->getAction();
    $target = $this->getTarget(); 
    if ($target!="") {
      $targetAttribute = "TARGET=\"$target\"";
    }
    $attribute = ($action != "") ? "ACTION=\"$action\" METHOD=\"POST\" ENCTYPE=\"$encType\" $targetAttribute" : "";

    $actionAvailable = $this->isActionAvailable ? "document.$id.isActionAvailable = true;" : "";

    $page = $this->getPage(); 
    $i18n = $page->getI18n();
    $wait = $i18n->getJs("wait", "palette");

    $header = "
<FORM NAME=\"$id\" ID=\"$id\" $attribute>
<INPUT TYPE=\"HIDDEN\" NAME=\"_save\" VALUE=\"\">
<SCRIPT LANGUAGE=\"javascript\">


if (!document.$id && document.getElementById) 
 document.$id = document.getElementById(\"$id\"); 
 
document._form_".$id."_wait = '$wait';

$actionAvailable

function $handlerName() {
  var form = document.$id;

  // call all handlers
  for(var i = 0; i < form.elements.length; i++) {
    element = form.elements[i];
    if(element.changeHandler != null && !element.changeHandler(element))
      return false;
    if(element.submitHandler != null && !element.submitHandler(element))
      return false;
  }

  return true;
}

document.$id.onsubmit = $handlerName;
</SCRIPT>
";

    return $header;
  }

  // description: translate the footer of the form into HTML representation
  // param: style: a Style object that defines the style of the representation
  //     Optional. If not supplied, default style is used
  // returns: HTML in string
  function toFooterHtml($style = "") {
    return "</FORM>\n";
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