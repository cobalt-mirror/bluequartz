<?php
// Author: Kevin K.M. Chiu
// $Id: FormField.php

// description:
// Form field is an abstract superclass of all form fields (e.g. IpAddress,
// Time)
//
// applicability:
// All concrete implementation of form fields should extend this class. Never
// instantiate this class because it is abstract

global $isFormFieldDefined;
if($isFormFieldDefined)
  return;
$isFormFieldDefined = true;

include_once("uifc/FormFieldBuilder.php");
include_once("uifc/HtmlComponent.php");

//
// protected class variables
//

// generic dimension of form fields
$GLOBALS["_FormField_height"] = 4;
$GLOBALS["_FormField_width"] = 20;
$GLOBALS["_FormField_width_big"] = 30;
// generic handlers
$GLOBALS["_FormField_change"] = "if(this.changeHandler != null) return this.changeHandler(this); else return true;";
$GLOBALS["_FormField_TextField_submit"] = "top.code.FormField_textFieldSubmitHandler";
$GLOBALS["_FormField_TextList_submit"] = "top.code.FormField_textListSubmitHandler";

class FormField extends HtmlComponent {
  //
  // private variables
  //

  // short abbreviated variable names are not good for code readability, but long
  // variable names in high level superclasses can result in big memory consumption.
  // This is because PHP stores full variable names in every object.

  public $access;
  // short for empty message
  public $em;
  public $id;
  // short for invalid message
  public $im;
  // short for the optional boolean
  public $opt;
  public $value;
  // to support the ability to turn data preservation on and off as desired
  public $_prsrv;

  //
  // public methods
  //

  // Sets the current label
  function setCurrentLabel($label) {
    $this->Label = $label;
  }

  // Returns the current label
  function getCurrentLabel() {
    if (!isset($this->Label)) {
      $this->Label = "";
    }
    return $this->Label;
  }

  // Sets the current label-description:
  function setDescription($description) {
    if (!isset($this->Description)) {
      $this->Description = "";
    }
    $this->Description = $description;
  }

  // Returns the current label-description:
  function getDescription() {
    return $this->Description;
  }

  // description: constructor
  // param: page: a Page object that this form field lives in
  // param: id: the identifier of this form field
  //     Used in the NAME attribute of input fields
  // param: value: the default value of this form field
  //     Depending on what this form field is, the value can be different
  // param: im: message to be shown upon invalid input. Optional
  // param: em: message to be shown upon empty input
  //     if the field is not optional. This message is optional
  function FormField(&$page, $id, $value = "", $i18n, $checktype="", $im = "", $em = "") {
    // superclass constructor
    $this->HtmlComponent($page);

    $this->setId($id);
    $this->setValue($value);
    $this->setInvalidMessage($im);
    $this->setEmptyMessage($em);
    $this->_prsrv = true;
    $this->i18n = $i18n;
    $this->checktype = $checktype;

    // set defaults
    $this->setAccess("rw");
    $this->setOptional(false);

  }

  function getI18n() {
    return $this->i18n;
  }

  function getCheckType() {
    return $this->checktype;
  }

  // description: get the access property
  // returns: a string
  // see: setAccess()
  function getAccess() {
    return $this->access;
  }

  // description: set the access property
  // param: access can be "" for hidden, "r" for read-only,
  //     "w" for write-only and "rw" for both read and write
  // returns: true if succeed, false if failed
  // see: getAccess()
  function setAccess($access) {
    if($access != "" &&
      $access != "r" &&
      $access != "html" &&
      $access != "w" &&
      $access != "R" &&
      $access != "rw")
      return false;

    $this->access = $access;

    return true;
  }

  function getCollatableValue() {
    return $this->value;
  }

  // description: get the message to display when the form field is empty while
  //     it should not
  // returns: a string
  // see: setEmptyMessage()
  function getEmptyMessage() {
    return $this->em;
  }

  // description: set the message to display when the form field is empty while
  //     it should not
  // param: em: a string
  // see: getEmptyMessage()
  function setEmptyMessage($em) {
    $this->em = $em;
  }

  // description: get the ID of the form field
  // returns: a string
  // see: setId()
  public function getId() {
    return $this->id;
  }

  // description: set the unique ID of the form field
  //     it is used to identify the form field
  //     when the form containing the field is submitted,
  //     this ID is a variable name in the submit
  //     only alphanumeric characters and underscores are supported
  // param: id: a string
  // see: getId()
  public function setId($id) {
    // strigent ID check because things like Javascript and CSS can break
    // in particular, Javascript dies on hyphen, CSS dies on underscore
    if(!preg_match("/^([a-zA-Z0-9\_])*$/", $id)) {
      $err = "Error: FormField ID $id contains characters that are not alphanumeric or underscore";
      print($err);
      error_log($err, 0);
      exit;
    }

    $this->id = $id;
  }

  // description: get the message to display when the form field is invalid
  // returns: a string
  // see: setInvalidMessage()
  function getInvalidMessage() {
    return $this->im;
  }

  // description: set the message to display when the form field is invalid
  // param: im: a string
  // see: getInvalidMessage()
  function setInvalidMessage($im) {
    $this->im = $im;
  }

  // description: get the optional flag
  // returns: true if this form field is optional, false otherwise
  // see: setOptional()
  function isOptional() {
    return $this->opt;
  }

  // description: set the optional flag
  //     it indicates if the form field can be empty
  // param: optional: true if the field is optional, false otherwise
  // see: isOptional()
  function setOptional($optional) {
    $this->opt = $optional;
  }

  // description: get the value
  // returns: the value of different types depending on which concrete subclass
  //     of form field this is
  // see: setValue()
  function getValue() {
    global $HTTP_POST_VARS;

    // return user data by default 
    if ($this->_prsrv && isset($HTTP_POST_VARS[$this->id]))
    {
        // debug code
        // error_log("FormField::getValue(): returning http_post_var for $this->id " . $HTTP_POST_VARS[$this->id]);
//        error_log("$this->id: data preserving");
        return $HTTP_POST_VARS[$this->id];
    }
    else  // use the value given during creation or via setValue
    {
        // debug code
        // error_log("FormField::getValue(): returning value for $this->id " . $this->value);
//        error_log("$this->id: NOT data preserving");
        return $this->value;
    }
  }

  // description: set the value
  //     depending on the concrete type of the form field (e.g. IpAddress),
  //     this value can be of different type
  // param: value: any variable
  // see: getValue()
  function setValue($value) {
    $this->value = $value;
  }

    // set whether or not to preserve user data
    function setPreserveData($status = true)
    {
        $this->_prsrv = $status;
    }

    // returns true if user data is being preserved, false otherwise
    function isDataPreserved()
    {
        return $this->_prsrv;
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