<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: personalEmail.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");
include("uifc/PagedBlock.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-user", "personalEmailHandler.php");
$loginName = $serverScriptHelper->getLoginName();

// get setting
$userEmail = $cceClient->getObject("User", array("name" => $loginName), "Email");

$page = $factory->getPage();

$block = new PagedBlock($page, "emailSettingsFor", $factory->getLabel("emailSettingsFor", false, array("userName" => $loginName)));

$forwardEnable = $factory->getOption("forwardEnable", $userEmail["forwardEnable"]);
$forwardEnable->addFormField(
  $factory->getEmailAddressList("forwardEmailField", $userEmail["forwardEmail"]),
  $factory->getLabel("forwardEmailField")
);
$forwardEnable->addFormField(
  $factory->getBoolean("forwardSaveField", $userEmail["forwardSave"]),
  $factory->getLabel("forwardSaveField")
);

$forward = $factory->getMultiChoice("forwardEnableField");
$forward->addOption($forwardEnable);
$block->addFormField($forward, $factory->getLabel("forwardEnableField"));

$autoResponder = $factory->getMultiChoice("autoResponderField");
$enableAutoResponder = $factory->getOption("enableAutoResponderField", $userEmail["vacationOn"]);
$enableAutoResponder->addFormField(
  $factory->getTextBlock("autoResponderMessageField", $userEmail["vacationMsg"]),
  $factory->getLabel("autoResponderMessageField")
);
$autoResponder->addOption($enableAutoResponder);
$block->addFormField($autoResponder, $factory->getLabel("autoResponderField"));

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

