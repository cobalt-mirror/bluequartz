<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: EmailAddress.php 3 2003-07-17 15:19:15Z will $

global $isEmailAddressDefined;
if($isEmailAddressDefined)
  return;
$isEmailAddressDefined = true;

include("uifc/FormField.php");
include("uifc/FormFieldBuilder.php");

class EmailAddress extends FormField {
  // private variables
  var $remote;

  // description: constructor
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  function EmailAddress(&$page, $id, $value = "", $im = "", $em = "") {
    // superclass constructor
    $this->FormField($page, $id, $value, $im, $em);
    $this->remote = false;
  }

  //
  // public methods
  //

  function toHtml($style = "") {
    $id = $this->getId();
    $builder = new FormFieldBuilder();
    $formField .= $builder->makeTextField($id, $this->getValue(),
		$this->getAccess(),
		$GLOBALS["_FormField_width"], null,
		$GLOBALS["_FormField_change"]
    );
    // if remote attribute set, use a different js validator.
    if ($this->isRemote()) {
	    $formField .= $builder->makeJavaScript($this,
		"top.code.EmailAddress_changeHandlerRemote",
		$GLOBALS["_FormField_TextField_submit"]);
    } else {
	    $formField .= $builder->makeJavaScript($this,
		"top.code.EmailAddress_changeHandler",
		$GLOBALS["_FormField_TextField_submit"]);
    }
    return $formField;
  }

  // description: get the remote attribute, this specifies if the js handler
  // will force the address to be of the form user@domain.com. Otherwise
  // unqualified aliases are allowed
  // returns: remote addribute in boolean
  // see: setRemote()
  function isRemote() {
    return $this->remote;
  }

  // description: set the remote attribute
  // param: fullSize: true to make the object accept only fully-qualified
  // email addresses of the form user@domain.com.  False otherwise.
  // returns: nothing
  // see: isRemote()
  function setRemote($remote) {
    $this->remote = $remote;
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

