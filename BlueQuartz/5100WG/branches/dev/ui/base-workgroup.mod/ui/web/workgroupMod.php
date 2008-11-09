<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: workgroupMod.php 201 2003-07-18 19:11:07Z will $

include("ArrayPacker.php");
include("ServerScriptHelper.php");
include("uifc/PagedBlock.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-workgroup", "/base/workgroup/workgroupModHandler.php");
$i18n = $serverScriptHelper->getI18n("workgroup");

// get settings
$group = $cceClient->getObject("Workgroup", array("name" => $groupName));
$groupDisk = $cceClient->getObject("Workgroup", array("name" => $groupName), "Disk");

$page = $factory->getPage();

$block = new PagedBlock($page, "modifyGroup", $factory->getLabel("modifyGroup", false, array("groupName" => $groupName)));

// Build maximum quota on the sites paritition
list($home_oid) = $cceClient->find('Disk', 
    array("mountPoint" => $group["volume"]));
$disk = $cceClient->get($home_oid);
$max_quota = intval(intval($disk['total']) / 1024);

if (($groupDisk["quota"] == -1) && $max_quota) {
	$groupDisk["quota"] = $max_quota;
}

$quota = $factory->getInteger(
	"maxDiskSpaceField",
	$groupDisk["quota"],
	1, ($max_quota > 1 ? $max_quota : 9999999999));

$quota->setOptional('silent');

if($max_quota && $max_quota != -1)
    $quota->showBounds(1);

$block->addFormField(
  $quota,
  $factory->getLabel("maxDiskSpaceField")
);

// build up group members
$userNames = array();
$users = $cceClient->getObjects("User");
for($i = 0; $i < count($users); $i++) {
  $user = $users[$i];
  $userNames[] = $user["name"];
}
$allUsers = arrayToString($userNames);

$memberSelector = $factory->getSetSelector("groupMembersField", $group["members"], $allUsers, "groupUsers", "allUsers");
$memberSelector->setOptional(true);

$block->addFormField(
  $memberSelector,
  $factory->getLabel("groupMembersField")
);

$flag = $group["desc_readonly"] ? "r" : "rw";
$textblock = $factory->getTextBlock("groupDescField", 
	     $i18n->interpolate($group["description"]), $flag);
$textblock->setWidth(2*$textblock->getWidth());
if (!$group["desc_readonly"]) {
	$textblock->setOptional(true);
}
$block->addFormField(
  $textblock,
  $factory->getLabel("groupDescField")
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/workgroup/workgroupList.php"));

$groupNameField = $factory->getGroupName("groupNameField", $groupName, "");

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($block->toHtml()); ?>
<?php print($groupNameField->toHtml()); ?>

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

