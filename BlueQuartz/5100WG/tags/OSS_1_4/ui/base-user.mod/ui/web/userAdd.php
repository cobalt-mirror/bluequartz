<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: userAdd.php 3 2003-07-17 15:19:15Z will $

include("ArrayPacker.php");
include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
// $factory = $serverScriptHelper->getHtmlComponentFactory("base-user", "/post.php");
$factory = $serverScriptHelper->getHtmlComponentFactory("base-user", "/base/user/userAddHandler.php");
$i18n = $factory->i18n;

// get defaults
$defaults = $cceClient->getObject("System", array(), "UserDefaults");

$page = $factory->getPage();
$form = $page->getForm();
$formId = $form->getId();

$block = $factory->getPagedBlock("addNewUser", array("account", "security"));


$block->addFormField(
  $factory->getFullName("fullNameField"),
  $factory->getLabel("fullNameField"),
  "account"
);

$prop=trim($i18n->getProperty("needSortName"));
if($prop=="yes"){
	$sortName=$factory->getFullName("sortNameField");
	$sortName->setOptional('silent');
	$block->addFormField(
	  $sortName,
	  $factory->getLabel("sortNameField"),
	  "account"
	);
}

$block->addFormField(
  $factory->getUserName("userNameField"),
  $factory->getLabel("userNameField"),
  "account"
);

$block->addFormField(
  $factory->getPassword("passwordField"),
  $factory->getLabel("passwordField"),
  "account"
);

$quota = $factory->getInteger(
			"maxDiskSpaceField", 
			($defaults["quota"] != -1 ? $defaults["quota"] : ""), 
			1, 1024000);
$quota->setOptional('silent');

$block->addFormField(
  $quota,
  $factory->getLabel("maxDiskSpaceField"),
  "account"
);

// make group list
$groups = $cceClient->getObjects("Workgroup");
$groupNames = array();
for($i = 0; $i < count($groups); $i++) {
  $group = $groups[$i];
  $groupNames[] = $group["name"];
}
$groupNamesString = arrayToString($groupNames);

$groupSelector = $factory->getSetSelector("memberGroupsField", "", $groupNamesString, "memberGroups", "allGroups");
$groupSelector->setOptional(true);
$block->addFormField(
  $groupSelector,
  $factory->getLabel("memberGroupsField"),
  "account"
);

$emailAliases = $factory->getEmailAliasList("emailAliasesField");
$emailAliases->setOptional(true);
$block->addFormField(
  $emailAliases,
  $factory->getLabel("emailAliasesField"),
  "account"

);

$textblock = $factory->getTextBlock("userDescField", 
	     $i18n->interpolate($default["description"]));
$textblock->setWidth(2*$textblock->getWidth());
$textblock->setOptional(true);
$block->addFormField(
  $textblock,
  $factory->getLabel("userDescField"),
  "account"
);

/* take care of the capabilities stuff */
$capgroups =& $serverScriptHelper->getAllCapabilityGroups();

/* get rid of any nested capgroups */
$masterExpansion = array();
foreach ($capgroups as $cap) {
  if ($cap["expanded"] != NULL) {
    $masterExpansion = array_merge($masterExpansion, array_diff($cap["expanded"], array($cap["name"])));
  }
}

include_once("uifc/Label.php");

function cmp($va, $vb) {
  $a = $va["sort"];
  $b = $vb["sort"];
  if ($a == $b) return 0;
  return ($a < $b ? -1 : 1);
}

usort($capgroups, "cmp");
foreach ($capgroups as $cap) {
  // If I can't do this..  then I don't show it.
  if (!$serverScriptHelper->getAllowed($cap["name"]))
    continue;
  if (in_array($cap["name"], $masterExpansion))
    continue;
  $block->addFormField(
    $factory->getBoolean("capgroup_" . $cap["name"], false, "rw"),
    new Label($page, $i18n->interpolate($cap["nameTag"]), $i18n->interpolate($cap["nameTagHelp"])),
    "security"
  );
}

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/user/userList.php"));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<SCRIPT LANGUAGE="javascript" SRC="userNameGenerator.JS"></SCRIPT>
<SCRIPT LANGUAGE="javascript" SRC="emailAliasGenerator.JS"></SCRIPT>

<?php print($block->toHtml()); ?>

<SCRIPT LANGUAGE="javascript">
var oldChangeHandler = document.<?php print($formId)?>.fullNameField.onchange;

function fullNameChangeHandler() {
  if(oldChangeHandler != null) {
    var ret = oldChangeHandler();
    if(!ret)
      return ret;
  }

  var form = document.<?php print($formId)?>;

  // generate user name
  if(form.userNameField.value == "")
    form.userNameField.value = userNameGenerator_generate(form.fullNameField.value, "<?php print($defaults["userNameGenMode"]) ?>");

  // generate email alias
  if(form.emailAliasesField.textArea.value == "") {
    var alias = emailAliasGenerator_generate(form.fullNameField.value);
    if(alias != form.userNameField.value)
      form.emailAliasesField.textArea.value = alias;
  }
}
<?php
if($i18n->getProperty("genUsername")=="yes"){
	print("document.$formId.fullNameField.onchange = fullNameChangeHandler;\n");
}
?>
</SCRIPT>

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

