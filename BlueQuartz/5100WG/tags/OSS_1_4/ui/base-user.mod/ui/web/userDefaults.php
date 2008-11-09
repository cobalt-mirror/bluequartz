<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: userDefaults.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-user", "/base/user/userDefaultsHandler.php");
$i18n = $factory->getI18n();

// get defaults
$defaults = $cceClient->getObject("System", array(), "UserDefaults");

$page = $factory->getPage();

$block = $factory->getPagedBlock("userDefaults");

$quota = $factory->getInteger(
			"maxDiskSpaceField", 
			($defaults["quota"] != -1 ? $defaults["quota"] : ""), 
			1, 1024000);
$quota->setOptional('silent');

$block->addFormField(
  $quota,
  $factory->getLabel("maxDiskSpaceFieldDefault")
);

$userNameGenMode = $factory->getMultiChoice("userNameGenMode", array("firstInitLast", "firstLastInit", "first", "last"));
if($defaults["userNameGenMode"] == "firstInitLast")
  $userNameGenMode->setSelected(0, true);
if($defaults["userNameGenMode"] == "firstLastInit")
  $userNameGenMode->setSelected(1, true);
else if($defaults["userNameGenMode"] == "first")
  $userNameGenMode->setSelected(2, true);
else if($defaults["userNameGenMode"] == "last")
  $userNameGenMode->setSelected(3, true);

if($i18n->getProperty("genUsername") == "yes"){
	$block->addFormField($userNameGenMode, $factory->getLabel("userNameGenField"));
}

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/user/userList.php"));

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

