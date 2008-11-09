<?php
// Author: Kevin K.M. Chiu, modified by Kenneth C.K. Leung
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: FullName.php 201 2003-07-18 19:11:07Z will $

global $isFullNameDefined;
if($isFullNameDefined)
  return;
$isFullNameDefined = true;

include("uifc/FormField.php");
include("uifc/FormFieldBuilder.php");

class FullName extends FormField {
  //
  // private variables
  //

  var $lastNameFirst = true;

  //
  // public methods
  //

  function isLastNameFirst(){
    return $this->lastNameFirst;
  }

  function setLastNameFirst($lastNameFirst){
    $this->lastNameFirst = $lastNameFirst;
  }

  function toHtml($style = "") {
    $id = $this->getId();
    $value = $this->getValue();

    if($this->getAccess() == "r" && $this->isLastNameFirst())
      $value = $this->_getLastNameFirst($value);

    $builder = new FormFieldBuilder();
    $formField .= $builder->makeTextField($id, $value, $this->getAccess(), $GLOBALS["_FormField_width"], null, $GLOBALS["_FormField_change"]);
    $formField .= $builder->makeJavaScript($this, "top.code.FullName_changeHandler", $GLOBALS["_FormField_TextField_submit"]);

    return $formField;
  }

  //
  // private methods
  //

  function _getLastNameFirst($fullName){
    if(strpos($fullName,",")==false) {
      $_nameChunk = explode(" ",$fullname);
      if(count($_nameChunk) > 1) {
	$fullName = $_name_Chunk[0].", ".substr($fullName,0,(strlen($fullName) - strlen($_name_Chunk[0])));
      }
    }else{
      $_cPos = strpos($fullName,",");
      if(substr($fullName,$cPos+1,1) != " ") {
	$fullName = substr($fullName,0,$cPos)." ".substr($fullName,$cPos);
      }
    }

    return $fullName;
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

