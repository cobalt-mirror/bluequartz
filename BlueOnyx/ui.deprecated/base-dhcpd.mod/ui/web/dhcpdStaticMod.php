<?php
// Author: Andrew Bose, Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: dhcpdStaticMod.php,v 1.1.1.1 2003/07/17 15:15:49 will Exp $

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-dhcpd", "/base/dhcpd/dhcpdStaticModHandler.php");

// get assignment 
$oids = $cceClient->find("DhcpStatic", array("ipaddr" => $ipaddr));
$static = $cceClient->get($oids[0]);


$page = $factory->getPage();

$block = $factory->getPagedBlock("modStaticAddress");

$block->addFormField(
  $factory->getIpAddress("ipAddressField", $static["ipaddr"]),
  $factory->getLabel("ipAddressField")
);

$block->addFormField(
  $factory->getMacAddress("macAddressField", $static["mac"]),
  $factory->getLabel("macAddressField")
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/dhcpd/dhcpdList.php"));

$ipOldField = $factory->getIpAddress("ipOldField", $static["ipaddr"], "");
$macOldField = $factory->getMacAddress("macOldField", $static["mac"], "");

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($block->toHtml()); ?>
<?php print($ipOldField->toHtml()); ?>
<?php print($macOldField->toHtml()); ?>
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
