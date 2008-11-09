<?php
// Author: Andrew Bose, Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: dhcpdList.php 201 2003-07-18 19:11:07Z will $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-dhcpd", "/base/dhcpd/dhcpdList.php");
$i18n = $serverScriptHelper->getI18n("base-dhcpd");

$page = $factory->getPage();

$backButton = $factory->getBackButton("/base/dhcpd/dhcpd.php");

// build the scroll list for dynamic assignments
$scrollList = $factory->getScrollList("dynamicAssignmentList", array("ipAddressLow", "ipAddressHi", "listActionDyn"), array(0, 1));
$scrollList->setAlignments(array("left", "left", "center"));
$scrollList->setColumnWidths(array("", "", "1%"));
$addbutt = $factory->getAddButton("/base/dhcpd/dhcpdDynamicAdd.php");
$addbutt->setHeader(true);
$scrollList->addButton($addbutt);

// build the scroll list for static assignments
$scrollList2 = $factory->getScrollList("staticAssignmentList", array("ipAddress", "macAddress", "listActionStatic"), array(0, 1));
$scrollList2->setAlignments(array("left", "left", "center"));
$scrollList2->setColumnWidths(array("", "", "1%"));
$addbutt2 = $factory->getAddButton("/base/dhcpd/dhcpdStaticAdd.php");
$addbutt2->setHeader(true);
$scrollList2->addButton($addbutt2);

$oids = $cceClient->find("DhcpDynamic");
$oids2 = $cceClient->find("DhcpStatic");

for($i = 0; $i < count($oids); $i++) {
  $dynamic = $cceClient->get($oids[$i]);
  $ipAddressLow = $dynamic["ipaddrlo"];
  $ipAddressHi = $dynamic["ipaddrhi"];

  $scrollList->addEntry(array(
    $factory->getIpAddress("", $ipAddressLow, "r"),
    $factory->getIpAddress("", $ipAddressHi, "r"),
    $factory->getCompositeFormField(array(
      $factory->getModifyButton("/base/dhcpd/dhcpdDynamicMod.php?ipaddrlo=$ipAddressLow"),
      $factory->getRemoveButton("javascript: confirmDynamicRemove('$ipAddressLow','$ipAddressHi')")
    ))
  ));
}

for($i = 0; $i < count($oids2); $i++) {
  $static = $cceClient->get($oids2[$i]);
  $ipAddress = $static["ipaddr"];
  $mac = $static["mac"];

  $scrollList2->addEntry(array(
    $factory->getIpAddress("", $ipAddress, "r"),
    $factory->getMacAddress("", $mac, "r"),
    $factory->getCompositeFormField(array(
      $factory->getModifyButton("/base/dhcpd/dhcpdStaticMod.php?ipaddr=$ipAddress"),
      $factory->getRemoveButton("javascript: confirmStaticRemove('$ipAddress','$mac')")
    ))
  ));
}

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<SCRIPT LANGUAGE="javascript">
function confirmDynamicRemove(ipAddressLow, ipAddressHi) {
  var message = "<?php print($i18n->get("removeDynamicConfirm"))?>";
  message = top.code.string_substitute(message, "[[VAR.ipAddressLow]]", ipAddressLow, "[[VAR.ipAddressHi]]", ipAddressHi);

  if(confirm(message))
    location = "/base/dhcpd/dynamicRemoveHandler.php?ipAddressLow="+ipAddressLow;
}

function confirmStaticRemove(ipAddress, macAddress) {
  var message = "<?php print($i18n->get("removeStaticConfirm"))?>";
  message = top.code.string_substitute(message, "[[VAR.ipAddress]]", ipAddress, "[[VAR.macAddress]]", macAddress);

  if(confirm(message))
    location = "/base/dhcpd/staticRemoveHandler.php?ipAddress="+ipAddress;
}
</SCRIPT>

<?php print($scrollList->toHtml()); ?>
<BR>

<?php print($scrollList2->toHtml()); ?>
<BR>
<?php print($backButton->toHtml()); ?>

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

