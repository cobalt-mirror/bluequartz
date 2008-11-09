<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: FormField.php 237 2003-09-10 08:22:45Z shibuya $

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

  var $access;
  // short for empty message
  var $em;
  var $id;
  // short for invalid message
  var $im;
  // short for the optional boolean
  var $opt;
  var $value;
  // to support the ability to turn data preservation on and off as desired
  var $_prsrv;

  //
  // public methods
  //

  // description: constructor
  // param: page: a Page object that this form field lives in
  // param: id: the identifier of this form field
  //     Used in the NAME attribute of input fields
  // param: value: the default value of this form field
  //     Depending on what this form field is, the value can be different
  // param: im: message to be shown upon invalid input. Optional
  // param: em: message to be shown upon empty input
  //     if the field is not optional. This message is optional
  function FormField(&$page, $id, $value = "", $im = "", $em = "") {
    // superclass constructor
    $this->HtmlComponent($page);

    $this->setId($id);
    $this->setValue($value);
    $this->setInvalidMessage($im);
    $this->setEmptyMessage($em);
    $this->_prsrv = true;

    // set defaults
    $this->setAccess("rw");
    $this->setOptional(false);
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
  function getId() {
    return $this->id;
  }

  // description: set the unique ID of the form field
  //     it is used to identify the form field
  //     when the form containing the field is submitted,
  //     this ID is a variable name in the submit
  //     only alphanumeric characters and underscores are supported
  // param: id: a string
  // see: getId()
  function setId($id) {
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
        return $HTTP_POST_VARS[$this->id];
    }
    else  // use the value given during creation or via setValue
    {
        // debug code
        // error_log("FormField::getValue(): returning value for $this->id " . $this->value);
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
