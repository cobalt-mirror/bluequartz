<?php
// Author: Kenneth C.K. Leung
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: snmp.php 1136 2008-06-05 01:48:04Z mstauber $

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-snmp", "/base/snmp/snmpHandler.php");

// get settings
$systemObj = $cceClient->getObject("System",array(),"Snmp");

$page = $factory->getPage();

$block = $factory->getPagedBlock("snmpSettings");
$block->processErrors($serverScriptHelper->getErrors());

$block->addFormField(
  $factory->getBoolean("enableServer", $systemObj["enabled"]),
  $factory->getLabel("enableServer")
);

$readCommunity = $factory->getSnmpCommunity("readSnmpCommunityField", $systemObj["readCommunity"]);
$readCommunity->setOptional(true);
$block->addFormField(
  $readCommunity,
  $factory->getLabel("readSnmpCommunityField")
);

$readWriteCommunity = $factory->getSnmpCommunity("readWriteSnmpCommunityField", $systemObj["readWriteCommunity"]);
$readWriteCommunity->setOptional(true);
$block->addFormField(
  $readWriteCommunity,
  $factory->getLabel("readWriteSnmpCommunityField")
);

// Don't ask why, but somehow with PHP5 we need to add a blank FormField or nothing shows on this page:
$hidden_block = $factory->getTextBlock("Nothing", "");
$hidden_block->setOptional(true);
$block->addFormField(
    $hidden_block,
    $factory->getLabel("Nothing"),
    "Hidden"
    );

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($block->toHtml()); ?>

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