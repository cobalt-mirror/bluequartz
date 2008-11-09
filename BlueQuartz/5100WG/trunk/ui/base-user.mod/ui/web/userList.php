<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: userList.php 3 2003-07-17 15:19:15Z will $

include("ServerScriptHelper.php");
include("uifc/Button.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-user");
$i18n = $serverScriptHelper->getI18n("base-user");

$page = $factory->getPage();

$defaults = $factory->getButton("javascript: location='/base/user/userDefaults.php'; top.code.flow_showNavigation(false)", "userDefaults");

// build the scroll list
$scrollList = $factory->getScrollList("userList", array("fullName", "userName", "userDesc", "listAction"), array(1));
$scrollList->setAlignments(array("left", "left", "left", "center"));
$scrollList->setColumnWidths(array("", "30%", "", "1%"));
$scrollList->addButton(
	$factory->getAddButton(
		"javascript: location='/base/user/userAdd.php';"
		. " top.code.flow_showNavigation(false)",
		"[[base-user.add_user_help]]"));

// disable sorting
$scrollList->setSortEnabled(false);

// find sort key
$sortBy=$i18n->getProperty("needSortName")=="yes"? "sortName" : "userName";

$sortKeyMap = array(0 => $sortBy, 1 => "name");
$sortKey = $sortKeyMap[$scrollList->getSortedIndex()];

// find page length
$pageLength = $scrollList->getLength();

// find start point
$start = $scrollList->getPageIndex()*$pageLength;

$oids = $cceClient->findSorted("User", $sortKey);

// sort in the right direction
if($scrollList->getSortOrder() == "descending")
  $oids = array_reverse($oids);

// number of users minus admin
$scrollList->setEntryNum(count($oids)-1);

// admin is not a regular user, so we need to remove it
$adminOids = $cceClient->find("User", array("name" => "admin"));
for($i = 0; $i < count($oids); $i++) {
  if($oids[$i] == $adminOids[0]) {
    $oids = array_merge(array_slice($oids, 0, $i), array_slice($oids, $i+1));
    break;
  }
}

for($i = $start; $i < count($oids) && $i < $start+$pageLength; $i++) {
  $user = $cceClient->get($oids[$i]);
  $fullName = $user["fullName"];
  $userName = $user["name"];
  $desc = $factory->getTextField("", 
	$i18n->interpolate($user["description"]), "r");
  $desc->setMaxLength(80);	

  $scrollList->addEntry(array(
    $factory->getFullName("", $fullName, "r"),
    $factory->getUserName("", $userName, "r"),
    $desc,
    $factory->getCompositeFormField(array(
      $factory->getModifyButton("javascript: location='/base/user/userMod.php?userNameField=$userName'; top.code.flow_showNavigation(false)"),
      $factory->getRemoveButton("javascript: confirmRemove('$userName')")
    ))
  ), "", false, $i);
}

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<SCRIPT LANGUAGE="javascript">
function confirmRemove(userName) {
  var message = "<?php print($i18n->get("removeUserConfirm"))?>";
  message = top.code.string_substitute(message, "[[VAR.userName]]", userName);

  if(confirm(message))
    location = "/base/user/userRemoveHandler.php?userName="+userName;
}

// show flow buttons
if(top.code != null && top.code.flow_showNavigation != null)
  top.code.flow_showNavigation(true);
</SCRIPT>

<?php print($defaults->toHtml()); ?>
<BR>

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

