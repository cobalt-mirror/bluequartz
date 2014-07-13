<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: Password.php 1186 2008-09-10 21:37:51Z mstauber $

global $isPasswordDefined;
if($isPasswordDefined)
  return;
$isPasswordDefined = true;

include_once("uifc/FormField.php");
include_once("uifc/FormFieldBuilder.php");

class Password extends FormField {
  //
  // private variables
  //

  var $isConfirm;

  //
  // public methods
  //

  // description: constructor
  // param: page: the Page object this form field lives in
  // param: id: the identifier of this object
  // param: value: the default value
  // param: invalidMessage: message to be shown upon invalid input. Optional
  // param: emptyMessage: message to be shown upon empty input
  //     if the field is not optional. This message is optional
  function Password(&$page, $id, $value, $invalidMessage, $emptyMessage = "") {
    // superclass constructor
    $this->FormField($page, $id, $value, $invalidMessage, $emptyMessage);

    $this->isConfirm = true;
  }

  function &getDefaultStyle(&$stylist) {
    return $stylist->getStyle("Password");
  }

  // description: see if the confirm field is shown
  // returns: if true, a confirm field is shown
  // see: setConfirm()
  function isConfirm() {
    return $this->isConfirm;
  }

  // description: show/hide the confirm field
  // param: isConfirm: if true, a confirm field is shown
  // see: isConfirm()
  function setConfirm($isConfirm) {
    $this->isConfirm = $isConfirm;
  }

  function toHtml($style = "") {
    $id = $this->getId();
    $access = $this->getAccess();

    $builder = new FormFieldBuilder();
    $field1 = $builder->makePasswordField($id, $this->getValue(), 
	$access, $GLOBALS["_FormField_width"], "");

    // no need to make confirm field?
    if(!$this->isConfirm())
      return $field1;

    $page = $this->getPage();

    if($style == null || $style->getPropertyNumber() == 0)
      $style =& $this->getDefaultStyle($page->getStylist());

    // find out style properties
    $subscriptStyleStr = $style->toTextStyle("subscript");

    // make repeat field
    $repeatId = "_".$id."_repeat";
    $field2 = $builder->makePasswordField($repeatId, $this->getValue(), 
	$access, $GLOBALS["_FormField_width"], "");

    $i18n =& $page->getI18n();
    $repeatStr = $i18n->get("repeat", "palette");
    $pwCheckStr = $i18n->get("pwCheckStr", "palette");

    $formField = "
  <script language=\"Javascript\" type=\"text/javascript\" src=\"/libJs/ajax_lib.js\"></script>
  <script language=\"Javascript\">
    <!--
      checkpassOBJ = function() {
        this.onFailure = function() {
          alert(\"Unable to validate password\");
        }
        this.OnSuccess = function() {
          var response = this.GetResponseText();
          document.getElementById(\"results\").innerHTML = response;
        }
      }


      function validate_password ( word ) {
        checkpassOBJ.prototype = new ajax_lib();
        checkpass = new checkpassOBJ();
        var URL = \"/uifc/check_password.php\";
        var PARAM = \"password=\" + word;
        checkpass.post(URL, PARAM);
      }

    //-->
  </script>
<TABLE BORDER=\"0\" CELLPADDING=\"0\" CELLSPACING=\"0\">
  <TR><div><TD NOWRAP>$field1 <FONT STYLE=\"$subscriptStyleStr\"><div id=\"results\">$pwCheckStr</div></FONT></TD></div></TR>
  <TR><TD NOWRAP>$field2 <FONT STYLE=\"$subscriptStyleStr\">($repeatStr)</FONT></TD></TR>
</TABLE>
";

    $formField .= $builder->makeJavaScript($this, "", "top.code.Password_submitHandler");

    $form =& $page->getForm();
    $formId = $form->getId();
    $formField .= "
<SCRIPT LANGUAGE=\"javascript\">
var element = document.$formId.$id;
element.repeatElement = document.$formId.$repeatId;
</SCRIPT>
";

    return $formField;
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
