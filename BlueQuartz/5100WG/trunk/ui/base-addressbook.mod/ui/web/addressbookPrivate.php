<?php
// Author: Kenneth C.K. Leung, Mike Waychison
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: addressbookPrivate.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");
include("./addressbookPrivateCommon.php");
include("uifc/Button.php");
include("uifc/ImageButton.php");
include("ArrayPacker.php");

$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-addressbook", "/base/addressbook/addressbookPrivate.php");
$i18n = $serverScriptHelper->getI18n("base-addressbook");

$page = $factory->getPage();

$scrollList = $factory->getScrollList("addressbookPrivate", array("fullname","email","phone","listAction"),array(1,2));
$scrollList->addButton($factory->getAddButton("/base/addressbook/addressbookPrivateAdd.php"));
$scrollList->setAlignments(array("left", "left", "center", "left"));
$scrollList->setColumnWidths(array("280", "230", "", "1%"));

$addys = addressBookGetall($serverScriptHelper);

for($i = 0; $i < count($addys); $i++) {
  $addressbookEntry = $addys[$i];

  $fullname = $factory->getTextField("", $addressbookEntry[$FULLNAME],"r");
  $email = $factory->getTextField("", $addressbookEntry[$EMAIL],"r");
  $phone = $factory->getTextField("", $addressbookEntry[$PHONE],"r");
  $actions = $factory->getCompositeFormField();
  $actions->addFormField($factory->getModifyButton("/base/addressbook/addressbookPrivateModify.php?oid=$addressbookEntry[$ID]"));
  $actions->addFormField($factory->getRemoveButton("javascript: confirmRemove('".$addressbookEntry[$ID]. "', '" . $addressbookEntry[$FULLNAME] . "')"));
  if ($addressbookEntry[$EMAIL]) $actions->addFormField(new ImageButton($page,"/base/webmail/compose.php?toAddy=".rawurlencode($addressbookEntry[$EMAIL]),"/libImage/composeEmail.gif", "mail", "mail_help"));
  $scrollList->addEntry(array($fullname,$email,$phone,$actions));
}

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(oid, fullname) {
  var message = "<?php print($i18n->get("removeAddressbookPrivateConfirm"))?>";
  message = top.code.string_substitute(message, "[[VAR.fullname]]", fullname);

  if(confirm(message))
    location = "/base/addressbook/addressbookPrivateRemoveHandler.php?oid="+oid;
}
</SCRIPT>
<?php print($scrollList->toHtml()); ?>
<?php print($page->toFooterHtml()); 
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

