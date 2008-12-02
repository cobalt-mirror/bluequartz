<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: UninstallButton.php 1050 2008-01-23 11:45:43Z mstauber $

global $isUninstallButtonDefined;
if($isUninstallButtonDefined)
  return;
$isUninstallButtonDefined = true;

include_once("uifc/Button.php");
include_once("uifc/ImageLabel.php");

class UninstallButton extends Button {
  //
  // public methods
  //

  function &getDefaultStyle(&$stylist) {
    return $stylist->getStyle("UninstallButton");
  }

  // description: constructor
  // param: page: the Page object this object lives in
  // param: action: the string used within HREF attribute of the A tag
  function UninstallButton(&$page, $action) {
    $i18n =& $page->getI18n();
    $stylist =& $page->getStylist();
    $style =& $stylist->getStyle("UninstallButton");

    $label = $i18n->get("uninstall", "palette");
    $description = $i18n->get("uninstall_help", "palette");
    $disabled_help = $i18n->get("uninstall_disabled_help", "palette");

    $this->Button($page, $action, new ImageLabel($page, $style->getProperty("uninstallIcon"), $label, $description), new ImageLabel($page, $style->getProperty("uninstallIconDisabled"), $label, $disabled_help));
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
