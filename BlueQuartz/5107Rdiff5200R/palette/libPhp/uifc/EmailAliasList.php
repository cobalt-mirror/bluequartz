<?php
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: EmailAliasList.php 995 2007-05-05 07:44:27Z shibuya $

global $isEmailAliasListDefined;
if($isEmailAliasListDefined)
  return;
$isEmailAliasListDefined = true;

include_once("ArrayPacker.php");
include_once("uifc/EmailAddressList.php");
include_once("uifc/FormFieldBuilder.php");

class EmailAliasList extends EmailAddressList {
  
	function EmailAddressList ($page, $id, $value, $invalidMessage, $emptyMessage) {
		// superclass constructor
    		$this->EmailAddressList($page, $id, $value, $invalidMessage, $emptyMessage);
	}

	function toHtml($style = "") {
    		$page =& $this->getPage();
    		$form =& $page->getForm();
    		$formId = $form->getId();

    		$result = "<table border=0 cellspacing=0 cellpadding=0>\n<tr>\n<td>"; 
	
    		$builder = new FormFieldBuilder();
      		$result .= $builder->makeTextListField(
					$this->getId(), 
					stringToArray($this->getValue()), 
					$this->getAccess(), 
					$formId, 
					$GLOBALS["_FormField_height"], 
					$GLOBALS["_FormField_width"]);
      		$result .= $builder->makeJavaScript(
					$this, 
					"top.code.EmailAliasList_changeHandler",					$GLOBALS["_FormField_TextList_submit"]);

		$result .="</td>\n</tr>\n</table>\n";
	
		return $result;
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