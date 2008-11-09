<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: userMod.php 3 2003-07-17 15:19:15Z will $

include("ArrayPacker.php");
include("ServerScriptHelper.php");
include("uifc/PagedBlock.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-user", "/base/user/userModHandler.php");

$i18n = $factory->getI18n();


$editingSelf = 0;
if ($HTTP_COOKIE_VARS["loginName"] == $userNameField)
  $editingSelf = 1;

// get user
$oids = $cceClient->find("User", array("name" => $userNameField));
$user = $cceClient->get($oids[0]);
$userDisk = $cceClient->get($oids[0], "Disk");
$userEmail = $cceClient->get($oids[0], "Email");

$page = $factory->getPage();

$block = new PagedBlock($page, "modifyUser", $factory->getLabel("modifyUser", false, array("userName" => $userNameField)));
$block->addPage("account", $factory->getLabel("account"));
$block->addPage("email", $factory->getLabel("email"));

if (!$editingSelf) {
  $block->addPage("security", $factory->getLabel("security"));


  // Security Levels stuff...

  // loop through all the possible CapabilityGroups
  $capgroups =& $serverScriptHelper->getAllCapabilityGroups(); 

  // Figure out which values are selected initially
  $setCapsVals = stringToArray($user["capLevels"]);

  /* get rid of any nested cap groups */
  /* we do this by creating a master list of expansions, and if a 
   * capgroup is found in this expansion, then it is not a level
   * one node, and does not get displayed */
  $masterExpansion = array();
  foreach ($capgroups as $cap) {
    if ($cap["expanded"] != NULL)  {
      $diffs = array_diff($cap["expanded"],array($cap["name"]));
      $masterExpansion = array_merge($masterExpansion, $diffs);
    }
  }
  
  /* loop and display the caps */
  include_once("uifc/Label.php");
  
  function cmp($va, $vb) {
    $a = $va["sort"];
    $b = $vb["sort"];
    if ($a == $b) return 0;
    return ($a < $b ? -1 : 1);
  }
  usort($capgroups, "cmp");
  foreach ($capgroups as $cap) {
    // if I can't do this..   then I don't show it.
    if (!$serverScriptHelper->getAllowed($cap["name"])) {
      continue;
    } 
    if (in_array($cap["name"], $masterExpansion))
      continue;
    $block->addFormField(
      $factory->getBoolean("capgroup_" . $cap["name"], in_array($cap["name"], $setCapsVals), "rw"),
      new Label($page, $i18n->interpolate($cap["nameTag"]), $i18n->interpolate($cap["nameTagHelp"])),
      "security"
    );
  }
  
} /* editingSelf */


$block->addFormField(
  $factory->getFullName("fullNameField", $user["fullName"]),
  $factory->getLabel("fullNameField"),
  "account"
);

$prop=trim($i18n->getProperty("needSortName"));
if($prop=="yes"){
	$sortName=$factory->getFullName("sortNameField",$user["sortName"]);
	$sortName->setOptional('silent');
	$block->addFormField(
          $sortName,
	  $factory->getLabel("sortNameField"),
	  "account"
	);
}

$password = $factory->getPassword("passwordField");
$password->setOptional(true);
$block->addFormField(
  $password,
  $factory->getLabel("newPasswordField"),
  "account"
);

$quota = $factory->getInteger(
			"maxDiskSpaceField", 
			($userDisk["quota"] != -1 ? $userDisk["quota"] : ""), 
			1, 1024000);
$quota->setOptional('silent');

$block->addFormField(
  $quota,
  $factory->getLabel("maxDiskSpaceField"),
  "account"
);

// make group list
$groups = $cceClient->getObjects("Workgroup");
$memberGroupNames = array();
$allGroupNames = array();
for($i = 0; $i < count($groups); $i++) {
  $group = $groups[$i];
  if(isInArrayString($user["name"], $group["members"]))
    $memberGroupNames[] = $group["name"];

  $allGroupNames[] = $group["name"];
}
$memberGroupNamesString = arrayToString($memberGroupNames);
$allGroupNamesString = arrayToString($allGroupNames);

$groupSelector = $factory->getSetSelector("memberGroupsField", $memberGroupNamesString, $allGroupNamesString, "memberGroups", "allGroups");
$groupSelector->setOptional(true);
$block->addFormField(
  $groupSelector,
  $factory->getLabel("memberGroupsField"),
  "account"
);

$emailAliases = $factory->getEmailAliasList("emailAliasesField", $userEmail["aliases"]);
$emailAliases->setOptional(true);
$block->addFormField(
  $emailAliases,
  $factory->getLabel("emailAliasesField"),
  "email"
);

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
$block->addFormField(
  $forward,
  $factory->getLabel("forwardEnableField"),
  "email"
);

$enableAutoResponder = $factory->getOption("enableAutoResponderField", $userEmail["vacationOn"]);
$enableAutoResponder->addFormField($factory->getTextBlock("autoResponderMessageField", $userEmail["vacationMsg"]), $factory->getLabel("autoResponderMessageField"));
$autoResponder = $factory->getMultiChoice("autoResponderField");
$autoResponder->addOption($enableAutoResponder);
$block->addFormField(
  $autoResponder,
  $factory->getLabel("autoResponderField"),
  "email"
);

$flags = $user["desc_readonly"] ? "r" : "rw";
$textblock = $factory->getTextBlock("userDescField", 
	     $i18n->interpolate($user["description"]), $flags);
$textblock->setWidth(2*$textblock->getWidth());
if (!$user["desc_readonly"]) {
  $textblock->setOptional(true);
}
$block->addFormField(
  $textblock,
  $factory->getLabel("userDescField"),
  "account"
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/user/userList.php"));

$userName = $factory->getUserName("userNameField", $userNameField, "");

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($block->toHtml()); ?>
<?php print($userName->toHtml()); ?>

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

